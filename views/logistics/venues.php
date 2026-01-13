<?php
// Venue list view
?>
<h2>Venue Master Plan</h2>

<table class="table table-sm table-striped mt-3">
    <thead>
    <tr>
        <th>Name</th>
        <th>Location</th>
        <th class="text-end">Capacity</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($venues as $v): ?>
        <tr>
            <td><?= htmlspecialchars($v['name']) ?></td>
            <td><?= htmlspecialchars($v['location']) ?></td>
            <td class="text-end"><?= (int)$v['capacity'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

