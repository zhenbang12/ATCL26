<?php
// Simple budget dashboard
?>
<h2>Budget Dashboard</h2>

<table class="table table-sm table-striped mt-3">
    <thead>
    <tr>
        <th>Department</th>
        <th class="text-end">Allocated</th>
        <th class="text-end">Spent</th>
        <th class="text-end">Balance</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($budgets as $b): ?>
        <?php
        $allocated = (float)$b['allocated_amount'];
        $spent = (float)$b['spent_amount'];
        $balance = $allocated - $spent;
        ?>
        <tr>
            <td><?= htmlspecialchars($b['department']) ?></td>
            <td class="text-end"><?= number_format($allocated, 2) ?></td>
            <td class="text-end"><?= number_format($spent, 2) ?></td>
            <td class="text-end"><?= number_format($balance, 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

