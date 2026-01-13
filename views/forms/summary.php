<h2>Form Summary: <?= htmlspecialchars($form['title']) ?></h2>

<div class="mb-3">
    <a href="/forms" class="btn btn-secondary btn-sm">Back to Forms</a>
    <a href="/forms/view?id=<?= $form['id'] ?>" class="btn btn-outline-secondary btn-sm">View Form</a>
    <a href="/forms/submissions?id=<?= $form['id'] ?>" class="btn btn-outline-info btn-sm">View All Submissions</a>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="card-title"><?= $totalSubmissions ?></h3>
                <p class="card-text text-muted mb-0">Total Submissions</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="card-title"><?= count($fields) ?></h3>
                <p class="card-text text-muted mb-0">Total Fields</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="card-title">
                    <?php
                    $avgRate = $totalSubmissions > 0 ? round(array_sum(array_column($summary, 'response_rate')) / count($summary), 1) : 0;
                    echo $avgRate;
                    ?>%
                </h3>
                <p class="card-text text-muted mb-0">Avg Response Rate</p>
            </div>
        </div>
    </div>
</div>

<?php foreach ($summary as $fieldSummary): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?= htmlspecialchars($fieldSummary['label']) ?></h5>
            <small class="text-muted"><?= ucfirst($fieldSummary['type']) ?> • <?= $fieldSummary['total_responses'] ?> responses (<?= $fieldSummary['response_rate'] ?>%)</small>
        </div>
        <div class="card-body">
            <?php if ($fieldSummary['type'] === 'rating' && isset($fieldSummary['average'])): ?>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Average Rating:</strong><br>
                        <span class="display-6 text-primary"><?= $fieldSummary['average'] ?></span> / 5
                    </div>
                    <div class="col-md-3">
                        <strong>Range:</strong><br>
                        <?= $fieldSummary['min'] ?> - <?= $fieldSummary['max'] ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Distribution:</strong><br>
                        <?php
                        $distribution = $fieldSummary['distribution'] ?? [];
                        for ($i = 1; $i <= 5; $i++):
                            $count = $distribution[$i] ?? 0;
                            $percentage = $fieldSummary['total_responses'] > 0 ? round(($count / $fieldSummary['total_responses']) * 100, 1) : 0;
                        ?>
                            <div class="d-flex align-items-center mb-1">
                                <span class="me-2" style="width: 30px;"><?= $i ?>:</span>
                                <div class="progress flex-grow-1" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%">
                                        <?= $count ?> (<?= $percentage ?>%)
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

            <?php elseif (isset($fieldSummary['responses']) && !empty($fieldSummary['responses'])): ?>
                <!-- Select, Radio, Checkbox responses -->
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Option</th>
                            <th>Count</th>
                            <th>Percentage</th>
                            <th>Visual</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($fieldSummary['responses'] as $option => $count): ?>
                            <?php
                            $percentage = $fieldSummary['total_responses'] > 0 ? round(($count / $fieldSummary['total_responses']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($option) ?></strong></td>
                                <td><?= $count ?></td>
                                <td><?= $percentage ?>%</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%">
                                            <?= $percentage ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif (isset($fieldSummary['sample_responses']) && !empty($fieldSummary['sample_responses'])): ?>
                <!-- Text responses - show sample -->
                <div>
                    <strong>Sample Responses (showing first <?= count($fieldSummary['sample_responses']) ?>):</strong>
                    <ul class="list-group mt-2">
                        <?php foreach ($fieldSummary['sample_responses'] as $response): ?>
                            <li class="list-group-item"><?= htmlspecialchars($response) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($fieldSummary['total_responses'] > count($fieldSummary['sample_responses'])): ?>
                        <p class="text-muted mt-2">
                            <small>Showing <?= count($fieldSummary['sample_responses']) ?> of <?= $fieldSummary['total_responses'] ?> responses. 
                            <a href="/forms/submissions?id=<?= $form['id'] ?>">View all submissions</a> for complete data.</small>
                        </p>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <p class="text-muted">No responses yet for this field.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php if (empty($summary)): ?>
    <div class="alert alert-info">
        No fields defined in this form yet.
    </div>
<?php endif; ?>
