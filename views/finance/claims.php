<?php
// Claims listing with simple create form
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Claims</h2>
</div>

<form method="post" action="/finance/claims/store" enctype="multipart/form-data" class="row g-3 mb-4">
    <div class="col-md-3">
        <label class="form-label">Claimant</label>
        <input type="text" name="claimant_name" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Department</label>
        <input type="text" name="department" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label">Amount</label>
        <input type="number" step="0.01" name="amount_total" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Receipt Image</label>
        <input type="file" name="receipt_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        <small class="text-muted">Max 5MB. Accepted formats: JPEG, PNG, GIF, WebP</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">Items Image</label>
        <input type="file" name="items_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        <small class="text-muted">Max 5MB. Accepted formats: JPEG, PNG, GIF, WebP</small>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary btn-sm">Submit claim</button>
    </div>
</form>

<table class="table table-sm table-striped">
    <thead>
    <tr>
        <th>Claimant</th>
        <th>Dept</th>
        <th>Description</th>
        <th>Receipt</th>
        <th>Items</th>
        <th>Status</th>
        <th class="text-end">Amount</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($claims as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['claimant_name']) ?></td>
            <td><?= htmlspecialchars($c['department']) ?></td>
            <td><?= htmlspecialchars($c['description'] ?? '') ?></td>
            <td>
                <?php if (!empty($c['receipt_image'])): ?>
                    <img src="<?= htmlspecialchars($c['receipt_image']) ?>" 
                         alt="Receipt" 
                         class="img-thumbnail" 
                         style="max-width: 100px; max-height: 100px; cursor: pointer; object-fit: cover;"
                         onclick="showImageModal('<?= htmlspecialchars($c['receipt_image']) ?>', 'Receipt')">
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($c['items_image'])): ?>
                    <img src="<?= htmlspecialchars($c['items_image']) ?>" 
                         alt="Items" 
                         class="img-thumbnail" 
                         style="max-width: 100px; max-height: 100px; cursor: pointer; object-fit: cover;"
                         onclick="showImageModal('<?= htmlspecialchars($c['items_image']) ?>', 'Items')">
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($c['status']) ?></td>
            <td class="text-end"><?= number_format((float)$c['amount_total'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="" class="img-fluid" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a id="modalImageLink" href="" target="_blank" class="btn btn-primary">Open in New Tab</a>
            </div>
        </div>
    </div>
</div>

<script>
function showImageModal(imageSrc, title) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    document.getElementById('imageModalLabel').textContent = title;
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('modalImage').alt = title;
    document.getElementById('modalImageLink').href = imageSrc;
    modal.show();
}
</script>
