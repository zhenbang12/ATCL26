<?php
// Admin: public landing page settings.
use App\Controller\SettingsController;

$slotLabels = SettingsController::landingSlotLabels();
$defaults = [];
foreach (array_keys($slotLabels) as $slot) {
    $defaults[$slot] = ['filename' => null, 'alt_text' => ''];
}
$rows = array_merge($defaults, $rows ?? []);

$message = $_SESSION['settings_message'] ?? null;
$messageType = $_SESSION['settings_message_type'] ?? 'info';
if (isset($_SESSION['settings_message'])) {
    unset($_SESSION['settings_message'], $_SESSION['settings_message_type']);
}

$logoNumbers = [1, 2, 3];
$contentSections = [
    [
        'number' => 1,
        'slot' => 'hero',
        'title_name' => 'section_1_title',
        'caption_name' => 'section_1_caption',
        'title_default' => 'What is it?',
        'image_label' => 'Image 1',
        'help' => 'Shown after the first title and caption.',
    ],
    [
        'number' => 2,
        'slot' => 'feature_1',
        'title_name' => 'section_2_title',
        'caption_name' => 'section_2_caption',
        'title_default' => 'What to expect',
        'image_label' => 'Image 2',
        'help' => 'Shown after the second title and caption.',
    ],
    [
        'number' => 3,
        'slot' => 'feature_2',
        'title_name' => 'section_3_title',
        'caption_name' => 'section_3_caption',
        'title_default' => 'Before you arrive',
        'image_label' => 'Image 3',
        'help' => 'Shown after the third title and caption.',
    ],
    [
        'number' => 4,
        'slot' => 'feature_3',
        'title_name' => 'section_4_title',
        'caption_name' => 'section_4_caption',
        'title_default' => 'Join the Team',
        'image_label' => 'Image 4',
        'help' => 'Shown at the bottom of the landing page.',
    ],
];
?>

<h2>Landing page settings</h2>
<p class="text-muted">
    Edit the public home page in the same order it appears to participants. Upload JPEG, PNG, WebP, or GIF up to 5 MB each.
</p>

