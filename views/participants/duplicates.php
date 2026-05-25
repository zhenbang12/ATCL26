<?php
/**
 * Duplicate Detection view
 *
 * @var int    $totalGroups   Total number of duplicate groups found
 * @var array  $emailDupes    Groups of participants with duplicate emails
 * @var array  $phoneDupes    Groups of participants with duplicate phone numbers
 * @var array  $nameDupes     Groups of participants with duplicate names
 * @var array  $flagged       Participants already flagged as duplicates
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
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Duplicate Detection</h2>
        <p class="text-muted small mb-0 mt-1">Review and resolve duplicate registrations by email, phone, or name.</p>
    </div>
    <a href="/participants" class="btn btn-outline-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">arrow_back</span> Back to Participants
    </a>
</div>

<!-- Compact Statistics Banner -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Potential Duplicate Groups</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-error) !important;"><?= $totalGroups ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Already Flagged</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-tertiary) !important;"><?= count($flagged) ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Match Types</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-primary) !important;"><?= count($emailDupes) ?>E / <?= count($phoneDupes) ?>P / <?= count($nameDupes) ?>N</h2>
            <small class="text-muted" style="font-size: 0.72rem;">Email / Phone / Name</small>
        </div>
    </div>
</div>

<!-- Duplicate Groups -->
<?php
$sections = [
    ['label' => 'Email Matches', 'icon' => 'email', 'data' => $emailDupes, 'match_key' => 'student_email'],
    ['label' => 'Phone Matches', 'icon' => 'phone', 'data' => $phoneDupes, 'match_key' => 'contact_no'],
    ['label' => 'Name Matches', 'icon' => 'person', 'data' => $nameDupes, 'match_key' => 'full_name'],
];
?>

<?php foreach ($sections as $section): ?>
    <?php if (empty($section['data'])) continue; ?>
    <div class="mb-4">
        <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
            <span class="material-symbols-outlined" style="font-size: 22px;"><?= $section['icon'] ?></span>
            <?= $section['label'] ?>
            <span class="badge bg-secondary ms-1"><?= count($section['data']) ?> groups</span>
        </h5>

        <?php foreach ($section['data'] as $groupIndex => $group): ?>
            <div class="card mb-3 p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold" style="color: var(--md-sys-color-primary);">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">content_copy</span>
                        <?= htmlspecialchars($group['match_value']) ?>
                    </h6>
                    <span class="badge bg-warning"><?= $group['count'] ?> records</span>
                </div>

                <form method="post" action="/participants/duplicates/resolve">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-2">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">Original</th>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Language</th>
                                    <th>Checked In</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group['participants'] as $p): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="radio" name="canonical_id" value="<?= (int)$p['id'] ?>" class="form-check-input" required>
                                        </td>
                                        <td><?= htmlspecialchars($p['full_name']) ?></td>
                                        <td><code><?= htmlspecialchars($p['student_id']) ?></code></td>
                                        <td><?= htmlspecialchars($p['student_email']) ?></td>
                                        <td><?= htmlspecialchars($p['contact_no']) ?></td>
                                        <td>
                                            <span class="badge" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container);">
                                                <?= htmlspecialchars($p['preferred_language']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($p['checked_in_at'])): ?>
                                                <span class="badge bg-success"><?= htmlspecialchars($p['checked_in_at']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-surface">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size: 0.82rem;"><?= htmlspecialchars($p['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 align-items-center mt-2">
                        <span class="text-muted small">Select the original (correct) record, then:</span>
                        <input type="hidden" name="duplicate_id" value="" class="duplicate-id-input">
                        <button type="submit" class="btn btn-sm btn-outline-danger duplicate-submit-btn" disabled>
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">flag</span> Mark Selected as Duplicate
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php if ($totalGroups === 0): ?>
    <div class="card p-5 text-center border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
        <span class="material-symbols-outlined" style="font-size: 48px; color: var(--md-sys-color-success);">check_circle</span>
        <h5 class="mt-2 fw-bold" style="color: var(--md-sys-color-on-surface);">No Duplicates Found</h5>
        <p class="text-muted mb-0">No potential duplicate registrations detected by email, phone, or name.</p>
    </div>
<?php endif; ?>

<!-- Already Flagged Section -->
<?php if (!empty($flagged)): ?>
    <div class="mt-4">
        <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
            <span class="material-symbols-outlined" style="font-size: 22px;">flag</span>
            Already Flagged as Duplicates
            <span class="badge bg-secondary ms-1"><?= count($flagged) ?></span>
        </h5>

        <div class="card p-3 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th>Duplicate Of</th>
                            <th>Checked In</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flagged as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['full_name']) ?></td>
                                <td><code><?= htmlspecialchars($p['student_id']) ?></code></td>
                                <td><?= htmlspecialchars($p['student_email']) ?></td>
                                <td>
                                    <?php if ($p['canonical_name']): ?>
                                        <span class="badge" style="background-color: var(--md-sys-color-tertiary-container); color: var(--md-sys-color-on-tertiary-container);">
                                            <?= htmlspecialchars($p['canonical_name']) ?> (<?= htmlspecialchars($p['canonical_student_id']) ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Record #<?= (int)$p['duplicate_of'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($p['checked_in_at'])): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($p['checked_in_at']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-surface">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" action="/participants/duplicates/unresolve" class="d-inline" onsubmit="return confirm('Unflag this record? It will reappear in the main participants list.');">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem;">
                                            Unflag
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // For each duplicate group form, handle the radio selection logic
    document.querySelectorAll('form[action="/participants/duplicates/resolve"]').forEach(function(form) {
        const radios = form.querySelectorAll('input[name="canonical_id"]');
        const duplicateInput = form.querySelector('.duplicate-id-input');
        const submitBtn = form.querySelector('.duplicate-submit-btn');

        radios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                const selectedId = this.value;
                const allIds = Array.from(radios).map(r => r.value);
                const dupeIds = allIds.filter(id => id !== selectedId);
                duplicateInput.value = dupeIds.join(',');
                submitBtn.disabled = false;
            });
        });

        form.addEventListener('submit', function(e) {
            if (!duplicateInput.value) {
                e.preventDefault();
                alert('Please select which record is the original (correct) one.');
                return;
            }
            const dupeCount = duplicateInput.value.split(',').length;
            if (!confirm('Mark ' + dupeCount + ' record(s) as duplicate(s)? They will be hidden from the main list.')) {
                e.preventDefault();
            }
        });
    });
});
</script>