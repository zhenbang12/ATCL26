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
            background-color: <?= htmlspecialchars($landingSettings['background_color']) ?> !important;
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
        .navbar {
            background-color: rgba(0, 0, 0, 0.8) !important;
            backdrop-filter: blur(10px);
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
            padding: 1.5rem 1rem 0;
            text-align: center;
            color: rgba(33, 37, 41, 0.7);
            font-size: 0.95rem;
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
                    <h1 class="display-6 fw-semibold mb-3"><?= htmlspecialchars($landingSettings['main_title']) ?></h1>
                    <p class="lead text-muted mb-0 mx-auto" style="max-width: 36rem;">
                        <?= htmlspecialchars($landingSettings['main_caption']) ?>
                    </p>
                </header>

                <section class="mb-5">
                    <h2 class="h5 fw-semibold mb-3 text-center"><?= htmlspecialchars($landingSettings['section_1_title']) ?></h2>
                    <p class="text-muted mb-4 text-center">
                        <?= nl2br(htmlspecialchars($landingSettings['section_1_caption'])) ?>
                    </p>
                    <?php
                    $heroUrl = $landingUrl($landingImages['hero']['filename'] ?? null);
                    $heroAlt = (string)($landingImages['hero']['alt_text'] ?? '');
                    ?>
                    <?php if ($heroUrl !== null): ?>
                        <figure class="text-center">
                            <img src="<?= htmlspecialchars($heroUrl) ?>" alt="<?= htmlspecialchars($heroAlt) ?>" class="img-fluid rounded shadow-sm" style="max-height: 360px;">
                        </figure>
                    <?php endif; ?>
                </section>

                <section class="mb-5">
                    <h2 class="h5 fw-semibold mb-3 text-center"><?= htmlspecialchars($landingSettings['section_2_title']) ?></h2>
                    <p class="text-muted mb-4 text-center">
                        <?= nl2br(htmlspecialchars($landingSettings['section_2_caption'])) ?>
                    </p>
                    <?php
                    $f1Url = $landingUrl($landingImages['feature_1']['filename'] ?? null);
                    $f1Alt = (string)($landingImages['feature_1']['alt_text'] ?? '');
                    ?>
                    <?php if ($f1Url !== null): ?>
                        <figure class="text-center">
                            <img src="<?= htmlspecialchars($f1Url) ?>" alt="<?= htmlspecialchars($f1Alt) ?>" class="img-fluid rounded shadow-sm" style="max-height: 360px;">
                        </figure>
                    <?php endif; ?>
                </section>

                <section class="mb-5">
                    <h2 class="h5 fw-semibold mb-3 text-center"><?= htmlspecialchars($landingSettings['section_3_title']) ?></h2>
                    <p class="text-muted mb-4 text-center">
                        <?= nl2br(htmlspecialchars($landingSettings['section_3_caption'])) ?>
                    </p>
                    <?php
                    $f2Url = $landingUrl($landingImages['feature_2']['filename'] ?? null);
                    $f2Alt = (string)($landingImages['feature_2']['alt_text'] ?? '');
                    ?>
                    <?php if ($f2Url !== null): ?>
                        <figure class="text-center">
                            <img src="<?= htmlspecialchars($f2Url) ?>" alt="<?= htmlspecialchars($f2Alt) ?>" class="img-fluid rounded shadow-sm" style="max-height: 360px;">
                        </figure>
                    <?php endif; ?>
                </section>

                <div class="card border-0 shadow-sm mt-5" style="background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                    <div class="card-body text-center py-5 px-4">
                        <h2 class="h5 fw-semibold mb-2">Ready to join?</h2>
                        <p class="text-muted small mb-4">
                            Takes only a minute. You will need your student ID and TAR UMT student email.
                        </p>
                        <?php if ($registrationSettings['pre_register_enabled']): ?>
                            <a href="/participants/create" class="btn btn-primary btn-lg px-5">Register for ATCL</a>
                        <?php else: ?>
                            <p class="text-muted mb-0">Pre-registration is currently closed.</p>
                        <?php endif; ?>
                        <div class="mt-3">
                            <a href="/participants/lookup" class="link-secondary small">Already registered? Find my QR code</a>
                        </div>
                        <p class="text-muted small mt-4 mb-0">
                            Committee or facilitators:
                            <a href="/login" class="link-secondary">Advisor / committee login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <footer class="landing-footer">
            Made with love by Zhen Bang
        </footer>
    </div>
<?php endif; ?>
