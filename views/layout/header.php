<?php
// Shared page header
use App\Core\Auth;

if (!isset($registrationSettings)) {
    try {
        $db = \App\Core\Container::get('db');
        $registrationSettings = \App\Controller\SettingsController::loadRegistrationSettings($db);
    } catch (\Exception $e) {
        $registrationSettings = [
            'pre_register_enabled' => true,
            'walk_in_enabled' => true,
            'theme' => 'violet',
        ];
    }
}
$theme = $registrationSettings['theme'] ?? '#6750A4';
$themeHex = \App\Core\ThemeHelper::resolveHex($theme);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'ATCL Management System') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="/css/material3.css?v=<?= file_exists(__DIR__ . '/../../css/material3.css') ? filemtime(__DIR__ . '/../../css/material3.css') : time() ?>">
    <?= \App\Core\ThemeHelper::renderCssBlock($theme) ?>
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
    <script>
        // Apply sidebar state immediately to prevent layout shift/flicker
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
        // Set global registration mode variable
        window.currentRegMode = "<?= ($registrationSettings['pre_register_enabled'] && !$registrationSettings['walk_in_enabled']) ? 'pre_reg' : (($registrationSettings['walk_in_enabled'] && !$registrationSettings['pre_register_enabled']) ? 'walk_in' : 'closed') ?>";

        // Global CSRF Protection: auto-attach token to forms and fetch requests
        document.addEventListener('DOMContentLoaded', function() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!token) return;

            function injectCsrf(form) {
                if ((form.method || '').toUpperCase() === 'POST' && !form.querySelector('input[name="csrf_token"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'csrf_token';
                    input.value = token;
                    form.appendChild(input);
                }
            }

            document.querySelectorAll('form').forEach(injectCsrf);

            document.addEventListener('submit', function(e) {
                if (e.target && e.target.tagName === 'FORM') {
                    injectCsrf(e.target);
                }
            }, true);

            const origFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                options = options || {};
                const method = (options.method || 'GET').toUpperCase();
                if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
                    options.headers = options.headers || {};
                    if (options.headers instanceof Headers) {
                        if (!options.headers.has('X-CSRF-TOKEN')) {
                            options.headers.append('X-CSRF-TOKEN', token);
                        }
                    } else if (Array.isArray(options.headers)) {
                        options.headers.push(['X-CSRF-TOKEN', token]);
                    } else {
                        options.headers['X-CSRF-TOKEN'] = token;
                    }
                }
                return origFetch(url, options);
            };

            // Intercept XMLHttpRequest (covers jQuery, Axios, etc.)
            const origXhrOpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function(method) {
                this._method = (method || 'GET').toUpperCase();
                return origXhrOpen.apply(this, arguments);
            };
            const origXhrSend = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.send = function() {
                if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(this._method)) {
                    try {
                        this.setRequestHeader('X-CSRF-TOKEN', token);
                    } catch (err) {}
                }
                return origXhrSend.apply(this, arguments);
            };
        });
    </script>
