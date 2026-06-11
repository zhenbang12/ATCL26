<?php
// Public participant landing page.
/** @var array<string, array{filename: ?string, alt_text: string}> $landingImages */
$landingImages = $landingImages ?? [];
/** @var array{logo_1_filename: ?string, logo_1_alt_text: string, logo_2_filename: ?string, logo_2_alt_text: string, logo_3_filename: ?string, logo_3_alt_text: string, background_color: string, main_title: string, main_caption: string, section_1_title: string, section_1_caption: string, section_2_title: string, section_2_caption: string, section_3_title: string, section_3_caption: string} $landingSettings */
$landingSettings = $landingSettings ?? [
    'logo_1_filename' => null,
    'logo_1_alt_text' => '',
    'logo_2_filename' => null,
    'logo_2_alt_text' => '',
    'logo_3_filename' => null,
    'logo_3_alt_text' => '',
    'background_color' => '#ffffff',
    'main_title' => 'Welcome to Adjustment To Campus Life',
    'main_caption' => 'A few days of games, teamwork, and community built for TAR UMT students to connect, learn, and make memories together.',
    'section_1_title' => 'What is it?',
    'section_1_caption' => 'ATCL is our annual camp-style programme. You will join a small group, take part in station games and activities, and get to know facilitators and participants from across programmes. It is run by student leaders and advisors with safety and inclusion in mind.',
    'section_2_title' => 'What to expect',
    'section_2_caption' => 'Icebreakers and group challenges across the event. Meals, briefings, and evening segments with your group. Check-in on arrival using the QR code you receive after registering. Language-friendly grouping so you can participate comfortably.',
    'section_3_title' => 'Before you arrive',
    'section_3_caption' => 'Pre-register with your student details so we can prepare your QR for check-in and place you in a group when you arrive. If you already registered, you can retrieve your QR any time.',
];
/** @var array{pre_register_enabled: bool, walk_in_enabled: bool} $registrationSettings */
$registrationSettings = $registrationSettings ?? [
    'pre_register_enabled' => true,
    'walk_in_enabled' => true,
];

$landingUrl = static function (?string $filename): ?string {
    if ($filename === null || $filename === '') {
        return null;
    }
    if (strpbrk($filename, '/\\') !== false) {
        return null;
    }

    return '/uploads/landing/' . rawurlencode($filename);
};
?>

