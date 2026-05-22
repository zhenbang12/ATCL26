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
    <link rel="stylesheet" href="/css/material3.css">
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
        
        <nav class="flex-grow-1">
            <ul class="m3-nav-group">
                <li>
                    <a href="/dashboard" class="m3-nav-item <?= ($title ?? '') === 'Dashboard' || strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">dashboard</span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="/participants" class="m3-nav-item <?= ($title ?? '') === 'Participants & Admission' || ($title ?? '') === 'Participants List' || strpos($_SERVER['REQUEST_URI'], '/participants') !== false ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">group</span>
                        Participants
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
                <?php if (in_array(\App\Core\Auth::role(), ['advisor', 'committee'], true)): ?>
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
            <!-- Sidebar Theme Switcher -->
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
            <a href="/login" class="btn btn-primary btn-sm">Login</a>
        </div>
    </div>
<?php endif; ?>
<div class="container-fluid mb-4">
