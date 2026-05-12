<?php
// Edit buying request form
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Edit Buying Request</h2>
    <a href="/finance/buying-requests" class="btn btn-secondary btn-sm">Back to Buying Requests</a>
</div>

<form method="post" action="/finance/buying-requests/update" enctype="multipart/form-data" class="row g-3">
    <input type="hidden" name="request_id" value="<?= $buyingRequest['id'] ?>">
    
    <div class="col-md-3">
        <label class="form-label">Requester</label>
        <input type="text" name="requester_name" class="form-control" value="<?= htmlspecialchars($buyingRequest['requester_name']) ?>" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Department</label>
        <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($buyingRequest['department']) ?>" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Quantity</label>
        <input type="number" name="quantity" class="form-control" value="<?= htmlspecialchars($buyingRequest['quantity']) ?>" min="1" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Estimated Cost (RM)</label>
        <input type="number" step="0.01" name="estimated_cost" class="form-control" value="<?= htmlspecialchars($buyingRequest['estimated_cost']) ?>" required>
    </div>
    <div class="col-md-12">
        <label class="form-label">Item Description</label>
        <textarea name="item_description" class="form-control" rows="2" required><?= htmlspecialchars($buyingRequest['item_description']) ?></textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Justification</label>
        <textarea name="justification" class="form-control" rows="2" placeholder="Why is this purchase needed?"><?= htmlspecialchars($buyingRequest['justification'] ?? '') ?></textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Vendor Preference (Optional)</label>
        <input type="text" name="vendor_preference" class="form-control" value="<?= htmlspecialchars($buyingRequest['vendor_preference'] ?? '') ?>" placeholder="Preferred vendor name">
    </div>
    <div class="col-md-12">
        <label class="form-label">Reference Image</label>
        <?php if (!empty($buyingRequest['reference_image'])): ?>
            <div class="mb-2">
                <img src="<?= htmlspecialchars($buyingRequest['reference_image']) ?>" 
                     alt="Current Reference" 
                     class="img-thumbnail" 
                     style="max-width: 150px; max-height: 150px;">
                <div><small class="text-muted">Current reference image</small></div>
            </div>
        <?php endif; ?>
        <input type="file" name="reference_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        <small class="text-muted">Leave empty to keep current image. Max 5MB. Accepted formats: JPEG, PNG, GIF, WebP</small>
    </div>
    <div class="col-12">
        <div class="alert alert-info">
            <strong>Status:</strong> 
            <span class="badge bg-<?= $buyingRequest['status'] === 'rejected' ? 'danger' : 'secondary' ?>">
                <?= ucfirst($buyingRequest['status']) ?>
            </span>
        </div>
    </div>
    <div class="col-12">
        <button type="submit" name="save" class="btn btn-primary">Save Changes</button>
        <?php if ($buyingRequest['status'] === 'draft'): ?>
            <button type="submit" name="save_as_draft" class="btn btn-secondary">Save as Draft</button>
            <button type="submit" name="submit_draft" class="btn btn-success">Submit</button>
        <?php elseif ($buyingRequest['status'] === 'rejected'): ?>
            <button type="submit" name="resubmit" class="btn btn-success">Save & Resubmit</button>
        <?php endif; ?>
        <a href="/finance/buying-requests" class="btn btn-secondary">Cancel</a>
    </div>
</form>
