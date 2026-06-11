<?php
/**
 * Participant Audit Logs view
 *
 * @var array  $logs  List of audit log records
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
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Participant Audit Logs</h2>
        <p class="text-muted small mb-0 mt-1">Review the history of modifications, registrations, and deletions for participant records.</p>
    </div>
    <a href="/participants" class="btn btn-outline-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">arrow_back</span> Back to Participants
    </a>
</div>

<!-- Audit Logs Table -->
<div class="card p-3 border-0" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important;">
    <div class="table-responsive">
        <table id="audit-logs-table" class="table table-sm table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Participant Name</th>
                    <th>Action</th>
                    <th>Changed Fields / Details</th>
                    <th>Performed By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="font-size: 0.85rem;" data-order="<?= strtotime($log['performed_at']) ?>">
                            <?= htmlspecialchars(date('j M Y, g:i A', strtotime($log['performed_at']))) ?>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($log['participant_name']) ?></div>
                            <small class="text-muted">ID: <?= (int)$log['participant_id'] ?></small>
                        </td>
                        <td>
                            <?php 
                            $badgeClass = 'bg-secondary';
                            if ($log['action'] === 'created') {
                                $badgeClass = 'bg-success';
                            } elseif ($log['action'] === 'deleted') {
                                $badgeClass = 'bg-danger';
                            } elseif ($log['action'] === 'excluded_from_anomalies') {
                                $badgeClass = 'bg-warning text-dark';
                            }
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(str_replace('_', ' ', $log['action'])) ?></span>
                        </td>
                        <td style="font-size: 0.85rem; max-width: 400px; word-wrap: break-word; white-space: normal;">
                            <?= htmlspecialchars($log['changed_fields'] ?? '') ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($log['performed_by']) ?></div>
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
    $('#audit-logs-table').DataTable({
        pageLength: 25,
        order: [[0, 'desc']], // Sort by Timestamp descending by default
        language: {
            search: "Search logs:",
            lengthMenu: "Show _MENU_ logs per page",
            info: "Showing _START_ to _END_ of _TOTAL_ logs",
            infoEmpty: "No logs found",
            infoFiltered: "(filtered from _MAX_ total logs)"
        }
    });
});
</script>
