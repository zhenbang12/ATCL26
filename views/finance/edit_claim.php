<?php
// Edit claim form
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Edit Claim</h2>
    <a href="/finance/claims" class="btn btn-secondary btn-sm">Back to Claims</a>
</div>

<form method="post" action="/finance/claims/update" enctype="multipart/form-data" class="row g-3">
    <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
    
    <div class="col-md-3">
        <label class="form-label">Claimant</label>
        <input type="text" name="claimant_name" class="form-control" value="<?= htmlspecialchars($claim['claimant_name']) ?>" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Department</label>
        <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($claim['department']) ?>" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($claim['description'] ?? '') ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label">Amount (RM)</label>
        <input type="number" step="0.01" name="amount_total" class="form-control" value="<?= htmlspecialchars($claim['amount_total']) ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Receipt Image</label>
        <?php if (!empty($claim['receipt_image'])): ?>
            <div class="mb-2">
                <img src="<?= htmlspecialchars($claim['receipt_image']) ?>" 
                     alt="Current Receipt" 
                     class="img-thumbnail" 
                     style="max-width: 150px; max-height: 150px;">
                <div><small class="text-muted">Current receipt image</small></div>
            </div>
        <?php endif; ?>
        <input type="file" name="receipt_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        <small class="text-muted">Leave empty to keep current image. Max 5MB. Accepted formats: JPEG, PNG, GIF, WebP</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">Items Image</label>
        <?php if (!empty($claim['items_image'])): ?>
            <div class="mb-2">
                <img src="<?= htmlspecialchars($claim['items_image']) ?>" 
                     alt="Current Items" 
                     class="img-thumbnail" 
                     style="max-width: 150px; max-height: 150px;">
                <div><small class="text-muted">Current items image</small></div>
            </div>
        <?php endif; ?>
        <input type="file" name="items_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        <small class="text-muted">Leave empty to keep current image. Max 5MB. Accepted formats: JPEG, PNG, GIF, WebP</small>
    </div>
    <div class="col-12">
        <div class="alert alert-info">
            <strong>Status:</strong> 
            <span class="badge bg-<?= $claim['status'] === 'rejected' ? 'danger' : 'secondary' ?>">
                <?= ucfirst($claim['status']) ?>
            </span>
        </div>
    </div>
    <div class="col-12">
        <button type="submit" name="save" class="btn btn-primary">Save Changes</button>
        <?php if ($claim['status'] === 'draft'): ?>
            <button type="submit" name="save_as_draft" class="btn btn-secondary">Save as Draft</button>
            <button type="submit" name="submit_draft" class="btn btn-success">Submit</button>
        <?php elseif ($claim['status'] === 'rejected'): ?>
            <button type="submit" name="resubmit" class="btn btn-success">Save & Resubmit</button>
        <?php endif; ?>
        <a href="/finance/claims" class="btn btn-secondary">Cancel</a>
    </div>
</form>
