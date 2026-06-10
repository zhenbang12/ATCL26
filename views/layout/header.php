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
$theme = $registrationSettings['theme'] ?? 'violet';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'ATCL Management System') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="/css/material3.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/css/material3.css') ?>">
    <script>
        // Apply sidebar state immediately to prevent layout shift/flicker
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
        // Set global registration mode variable
        window.currentRegMode = "<?= ($registrationSettings['pre_register_enabled'] && !$registrationSettings['walk_in_enabled']) ? 'pre_reg' : (($registrationSettings['walk_in_enabled'] && !$registrationSettings['pre_register_enabled']) ? 'walk_in' : 'closed') ?>";
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
        <a href="/sessions" class="d-flex align-items-center gap-2 px-2 py-2 mb-2 text-decoration-none rounded" style="background: var(--md-sys-color-primary-container, #EADDFF); color: var(--md-sys-color-on-primary-container, #21005D); font-size: 0.8rem;" title="Manage Sessions">
            <span class="material-symbols-outlined" style="font-size: 18px;">event</span>
            <span class="text-truncate fw-semibold"><?= htmlspecialchars($currentSessionData['name']) ?></span>
            <span class="material-symbols-outlined ms-auto" style="font-size: 16px;">swap_horiz</span>
        </a>
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
                        <a href="/settings/landing" class="m3-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">settings</span>
                            Settings
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="mt-auto pt-3 border-top">
            <!-- Sidebar Theme Switcher (Superuser Only) -->
            <?php if (\App\Core\Auth::isSuperuser()): ?>
            <div class="px-2 mb-3 theme-switcher-sidebar">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-muted fw-semibold" style="font-size: 0.70rem; letter-spacing: 0.5px;">THEME</span>
                    <div class="d-flex align-items-center gap-2" id="sidebarThemeSwitcher">
                        <button type="button" class="theme-swatch-btn rounded-circle p-0 <?= $theme === 'violet' ? 'active' : '' ?>" data-theme-val="violet" title="Violet Theme" style="width: 16px; height: 16px; background-color: #6750A4; border: 1px solid #EADDFF; cursor: pointer; transition: all 0.2s;"></button>
                        <button type="button" class="theme-swatch-btn rounded-circle p-0 <?= $theme === 'pink' ? 'active' : '' ?>" data-theme-val="pink" title="Pink Theme" style="width: 16px; height: 16px; background-color: #A2396C; border: 1px solid #FFD8E7; cursor: pointer; transition: all 0.2s;"></button>
                        <button type="button" class="theme-swatch-btn rounded-circle p-0 <?= $theme === 'yellow' ? 'active' : '' ?>" data-theme-val="yellow" title="Yellow Theme" style="width: 16px; height: 16px; background-color: #765B00; border: 1px solid #FFE08E; cursor: pointer; transition: all 0.2s;"></button>
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
            <?php /* <a href="/login" class="btn btn-primary btn-sm">Login as Committee</a> */ ?>
        </div>
    </div>
<?php endif; ?>
<div class="container-fluid mb-4">
