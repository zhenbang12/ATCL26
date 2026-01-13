<?php
// Task list view with simple dependency display
?>
<h2>Task Timeline</h2>

<table class="table table-sm table-striped mt-3">
    <thead>
    <tr>
        <th>Task</th>
        <th>Status</th>
        <th>Due</th>
        <th>Depends on</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($tasks as $t): ?>
        <tr>
            <td><?= htmlspecialchars($t['title']) ?></td>
            <td><?= htmlspecialchars($t['status']) ?></td>
            <td><?= htmlspecialchars($t['due_date'] ?? '-') ?></td>
            <td><?= $t['depends_on_task_id'] ? (int)$t['depends_on_task_id'] : '-' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

