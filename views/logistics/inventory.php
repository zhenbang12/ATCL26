<?php
// Inventory list view
?>
<h2>Equipment & Inventory</h2>

<table class="table table-sm table-striped mt-3">
    <thead>
    <tr>
        <th>Item</th>
        <th>Category</th>
        <th class="text-end">Available</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $i): ?>
        <tr>
            <td><?= htmlspecialchars($i['item_name']) ?></td>
            <td><?= htmlspecialchars($i['category']) ?></td>
            <td class="text-end"><?= (int)$i['quantity_available'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

