<?php
// Crew listing
$crew = $crew ?? [];
$message = $_SESSION['crew_message'] ?? null;
$messageType = $_SESSION['crew_message_type'] ?? 'info';
if ($message !== null) {
    unset($_SESSION['crew_message'], $_SESSION['crew_message_type']);
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Crew Management</h2>
    <a href="/operations/crew/create" class="btn btn-primary btn-sm">Add Crew</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<table class="table table-sm table-striped mt-3">
    <thead>
    <tr>
        <th>Name</th>
        <th>Role</th>
        <th>Facilitator</th>
        <th>Assigned group</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($crew as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= htmlspecialchars($c['role']) ?></td>
            <td><?= ((int)($c['is_facilitator'] ?? 0) === 1) ? 'Yes' : 'No' ?></td>
            <td><?= htmlspecialchars($c['assigned_group_code'] ?? '-') ?></td>
            <td>
                <form method="post" action="/operations/crew/update-facilitator" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="crew_id" value="<?= (int)$c['id'] ?>">
                    <div class="form-check m-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="is_facilitator"
                            value="1"
                            id="facilitator_<?= (int)$c['id'] ?>"
                            <?= ((int)($c['is_facilitator'] ?? 0) === 1) ? 'checked' : '' ?>
                        >
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

