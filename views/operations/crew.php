<?php
// Crew listing
$crew = $crew ?? [];
$message = $_SESSION['crew_message'] ?? null;
$messageType = $_SESSION['crew_message_type'] ?? 'info';
if ($message !== null) {
    unset($_SESSION['crew_message'], $_SESSION['crew_message_type']);
}
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Senior Buddy &amp; Crew Management</h2>
        <p class="text-muted small mb-0">Manage crew members, roles, and Senior Buddy eligibility.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/participants/assign-buddy" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <span class="material-symbols-outlined" style="font-size: 18px;">assignment_ind</span>
            Assign Senior Buddies
        </a>
        <a href="/operations/crew/create" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
            <span class="material-symbols-outlined" style="font-size: 18px;">person_add</span>
            Add Crew
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($crew)): ?>
    <div class="card text-center py-5" style="border-radius: 20px; border: 1px solid var(--md-sys-color-outline-variant);">
        <span class="material-symbols-outlined mx-auto mb-2" style="font-size: 48px; color: var(--md-sys-color-outline);">group_off</span>
        <p class="text-muted mb-3">No crew members added yet.</p>
        <a href="/operations/crew/create" class="btn btn-primary btn-sm mx-auto" style="width: fit-content;">Add First Crew Member</a>
    </div>
<?php else: ?>
<div class="card" style="border-radius: 20px; border: 1px solid var(--md-sys-color-outline-variant); overflow: hidden;">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead style="background: var(--md-sys-color-surface-container-high);">
                <tr>
                    <th class="ps-3 py-3">Name</th>
                    <th>Role</th>
                    <th>Senior Buddy</th>
                    <th>Assigned Group</th>
                    <th class="pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($crew as $c): ?>
                <tr>
                    <td class="ps-3 fw-semibold"><?= htmlspecialchars($c['full_name']) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($c['role'] ?: '—') ?></td>
                    <td>
                        <?php if ((int)($c['is_facilitator'] ?? 0) === 1): ?>
                            <span class="badge bg-primary-filled" style="font-size: 0.72rem;">
                                <span class="material-symbols-outlined" style="font-size: 13px; vertical-align: -2px;">star</span>
                                Senior Buddy
                            </span>
                        <?php else: ?>
                            <span class="badge bg-surface" style="font-size: 0.72rem;">Crew</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($c['assigned_group_code'])): ?>
                            <span class="badge bg-primary" style="font-size: 0.72rem;">Group <?= htmlspecialchars($c['assigned_group_code']) ?></span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="pe-3">
                        <div class="d-flex align-items-center gap-2">
                            <a href="/operations/crew/edit?id=<?= (int)$c['id'] ?>"
                               class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                                <span class="material-symbols-outlined" style="font-size: 15px;">edit</span>
                                Edit
                            </a>
                            <form
                                method="post"
                                action="/operations/crew/delete"
                                class="m-0"
                                onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($c['full_name'])) ?> from the roster? This cannot be undone.');"
                            >
                                <input type="hidden" name="crew_id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">delete</span>
                                    Delete
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
<?php endif; ?>
