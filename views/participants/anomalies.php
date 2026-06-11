<?php
/**
 * Email Anomalies view
 *
 * @var int    $totalAnomalies     Total number of email anomalies
 * @var int    $totalParticipants   Total active participants checked
 * @var array  $anomalies          List of anomalies (each has 'participant' and 'reason')
 */

$participantsMessage = $_SESSION['participants_message'] ?? null;
$participantsMessageType = $_SESSION['participants_message_type'] ?? 'info';
if (isset($_SESSION['participants_message'])) {
    unset($_SESSION['participants_message'], $_SESSION['participants_message_type']);
}
?>

<?php if ($participantsMessage): ?>
    <div class="alert alert-<?= $participantsMessageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($participantsMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Title and Top Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Email Anomalies</h2>
        <p class="text-muted small mb-0 mt-1">Review registrations that do not match the expected student email formats for the 2026 intake.</p>
    </div>
    <a href="/participants" class="btn btn-outline-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">arrow_back</span> Back to Participants
    </a>
</div>

<!-- Compact Statistics Banner -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Email Anomalies</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-error) !important;"><?= $totalAnomalies ?></h2>
            <small class="text-muted" style="font-size: 0.72rem;">Action required</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Participants Checked</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-primary) !important;"><?= $totalParticipants ?></h2>
            <small class="text-muted" style="font-size: 0.72rem;">Included in anomaly scan (<?= $totalRegistered - $totalParticipants ?> removed from scan)</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Anomaly Rate</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-tertiary) !important;">
                <?= $totalParticipants > 0 ? number_format(($totalAnomalies / $totalParticipants) * 100, 1) : 0 ?>%
            </h2>
            <small class="text-muted" style="font-size: 0.72rem;">Target: 0%</small>
        </div>
    </div>
</div>

<!-- Expected Pattern Reference Info Box -->
<div class="card mb-4 p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
    <h5 class="fw-semibold mb-2 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
        <span class="material-symbols-outlined text-primary" style="font-size: 22px;">info</span>
        Expected 2026 Intake Email Formats
    </h5>
    <p class="text-muted mb-3">To ensure participant emails correspond to the 2026 intake year, they must end with <code>@student.tarc.edu.my</code> and match one of the following formats:</p>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="p-3 rounded-3 h-100" style="background-color: var(--md-sys-color-surface-container); border: 1px solid var(--md-sys-color-outline-variant);">
                <div class="fw-bold text-primary mb-1">1. A-Level Student Email (26XXXXXXXX)</div>
                <div class="small text-muted mb-2">Used by A-Level students. The username starts with <code>26</code> followed by letters/numbers:</div>
                <code><strong>26</strong>wcy80490@student.tarc.edu.my</code>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded-3 h-100" style="background-color: var(--md-sys-color-surface-container); border: 1px solid var(--md-sys-color-outline-variant);">
                <div class="fw-bold text-primary mb-1">2. Other Student Types Email (wX26)</div>
                <div class="small text-muted mb-2">Used by Diploma, Degree, or Foundation students. The username ends with <code>w</code> + campus code + <code>26</code>:</div>
                <code>adellynabna-<strong>wb26</strong>@student.tarc.edu.my</code>
            </div>
        </div>
    </div>
</div>

<!-- Anomalies Table -->
<div class="card p-3 border-0" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important;">
    <div class="table-responsive">
        <table id="anomalies-table" class="table table-sm table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Flag Reason</th>
                    <th>Registered At</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anomalies as $item): ?>
                    <?php $p = $item['participant']; ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($p['full_name'] ?? '') ?></div>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($p['student_id'] ?? '') ?></code>
                        </td>
                        <td>
                            <span class="text-danger fw-semibold"><?= htmlspecialchars($p['student_email'] ?? 'Blank') ?></span>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($p['contact_no'] ?? '-') ?></code>
                        </td>
                        <td>
                            <span class="badge bg-warning"><?= htmlspecialchars($item['reason']) ?></span>
                        </td>
                        <td style="font-size: 0.85rem;">
                            <?= !empty($p['created_at']) ? htmlspecialchars(date('j M Y, g:i A', strtotime($p['created_at']))) : '-' ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="/participants/edit?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-3" style="border-radius: 8px !important; font-size: 0.75rem !important;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: text-bottom;">edit</span> Edit
                                </a>
                                <form method="post" action="/participants/remove-anomaly" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to remove this participant from the anomalies list?');">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-3" style="border-radius: 8px !important; font-size: 0.75rem !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: text-bottom;">delete</span> Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#anomalies-table').DataTable({
        pageLength: 25,
        order: [[0, 'asc']], // Sort by Name by default
        columnDefs: [
            { orderable: false, targets: 6 } // Action is not orderable
        ],
        language: {
            search: "Search anomalies:",
            lengthMenu: "Show _MENU_ anomalies per page",
            info: "Showing _START_ to _END_ of _TOTAL_ anomalies",
            infoEmpty: "No anomalies found",
            infoFiltered: "(filtered from _MAX_ total anomalies)"
        }
    });
});
</script>
