<?php
/**
 * Theme & Appearance Settings View (Material Design 3 Dynamic Color)
 */
use App\Core\Auth;
use App\Core\ThemeHelper;

$resolvedHex = ThemeHelper::resolveHex($currentTheme ?? '#6750A4');
?>

<div class="container-fluid px-3 px-md-4 py-4" style="max-width: 1280px;">
    <!-- Page Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="material-symbols-outlined text-primary" style="font-size: 32px;">palette</span>
                <h1 class="h3 fw-bold mb-0" style="color: var(--md-sys-color-on-surface, #1d1b20);">Theme &amp; Appearance</h1>
            </div>
            <p class="text-muted small mb-0">
                Material Design 3 dynamic color generation. Pick any primary color to automatically derive the full tonal palette for the entire system.
            </p>
        </div>
        <div>
            <a href="/settings/landing" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
                Back to Settings
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType ?? 'info') ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
            <span class="material-symbols-outlined"><?= $messageType === 'success' ? 'check_circle' : 'info' ?></span>
            <div><?= htmlspecialchars($message) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="/settings/theme/save" id="themeSettingsForm">
        <input type="hidden" name="theme" id="selectedThemeInput" value="<?= htmlspecialchars($resolvedHex) ?>">

        <div class="row g-4">
            <!-- Left Column: Palette Selection & Custom Picker -->
            <div class="col-lg-6">
                <!-- Preset Palettes Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4" style="background-color: var(--md-sys-color-surface-container-low, #f7f2fa);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
                                <span class="material-symbols-outlined" style="font-size: 20px;">swatch</span>
                                Material 3 Preset Palettes
                            </h2>
                            <span class="badge rounded-pill text-bg-light small fw-normal border">Curated</span>
                        </div>
                        <p class="small text-muted mb-3">
                            Click any preset below to instantly preview the complete harmonious theme.
                        </p>

                        <div class="row row-cols-2 row-cols-sm-4 g-2" id="presetSwatchesGrid">
                            <?php foreach (ThemeHelper::PRESETS as $key => $preset): 
                                if (!empty($preset['superuser_only'])) continue; // Handled separately
                                $isActive = strtoupper($preset['hex']) === strtoupper($resolvedHex);
                            ?>
                                <div class="col">
                                    <button type="button" 
                                            class="preset-card btn w-100 p-2 text-start rounded-3 d-flex flex-column gap-2 transition-all <?= $isActive ? 'active-preset' : '' ?>" 
                                            data-hex="<?= htmlspecialchars($preset['hex']) ?>"
                                            style="background: #ffffff; border: 2px solid <?= $isActive ? 'var(--md-sys-color-primary)' : '#e0e0e0' ?>; cursor: pointer;">
                                        <div class="d-flex align-items-center justify-content-between w-100">
                                            <span class="rounded-circle shadow-sm" style="width: 28px; height: 28px; background-color: <?= htmlspecialchars($preset['hex']) ?>; border: 2px solid #ffffff;"></span>
                                            <span class="material-symbols-outlined preset-check text-primary" style="font-size: 18px; display: <?= $isActive ? 'inline-block' : 'none' ?>;">check_circle</span>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small text-truncate" style="font-size: 0.8rem;"><?= htmlspecialchars($preset['name']) ?></div>
                                            <div class="text-muted" style="font-size: 0.7rem; font-family: monospace;"><?= htmlspecialchars($preset['hex']) ?></div>
                                        </div>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- RGB Gamer Mode (Superuser Only) -->
                <?php if (Auth::isSuperuser()): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4 position-relative overflow-hidden" 
                     id="rgbModeCard"
                     style="background: linear-gradient(135deg, rgba(255,0,85,0.06), rgba(0,229,255,0.06), rgba(213,0,249,0.06)); border: 2px solid <?= $resolvedHex === 'rgb' ? '#ff0055' : 'rgba(0,0,0,0.08)' ?> !important;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="material-symbols-outlined" style="font-size: 24px; color: #ff0055;">auto_awesome</span>
                                <h2 class="h6 fw-bold mb-0" style="color: var(--md-sys-color-on-surface);">RGB Gamer Mode 🌈</h2>
                            </div>
                            <span class="badge rounded-pill px-3 py-1 small fw-bold text-white shadow-sm" 
                                  style="background: linear-gradient(90deg, #ff0055, #ff7700, #ffe600, #00e676, #00b0ff, #d500f9); font-size: 0.70rem; letter-spacing: 0.5px;">
                                SUPERUSER ONLY
                            </span>
                        </div>
                        <p class="small text-muted mb-3">
                            Enables a dynamic 360° chromatic wave cycling through the full rainbow spectrum across buttons, active navigation, badges, chips, and outlines in real-time.
                        </p>
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-white border shadow-sm">
                            <div>
                                <span class="fw-semibold small d-block" id="rgbStatusText">
                                    <?= $resolvedHex === 'rgb' ? '⚡ RGB Chroma Active' : 'RGB Mode is Off' ?>
                                </span>
                                <span class="text-muted" style="font-size: 0.75rem;">Continuous live rainbow spectrum cycling.</span>
                            </div>
                            <button type="button" 
                                    id="toggleRgbBtn" 
                                    class="btn rounded-pill px-3 py-2 btn-sm fw-bold shadow-sm d-flex align-items-center gap-2 text-white" 
                                    style="background: linear-gradient(90deg, #ff0055, #ff7700, #00e676, #00b0ff); border: none;">
                                <span class="material-symbols-outlined" style="font-size: 18px;">power_settings_new</span>
                                <span id="rgbBtnLabel"><?= $resolvedHex === 'rgb' ? 'Disable RGB' : 'Enable RGB' ?></span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4 opacity-75" style="background-color: var(--md-sys-color-surface-container-low, #f7f2fa);">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="material-symbols-outlined text-muted" style="font-size: 20px;">lock</span>
                            <span class="small fw-semibold text-muted">RGB Gamer Mode (Chroma Cycle)</span>
                        </div>
                        <span class="badge rounded-pill text-bg-secondary small fw-normal">Superuser Only</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Custom Color Picker Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4" style="background-color: var(--md-sys-color-surface-container-low, #f7f2fa);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
                                <span class="material-symbols-outlined" style="font-size: 20px;">colorize</span>
                                Custom Key Color
                            </h2>
                            <span class="badge rounded-pill text-bg-light small fw-normal border">Hex / Picker</span>
                        </div>
                        <p class="small text-muted mb-3">
                            Choose any primary brand color. The algorithm will automatically calculate complementary tonal values according to Material Design 3 guidelines.
                        </p>

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <!-- Native Color Input Box -->
                            <div class="position-relative" style="width: 56px; height: 56px;">
                                <input type="color" 
                                       id="nativeColorPicker" 
                                       class="form-control form-control-color w-100 h-100 p-0 border-0 rounded-4 shadow-sm" 
                                       value="<?= htmlspecialchars($resolvedHex) ?>"
                                       style="cursor: pointer;">
                            </div>

                            <!-- Hex Text Input -->
                            <div class="flex-grow-1">
                                <label for="hexTextInput" class="form-label small fw-semibold text-muted mb-1">Hex Color Code</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted">#</span>
                                    <input type="text" 
                                           id="hexTextInput" 
                                           class="form-control border-start-0" 
                                           maxlength="6" 
                                           placeholder="6750A4" 
                                           value="<?= htmlspecialchars(ltrim($resolvedHex, '#')) ?>" 
                                           style="font-family: monospace; text-transform: uppercase;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="d-flex align-items-center gap-3 pt-2">
                    <button type="submit" id="saveThemeBtn" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 20px;">save</span>
                        Save Theme Settings
                    </button>
                    <button type="button" id="resetDefaultBtn" class="btn btn-outline-secondary px-3 py-2 rounded-pill d-flex align-items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 18px;">restart_alt</span>
                        Reset to Violet
                    </button>
                </div>
            </div>

            <!-- Right Column: Live Material 3 Showcase & Tonal Palette -->
            <div class="col-lg-6">
                <!-- Tonal Palette Breakdown -->
                <div class="card border-0 shadow-sm rounded-4 mb-4" style="background-color: var(--md-sys-color-surface-container, #f3edf7);">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-2 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
                            <span class="material-symbols-outlined" style="font-size: 20px;">auto_awesome</span>
                            Generated M3 Tonal Palette
                        </h2>
                        <p class="small text-muted mb-3">
                            Tokens generated live in real-time from your primary key color:
                        </p>

                        <!-- Color Chips Display -->
                        <div class="row g-2 mb-3" id="liveTonalChips">
                            <div class="col-6">
                                <div class="p-3 rounded-3 shadow-sm d-flex flex-column justify-content-between" style="background-color: var(--md-sys-color-primary); color: var(--md-sys-color-on-primary); min-height: 72px;">
                                    <span class="fw-bold small">Primary</span>
                                    <span class="small opacity-75" style="font-size: 0.75rem;">Main Actions</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-3 shadow-sm d-flex flex-column justify-content-between" style="background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); min-height: 72px;">
                                    <span class="fw-bold small">Primary Container</span>
                                    <span class="small opacity-75" style="font-size: 0.75rem;">Card / Active Nav</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-3 shadow-sm d-flex flex-column justify-content-between" style="background-color: var(--md-sys-color-secondary); color: var(--md-sys-color-on-secondary); min-height: 72px;">
                                    <span class="fw-bold small">Secondary</span>
                                    <span class="small opacity-75" style="font-size: 0.75rem;">Filter &amp; Badges</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-3 shadow-sm d-flex flex-column justify-content-between" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); min-height: 72px;">
                                    <span class="fw-bold small">Secondary Container</span>
                                    <span class="small opacity-75" style="font-size: 0.75rem;">Tonal Badges</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-3 shadow-sm d-flex flex-column justify-content-between" style="background-color: var(--md-sys-color-tertiary); color: var(--md-sys-color-on-tertiary); min-height: 72px;">
                                    <span class="fw-bold small">Tertiary</span>
                                    <span class="small opacity-75" style="font-size: 0.75rem;">Accent &amp; Contrast</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-3 shadow-sm d-flex flex-column justify-content-between border" style="background-color: var(--md-sys-color-surface-container); color: var(--md-sys-color-on-surface); min-height: 72px;">
                                    <span class="fw-bold small">Surface Container</span>
                                    <span class="small opacity-75" style="font-size: 0.75rem;">Dialogs &amp; Tables</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Components Playground -->
                <div class="card border-0 shadow-sm rounded-4" style="background-color: var(--md-sys-color-surface-container-low, #f7f2fa);">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
                            <span class="material-symbols-outlined" style="font-size: 20px;">preview</span>
                            Live Component Preview
                        </h2>

                        <!-- Sample Buttons -->
                        <div class="mb-4">
                            <label class="small fw-semibold text-muted d-block mb-2">Material 3 Buttons</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-primary rounded-pill px-3 py-2 btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">check</span>
                                    Filled Button
                                </button>
                                <button type="button" class="btn rounded-pill px-3 py-2 btn-sm d-inline-flex align-items-center gap-1" style="background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); border: none;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">bookmark</span>
                                    Tonal Button
                                </button>
                                <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-2 btn-sm d-inline-flex align-items-center gap-1">
                                    Outlined Button
                                </button>
                            </div>
                        </div>

                        <!-- Sample Chips & Badges -->
                        <div class="mb-4">
                            <label class="small fw-semibold text-muted d-block mb-2">Chips &amp; Badges</label>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1" style="background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container);">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">check</span>
                                    Active Filter
                                </span>
                                <span class="badge rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container);">
                                    Category Chip
                                </span>
                                <span class="badge rounded-pill px-3 py-2 border text-dark bg-white">
                                    Standard Badge
                                </span>
                            </div>
                        </div>

                        <!-- Sample Surface Card -->
                        <div>
                            <label class="small fw-semibold text-muted d-block mb-2">Sample Surface Card</label>
                            <div class="p-3 rounded-3 shadow-sm border" style="background-color: #ffffff;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="material-symbols-outlined text-primary" style="font-size: 24px;">verified_user</span>
                                    <span class="fw-bold small" style="color: var(--md-sys-color-on-surface);">Camp Management System</span>
                                </div>
                                <p class="small text-muted mb-0">
                                    All tables, cards, navigation items, and forms across ATCL26 adapt seamlessly to the selected Material 3 dynamic color scheme.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function() {
    // --- Material Design 3 Color Math in JavaScript ---
    function hexToHsl(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        const r = parseInt(hex.substring(0, 2), 16) / 255;
        const g = parseInt(hex.substring(2, 4), 16) / 255;
        const b = parseInt(hex.substring(4, 6), 16) / 255;

        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        const d = max - min;
        let l = (max + min) / 2;
        let h = 0;
        let s = 0;

        if (d !== 0) {
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                case g: h = (b - r) / d + 2; break;
                case b: h = (r - g) / d + 4; break;
            }
            h /= 6;
        }

        return {
            h: Math.round(h * 360),
            s: Math.round(s * 100),
            l: Math.round(l * 100)
        };
    }

    function generateM3Tokens(sourceHex) {
        const hsl = hexToHsl(sourceHex);
        const h = hsl.h;
        const s = Math.max(25, Math.min(85, hsl.s));

        const hSec = (h + 12) % 360;
        const sSec = Math.max(15, s * 0.35);

        const hTer = (h + 60) % 360;
        const sTer = Math.max(20, s * 0.50);

        return {
            '--md-sys-color-primary': `hsl(${h}, ${s}%, 45%)`,
            '--md-sys-color-primary-hover': `hsl(${h}, ${s}%, 38%)`,
            '--md-sys-color-on-primary': '#FFFFFF',
            '--md-sys-color-primary-container': `hsl(${h}, ${Math.round(s * 0.7)}%, 92%)`,
            '--md-sys-color-on-primary-container': `hsl(${h}, ${s}%, 15%)`,

            '--md-sys-color-secondary': `hsl(${hSec}, ${Math.round(sSec)}%, 40%)`,
            '--md-sys-color-on-secondary': '#FFFFFF',
            '--md-sys-color-secondary-container': `hsl(${hSec}, ${Math.round(sSec)}%, 92%)`,
            '--md-sys-color-on-secondary-container': `hsl(${hSec}, ${Math.round(sSec * 1.1)}%, 14%)`,

            '--md-sys-color-tertiary': `hsl(${hTer}, ${Math.round(sTer)}%, 42%)`,
            '--md-sys-color-on-tertiary': '#FFFFFF',
            '--md-sys-color-tertiary-container': `hsl(${hTer}, ${Math.round(sTer)}%, 92%)`,
            '--md-sys-color-on-tertiary-container': `hsl(${hTer}, ${Math.round(sTer * 1.1)}%, 14%)`,

            '--md-sys-color-background': `hsl(${h}, 18%, 98.5%)`,
            '--md-sys-color-on-background': `hsl(${h}, 10%, 12%)`,
            '--md-sys-color-surface': `hsl(${h}, 18%, 98.5%)`,
            '--md-sys-color-surface-dim': `hsl(${h}, 12%, 87%)`,
            '--md-sys-color-surface-bright': `hsl(${h}, 18%, 98.5%)`,
            '--md-sys-color-surface-container-lowest': '#FFFFFF',
            '--md-sys-color-surface-container-low': `hsl(${h}, 12%, 96%)`,
            '--md-sys-color-surface-container': `hsl(${h}, 12%, 94%)`,
            '--md-sys-color-surface-container-high': `hsl(${h}, 12%, 91%)`,
            '--md-sys-color-surface-container-highest': `hsl(${h}, 12%, 88%)`,
            '--md-sys-color-on-surface': `hsl(${h}, 10%, 12%)`,
            '--md-sys-color-on-surface-variant': `hsl(${h}, 8%, 32%)`,

            '--md-sys-color-outline': `hsl(${h}, 8%, 50%)`,
            '--md-sys-color-outline-variant': `hsl(${h}, 12%, 80%)`,
            '--md-sys-color-shadow': `hsla(${h}, ${s}%, 30%, 0.08)`
        };
    }

    // Apply tokens live to document
    function applyLiveTokens(hex) {
        const tokens = generateM3Tokens(hex);
        let styleTag = document.getElementById('m3-dynamic-theme-vars');
        if (!styleTag) {
            styleTag = document.createElement('style');
            styleTag.id = 'm3-dynamic-theme-vars';
            document.head.appendChild(styleTag);
        }

        let css = ':root, html {\n';
        for (const [prop, val] of Object.entries(tokens)) {
            css += `    ${prop}: ${val};\n`;
            document.documentElement.style.setProperty(prop, val);
        }
        css += '}\n';
        styleTag.textContent = css;
    }

    // DOM Elements
    const nativeColorPicker = document.getElementById('nativeColorPicker');
    const hexTextInput = document.getElementById('hexTextInput');
    const selectedThemeInput = document.getElementById('selectedThemeInput');
    const presetButtons = document.querySelectorAll('.preset-card');
    const resetDefaultBtn = document.getElementById('resetDefaultBtn');
    const toggleRgbBtn = document.getElementById('toggleRgbBtn');
    const rgbModeCard = document.getElementById('rgbModeCard');
    const rgbStatusText = document.getElementById('rgbStatusText');
    const rgbBtnLabel = document.getElementById('rgbBtnLabel');

    let isRgbActive = false;
    let rgbAnimId = null;
    let rgbHue = 0;
    let lastTime = performance.now();

    function startRgbMode() {
        isRgbActive = true;
        selectedThemeInput.value = 'rgb';

        if (rgbModeCard) rgbModeCard.style.borderColor = '#ff0055';
        if (rgbStatusText) rgbStatusText.textContent = '⚡ RGB Chroma Active';
        if (rgbBtnLabel) rgbBtnLabel.textContent = 'Disable RGB';

        presetButtons.forEach(btn => {
            btn.style.borderColor = '#e0e0e0';
            const check = btn.querySelector('.preset-check');
            if (check) check.style.display = 'none';
        });

        lastTime = performance.now();

        function rgbLoop(now) {
            if (!isRgbActive) return;
            const delta = now - lastTime;
            lastTime = now;
            rgbHue = (rgbHue + delta * 0.08) % 360;

            const h = rgbHue.toFixed(1);
            const root = document.documentElement;

            root.style.setProperty('--rgb-hue', h);
            root.style.setProperty('--md-sys-color-primary', `hsl(${h}, 90%, 48%)`);
            root.style.setProperty('--md-sys-color-primary-hover', `hsl(${h}, 90%, 40%)`);
            root.style.setProperty('--md-sys-color-primary-container', `hsl(${h}, 85%, 92%)`);
            root.style.setProperty('--md-sys-color-on-primary-container', `hsl(${h}, 95%, 15%)`);
            root.style.setProperty('--md-sys-color-secondary', `hsl(${(rgbHue + 60) % 360}, 85%, 44%)`);
            root.style.setProperty('--md-sys-color-secondary-container', `hsl(${(rgbHue + 60) % 360}, 75%, 92%)`);
            root.style.setProperty('--md-sys-color-tertiary', `hsl(${(rgbHue + 180) % 360}, 85%, 45%)`);
            root.style.setProperty('--md-sys-color-shadow', `hsla(${h}, 90%, 40%, 0.15)`);

            rgbAnimId = requestAnimationFrame(rgbLoop);
        }

        if (rgbAnimId) cancelAnimationFrame(rgbAnimId);
        rgbAnimId = requestAnimationFrame(rgbLoop);
    }

    function stopRgbMode() {
        if (!isRgbActive) return;
        isRgbActive = false;
        if (rgbAnimId) {
            cancelAnimationFrame(rgbAnimId);
            rgbAnimId = null;
        }
        if (rgbModeCard) rgbModeCard.style.borderColor = 'rgba(0,0,0,0.08)';
        if (rgbStatusText) rgbStatusText.textContent = 'RGB Mode is Off';
        if (rgbBtnLabel) rgbBtnLabel.textContent = 'Enable RGB';
    }

    function updateActiveState(currentHex) {
        currentHex = currentHex.toUpperCase();
        presetButtons.forEach(btn => {
            const btnHex = btn.getAttribute('data-hex').toUpperCase();
            const checkIcon = btn.querySelector('.preset-check');
            if (btnHex === currentHex) {
                btn.style.borderColor = 'var(--md-sys-color-primary)';
                if (checkIcon) checkIcon.style.display = 'inline-block';
            } else {
                btn.style.borderColor = '#e0e0e0';
                if (checkIcon) checkIcon.style.display = 'none';
            }
        });
    }

    function syncColor(hex) {
        stopRgbMode();
        if (!hex.startsWith('#')) hex = '#' + hex;
        if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
            nativeColorPicker.value = hex;
            hexTextInput.value = hex.replace('#', '').toUpperCase();
            selectedThemeInput.value = hex.toUpperCase();
            applyLiveTokens(hex);
            updateActiveState(hex);
        }
    }

    // Toggle RGB Button
    if (toggleRgbBtn) {
        toggleRgbBtn.addEventListener('click', function() {
            if (isRgbActive) {
                stopRgbMode();
                syncColor('#6750A4');
            } else {
                startRgbMode();
            }
        });
    }

    // Listeners
    if (nativeColorPicker) {
        nativeColorPicker.addEventListener('input', function(e) {
            syncColor(e.target.value);
        });
    }

    if (hexTextInput) {
        hexTextInput.addEventListener('input', function(e) {
            let val = e.target.value.trim().replace('#', '');
            if (val.length === 6 && /^[0-9A-Fa-f]{6}$/.test(val)) {
                syncColor('#' + val);
            }
        });
    }

    presetButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const hex = this.getAttribute('data-hex');
            syncColor(hex);
        });
    });

    if (resetDefaultBtn) {
        resetDefaultBtn.addEventListener('click', function() {
            syncColor('#6750A4');
        });
    }

    // Initial sync
    const initialTheme = '<?= htmlspecialchars($resolvedHex) ?>';
    if (initialTheme === 'rgb') {
        startRgbMode();
    } else {
        syncColor(initialTheme);
    }
})();
</script>
