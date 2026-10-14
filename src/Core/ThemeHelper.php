<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Material Design 3 Dynamic Theme Engine
 * Generates full M3 tonal color palettes from a single source/primary color.
 */
class ThemeHelper
{
    /** @var array<string, array{name: string, hex: string}> */
    public const PRESETS = [
        'violet' => [
            'name' => 'Classic Violet',
            'hex'  => '#6750A4',
        ],
        'blue' => [
            'name' => 'Ocean Blue',
            'hex'  => '#0061A4',
        ],
        'teal' => [
            'name' => 'Deep Teal',
            'hex'  => '#006A6A',
        ],
        'green' => [
            'name' => 'Emerald Green',
            'hex'  => '#2E6B4E',
        ],
        'yellow' => [
            'name' => 'Amber Gold',
            'hex'  => '#765B00',
        ],
        'pink' => [
            'name' => 'Rose Berry',
            'hex'  => '#A2396C',
        ],
        'red' => [
            'name' => 'Crimson Red',
            'hex'  => '#B3261E',
        ],
        'slate' => [
            'name' => 'Midnight Slate',
            'hex'  => '#475569',
        ],
        'rgb' => [
            'name' => 'RGB Gamer Mode 🌈',
            'hex'  => 'rgb',
            'superuser_only' => true,
        ],
    ];

    /**
     * Resolve a theme string (preset name or hex code) to a valid hex color or 'rgb'.
     */
    public static function resolveHex(string $theme): string
    {
        $theme = trim($theme);
        if (strtolower($theme) === 'rgb') {
            return 'rgb';
        }

        if (isset(self::PRESETS[strtolower($theme)])) {
            return self::PRESETS[strtolower($theme)]['hex'];
        }

        // Validate hex format (e.g. #6750A4 or 6750A4)
        if (preg_match('/^#?([a-f0-9]{6})$/i', $theme, $matches)) {
            return '#' . strtoupper($matches[1]);
        }

        return self::PRESETS['violet']['hex'];
    }

    /**
     * Convert Hex (#RRGGBB) to HSL array [h: 0-360, s: 0-100, l: 0-100].
     *
     * @return array{h: float, s: float, l: float}
     */
    public static function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255.0;
        $g = hexdec(substr($hex, 2, 2)) / 255.0;
        $b = hexdec(substr($hex, 4, 2)) / 255.0;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $d = $max - $min;

        $l = ($max + $min) / 2.0;

        if ($d == 0) {
            $h = 0.0;
            $s = 0.0;
        } else {
            $s = $l > 0.5 ? $d / (2.0 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6.0 : 0.0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2.0;
                    break;
                default:
                    $h = ($r - $g) / $d + 4.0;
                    break;
            }
            $h /= 6.0;
        }

