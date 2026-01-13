<?php
// Grouping overview with auto-assignment
$message = $_SESSION['grouping_message'] ?? null;
$messageType = $_SESSION['grouping_message_type'] ?? 'info';
if (isset($_SESSION['grouping_message'])) {
    unset($_SESSION['grouping_message'], $_SESSION['grouping_message_type']);
}
?>
<h2>Grouping Overview</h2>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Grouping Options</h5>
                <div class="row">
                    <div class="col-md-4">
                        <h6>Round-Robin (Random)</h6>
                        <p class="small text-muted">Assign participants evenly across groups regardless of attributes.</p>
                        <form method="post" action="/participants/auto-group" class="mb-2">
                            <div class="input-group">
                                <label class="input-group-text" for="num_groups">Groups:</label>
                                <input type="number" name="num_groups" id="num_groups" class="form-control form-control-sm" value="8" min="1" max="26" required>
                                <button type="submit" class="btn btn-primary btn-sm">Round-Robin</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <h6>Group by Faculty</h6>
                        <p class="small text-muted">Group participants by faculty, then distribute evenly within each faculty.</p>
                        <form method="post" action="/participants/group-by-faculty" class="mb-2">
                            <div class="input-group">
                                <label class="input-group-text" for="num_groups_faculty">Groups:</label>
                                <input type="number" name="num_groups" id="num_groups_faculty" class="form-control form-control-sm" value="8" min="1" max="26" required>
                                <button type="submit" class="btn btn-success btn-sm">By Faculty</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <h6>Group by Language</h6>
                        <p class="small text-muted">Group participants by preferred language, then distribute evenly within each language.</p>
                        <form method="post" action="/participants/group-by-language" class="mb-2">
                            <div class="input-group">
                                <label class="input-group-text" for="num_groups_lang">Groups:</label>
                                <input type="number" name="num_groups" id="num_groups_lang" class="form-control form-control-sm" value="8" min="1" max="26" required>
                                <button type="submit" class="btn btn-info btn-sm">By Language</button>
                            </div>
                        </form>
                    </div>
                </div>
                <hr>
                <form method="post" action="/participants/clear-groups" class="d-inline" onsubmit="return confirm('Are you sure you want to clear all group assignments?');">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Clear All Groups</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Summary</h5>
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Total Groups:</strong> <?= count($groups) ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Ungrouped Participants:</strong> <?= (int)$ungrouped ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-0"><strong>Total Grouped:</strong> <?= array_sum(array_column($groups, 'count')) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <h4>Group Distribution</h4>
        <table class="table table-sm table-striped">
            <thead>
            <tr>
                <th>Group</th>
                <th>Count</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($groups)): ?>
                <tr>
                    <td colspan="2" class="text-muted">No groups assigned yet</td>
                </tr>
            <?php else: ?>
                <?php foreach ($groups as $g): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($g['group_code']) ?></strong></td>
                        <td><?= (int)$g['count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-8">
        <h4>Participants by Group</h4>
        <?php if (empty($participantsByGroup)): ?>
            <p class="text-muted">No participants assigned to groups yet. Use the auto-assign function above.</p>
        <?php else: ?>
            <?php foreach ($participantsByGroup as $groupCode => $participants): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <strong>Group <?= htmlspecialchars($groupCode) ?></strong> (<?= count($participants) ?> participants)
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $counter = 1; foreach ($participants as $p): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($p['full_name']) ?></td>
                                        <td><?= htmlspecialchars($p['student_id'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

