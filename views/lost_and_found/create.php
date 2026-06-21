<?php
// Admin: Add Lost & Found item form (M3 styled)
?>

<?php if (!empty($_SESSION['lf_message'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['lf_message_type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['lf_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['lf_message'], $_SESSION['lf_message_type']); ?>
<?php endif; ?>

<!-- Title and Back -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">
            <span class="material-symbols-outlined" style="font-size: 28px; vertical-align: text-bottom;">add_circle</span>
            Add Lost & Found Item
        </h2>
        <p class="text-muted small mb-0 mt-1">Upload a photo and describe the found item.</p>
    </div>
    <a href="/lost-and-found" class="btn btn-outline-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">arrow_back</span> Back to List
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
            <form method="POST" action="/lost-and-found/store" enctype="multipart/form-data">
                <!-- Photo Upload -->
                <div class="mb-4">
                    <label for="photo" class="form-label fw-semibold d-flex align-items-center gap-1" style="color: var(--md-sys-color-on-surface);">
                        <span class="material-symbols-outlined" style="font-size: 20px;">photo_camera</span>
                        Photo <span class="text-muted fw-normal">(optional)</span>
                    </label>
                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                    <div class="form-text" style="color: var(--md-sys-color-on-surface-variant);">Accepted formats: JPG, PNG, GIF, WEBP</div>
                    <div id="photoPreview" class="mt-3" style="display: none;">
                        <div class="card p-2 d-inline-block" style="border-radius: 16px !important; max-width: 300px;">
                            <img id="previewImg" src="" alt="Preview" class="rounded" style="max-height: 200px; max-width: 100%; object-fit: cover;">
                        </div>
                    </div>
                </div>

                <!-- Caption -->
                <div class="mb-4">
                    <label for="caption" class="form-label fw-semibold d-flex align-items-center gap-1" style="color: var(--md-sys-color-on-surface);">
                        <span class="material-symbols-outlined" style="font-size: 20px;">edit</span>
                        Caption <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="caption" name="caption"
                           placeholder="e.g. Blue water bottle, White T-shirt (size M)" required maxlength="255">
                    <div class="form-text" style="color: var(--md-sys-color-on-surface-variant);">A short title to identify the item</div>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold d-flex align-items-center gap-1" style="color: var(--md-sys-color-on-surface);">
                        <span class="material-symbols-outlined" style="font-size: 20px;">notes</span>
                        Description <span class="text-muted fw-normal">(optional)</span>
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="3"
                              placeholder="e.g. Found near the main hall on Day 2, has a sticker on the side"></textarea>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">upload</span> Add Item
                    </button>
                    <a href="/lost-and-found" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('photoPreview');
    const img = document.getElementById('previewImg');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            img.src = ev.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});
</script>