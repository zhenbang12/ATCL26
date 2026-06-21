<?php
// Public: Lost & Found browsing page (M3 styled)
// Available: $items (array of unclaimed lost_and_found_items rows)
?>

<?php if (!empty($_SESSION['lf_message'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['lf_message_type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['lf_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['lf_message'], $_SESSION['lf_message_type']); ?>
<?php endif; ?>

<!-- Title -->
<div class="text-center mb-4">
    <h2 class="fw-bold d-flex align-items-center justify-content-center gap-2" style="color: var(--md-sys-color-on-surface);">
        <span class="material-symbols-outlined" style="font-size: 32px;">search_check_2</span>
        Lost & Found
    </h2>
    <p class="text-muted" style="max-width: 500px; margin: 0 auto;">Lost something? Browse the items below and claim what belongs to you.</p>
</div>

<?php if (empty($items)): ?>
    <div class="card p-5 text-center border-0 mx-auto" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important; max-width: 500px;">
        <span class="material-symbols-outlined" style="font-size: 64px; color: var(--md-sys-color-success);">check_circle</span>
        <h5 class="mt-2 fw-bold" style="color: var(--md-sys-color-on-surface);">No Unclaimed Items</h5>
        <p class="text-muted mb-0">All items have been claimed, or none have been added yet. Check back later or contact the committee.</p>
    </div>
<?php else: ?>
    <div class="row g-3 justify-content-center">
        <?php foreach ($items as $item): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100" style="border-radius: 20px !important; overflow: hidden;">
                    <?php if ($item['photo_filename']): ?>
                        <img src="/uploads/lost_and_found/<?= htmlspecialchars($item['photo_filename']) ?>"
                             class="card-img-top" alt="<?= htmlspecialchars($item['caption']) ?>"
                             style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center" style="height: 180px; background-color: var(--md-sys-color-surface-container);">
                            <span class="material-symbols-outlined" style="font-size: 56px; color: var(--md-sys-color-outline-variant);">image_not_supported</span>
                        </div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column" style="padding: 1rem 1.25rem;">
                        <h6 class="card-title fw-bold mb-1" style="color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($item['caption']) ?></h6>
                        <?php if ($item['description']): ?>
                            <p class="card-text small mb-0" style="color: var(--md-sys-color-on-surface-variant);"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                        <?php endif; ?>
                    </div>

                    <div style="padding: 0 1.25rem 1rem;">
                        <a href="/lost-and-found/claim?id=<?= (int)$item['id'] ?>" class="btn btn-primary w-100">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">pan_tool</span> This is mine!
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>