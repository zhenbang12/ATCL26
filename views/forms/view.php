<h2><?= htmlspecialchars($form['title']) ?></h2>

<div class="card mb-3">
    <div class="card-body">
        <p class="text-muted"><?= htmlspecialchars($form['description']) ?></p>
        <div class="mb-2">
            <strong>Target Audience:</strong> <span class="badge bg-secondary"><?= ucfirst($form['target_audience']) ?></span>
            <strong class="ms-3">Status:</strong> 
            <?php if ($form['is_active']): ?>
                <span class="badge bg-success">Active</span>
            <?php else: ?>
                <span class="badge bg-secondary">Inactive</span>
            <?php endif; ?>
        </div>
        <div>
            <a href="/forms/public?id=<?= $form['id'] ?>" class="btn btn-primary" target="_blank">View Public Form</a>
            <a href="/forms/submissions?id=<?= $form['id'] ?>" class="btn btn-info">View Submissions</a>
            <a href="/forms/summary?id=<?= $form['id'] ?>" class="btn btn-success">View Summary</a>
            <a href="/forms/edit?id=<?= $form['id'] ?>" class="btn btn-secondary">Edit</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Form Fields</div>
    <div class="card-body">
        <?php if (empty($fields)): ?>
            <p class="text-muted">No fields defined yet.</p>
        <?php else: ?>
            <table class="table table-sm">
                <thead>
                <tr>
                    <th>Order</th>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Options</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($fields as $field): ?>
                    <tr>
                        <td><?= $field['field_order'] + 1 ?></td>
                        <td><strong><?= htmlspecialchars($field['field_label']) ?></strong></td>
                        <td><?= ucfirst($field['field_type']) ?></td>
                        <td><?= $field['is_required'] ? '<span class="badge bg-danger">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                        <td>
                            <?php
                            $options = json_decode($field['field_options'] ?? '[]', true);
                            if (!empty($options)) {
                                echo htmlspecialchars(implode(', ', $options));
                            } else {
                                echo '<span class="text-muted">-</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<a href="/forms" class="btn btn-secondary mt-3">Back to Forms</a>
