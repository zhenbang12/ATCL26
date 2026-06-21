<?php
// Admin: Edit Lost & Found item form (M3 styled)
// Available: $item (single lost_and_found_items row)
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
            <span class="material-symbols-outlined" style="font-size: 28px; vertical-align: text-bottom;">edit</span>
            Edit Lost & Found Item
        </h2>
        <p class="text-muted small mb-0 mt-1">Update the photo, caption, or description.</p>
    </div>
    <a href="/lost-and-found" class="btn btn-outline-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">arrow_back</span> Back to List
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
            <form method="POST" action="/lost-and-found/update" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

                <!-- Current Photo -->
                <?php if ($item['photo_filename']): ?>
                    <div class="mb-4">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1" style="color: var(--md-sys-color-on-surface);">
                            <span class="material-symbols-outlined" style="font-size: 20px;">photo_camera</span>
                            Current Photo
                        </label>
                        <div class="card p-2 d-inline-block" style="border-radius: 16px !important; max-width: 300px;">
                            <img src="/uploads/lost_and_found/<?= htmlspecialchars($item['photo_filename']) ?>"
                                 alt="Current photo" class="rounded" style="max-height: 200px; max-width: 100%; object-fit: cover;">
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_photo" id="removePhoto" value="1">
                            <label class="form-check-label small" for="removePhoto" style="color: var(--md-sys-color-on-surface-variant);">
                                Remove current photo
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- New Photo Upload -->
                <div class="mb-4">
                    <label for="photo" class="form-label fw-semibold d-flex align-items-center gap-1" style="color: var(--md-sys-color-on-surface);">
                        <span class="material-symbols-outlined" style="font-size: 20px;">add_a_photo</span>
                        <?= $item['photo_filename'] ? 'Replace Photo' : 'Photo' ?> <span class="text-muted fw-normal">(optional)</span>
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
                           value="<?= htmlspecialchars($item['caption']) ?>"
                           placeholder="e.g. Blue water bottle, White T-shirt (size M)" required maxlength="255">
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold d-flex align-items-center gap-1" style="color: var(--md-sys-color-on-surface);">
                        <span class="material-symbols-outlined" style="font-size: 20px;">notes</span>
                        Description <span class="text-muted fw-normal">(optional)</span>
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="3"
                              placeholder="e.g. Found near the main hall on Day 2, has a sticker on the side"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">save</span> Save Changes
                    </button>
                    <a href="/lost-and-found" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const photoInput = document.getElementById('photo');
const preview = document.getElementById('photoPreview');
const previewImg = document.getElementById('previewImg');

photoInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) {
        preview.style.display = 'none';
        return;
    }
    // Compress image on client side before upload
    compressImage(file, 1200, 0.8).then(function(compressedFile) {
        const dt = new DataTransfer();
        dt.items.add(compressedFile);
        photoInput.files = dt.files;
        const reader = new FileReader();
        reader.onload = function(ev) {
            previewImg.src = ev.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(compressedFile);
    });
});

function compressImage(file, maxDim, quality) {
    return new Promise(function(resolve) {
        const img = new Image();
        img.onload = function() {
            let w = img.width, h = img.height;
            if (w > maxDim || h > maxDim) {
                const ratio = Math.min(maxDim / w, maxDim / h);
                w = Math.round(w * ratio);
                h = Math.round(h * ratio);
            }
            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
            canvas.toBlob(function(finalBlob) {
                const newName = file.name.replace(/\.[^.]+$/, '.jpg');
                resolve(new File([finalBlob], newName, { type: 'image/jpeg' }));
            }, 'image/jpeg', quality);
        };
        img.src = URL.createObjectURL(file);
    });
}
</script>
