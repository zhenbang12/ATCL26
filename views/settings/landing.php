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
    if (!picker || !hex) {
        return;
    }

    picker.addEventListener('input', function () {
        hex.value = picker.value;
    });

    hex.addEventListener('input', function () {
        if (/^#[a-fA-F0-9]{6}$/.test(hex.value)) {
            picker.value = hex.value;
        }
    });
});
</script>
