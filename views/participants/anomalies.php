<?php
/**
 * Anomalies view with admin constraint management
 *
 * @var int    $totalAnomalies     Total number of anomalies
 * @var int    $totalParticipants   Total active participants checked
 * @var int    $totalRegistered     Total registered participants (including excluded from anomalies)
 * @var array  $anomalies          List of anomalies (each has 'participant' and 'reason')
 * @var array  $constraints        List of custom anomaly constraints
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
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Anomalies & Constraints</h2>
        <p class="text-muted small mb-0 mt-1">Review registrations that do not match expected patterns or admin-defined constraints.</p>
    </div>
    <a href="/participants" class="btn btn-outline-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">arrow_back</span> Back to Participants
    </a>
</div>

<!-- Compact Statistics Banner -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Anomalies Found</div>
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

<!-- Email Anomaly Checks -->
<div class="card mb-4 p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
    <h5 class="fw-semibold mb-2 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
        <span class="material-symbols-outlined text-primary" style="font-size: 22px;">email</span>
        Email Anomaly Checks
    </h5>
    <p class="text-muted mb-4" style="font-size: 0.85rem;">
        The system always flags participants with <strong>empty emails</strong> or <strong>non-TARC domains</strong> (not <code>@student.tarc.edu.my</code>).
        The checks below validate the intake year pattern in the email username.
    </p>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant); border-radius: 16px; background: var(--md-sys-color-surface);">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-3">
                        <div class="fw-semibold" style="font-size: 0.9rem;">A-Level Email Check</div>
                        <div class="text-muted" style="font-size: 0.78rem; margin-top: 4px;">
                            Email username must start with <code><?= date('y') ?></code>
                            <br><small>e.g. <code><?= date('y') ?>ABC01234@student.tarc.edu.my</code></small>
                        </div>
                    </div>
                    <form method="post" action="/participants/anomalies/save-settings" class="m-0">
                        <input type="hidden" name="check_email_a_level" value="0">
                        <button type="submit" name="check_email_a_level" value="<?= $emailCheckALevel ? '0' : '1' ?>" class="btn btn-sm p-0 border-0 bg-transparent" title="<?= $emailCheckALevel ? 'Enabled - click to disable' : 'Disabled - click to enable' ?>">
                            <?php if ($emailCheckALevel): ?>
                                <span class="material-symbols-outlined text-success" style="font-size: 32px;">toggle_on</span>
                            <?php else: ?>
                                <span class="material-symbols-outlined text-muted" style="font-size: 32px;">toggle_off</span>
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant); border-radius: 16px; background: var(--md-sys-color-surface);">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-3">
                        <div class="fw-semibold" style="font-size: 0.9rem;">Other Student Email Check</div>
                        <div class="text-muted" style="font-size: 0.78rem; margin-top: 4px;">
                            Email username must end with <code>XX<?= date('y') ?></code>
                            <br><small>e.g. <code>wm<?= date('y') ?>01234@student.tarc.edu.my</code></small>
                        </div>
                    </div>
                    <form method="post" action="/participants/anomalies/save-settings" class="m-0">
                        <input type="hidden" name="check_email_xx26" value="0">
                        <button type="submit" name="check_email_xx26" value="<?= $emailCheckXX26 ? '0' : '1' ?>" class="btn btn-sm p-0 border-0 bg-transparent" title="<?= $emailCheckXX26 ? 'Enabled - click to disable' : 'Disabled - click to enable' ?>">
                            <?php if ($emailCheckXX26): ?>
                                <span class="material-symbols-outlined text-success" style="font-size: 32px;">toggle_on</span>
                            <?php else: ?>
                                <span class="material-symbols-outlined text-muted" style="font-size: 32px;">toggle_off</span>
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Intake Period Blocking -->
<div class="card mb-4 p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
    <h5 class="fw-semibold mb-2 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
        <span class="material-symbols-outlined" style="font-size: 22px;">block</span>
        Block Registration by Intake Period
    </h5>
    <p class="text-muted mb-3" style="font-size: 0.85rem;">
        Enable blocking for specific intake periods. Participants selecting a blocked intake will be prevented from completing registration.
    </p>
    <div class="row g-2">
        <?php foreach ($intakeBlocking as $period => $isBlocked): ?>
        <div class="col-md-4 col-lg-3">
            <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background: var(--md-sys-color-surface); border: 1px solid var(--md-sys-color-outline-variant); font-size: 0.82rem;">
                <span class="text-truncate me-2" title="<?= htmlspecialchars($period) ?>"><?= htmlspecialchars($period) ?></span>
                <form method="post" action="/participants/anomalies/save-settings" class="m-0 flex-shrink-0">
                    <input type="hidden" name="intake_period" value="<?= htmlspecialchars($period) ?>">
                    <input type="hidden" name="enabled" value="<?= $isBlocked ? '0' : '1' ?>">
                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="<?= $isBlocked ? 'Blocked - click to allow' : 'Allowed - click to block' ?>">
                        <?php if ($isBlocked): ?>
                            <span class="material-symbols-outlined text-danger" style="font-size: 28px;">block</span>
                        <?php else: ?>
                            <span class="material-symbols-outlined text-muted" style="font-size: 28px;">check_circle</span>
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
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
                            <?php
                            $reasons = explode('; ', $item['reason']);
                            foreach ($reasons as $reason): ?>
                                <span class="badge bg-warning mb-1"><?= htmlspecialchars($reason) ?></span>
                            <?php endforeach; ?>
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
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: 6 }
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

function togglePatternField() {
    var type = document.getElementById('constraintTypeSelect').value;
    var patternGroup = document.getElementById('patternFieldGroup');
    var patternInput = document.getElementById('patternInput');
    if (type === 'field_empty' || type === 'field_not_empty') {
        patternGroup.style.display = 'none';
        patternInput.value = '';
    } else {
        patternGroup.style.display = 'block';
        if (type === 'email_pattern' || type === 'field_regex') {
            patternInput.placeholder = 'e.g. /^26/i or /w[a-z]26$/i';
        } else if (type === 'field_contains') {
            patternInput.placeholder = 'e.g. gmail, yahoo';
        } else {
            patternInput.placeholder = 'e.g. exact value to match';
        }
    }
}
</script>