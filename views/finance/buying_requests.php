<?php
// Buying requests listing with simple create form
use App\Core\Auth;
use App\Core\Container;
$userRole = Auth::role();
$canApprove = in_array($userRole, ['advisor', 'committee', 'treasurer']);
$currentFilter = $_GET['filter'] ?? 'all';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Buying Requests</h2>
</div>

<!-- Tabs -->
<?php
// Determine default tab: drafts if filter=draft, view if other filter, submit otherwise
$defaultTab = 'submit';
if (isset($_GET['filter'])) {
    $defaultTab = ($_GET['filter'] === 'draft') ? 'drafts' : 'view';
}
?>
<ul class="nav nav-tabs mb-4" id="buyingRequestsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $defaultTab === 'submit' ? 'active' : '' ?>" id="submit-tab" data-bs-toggle="tab" data-bs-target="#submit" type="button" role="tab" aria-controls="submit" aria-selected="<?= $defaultTab === 'submit' ? 'true' : 'false' ?>">Submit Request</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $defaultTab === 'drafts' ? 'active' : '' ?>" id="drafts-tab" data-bs-toggle="tab" data-bs-target="#drafts" type="button" role="tab" aria-controls="drafts" aria-selected="<?= $defaultTab === 'drafts' ? 'true' : 'false' ?>">Drafts</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $defaultTab === 'view' ? 'active' : '' ?>" id="view-tab" data-bs-toggle="tab" data-bs-target="#view" type="button" role="tab" aria-controls="view" aria-selected="<?= $defaultTab === 'view' ? 'true' : 'false' ?>">View Requests</button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="buyingRequestsTabsContent">
    <!-- Submit Request Tab -->
    <div class="tab-pane fade <?= $defaultTab === 'submit' ? 'show active' : '' ?>" id="submit" role="tabpanel" aria-labelledby="submit-tab">
        <form method="post" action="/finance/buying-requests/store" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Requester</label>
                <input type="text" name="requester_name" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control" value="1" min="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Estimated Cost (RM)</label>
                <input type="number" step="0.01" name="estimated_cost" class="form-control" required>
            </div>
            <div class="col-md-12">
                <label class="form-label">Item Description</label>
                <textarea name="item_description" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Justification</label>
                <textarea name="justification" class="form-control" rows="2" placeholder="Why is this purchase needed?"></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Vendor Preference (Optional)</label>
                <input type="text" name="vendor_preference" class="form-control" placeholder="Preferred vendor name">
            </div>
            <div class="col-md-12">
                <label class="form-label">Reference Image</label>
                <input type="file" name="reference_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                <small class="text-muted">Max 5MB. Accepted formats: JPEG, PNG, GIF, WebP</small>
            </div>
            <div class="col-12">
                <button type="submit" name="submit" class="btn btn-primary">Submit Request</button>
                <button type="submit" name="save_as_draft" class="btn btn-secondary">Save as Draft</button>
            </div>
        </form>
    </div>

    <!-- Drafts Tab -->
    <div class="tab-pane fade <?= $defaultTab === 'drafts' ? 'show active' : '' ?>" id="drafts" role="tabpanel" aria-labelledby="drafts-tab">
        <?php
        // Get only drafts for this tab
        $db = Container::get('db');
        $stmt = $db->query("SELECT id, requester_name, department, item_description, quantity, estimated_cost, justification, vendor_preference, reference_image, status, created_at FROM buying_requests WHERE status = 'draft' ORDER BY created_at DESC");
        $draftBuyingRequests = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        ?>
        <table id="drafts-buying-requests-table" class="table table-sm table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Requester</th>
                <th>Dept</th>
                <th>Item Description</th>
                <th>Qty</th>
                <th>Cost (RM)</th>
                <th>Vendor</th>
                <th>Image</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php $counter = 1; foreach ($draftBuyingRequests as $br): ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td><?= !empty($br['created_at']) ? date('Y-m-d H:i', strtotime($br['created_at'])) : '-' ?></td>
                    <td><?= htmlspecialchars($br['requester_name']) ?></td>
                    <td><?= htmlspecialchars($br['department']) ?></td>
                    <td>
                        <div><?= htmlspecialchars($br['item_description']) ?></div>
                        <?php if (!empty($br['justification'])): ?>
                            <small class="text-muted"><?= htmlspecialchars($br['justification']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($br['quantity']) ?></td>
                    <td class="text-end">RM <?= number_format((float)$br['estimated_cost'], 2) ?></td>
                    <td><?= htmlspecialchars($br['vendor_preference'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($br['reference_image'])): ?>
                            <img src="<?= htmlspecialchars($br['reference_image']) ?>" 
                                 alt="Reference" 
                                 class="img-thumbnail" 
                                 style="max-width: 100px; max-height: 100px; cursor: pointer; object-fit: cover;"
                                 onclick="showImageModal('<?= htmlspecialchars($br['reference_image']) ?>', 'Reference Image')">
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-warning">Draft</span>
                    </td>
                    <td>
                        <a href="/finance/buying-requests/edit?id=<?= $br['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- View Requests Tab -->
    <div class="tab-pane fade <?= $defaultTab === 'view' ? 'show active' : '' ?>" id="view" role="tabpanel" aria-labelledby="view-tab">
        <div class="mb-3">
            <div class="btn-group" role="group">
                <a href="/finance/buying-requests?filter=all" class="btn btn-sm <?= $currentFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    All
                </a>
                <a href="/finance/buying-requests?filter=pending" class="btn btn-sm <?= $currentFilter === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Pending
                </a>
                <a href="/finance/buying-requests?filter=approved" class="btn btn-sm <?= $currentFilter === 'approved' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Approved
                </a>
                <a href="/finance/buying-requests?filter=rejected" class="btn btn-sm <?= $currentFilter === 'rejected' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Rejected
                </a>
            </div>
        </div>
        <table id="buying-requests-table" class="table table-sm table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Requester</th>
                <th>Dept</th>
                <th>Item Description</th>
                <th>Qty</th>
                <th>Cost (RM)</th>
                <th>Vendor</th>
                <th>Image</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php $counter = 1; foreach ($buyingRequests as $br): ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td><?= !empty($br['created_at']) ? date('Y-m-d H:i', strtotime($br['created_at'])) : '-' ?></td>
                    <td><?= htmlspecialchars($br['requester_name']) ?></td>
                    <td><?= htmlspecialchars($br['department']) ?></td>
                    <td>
                        <div><?= htmlspecialchars($br['item_description']) ?></div>
                        <?php if (!empty($br['justification'])): ?>
                            <small class="text-muted"><?= htmlspecialchars($br['justification']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($br['quantity']) ?></td>
                    <td class="text-end">RM <?= number_format((float)$br['estimated_cost'], 2) ?></td>
                    <td><?= htmlspecialchars($br['vendor_preference'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($br['reference_image'])): ?>
                            <img src="<?= htmlspecialchars($br['reference_image']) ?>" 
                                 alt="Reference" 
                                 class="img-thumbnail" 
                                 style="max-width: 100px; max-height: 100px; cursor: pointer; object-fit: cover;"
                                 onclick="showImageModal('<?= htmlspecialchars($br['reference_image']) ?>', 'Reference Image')">
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                $statusColors = [
                    'draft' => 'warning',
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'purchased' => 'info'
                ];
                        $color = $statusColors[$br['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>"><?= ucfirst($br['status']) ?></span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <?php if ($br['status'] === 'draft'): ?>
                                <a href="/finance/buying-requests/edit?id=<?= $br['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <?php elseif ($br['status'] === 'rejected'): ?>
                                <a href="/finance/buying-requests/edit?id=<?= $br['id'] ?>" class="btn btn-warning btn-sm">Edit & Resubmit</a>
                            <?php elseif ($canApprove && $br['status'] === 'pending'): ?>
                                <form method="post" action="/finance/buying-requests/approve" style="display: inline;" onsubmit="return confirm('Approve this buying request?');">
                                    <input type="hidden" name="request_id" value="<?= $br['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="post" action="/finance/buying-requests/reject" style="display: inline;" onsubmit="return confirm('Reject this buying request?');">
                                    <input type="hidden" name="request_id" value="<?= $br['id'] ?>">
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
    $('#buying-requests-table').DataTable({
        pageLength: 25,
        order: [[1, 'desc']], // Sort by Date column (newest first)
        language: {
            search: "Search requests:",
            lengthMenu: "Show _MENU_ requests per page",
            info: "Showing _START_ to _END_ of _TOTAL_ requests",
            infoEmpty: "No requests found",
            infoFiltered: "(filtered from _MAX_ total requests)"
        },
        columnDefs: [
            { orderable: false, targets: [4, 8, 10] } // Disable sorting on Item Description, Image, Actions columns
        ]
    });
});
</script>
