<?php
$message = $_SESSION['form_message'] ?? null;
$messageType = $_SESSION['form_message_type'] ?? 'info';
if (isset($_SESSION['form_message'])) {
    unset($_SESSION['form_message'], $_SESSION['form_message_type']);
}
?>
<h2>Form Management</h2>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Create and manage evaluation forms and other forms for participants, crew, and committee.</p>
    <a href="/forms/create" class="btn btn-primary">Create New Form</a>
</div>

<table class="table table-striped">
    <thead>
    <tr>
        <th>Title</th>
        <th>Target Audience</th>
        <th>Status</th>
        <th>Submissions</th>
        <th>Created</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($forms)): ?>
        <tr>
            <td colspan="6" class="text-center text-muted">No forms created yet. <a href="/forms/create">Create your first form</a>.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($forms as $form): ?>
            <tr>
                <td><strong><?= htmlspecialchars($form['title']) ?></strong></td>
                <td>
                    <span class="badge bg-secondary"><?= ucfirst($form['target_audience']) ?></span>
                </td>
                <td>
                    <?php if ($form['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td><?= (int)$form['submission_count'] ?></td>
                <td><?= date('Y-m-d', strtotime($form['created_at'])) ?></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="/forms/view?id=<?= $form['id'] ?>" class="btn btn-outline-primary">View</a>
                        <a href="/forms/submissions?id=<?= $form['id'] ?>" class="btn btn-outline-info">Submissions</a>
                        <?php if ($form['submission_count'] > 0): ?>
                            <a href="/forms/summary?id=<?= $form['id'] ?>" class="btn btn-outline-success">Summary</a>
                        <?php endif; ?>
                        <a href="/forms/edit?id=<?= $form['id'] ?>" class="btn btn-outline-secondary">Edit</a>
                        <a href="/forms/public?id=<?= $form['id'] ?>" class="btn btn-outline-success" target="_blank">Public Link</a>
                        <form method="post" action="/forms/delete" class="d-inline" onsubmit="return confirm('Delete this form? All submissions will be deleted too.');">
                            <input type="hidden" name="id" value="<?= $form['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
