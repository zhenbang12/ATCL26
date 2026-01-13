<?php
// Crew listing
?>
<h2>Crew Management</h2>

<table class="table table-sm table-striped mt-3">
    <thead>
    <tr>
        <th>Name</th>
        <th>Role</th>
        <th>Assigned group</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($crew as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= htmlspecialchars($c['role']) ?></td>
            <td><?= htmlspecialchars($c['assigned_group_code'] ?? '-') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

