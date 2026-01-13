<?php
// Participants listing
$currentFilter = $_GET['filter'] ?? 'all';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Participants</h2>
    <div>
        <a href="/participants/create" class="btn btn-primary btn-sm">New registration</a>
        <a href="/participants/checkin" class="btn btn-outline-secondary btn-sm">QR Check-in</a>
        <a href="/participants/groups" class="btn btn-outline-secondary btn-sm">Grouping overview</a>
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
        <th>Intake</th>
        <th>Programme</th>
        <th>Faculty</th>
        <th>Phone</th>
        <th>Language</th>
        <th>Group</th>
        <th>Checked in?</th>
    </tr>
    </thead>
    <tbody>
    <?php $counter = 1; foreach ($participants as $p): ?>
        <tr>
            <td><?= $counter++ ?></td>
            <td><?= htmlspecialchars($p['full_name']) ?></td>
            <td><?= htmlspecialchars($p['student_id'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['intake'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['programme_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['faculty'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['contact_no'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['preferred_language'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['group_code'] ?? '-') ?></td>
            <td><?= !empty($p['checked_in_at'] ?? null) ? 'Yes' : 'No' ?></td>
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