        return [
            'h' => round($h * 360.0, 1),
            's' => round($s * 100.0, 1),
            'l' => round($l * 100.0, 1),
        ];
    }

    /**
     * Generate all Material Design 3 CSS tokens from a source hex color.
     *
     * @return array<string, string>
     */
    public static function generateM3Palette(string $sourceHex): array
    {
        $hex = self::resolveHex($sourceHex);
        $hsl = self::hexToHsl($hex);
        $h = $hsl['h'];
        $s = max(25.0, min(85.0, $hsl['s'])); // normalize chroma

        // Derive secondary (analogous +12 deg, lower saturation)
        $hSec = fmod($h + 12.0, 360.0);
        $sSec = max(15.0, $s * 0.35);

        // Derive tertiary (triadic +60 deg, balanced saturation)
        $hTer = fmod($h + 60.0, 360.0);
        $sTer = max(20.0, $s * 0.50);

        return [
            // Primary
            '--md-sys-color-primary' => "hsl({$h}, {$s}%, 45%)",
            '--md-sys-color-primary-hover' => "hsl({$h}, {$s}%, 38%)",
            '--md-sys-color-on-primary' => '#FFFFFF',
            '--md-sys-color-primary-container' => "hsl({$h}, " . round($s * 0.7, 1) . "%, 92%)",
            '--md-sys-color-on-primary-container' => "hsl({$h}, {$s}%, 15%)",

            // Secondary
            '--md-sys-color-secondary' => "hsl({$hSec}, " . round($sSec, 1) . "%, 40%)",
            '--md-sys-color-on-secondary' => '#FFFFFF',
            '--md-sys-color-secondary-container' => "hsl({$hSec}, " . round($sSec, 1) . "%, 92%)",
            '--md-sys-color-on-secondary-container' => "hsl({$hSec}, " . round($sSec * 1.1, 1) . "%, 14%)",

            // Tertiary
            '--md-sys-color-tertiary' => "hsl({$hTer}, " . round($sTer, 1) . "%, 42%)",
            '--md-sys-color-on-tertiary' => '#FFFFFF',
            '--md-sys-color-tertiary-container' => "hsl({$hTer}, " . round($sTer, 1) . "%, 92%)",
            '--md-sys-color-on-tertiary-container' => "hsl({$hTer}, " . round($sTer * 1.1, 1) . "%, 14%)",

            // Surface & Background (Light Mode)
            '--md-sys-color-background' => "hsl({$h}, 18%, 98.5%)",
            '--md-sys-color-on-background' => "hsl({$h}, 10%, 12%)",
            '--md-sys-color-surface' => "hsl({$h}, 18%, 98.5%)",
            '--md-sys-color-surface-dim' => "hsl({$h}, 12%, 87%)",
            '--md-sys-color-surface-bright' => "hsl({$h}, 18%, 98.5%)",
            '--md-sys-color-surface-container-lowest' => '#FFFFFF',
            '--md-sys-color-surface-container-low' => "hsl({$h}, 12%, 96%)",
            '--md-sys-color-surface-container' => "hsl({$h}, 12%, 94%)",
            '--md-sys-color-surface-container-high' => "hsl({$h}, 12%, 91%)",
            '--md-sys-color-surface-container-highest' => "hsl({$h}, 12%, 88%)",
            '--md-sys-color-on-surface' => "hsl({$h}, 10%, 12%)",
            '--md-sys-color-on-surface-variant' => "hsl({$h}, 8%, 32%)",

            // Outline & Shadow
            '--md-sys-color-outline' => "hsl({$h}, 8%, 50%)",
            '--md-sys-color-outline-variant' => "hsl({$h}, 12%, 80%)",
            '--md-sys-color-shadow' => "hsla({$h}, {$s}%, 30%, 0.08)",
        ];
    }

    /**
     * Render the CSS block to be injected directly into the HTML <head>.
     */
    public static function renderCssBlock(string $theme): string
    {
        if (strtolower(trim($theme)) === 'rgb') {
            return self::renderRgbBlock();
        }

        $tokens = self::generateM3Palette($theme);
        $css = ":root, html {\n";
        foreach ($tokens as $property => $value) {
            $css .= "    {$property}: {$value};\n";
        }
        $css .= "}\n";

        return "<style id=\"m3-dynamic-theme-vars\">\n{$css}</style>";
    }

    /**
     * Render the dynamic RGB chroma cycle engine for superuser RGB Mode.
     */
    public static function renderRgbBlock(): string
    {
        return <<<'HTML'
<style id="m3-dynamic-theme-vars">
:root, html {
    --rgb-hue: 0;
    --md-sys-color-primary: hsl(var(--rgb-hue), 90%, 48%);
    --md-sys-color-primary-hover: hsl(var(--rgb-hue), 90%, 40%);
    --md-sys-color-on-primary: #FFFFFF;
    --md-sys-color-primary-container: hsl(var(--rgb-hue), 85%, 92%);
    --md-sys-color-on-primary-container: hsl(var(--rgb-hue), 95%, 15%);
    --md-sys-color-secondary: hsl(calc(var(--rgb-hue) + 60), 85%, 44%);
    --md-sys-color-on-secondary: #FFFFFF;
    --md-sys-color-secondary-container: hsl(calc(var(--rgb-hue) + 60), 75%, 92%);
    --md-sys-color-on-secondary-container: hsl(calc(var(--rgb-hue) + 60), 90%, 14%);
    --md-sys-color-tertiary: hsl(calc(var(--rgb-hue) + 180), 85%, 45%);
    --md-sys-color-on-tertiary: #FFFFFF;
    --md-sys-color-tertiary-container: hsl(calc(var(--rgb-hue) + 180), 75%, 92%);
    --md-sys-color-on-tertiary-container: hsl(calc(var(--rgb-hue) + 180), 90%, 14%);
    --md-sys-color-background: hsl(var(--rgb-hue), 25%, 98.5%);
    --md-sys-color-on-background: hsl(var(--rgb-hue), 15%, 12%);
    --md-sys-color-surface: hsl(var(--rgb-hue), 25%, 98.5%);
    --md-sys-color-surface-dim: hsl(var(--rgb-hue), 18%, 88%);
    --md-sys-color-surface-bright: hsl(var(--rgb-hue), 25%, 98.5%);
    --md-sys-color-surface-container-lowest: #FFFFFF;
    --md-sys-color-surface-container-low: hsl(var(--rgb-hue), 18%, 96%);
    --md-sys-color-surface-container: hsl(var(--rgb-hue), 18%, 94%);
    --md-sys-color-surface-container-high: hsl(var(--rgb-hue), 18%, 91%);
    --md-sys-color-surface-container-highest: hsl(var(--rgb-hue), 18%, 88%);
    --md-sys-color-on-surface: hsl(var(--rgb-hue), 15%, 12%);
    --md-sys-color-on-surface-variant: hsl(var(--rgb-hue), 12%, 32%);
    --md-sys-color-outline: hsl(var(--rgb-hue), 15%, 55%);
    --md-sys-color-outline-variant: hsl(var(--rgb-hue), 20%, 82%);
    --md-sys-color-shadow: hsla(var(--rgb-hue), 90%, 40%, 0.15);
}

/* RGB Rainbow gradient header title glow */
.m3-brand-name {
    background: linear-gradient(90deg, #ff0055, #ff7700, #ffe600, #00e676, #00b0ff, #d500f9, #ff0055);
    background-size: 300% 300%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: rgb-rainbow-flow 6s linear infinite;
}

@keyframes rgb-rainbow-flow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
</style>
<script>
(function() {
    let rgbHue = 0;
    let lastTime = performance.now();
    const speed = 0.08; // degrees per ms -> complete 360 deg cycle every 4.5s

    function cycleRgb(now) {
        const delta = now - lastTime;
        lastTime = now;
        rgbHue = (rgbHue + delta * speed) % 360;

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

        requestAnimationFrame(cycleRgb);
    }
    requestAnimationFrame(cycleRgb);
})();
</script>
HTML;
    }
}
