<h2>Submissions: <?= htmlspecialchars($form['title']) ?></h2>

<div class="mb-3">
    <a href="/forms" class="btn btn-secondary btn-sm">Back to Forms</a>
    <a href="/forms/view?id=<?= $form['id'] ?>" class="btn btn-outline-secondary btn-sm">View Form</a>
    <a href="/forms/summary?id=<?= $form['id'] ?>" class="btn btn-primary btn-sm">View Summary</a>
</div>

<?php if (empty($submissions)): ?>
    <div class="alert alert-info">
        No submissions yet for this form.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <strong><?= count($submissions) ?></strong> submission(s)
        </div>
        <div class="card-body">
            <?php foreach ($submissions as $index => $submission): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between">
                        <span>
                            <strong>Submission #<?= count($submissions) - $index ?></strong>
                            <span class="badge bg-secondary ms-2"><?= ucfirst($submission['submitted_by_type']) ?></span>
                        </span>
                        <small class="text-muted"><?= date('Y-m-d H:i:s', strtotime($submission['submitted_at'])) ?></small>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Submitted by:</strong> <?= htmlspecialchars($submission['submitted_by']) ?></p>
                        <?php
                        $data = json_decode($submission['submission_data'], true);
                        ?>
                        <table class="table table-sm table-bordered">
                            <thead>
                            <tr>
                                <th>Field</th>
                                <th>Response</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($data as $label => $value): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($label) ?></strong></td>
                                    <td>
                                        <?php
                                        if (is_array($value)) {
                                            echo htmlspecialchars(implode(', ', $value));
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