<?php if (!\App\Core\Auth::check() || isset($forcePublic)): ?>
    <style>
        body {
            background-color: <?= ($landingSettings['background_color'] === '#ffffff' || $landingSettings['background_color'] === '#FFF') ? 'var(--md-sys-color-background)' : htmlspecialchars($landingSettings['background_color']) ?> !important;
            margin: 0;
            padding: 0;
        }
        .landing-container {
            background-color: transparent !important;
            margin: 0;
            padding: clamp(2rem, 6vw, 4rem) 0 0;
            max-width: none;
            width: 100%;
        }
        .landing-logo-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(0.75rem, 3vw, 1.5rem);
            flex-wrap: nowrap;
        }
        .landing-logo-strip img {
            width: auto;
            max-width: calc((100% - 3rem) / 3);
            max-height: clamp(42px, 13vw, 80px);
            object-fit: contain;
        }
        .landing-footer {
            padding: 2.5rem 1rem 1.5rem;
            text-align: center;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 0.95rem;
            border-top: 1px solid var(--md-sys-color-outline-variant);
            margin-top: 4rem;
        }
    </style>

    <div class="landing-container">
        <div class="row justify-content-center" style="background-color: transparent; min-height: 100vh;">
            <div class="col-lg-10 col-xl-8">
                <?php if (!empty($landingSettings['logo_1_filename']) || !empty($landingSettings['logo_2_filename']) || !empty($landingSettings['logo_3_filename'])): ?>
                    <div class="text-center mb-4">
                        <div class="landing-logo-strip">
                            <?php if (!empty($landingSettings['logo_1_filename'])): ?>
                                <img src="<?= htmlspecialchars($landingUrl($landingSettings['logo_1_filename'])) ?>" alt="<?= htmlspecialchars($landingSettings['logo_1_alt_text']) ?>">
                            <?php endif; ?>
                            <?php if (!empty($landingSettings['logo_2_filename'])): ?>
                                <img src="<?= htmlspecialchars($landingUrl($landingSettings['logo_2_filename'])) ?>" alt="<?= htmlspecialchars($landingSettings['logo_2_alt_text']) ?>">
                            <?php endif; ?>
                            <?php if (!empty($landingSettings['logo_3_filename'])): ?>
                                <img src="<?= htmlspecialchars($landingUrl($landingSettings['logo_3_filename'])) ?>" alt="<?= htmlspecialchars($landingSettings['logo_3_alt_text']) ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <header class="text-center mb-5">
                    <h1 class="display-5 fw-bold mb-3" style="color: var(--md-sys-color-primary);"><?= htmlspecialchars($landingSettings['main_title']) ?></h1>
                    <p class="lead mb-0 mx-auto" style="max-width: 38rem; color: var(--md-sys-color-on-surface-variant); font-size: 1.15rem; line-height: 1.6;">
                        <?= htmlspecialchars($landingSettings['main_caption']) ?>
                    </p>
                </header>

                <div class="card mb-5 border-0" style="background-color: var(--md-sys-color-primary-container) !important; color: var(--md-sys-color-on-primary-container) !important; box-shadow: 0 4px 20px var(--md-sys-color-shadow) !important;">
                    <div class="card-body text-center py-5 px-4">
                        <span class="material-symbols-outlined mb-2 text-primary" style="font-size: 48px;">campaign</span>
                        <h2 class="h4 fw-bold mb-2">Ready to join?</h2>
                        <p class="mb-4 text-center mx-auto" style="max-width: 28rem; opacity: 0.9;">
                            Takes only a minute. You will need your student ID and TAR UMT student email.
                        </p>
                        <?php if ($registrationSettings['pre_register_enabled']): ?>
                            <a href="/participants/create" class="btn btn-primary btn-lg px-5 fs-6 shadow-sm">Register for ATCL</a>
                        <?php elseif ($registrationSettings['walk_in_enabled']): ?>
                            <a href="/participants/create-walkin" class="btn btn-primary btn-lg px-5 fs-6 shadow-sm">Register as Walk-in</a>
                        <?php else: ?>
                            <p class="mb-0 fw-medium">Registration is currently closed, please walk in at DTAR tomorrow 1PM.</p>
                        <?php endif; ?>
                        <div class="mt-3">
                            <a href="/participants/lookup" class="btn btn-link btn-sm text-decoration-none fw-semibold" style="color: var(--md-sys-color-primary) !important;">Already registered? Find my QR code</a>
                        </div>
                        <p class="small mt-4 mb-0" style="opacity: 0.8;">
                            Committee or facilitators:
                            <a href="/login" class="fw-semibold text-decoration-none" style="color: var(--md-sys-color-primary) !important;">Advisor / committee login</a>
                        </p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12">
                        <section class="card p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important;">
                            <div class="card-body">
                                <h2 class="h4 fw-bold mb-3 text-center" style="color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($landingSettings['section_1_title']) ?></h2>
                                <p class="text-center mb-4" style="color: var(--md-sys-color-on-surface-variant); line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($landingSettings['section_1_caption'])) ?>
                                </p>
                                <?php
                                $heroUrl = $landingUrl($landingImages['hero']['filename'] ?? null);
                                $heroAlt = (string)($landingImages['hero']['alt_text'] ?? '');
                                ?>
                                <?php if ($heroUrl !== null): ?>
                                    <div class="text-center mt-2">
                                        <img src="<?= htmlspecialchars($heroUrl) ?>" alt="<?= htmlspecialchars($heroAlt) ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 380px; border: 1px solid var(--md-sys-color-outline-variant); width: 100%; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <div class="col-md-6">
                        <section class="card h-100 p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important;">
                            <div class="card-body d-flex flex-column h-100">
                                <h2 class="h4 fw-bold mb-3" style="color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($landingSettings['section_2_title']) ?></h2>
                                <p class="mb-4 flex-grow-1" style="color: var(--md-sys-color-on-surface-variant); line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($landingSettings['section_2_caption'])) ?>
                                </p>
                                <?php
                                $f1Url = $landingUrl($landingImages['feature_1']['filename'] ?? null);
                                $f1Alt = (string)($landingImages['feature_1']['alt_text'] ?? '');
                                ?>
                                <?php if ($f1Url !== null): ?>
                                    <div class="text-center mt-auto pt-2">
                                        <img src="<?= htmlspecialchars($f1Url) ?>" alt="<?= htmlspecialchars($f1Alt) ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 280px; border: 1px solid var(--md-sys-color-outline-variant); width: 100%; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <div class="col-md-6">
                        <section class="card h-100 p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important;">
                            <div class="card-body d-flex flex-column h-100">
                                <h2 class="h4 fw-bold mb-3" style="color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($landingSettings['section_3_title']) ?></h2>
                                <p class="mb-4 flex-grow-1" style="color: var(--md-sys-color-on-surface-variant); line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($landingSettings['section_3_caption'])) ?>
                                </p>
                                <?php
                                $f2Url = $landingUrl($landingImages['feature_2']['filename'] ?? null);
                                $f2Alt = (string)($landingImages['feature_2']['alt_text'] ?? '');
                                ?>
                                <?php if ($f2Url !== null): ?>
                                    <div class="text-center mt-auto pt-2">
                                        <img src="<?= htmlspecialchars($f2Url) ?>" alt="<?= htmlspecialchars($f2Alt) ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 280px; border: 1px solid var(--md-sys-color-outline-variant); width: 100%; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <div class="col-12">
                        <section class="card p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important;">
                            <div class="card-body">
                                <h2 class="h4 fw-bold mb-3 text-center" style="color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($landingSettings['section_4_title']) ?></h2>
                                <p class="text-center mb-4" style="color: var(--md-sys-color-on-surface-variant); line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($landingSettings['section_4_caption'])) ?>
                                </p>
                                <?php if (!empty($landingSettings['section_4_url'])): ?>
                                    <div class="text-center mb-4">
                                        <a href="<?= htmlspecialchars($landingSettings['section_4_url']) ?>" class="btn btn-primary px-4 shadow-sm" target="_blank" rel="noopener">
                                            <?= htmlspecialchars($landingSettings['section_4_button_text'] ?? 'View Booklet') ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php
                                $f3Url = $landingUrl($landingImages['feature_3']['filename'] ?? null);
                                $f3Alt = (string)($landingImages['feature_3']['alt_text'] ?? '');
                                ?>
                                <?php if ($f3Url !== null): ?>
                                    <div class="text-center mt-2">
                                        <img src="<?= htmlspecialchars($f3Url) ?>" alt="<?= htmlspecialchars($f3Alt) ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 500px; border: 1px solid var(--md-sys-color-outline-variant); width: auto; max-width: 100%; object-fit: contain;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
        <footer class="landing-footer">
            Made with love by Zhen Bang
        </footer>
    </div>
<?php endif; ?>
