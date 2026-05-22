<?php
// Participants listing
$currentFilter = $_GET['filter'] ?? 'all';
$participants = $participants ?? [];
$registrationSettings = $registrationSettings ?? [
    'pre_register_enabled' => true,
    'walk_in_enabled' => true,
];
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Participants List</h2>
    <div>
        <a href="/participants" class="btn btn-outline-primary btn-sm">Dashboard</a>
        <?php if ($registrationSettings['pre_register_enabled'] || \App\Core\Auth::check()): ?>
            <a href="/participants/create" class="btn btn-primary btn-sm">Pre-register</a>
        <?php endif; ?>
        <a href="/participants/create-walkin" class="btn btn-dark btn-sm">Walk-in Registration</a>
        <a href="/participants/checkin" class="btn btn-outline-secondary btn-sm">QR Check-in</a>
        <a href="/participants/groups" class="btn btn-outline-secondary btn-sm">Grouping Overview</a>
        <a href="/participants/export?filter=<?= urlencode($currentFilter) ?>" class="btn btn-success btn-sm">
            Export CSV
        </a>
    </div>
</div>

<div class="mb-3">
    <div class="btn-group" role="group">
        <a href="/participants?filter=all" class="btn btn-sm <?= $currentFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
            All
        </a>
        <a href="/participants?filter=checked_in" class="btn btn-sm <?= $currentFilter === 'checked_in' ? 'btn-primary' : 'btn-outline-primary' ?>">
            Checked In
        </a>
        <a href="/participants?filter=not_checked_in" class="btn btn-sm <?= $currentFilter === 'not_checked_in' ? 'btn-primary' : 'btn-outline-primary' ?>">
            Not Checked In
        </a>
    </div>
</div>

<table id="participants-table" class="table table-sm table-striped">
    <thead>
    <tr>
        <th>#</th>
        <th>Name</th>
        <th>Student ID</th>
        <th>Email</th>
        <th>Intake</th>
        <th>Programme</th>
        <th>Faculty</th>
        <th>Phone</th>
        <th>Language</th>
        <th>Registration</th>
        <th>Group</th>
        <th>Checked in?</th>
        <?php if (\App\Core\Auth::check()): ?>
            <th>Actions</th>
        <?php endif; ?>
    </tr>
    </thead>
    <tbody>
    <?php $counter = 1; foreach ($participants as $p): ?>
        <tr>
            <td><?= $counter++ ?></td>
            <td><?= htmlspecialchars($p['full_name']) ?></td>
            <td><?= htmlspecialchars($p['student_id'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['student_email'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['intake'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['programme_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['faculty'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['contact_no'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['preferred_language'] ?? '') ?></td>
            <td>
                <?php if (($p['registration_type'] ?? 'pre_register') === 'walk_in'): ?>
                    <span class="badge bg-dark">Walk-in</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Pre-register</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['group_code'] ?? '-') ?></td>
            <td><?= !empty($p['checked_in_at'] ?? null) ? 'Yes' : 'No' ?></td>
            <?php if (\App\Core\Auth::check()): ?>
                <td>
                    <div class="d-flex align-items-center">
                        <a href="/participants/edit?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-2">Edit</a>
                        <form method="post" action="/participants/delete" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this participant?');">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#participants-table').DataTable({
            pageLength: 25,
            order: [[1, 'asc']], // Sort by Name column by default
            language: {
                search: "Search participants:",
                lengthMenu: "Show _MENU_ participants per page",
                info: "Showing _START_ to _END_ of _TOTAL_ participants",
                infoEmpty: "No participants found",
                infoFiltered: "(filtered from _MAX_ total participants)"
            }
        });
    });
</script>