</head>
<body>
<?php if (\App\Core\Auth::check() && !isset($forcePublic)): ?>
<div class="m3-layout-container">
    <aside class="m3-sidebar" id="m3Sidebar">
        <div class="m3-brand-container d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined text-primary" style="font-size: 32px;">campaign</span>
                <h1 class="m3-brand-name m-0">ATCL MS</h1>
            </div>
            <button class="m3-sidebar-collapse-btn d-none d-lg-flex" id="sidebarCollapseBtn" title="Collapse sidebar">
                <span class="material-symbols-outlined" style="font-size: 20px;">menu_open</span>
            </button>
        </div>
        <!-- Session Selector -->
        <?php
        try {
            $currentSessionData = \App\Core\SessionHelper::currentSession();
            $allSessions = \App\Core\SessionHelper::all();
        } catch (\Exception $e) {
            $currentSessionData = null;
            $allSessions = [];
        }
        ?>
        <?php if ($currentSessionData && in_array(\App\Core\Auth::role(), ['advisor', 'committee', 'superuser'], true)): ?>
        <?php $isDemo = \App\Core\SessionHelper::isDemoMode() || \App\Core\DemoHelper::isDemoSession((int)$currentSessionData['id']); ?>
        <?php if ($isDemo): ?>
            <div class="d-flex align-items-center gap-2 px-2 py-2 mb-2 rounded" style="background: #FFE0B2; color: #E65100; font-size: 0.8rem;" title="Sandboxed Demonstration Session">
                <span class="material-symbols-outlined" style="font-size: 18px; color: #E65100;">lock</span>
                <span class="text-truncate fw-semibold">Demo Sandbox (Locked)</span>
            </div>
        <?php else: ?>
            <a href="/sessions" class="d-flex align-items-center gap-2 px-2 py-2 mb-2 text-decoration-none rounded" style="background: var(--md-sys-color-primary-container, #EADDFF); color: var(--md-sys-color-on-primary-container, #21005D); font-size: 0.8rem;" title="Manage Sessions">
                <span class="material-symbols-outlined" style="font-size: 18px;">event</span>
                <span class="text-truncate fw-semibold"><?= htmlspecialchars($currentSessionData['name']) ?></span>
                <span class="material-symbols-outlined ms-auto" style="font-size: 16px;">swap_horiz</span>
            </a>
        <?php endif; ?>
        <?php endif; ?>
        
        <nav class="flex-grow-1">
            <ul class="m3-nav-group">
                <li>
                    <a href="/dashboard" class="m3-nav-item <?= ($title ?? '') === 'Dashboard' || strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">dashboard</span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="/participants" class="m3-nav-item <?= ($title ?? '') === 'Participants & Admission' || ($title ?? '') === 'Participants List' || (strpos($_SERVER['REQUEST_URI'], '/participants') !== false && strpos($_SERVER['REQUEST_URI'], '/participants/groups') === false && strpos($_SERVER['REQUEST_URI'], '/participants/checkin') === false && strpos($_SERVER['REQUEST_URI'], '/participants/assign-buddy') === false && strpos($_SERVER['REQUEST_URI'], '/participants/duplicates') === false && strpos($_SERVER['REQUEST_URI'], '/participants/anomalies') === false) ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">group</span>
                        Participants
                    </a>
                </li>
                <li>
                    <a href="/participants/groups" class="m3-nav-item <?= ($title ?? '') === 'Grouping Overview' || (strpos($_SERVER['REQUEST_URI'], '/participants/groups') !== false && strpos($_SERVER['REQUEST_URI'], '/participants/groups/assign-facilitator') === false) ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">group_work</span>
                        Grouping
                    </a>
                </li>
                <li>
                    <a href="/operations/crew" class="m3-nav-item <?= ($title ?? '') === 'Crew Management' || ($title ?? '') === 'Add Crew' || ($title ?? '') === 'Edit Crew Member' || strpos($_SERVER['REQUEST_URI'], '/operations/crew') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">supervisor_account</span>
                        Senior Buddies
                    </a>
                </li>
                <li>
                    <a href="/participants/assign-buddy" class="m3-nav-item <?= ($title ?? '') === 'Assign Senior Buddies' || strpos($_SERVER['REQUEST_URI'], '/participants/assign-buddy') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">assignment_ind</span>
                        Buddy Assignment
                    </a>
                </li>
                <li>
                    <a href="/participants/checkin" class="m3-nav-item <?= ($title ?? '') === 'QR Check-in' || strpos($_SERVER['REQUEST_URI'], '/participants/checkin') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">qr_code_scanner</span>
                        QR Check-in
                    </a>
                </li>
                <li>
                    <a href="/participants/duplicates" class="m3-nav-item <?= ($title ?? '') === 'Duplicate Detection' || strpos($_SERVER['REQUEST_URI'], '/participants/duplicates') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">content_copy</span>
                        Duplicates
                    </a>
                </li>
                <li>
                    <a href="/participants/anomalies" class="m3-nav-item <?= ($title ?? '') === 'Email Anomalies' || strpos($_SERVER['REQUEST_URI'], '/participants/anomalies') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">warning</span>
                        Anomalies
                    </a>
                </li>
                <li>
                    <a href="/participants/audit-logs" class="m3-nav-item <?= ($title ?? '') === 'Audit Logs' || strpos($_SERVER['REQUEST_URI'], '/participants/audit-logs') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">history</span>
                        Audit Logs
                    </a>
                </li>
                <li>
                    <a href="/lost-and-found" class="m3-nav-item <?= ($title ?? '') === 'Lost & Found' || strpos($_SERVER['REQUEST_URI'], '/lost-and-found') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">search_check_2</span>
                        Claim My Belongings
                    </a>
                </li>
                <li>
                    <a href="/insights" class="m3-nav-item <?= ($title ?? '') === 'Insights & Graphs' || strpos($_SERVER['REQUEST_URI'], '/insights') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">insights</span>
                        Insights & Graphs
                    </a>
                </li>
                <?php /*
                <li>
                    <a href="/finance" class="m3-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/finance') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">payments</span>
                        Finance
                    </a>
                </li>
                <li>
                    <a href="/forms" class="m3-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/forms') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">description</span>
                        Forms
                    </a>
                </li>
                <li>
                    <a href="/operations" class="m3-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/operations') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">engineering</span>
                        Operations
                    </a>
                </li>
                */ ?>
                <?php if (\App\Core\Auth::isSuperuser()): ?>
                    <li>
                        <a href="/users" class="m3-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">manage_accounts</span>
                            Users
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array(\App\Core\Auth::role(), ['advisor', 'committee', 'superuser'], true)): ?>
                    <li>
                        <a href="/settings/theme" class="m3-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/settings/theme') !== false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">palette</span>
                            Theme &amp; Appearance
                        </a>
                    </li>
                    <?php if (!\App\Core\SessionHelper::isDemoMode()): ?>
                    <li>
                        <a href="/settings/landing" class="m3-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/settings/landing') !== false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">settings</span>
                            Settings
                        </a>
                    </li>
                    <li>
                        <a href="/settings/backup" class="m3-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/settings/backup') !== false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">backup</span>
                            Backup &amp; Restore
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="mt-auto pt-3 border-top">
            <!-- Sidebar Theme Switcher -->
            <?php if (in_array(\App\Core\Auth::role(), ['advisor', 'committee', 'superuser'], true)): ?>
            <div class="px-2 mb-3 theme-switcher-sidebar">
                <div class="d-flex align-items-center justify-content-between">
                    <a href="/settings/theme" class="small text-muted fw-semibold text-decoration-none d-flex align-items-center gap-1" style="font-size: 0.70rem; letter-spacing: 0.5px;">
                        <span>THEME</span>
                    </a>
                    <div class="d-flex align-items-center gap-2" id="sidebarThemeSwitcher">
                        <button type="button" class="theme-swatch-btn rounded-circle p-0 <?= $themeHex === '#6750A4' ? 'active' : '' ?>" data-theme-val="#6750A4" title="Violet" style="width: 16px; height: 16px; background-color: #6750A4; border: 1px solid #EADDFF; cursor: pointer; transition: all 0.2s;"></button>
                        <button type="button" class="theme-swatch-btn rounded-circle p-0 <?= $themeHex === '#0061A4' ? 'active' : '' ?>" data-theme-val="#0061A4" title="Ocean Blue" style="width: 16px; height: 16px; background-color: #0061A4; border: 1px solid #D1E4FF; cursor: pointer; transition: all 0.2s;"></button>
                        <button type="button" class="theme-swatch-btn rounded-circle p-0 <?= $themeHex === '#2E6B4E' ? 'active' : '' ?>" data-theme-val="#2E6B4E" title="Emerald Green" style="width: 16px; height: 16px; background-color: #2E6B4E; border: 1px solid #C8E6C9; cursor: pointer; transition: all 0.2s;"></button>
                        <button type="button" class="theme-swatch-btn rounded-circle p-0 <?= $themeHex === '#A2396C' ? 'active' : '' ?>" data-theme-val="#A2396C" title="Rose Berry" style="width: 16px; height: 16px; background-color: #A2396C; border: 1px solid #FFD8E7; cursor: pointer; transition: all 0.2s;"></button>
                        <?php if (\App\Core\Auth::isSuperuser()): ?>
                        <button type="button" class="theme-swatch-btn rounded-circle p-0 rgb-swatch-btn <?= $theme === 'rgb' ? 'active' : '' ?>" data-theme-val="rgb" title="RGB Mode (Superuser Only)" style="width: 16px; height: 16px; background: linear-gradient(135deg, #ff0055, #ffe600, #00e676, #00b0ff); border: 1px solid #fff; cursor: pointer; transition: all 0.2s;"></button>
                        <?php endif; ?>
                        <a href="/settings/theme" class="text-muted d-flex align-items-center text-decoration-none" title="Custom Colors & Full Palette"><span class="material-symbols-outlined" style="font-size: 16px;">tune</span></a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- User Profile -->
            <div class="d-flex align-items-center gap-2 px-2 pt-2 border-top">
                <span class="material-symbols-outlined text-secondary" style="font-size: 32px;">account_circle</span>
                <div class="overflow-hidden">
                    <div class="fw-semibold text-truncate small" style="color: var(--md-sys-color-on-background);"><?= htmlspecialchars(\App\Core\Auth::user()['username'] ?? '') ?></div>
                    <div class="text-muted text-truncate" style="font-size: 0.75rem;"><?= htmlspecialchars(ucfirst(\App\Core\Auth::role() ?? '')) ?></div>
                </div>
            </div>
            <a href="/logout" class="btn btn-outline-danger btn-sm w-100 mt-3 d-flex align-items-center justify-content-center gap-1">
                <span class="material-symbols-outlined" style="font-size: 18px;">logout</span>
                Logout
            </a>
        </div>
    </aside>
    <div class="m3-sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="m3-main-content">
        <!-- Desktop Header Bar (visible when sidebar is collapsed) -->
        <div class="m3-desktop-header mb-3" id="desktopHeader">
            <button class="m3-sidebar-expand-btn" id="sidebarExpandBtn" title="Expand sidebar">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>

        <!-- Mobile Header Bar -->
        <div class="m3-mobile-header mb-3 rounded-3">
            <button class="m3-hamburger" id="sidebarToggle">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <span class="fw-semibold text-primary">ATCL MS</span>
            <a href="/logout" class="text-danger d-flex align-items-center">
                <span class="material-symbols-outlined">logout</span>
            </a>
        </div>
