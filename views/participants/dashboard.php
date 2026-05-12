<?php
// Participants dashboard
$currentFilter = $_GET['filter'] ?? 'all';
$stats = $stats ?? [
    'total' => 0,
    'checked_in' => 0,
    'not_checked_in' => 0,
    'groups' => 0,
    'faculty_distribution' => [],
    'language_distribution' => [],
    'group_distribution' => [],
];
$recentParticipants = $recentParticipants ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Participants Dashboard</h2>
    <div>
        <a href="/participants/create" class="btn btn-primary btn-sm">Pre-register</a>
        <a href="/participants/create-walkin" class="btn btn-dark btn-sm">Walk-in Registration</a>
        <a href="/participants/checkin" class="btn btn-outline-secondary btn-sm">QR Check-in</a>
        <a href="/participants/groups" class="btn btn-outline-secondary btn-sm">Grouping Overview</a>
        <a href="/participants/list" class="btn btn-outline-primary btn-sm">View List</a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted mb-2">Total Participants</h5>
                <h2 class="mb-0"><?= $stats['total'] ?? 0 ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted mb-2">Checked In</h5>
                <h2 class="mb-0 text-success"><?= $stats['checked_in'] ?? 0 ?></h2>
                <small class="text-muted"><?= $stats['total'] > 0 ? number_format(($stats['checked_in'] / $stats['total']) * 100, 1) : 0 ?>%</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted mb-2">Not Checked In</h5>
                <h2 class="mb-0 text-warning"><?= $stats['not_checked_in'] ?? 0 ?></h2>
                <small class="text-muted"><?= $stats['total'] > 0 ? number_format(($stats['not_checked_in'] / $stats['total']) * 100, 1) : 0 ?>%</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted mb-2">Groups</h5>
                <h2 class="mb-0 text-info"><?= $stats['groups'] ?? 0 ?></h2>
                <small class="text-muted">Active groups</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Distribution -->
<div class="row mb-4">
    <!-- Faculty Distribution -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Faculty Distribution</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['faculty_distribution'])): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Faculty</th>
                                <th class="text-end">Count</th>
                                <th class="text-end">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['faculty_distribution'] as $faculty => $count): ?>
                                <tr>
                                    <td><?= htmlspecialchars($faculty ?: 'Not Specified') ?></td>
                                    <td class="text-end"><?= $count ?></td>
                                    <td class="text-end"><?= $stats['total'] > 0 ? number_format(($count / $stats['total']) * 100, 1) : 0 ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted mb-0">No faculty data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Language Distribution -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Language Distribution</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['language_distribution'])): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Language</th>
                                <th class="text-end">Count</th>
                                <th class="text-end">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['language_distribution'] as $language => $count): ?>
                                <tr>
                                    <td><?= htmlspecialchars($language ?: 'Not Specified') ?></td>
                                    <td class="text-end"><?= $count ?></td>
                                    <td class="text-end"><?= $stats['total'] > 0 ? number_format(($count / $stats['total']) * 100, 1) : 0 ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted mb-0">No language data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Group Statistics -->
<?php if (!empty($stats['group_distribution'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Group Distribution</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($stats['group_distribution'] as $group => $count): ?>
                        <div class="col-md-2 mb-2">
                            <div class="text-center p-2 border rounded">
                                <strong><?= htmlspecialchars($group ?: 'Ungrouped') ?></strong>
                                <div class="h4 mb-0"><?= $count ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Registrations -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Registrations</h5>
                <a href="/participants/list" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentParticipants)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Intake</th>
                                    <th>Faculty</th>
                                    <th>Registration</th>
                                    <th>Group</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentParticipants as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['full_name']) ?></td>
                                        <td><?= htmlspecialchars($p['student_id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['intake'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['faculty'] ?? '-') ?></td>
                                        <td>
                                            <?php if (($p['registration_type'] ?? 'pre_register') === 'walk_in'): ?>
                                                <span class="badge bg-dark">Walk-in</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pre-register</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($p['group_code'] ?? '-') ?></td>
                                        <td>
                                            <?php if (!empty($p['checked_in_at'])): ?>
                                                <span class="badge bg-success">Checked In</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Not Checked In</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No recent registrations</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
