<?php
// Public: Claim form for a lost & found item (M3 styled)
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
            <span class="material-symbols-outlined" style="font-size: 28px; vertical-align: text-bottom;">pan_tool</span>
            Claim Item
        </h2>
        <p class="text-muted small mb-0 mt-1">Confirm this item is yours by filling in your details below.</p>
    </div>
    <a href="/lost-and-found/public" class="btn btn-outline-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">arrow_back</span> Back to Lost & Found
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Item Preview Card -->
        <div class="card mb-4 border-0" style="border-radius: 20px !important; overflow: hidden; background-color: var(--md-sys-color-surface-container-low) !important;">
            <?php if ($item['photo_filename']): ?>
                <img src="/uploads/lost_and_found/<?= htmlspecialchars($item['photo_filename']) ?>"
                     class="card-img-top" alt="<?= htmlspecialchars($item['caption']) ?>"
                     style="max-height: 300px; object-fit: contain; background: var(--md-sys-color-surface-container); padding: 12px;">
            <?php endif; ?>
            <div class="card-body" style="padding: 1.25rem;">
                <h5 class="fw-bold mb-1" style="color: var(--md-sys-color-on-surface);">
                    <span class="material-symbols-outlined" style="font-size: 22px; vertical-align: text-bottom;">inventory_2</span>
                    <?= htmlspecialchars($item['caption']) ?>
                </h5>
                <?php if ($item['description']): ?>
                    <p class="mb-0" style="color: var(--md-sys-color-on-surface-variant);"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Claim Form Card -->
        <div class="card border-0 p-4" style="border-radius: 20px !important; background-color: var(--md-sys-color-surface-container-low) !important;">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
                <span class="material-symbols-outlined" style="font-size: 22px;">edit_note</span>
                Confirm this is yours
            </h5>
            <p class="text-muted small mb-4">Please fill in your details below. The committee will verify your claim before handing the item over.</p>

            <form method="POST" action="/lost-and-found/submit-claim">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

                <div class="mb-4">
                    <label for="claimant_name" class="form-label fw-semibold d-flex align-items-center gap-1" style="color: var(--md-sys-color-on-surface);">
                        <span class="material-symbols-outlined" style="font-size: 20px;">person</span>
                        Your Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="claimant_name" name="claimant_name"
                           placeholder="Enter your full name" required maxlength="255">
                </div>

                <div class="mb-4">
                    <label for="claimant_phone" class="form-label fw-semibold d-flex align-items-center gap-1" style="color: var(--md-sys-color-on-surface);">
                        <span class="material-symbols-outlined" style="font-size: 20px;">phone</span>
                        Phone Number <span class="text-danger">*</span>
                    </label>
                    <input type="tel" class="form-control" id="claimant_phone" name="claimant_phone"
                           placeholder="e.g. 012-345 6789" required maxlength="50">
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">check</span> Submit Claim
                    </button>
                    <a href="/lost-and-found/public" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>