<?php else: ?>
<div class="m3-full-width-container">
    <div class="m3-top-app-bar mb-4">
        <div class="d-flex align-items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-size: 28px;">campaign</span>
            <a href="/" class="text-decoration-none"><h1 class="m3-brand-name fs-5">ATCL</h1></a>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="/participants/lookup" class="btn btn-outline-primary btn-sm">Find My QR</a>
        </div>
    </div>
<?php endif; ?>
<div class="container-fluid mb-4">
    <?php if (\App\Core\SessionHelper::isDemoMode()): ?>
        <div class="alert alert-warning mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-sm rounded-4 border-0 py-2 px-3" style="background: linear-gradient(90deg, #FFF8E1 0%, #FFECB3 100%); color: #795548; border-left: 5px solid #FFA000 !important;">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined text-warning" style="font-size: 24px; color: #E65100 !important;">science</span>
                <div>
                    <strong style="color: #E65100;">Demo Mode Active</strong>
                    <span class="d-none d-md-inline ms-1 opacity-75">— Operating in isolated demonstration session. Real data is protected.</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="post" action="/demo/reset" class="d-inline m-0" onsubmit="return confirm('Restore all demo records to initial sample state?');">
                    <button type="submit" class="btn btn-sm btn-white bg-white border text-dark fw-semibold rounded-pill py-1 px-3 d-flex align-items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined" style="font-size: 16px;">restart_alt</span>
                        Reset Demo Data
                    </button>
                </form>
                <a href="/demo/exit" class="btn btn-sm btn-dark fw-semibold rounded-pill py-1 px-3 text-decoration-none d-flex align-items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                    Exit Demo
                </a>
            </div>
        </div>
    <?php endif; ?>
