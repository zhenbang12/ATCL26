<?php
// Proposals overview
?>
<h2>Proposals & Approvals</h2>

<table class="table table-sm table-striped mt-3">
    <thead>
    <tr>
        <th>Title</th>
        <th>Owner</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($proposals as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['title']) ?></td>
            <td><?= htmlspecialchars($p['owner_name']) ?></td>
            <td><?= htmlspecialchars($p['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

