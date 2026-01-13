<?php
// Games and scores basic view
?>
<h2>Games & Scores</h2>

<p class="text-muted">
    This is a simple placeholder view; extend it into live scoreboards and best-group leaderboards.
    Data shown below comes from the <code>games</code> and <code>scores</code> tables.
</p>

<table class="table table-sm table-striped mt-3">
    <thead>
    <tr>
        <th>Game</th>
        <th>Group</th>
        <th class="text-end">Score</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['group_code'] ?? '-') ?></td>
            <td class="text-end"><?= $row['score'] !== null ? (int)$row['score'] : '-' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

