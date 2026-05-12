<?php
// Claims listing with simple create form
use App\Core\Auth;
use App\Core\Container;
$userRole = Auth::role();
$canApprove = in_array($userRole, ['advisor', 'committee', 'treasurer']);
$currentFilter = $_GET['filter'] ?? 'all';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Claims</h2>
</div>

<!-- Tabs -->
<?php
// Determine default tab: drafts if filter=draft, view if other filter, submit otherwise
$defaultTab = 'submit';
if (isset($_GET['filter'])) {
    $defaultTab = ($_GET['filter'] === 'draft') ? 'drafts' : 'view';
}
?>
<ul class="nav nav-tabs mb-4" id="claimsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $defaultTab === 'submit' ? 'active' : '' ?>" id="submit-tab" data-bs-toggle="tab" data-bs-target="#submit" type="button" role="tab" aria-controls="submit" aria-selected="<?= $defaultTab === 'submit' ? 'true' : 'false' ?>">Submit Claim</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $defaultTab === 'drafts' ? 'active' : '' ?>" id="drafts-tab" data-bs-toggle="tab" data-bs-target="#drafts" type="button" role="tab" aria-controls="drafts" aria-selected="<?= $defaultTab === 'drafts' ? 'true' : 'false' ?>">Drafts</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $defaultTab === 'view' ? 'active' : '' ?>" id="view-tab" data-bs-toggle="tab" data-bs-target="#view" type="button" role="tab" aria-controls="view" aria-selected="<?= $defaultTab === 'view' ? 'true' : 'false' ?>">View Claims</button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="claimsTabsContent">
    <!-- Submit Claim Tab -->
    <div class="tab-pane fade <?= $defaultTab === 'submit' ? 'show active' : '' ?>" id="submit" role="tabpanel" aria-labelledby="submit-tab">
        <form method="post" action="/finance/claims/store" enctype="multipart/form-data" class="row g-3">
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
                <label class="form-label">Amount (RM)</label>
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
                <button type="submit" name="submit" class="btn btn-primary">Submit claim</button>
                <button type="submit" name="save_as_draft" class="btn btn-secondary">Save as Draft</button>
            </div>
        </form>
    </div>

    <!-- Drafts Tab -->
    <div class="tab-pane fade <?= $defaultTab === 'drafts' ? 'show active' : '' ?>" id="drafts" role="tabpanel" aria-labelledby="drafts-tab">
        <?php
        // Get only drafts for this tab
        $db = Container::get('db');
        $stmt = $db->query("SELECT id, claimant_name, department, description, status, amount_total, receipt_image, items_image, created_at FROM claims WHERE status = 'draft' ORDER BY created_at DESC");
        $draftClaims = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        ?>
        <table id="drafts-claims-table" class="table table-sm table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Claimant</th>
                <th>Dept</th>
                <th>Description</th>
                <th>Receipt</th>
                <th>Items</th>
                <th>Status</th>
                <th class="text-end">Amount (RM)</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php $counter = 1; foreach ($draftClaims as $c): ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td><?= !empty($c['created_at']) ? date('Y-m-d H:i', strtotime($c['created_at'])) : '-' ?></td>
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
                    <td>
                        <span class="badge bg-warning">Draft</span>
                    </td>
                    <td class="text-end">RM <?= number_format((float)$c['amount_total'], 2) ?></td>
                    <td>
                        <a href="/finance/claims/edit?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- View Claims Tab -->
    <div class="tab-pane fade <?= $defaultTab === 'view' ? 'show active' : '' ?>" id="view" role="tabpanel" aria-labelledby="view-tab">
        <div class="mb-3">
            <div class="btn-group" role="group">
                <a href="/finance/claims?filter=all" class="btn btn-sm <?= $currentFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    All
                </a>
                <a href="/finance/claims?filter=approved" class="btn btn-sm <?= $currentFilter === 'approved' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Approved
                </a>
                <a href="/finance/claims?filter=rejected" class="btn btn-sm <?= $currentFilter === 'rejected' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Rejected
                </a>
            </div>
        </div>
        <table id="claims-table" class="table table-sm table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Claimant</th>
                <th>Dept</th>
                <th>Description</th>
                <th>Receipt</th>
                <th>Items</th>
                <th>Status</th>
                <th class="text-end">Amount (RM)</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php $counter = 1; foreach ($claims as $c): ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td><?= !empty($c['created_at']) ? date('Y-m-d H:i', strtotime($c['created_at'])) : '-' ?></td>
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
                    <td>
                        <?php
                $statusColors = [
                    'draft' => 'warning',
                    'submitted' => 'secondary',
                    'verified' => 'info',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'paid' => 'primary'
                ];
                        $color = $statusColors[$c['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>"><?= ucfirst($c['status']) ?></span>
                    </td>
                    <td class="text-end">RM <?= number_format((float)$c['amount_total'], 2) ?></td>
                    <td>
                        <div class="btn-group" role="group">
                            <?php if ($c['status'] === 'draft'): ?>
                                <a href="/finance/claims/edit?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <?php elseif ($c['status'] === 'rejected'): ?>
                                <a href="/finance/claims/edit?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm">Edit & Resubmit</a>
                            <?php elseif ($canApprove && ($c['status'] === 'submitted' || $c['status'] === 'verified')): ?>
                                <form method="post" action="/finance/claims/approve" style="display: inline;" onsubmit="return confirm('Approve this claim?');">
                                    <input type="hidden" name="claim_id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="post" action="/finance/claims/reject" style="display: inline;" onsubmit="return confirm('Reject this claim?');">
                                    <input type="hidden" name="claim_id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

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

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
function showImageModal(imageSrc, title) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    document.getElementById('imageModalLabel').textContent = title;
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('modalImage').alt = title;
    document.getElementById('modalImageLink').href = imageSrc;
    modal.show();
}

$(document).ready(function() {
    $('#claims-table').DataTable({
        pageLength: 25,
        order: [[1, 'desc']], // Sort by Date column (newest first)
        language: {
            search: "Search claims:",
            lengthMenu: "Show _MENU_ claims per page",
            info: "Showing _START_ to _END_ of _TOTAL_ claims",
            infoEmpty: "No claims found",
            infoFiltered: "(filtered from _MAX_ total claims)"
        },
        columnDefs: [
            { orderable: false, targets: [4, 5, 6, 9] } // Disable sorting on Description, Receipt, Items, Actions columns
        ]
    });
    
    $('#drafts-claims-table').DataTable({
        pageLength: 25,
        order: [[1, 'desc']], // Sort by Date column (newest first)
        language: {
            search: "Search drafts:",
            lengthMenu: "Show _MENU_ drafts per page",
            info: "Showing _START_ to _END_ of _TOTAL_ drafts",
            infoEmpty: "No drafts found",
            infoFiltered: "(filtered from _MAX_ total drafts)"
        },
        columnDefs: [
            { orderable: false, targets: [4, 5, 6, 9] } // Disable sorting on Description, Receipt, Items, Actions columns
        ]
    });
});
</script>