<?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="post" action="/settings/landing/save" enctype="multipart/form-data" class="mt-3">
    <div class="card mb-4 border-primary">
        <div class="card-body">
            <h5 class="card-title">Page Background</h5>
            <p class="small text-muted mb-3">This color is applied behind the whole public landing page.</p>

            <div class="row g-3 align-items-end">
                <div class="col-sm-auto">
                    <label class="form-label" for="background_color_picker">Background Color</label>
                    <input type="color" id="background_color_picker" class="form-control form-control-color" value="<?= htmlspecialchars($settings['background_color'] ?? '#ffffff') ?>" style="height: 50px;">
                </div>
                <div class="col-sm-4">
                    <label class="form-label" for="background_color">Hex code</label>
                    <input type="text" name="background_color" id="background_color" class="form-control" maxlength="7" pattern="#[a-fA-F0-9]{6}" value="<?= htmlspecialchars($settings['background_color'] ?? '#ffffff') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Top Logos</h5>
            <p class="small text-muted mb-3">These appear above the main title on the public landing page.</p>

            <div class="row g-4">
                <?php foreach ($logoNumbers as $logoNumber): ?>
                    <?php
                    $fileKey = 'logo_' . $logoNumber . '_filename';
                    $altKey = 'logo_' . $logoNumber . '_alt_text';
                    $fileInput = 'logo_' . $logoNumber . '_file';
                    $altInput = 'logo_' . $logoNumber . '_alt';
                    $removeInput = 'remove_logo_' . $logoNumber;
                    ?>
                    <div class="col-md-4">
                        <h6>Logo <?= $logoNumber ?></h6>

                        <?php if (!empty($settings[$fileKey])): ?>
                            <div class="mb-3">
                                <img src="/uploads/landing/<?= htmlspecialchars($settings[$fileKey]) ?>" alt="" class="img-fluid rounded border" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label" for="<?= htmlspecialchars($fileInput) ?>">Logo <?= $logoNumber ?> file</label>
                            <input type="file" name="<?= htmlspecialchars($fileInput) ?>" id="<?= htmlspecialchars($fileInput) ?>" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="<?= htmlspecialchars($altInput) ?>">Logo <?= $logoNumber ?> alt text</label>
                            <input type="text" name="<?= htmlspecialchars($altInput) ?>" id="<?= htmlspecialchars($altInput) ?>" class="form-control" maxlength="500" value="<?= htmlspecialchars($settings[$altKey] ?? '') ?>">
                        </div>

                        <?php if (!empty($settings[$fileKey])): ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="<?= htmlspecialchars($removeInput) ?>" value="1" id="<?= htmlspecialchars($removeInput) ?>">
                                <label class="form-check-label text-danger" for="<?= htmlspecialchars($removeInput) ?>">Remove logo <?= $logoNumber ?></label>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Main Title Area</h5>
            <p class="small text-muted mb-3">This appears before Image 1 and the numbered content sections.</p>

            <div class="mb-3">
                <label class="form-label" for="main_title">Main Title</label>
                <input type="text" name="main_title" id="main_title" class="form-control" maxlength="255" value="<?= htmlspecialchars($settings['main_title'] ?? 'Welcome to Adjustment To Campus Life') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label" for="main_caption">Main Caption</label>
                <textarea name="main_caption" id="main_caption" class="form-control" rows="3" maxlength="1000"><?= htmlspecialchars($settings['main_caption'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Registration Call-to-Action Card Settings -->
    <div class="card mb-4 border-0 shadow-sm rounded-4" style="background-color: var(--md-sys-color-surface-container-low, #f7f2fa);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined text-primary" style="font-size: 26px;">campaign</span>
                    <h5 class="card-title fw-bold mb-0">Registration Call-to-Action Card</h5>
                </div>
                <span class="badge rounded-pill text-bg-primary px-3 py-1">Public Landing</span>
            </div>
            <p class="small text-muted mb-4">
                This configures the join/registration card located directly below the main title on the public home page.
            </p>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="cta_title">Card Headline</label>
                    <input type="text" name="cta_title" id="cta_title" class="form-control" maxlength="255" value="<?= htmlspecialchars($settings['cta_title'] ?? 'Ready to join?') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="cta_caption">Card Subtitle / Description</label>
                    <input type="text" name="cta_caption" id="cta_caption" class="form-control" maxlength="500" value="<?= htmlspecialchars($settings['cta_caption'] ?? 'Takes only a minute. You will need your student ID and TAR UMT student email.') ?>">
                </div>
            </div>

            <label class="form-label fw-bold d-block mb-2">Registration Status &amp; Action Options:</label>
            
            <div class="d-flex flex-column gap-2 mb-3">
                <!-- Option 1: Registration closed -->
                <div class="form-check p-3 rounded-3 border bg-white shadow-sm">
                    <input class="form-check-input ms-0 me-3" type="radio" name="cta_mode" id="ctaModeClosed" value="closed" <?= ($settings['cta_mode'] ?? 'closed_walkin') === 'closed' ? 'checked' : '' ?>>
                    <label class="form-check-label w-100" for="ctaModeClosed" style="cursor: pointer;">
                        <div class="fw-bold text-danger d-flex align-items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size: 18px;">block</span>
                            1. Registration closed
                        </div>
                        <div class="text-muted small mt-1">Hides the register button and indicates that registration is closed.</div>
                    </label>
                </div>

                <!-- Option 2: Registration open -->
                <div class="form-check p-3 rounded-3 border bg-white shadow-sm">
                    <input class="form-check-input ms-0 me-3" type="radio" name="cta_mode" id="ctaModeOpen" value="open" <?= ($settings['cta_mode'] ?? 'closed_walkin') === 'open' ? 'checked' : '' ?>>
                    <label class="form-check-label w-100" for="ctaModeOpen" style="cursor: pointer;">
                        <div class="fw-bold text-success d-flex align-items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size: 18px;">how_to_reg</span>
                            2. Registration open
                        </div>
                        <div class="text-muted small mt-1">Displays the active "Register for ATCL" button for students to register online.</div>
                    </label>
                </div>

                <!-- Option 3: Registration closed, walk in to XXX at XXX -->
                <div class="form-check p-3 rounded-3 border bg-white shadow-sm">
                    <input class="form-check-input ms-0 me-3" type="radio" name="cta_mode" id="ctaModeClosedWalkin" value="closed_walkin" <?= ($settings['cta_mode'] ?? 'closed_walkin') === 'closed_walkin' ? 'checked' : '' ?>>
                    <label class="form-check-label w-100" for="ctaModeClosedWalkin" style="cursor: pointer;">
                        <div class="fw-bold text-primary d-flex align-items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size: 18px;">directions_walk</span>
                            3. Registration closed, walk in to XXX at XXX
                        </div>
                        <div class="text-muted small mt-1">Closes online registration and instructs participants to walk in at a specific venue and time.</div>
                    </label>

                    <!-- Dynamic inputs for Location and Time when Option 3 is selected -->
                    <div id="walkinDetailsBox" class="mt-3 pt-3 border-top" style="<?= ($settings['cta_mode'] ?? 'closed_walkin') === 'closed_walkin' ? '' : 'display: none;' ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold" for="walkin_location">Walk-in Venue / Location (XXX)</label>
                                <input type="text" name="walkin_location" id="walkin_location" class="form-control" placeholder="e.g. DTAR" value="<?= htmlspecialchars($settings['walkin_location'] ?? 'DTAR') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold" for="walkin_time">Walk-in Date &amp; Time (at XXX)</label>
                                <input type="text" name="walkin_time" id="walkin_time" class="form-control" placeholder="e.g. tomorrow 1PM" value="<?= htmlspecialchars($settings['walkin_time'] ?? 'tomorrow 1PM') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Notice Preview & Custom Override -->
            <div class="card border-0 bg-light p-3 rounded-3 mt-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-secondary">Notice Display Preview:</span>
                </div>
                <div class="alert alert-secondary py-2 px-3 mb-3 small fw-medium" id="liveNoticePreview">
                    Registration is currently closed, please walk in at DTAR tomorrow 1PM.
                </div>

                <div class="mb-0">
                    <label class="form-label small text-muted mb-1" for="cta_custom_notice">
                        Custom Notice Override (optional):
                    </label>
                    <input type="text" name="cta_custom_notice" id="cta_custom_notice" class="form-control form-control-sm" placeholder="Leave empty to use the auto-generated notice above" value="<?= htmlspecialchars($settings['cta_custom_notice'] ?? '') ?>">
                    <div class="form-text small" style="font-size: 0.72rem;">If entered, this exact wording will be shown when registration is closed instead of the standard text.</div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($contentSections as $section): ?>
        <?php
        $slot = $section['slot'];
        $row = $rows[$slot] ?? ['filename' => null, 'alt_text' => ''];
        $fn = $row['filename'] ?? null;
        ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Section <?= (int)$section['number'] ?></h5>
                <p class="small text-muted mb-3"><?= htmlspecialchars($section['help']) ?></p>

                <div class="mb-3">
                    <label class="form-label" for="<?= htmlspecialchars($section['title_name']) ?>">Title <?= (int)$section['number'] ?></label>
                    <input type="text" name="<?= htmlspecialchars($section['title_name']) ?>" id="<?= htmlspecialchars($section['title_name']) ?>" class="form-control" maxlength="255" value="<?= htmlspecialchars($settings[$section['title_name']] ?? $section['title_default']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="<?= htmlspecialchars($section['caption_name']) ?>">Caption <?= (int)$section['number'] ?></label>
                    <textarea name="<?= htmlspecialchars($section['caption_name']) ?>" id="<?= htmlspecialchars($section['caption_name']) ?>" class="form-control" rows="4" maxlength="2000"><?= htmlspecialchars($settings[$section['caption_name']] ?? '') ?></textarea>
                </div>

                <?php if ((int)$section['number'] === 4): ?>
                    <div class="row g-3">
                        <div class="col-md-8 mb-3">
                            <label class="form-label" for="section_4_url">Button Action URL</label>
                            <input type="url" name="section_4_url" id="section_4_url" class="form-control" maxlength="1024" value="<?= htmlspecialchars($settings['section_4_url'] ?? '') ?>" placeholder="https://example.com/brochure">
                            <div class="form-text">Optional. Enter a URL to make this section show a call-to-action button.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="section_4_button_text">Button Text</label>
                            <input type="text" name="section_4_button_text" id="section_4_button_text" class="form-control" maxlength="255" value="<?= htmlspecialchars($settings['section_4_button_text'] ?? 'View Booklet') ?>" placeholder="View Booklet">
                        </div>
                    </div>
                <?php endif; ?>

                <hr class="my-4">

                <h6 class="mb-3"><?= htmlspecialchars($section['image_label']) ?> Settings</h6>

                <?php if (!empty($fn)): ?>
                    <div class="mb-3">
                        <img src="/uploads/landing/<?= htmlspecialchars($fn) ?>" alt="" class="img-fluid rounded border" style="max-height: 220px;">
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label" for="file_<?= htmlspecialchars($slot) ?>"><?= htmlspecialchars($section['image_label']) ?> file</label>
                    <input type="file" name="file_<?= htmlspecialchars($slot) ?>" id="file_<?= htmlspecialchars($slot) ?>" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="alt_<?= htmlspecialchars($slot) ?>"><?= htmlspecialchars($section['image_label']) ?> alt text</label>
                    <input type="text" name="alt_<?= htmlspecialchars($slot) ?>" id="alt_<?= htmlspecialchars($slot) ?>" class="form-control" maxlength="500" value="<?= htmlspecialchars($row['alt_text'] ?? '') ?>">
                </div>

                <?php if (!empty($fn)): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_<?= htmlspecialchars($slot) ?>" value="1" id="remove_<?= htmlspecialchars($slot) ?>">
                        <label class="form-check-label text-danger" for="remove_<?= htmlspecialchars($slot) ?>">Remove <?= htmlspecialchars(strtolower($section['image_label'])) ?></label>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">Save</button>
    <a href="/public" class="btn btn-outline-secondary ms-2" target="_blank" rel="noopener">Preview home page</a>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var picker = document.getElementById('background_color_picker');
    var hex = document.getElementById('background_color');
    if (picker && hex) {
        picker.addEventListener('input', function () {
            hex.value = picker.value;
        });

        hex.addEventListener('input', function () {
            if (/^#[a-fA-F0-9]{6}$/.test(hex.value)) {
                picker.value = hex.value;
            }
        });
    }

    // CTA Registration Mode Interactivity
    var ctaModeRadios = document.querySelectorAll('input[name="cta_mode"]');
    var walkinBox = document.getElementById('walkinDetailsBox');
    var walkinLocInput = document.getElementById('walkin_location');
    var walkinTimeInput = document.getElementById('walkin_time');
    var customNoticeInput = document.getElementById('cta_custom_notice');
    var noticePreview = document.getElementById('liveNoticePreview');

    function updateCtaUI() {
        var selectedMode = 'closed_walkin';
        ctaModeRadios.forEach(function (radio) {
            if (radio.checked) selectedMode = radio.value;
        });

        if (walkinBox) {
            walkinBox.style.display = selectedMode === 'closed_walkin' ? 'block' : 'none';
        }

        if (noticePreview) {
            var custom = (customNoticeInput && customNoticeInput.value.trim()) || '';
            if (selectedMode === 'open') {
                noticePreview.className = 'alert alert-success py-2 px-3 mb-3 small fw-medium';
                noticePreview.innerHTML = '<span class="material-symbols-outlined align-middle me-1" style="font-size: 18px;">check_circle</span> Registration is OPEN: "Register for ATCL" button will be displayed.';
            } else if (selectedMode === 'closed') {
                noticePreview.className = 'alert alert-danger py-2 px-3 mb-3 small fw-medium';
                noticePreview.textContent = custom || 'Registration is currently closed.';
            } else {
                noticePreview.className = 'alert alert-info py-2 px-3 mb-3 small fw-medium';
                var loc = (walkinLocInput && walkinLocInput.value.trim()) || 'DTAR';
                var time = (walkinTimeInput && walkinTimeInput.value.trim()) || 'tomorrow 1PM';
                noticePreview.textContent = custom || ('Registration is currently closed, please walk in at ' + loc + ' ' + time + '.');
            }
        }
    }

    ctaModeRadios.forEach(function (r) {
        r.addEventListener('change', updateCtaUI);
    });
    if (walkinLocInput) walkinLocInput.addEventListener('input', updateCtaUI);
    if (walkinTimeInput) walkinTimeInput.addEventListener('input', updateCtaUI);
    if (customNoticeInput) customNoticeInput.addEventListener('input', updateCtaUI);

    updateCtaUI();
});
</script>
