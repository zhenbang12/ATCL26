<?php
// Unified Participants Listing and Insights Console
$currentFilter = $currentFilter ?? 'all';
$registrationSettings = $registrationSettings ?? [
    'pre_register_enabled' => true,
    'walk_in_enabled' => true,
];
$participantsMessage = $_SESSION['participants_message'] ?? null;
$participantsMessageType = $_SESSION['participants_message_type'] ?? 'info';
if (isset($_SESSION['participants_message'])) {
    unset($_SESSION['participants_message'], $_SESSION['participants_message_type']);
}

$stats = $stats ?? [
    'total' => 0,
    'checked_in' => 0,
    'not_checked_in' => 0,
    'groups' => 0,
    'faculty_distribution' => [],
    'language_distribution' => [],
    'group_distribution' => [],
];
?>

<?php if ($participantsMessage): ?>
    <div class="alert alert-<?= $participantsMessageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($participantsMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Title and Top Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Participants Console</h2>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($registrationSettings['pre_register_enabled'] || \App\Core\Auth::check()): ?>
            <a href="/participants/create" class="btn btn-primary btn-sm">Pre-register</a>
        <?php endif; ?>
        <a href="/participants/create-walkin" class="btn btn-dark btn-sm">Walk-in Registration</a>
        <a href="/participants/export?filter=<?= urlencode($currentFilter) ?>" class="btn btn-success btn-sm">Export CSV</a>
        <a href="/participants/duplicates" class="btn btn-outline-warning btn-sm" style="border-radius: 100px;">
            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">content_copy</span> Duplicates
        </a>
    </div>
</div>

<!-- Compact Statistics Banner -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Total Participants</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-primary) !important;"><?= $stats['total'] ?? 0 ?></h2>
            <small class="text-muted" style="font-size: 0.72rem;">Registered participants</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Checked In</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-success) !important;"><?= $stats['checked_in'] ?? 0 ?></h2>
            <small class="text-muted" style="font-size: 0.72rem;"><?= $stats['total'] > 0 ? number_format(($stats['checked_in'] / $stats['total']) * 100, 1) : 0 ?>% of total</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Pending Check-In</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-tertiary) !important;"><?= $stats['not_checked_in'] ?? 0 ?></h2>
            <small class="text-muted" style="font-size: 0.72rem;"><?= $stats['total'] > 0 ? number_format(($stats['not_checked_in'] / $stats['total']) * 100, 1) : 0 ?>% remaining</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Groups</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-on-surface) !important;"><?= $stats['groups'] ?? 0 ?></h2>
            <small class="text-muted" style="font-size: 0.72rem;">Active group pools</small>
        </div>
    </div>
</div>

<!-- Collapsible distributions and analytics -->
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#distributionCollapse" aria-expanded="false" aria-controls="distributionCollapse" id="toggle-insights-btn">
        <span class="material-symbols-outlined" style="font-size: 18px;">analytics</span>
        Show Distributions & Insights
    </button>
</div>

<div class="collapse mb-4" id="distributionCollapse">
    <div class="card p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important;">
        <h4 class="h6 fw-bold mb-3 d-flex align-items-center gap-1" style="color: var(--md-sys-color-on-surface);">
            <span class="material-symbols-outlined text-primary" style="font-size: 20px;">query_stats</span>
            Participant Distribution Analytics
        </h4>
        
        <div class="row g-3">
            <!-- Faculty Distribution -->
            <div class="col-md-6">
                <div class="card h-100 p-3 border-0" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container) !important;">
                    <h5 class="card-title fw-bold mb-2 small" style="color: var(--md-sys-color-on-surface-variant);">Faculty Distribution</h5>
                    <?php if (!empty($stats['faculty_distribution'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0 small">
                                <thead>
                                    <tr class="border-bottom" style="font-size: 0.8rem;">
                                        <th class="py-1">Faculty</th>
                                        <th class="text-end py-1">Count</th>
                                        <th class="text-end py-1">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['faculty_distribution'] as $faculty => $count): ?>
                                        <tr>
                                            <td class="py-1 fw-semibold"><?= htmlspecialchars($faculty ?: 'Not Specified') ?></td>
                                            <td class="text-end py-1"><?= $count ?></td>
                                            <td class="text-end py-1 text-muted"><?= $stats['total'] > 0 ? number_format(($count / $stats['total']) * 100, 1) : 0 ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0 small">No faculty data available</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Language Distribution -->
            <div class="col-md-6">
                <div class="card h-100 p-3 border-0" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container) !important;">
                    <h5 class="card-title fw-bold mb-2 small" style="color: var(--md-sys-color-on-surface-variant);">Language Distribution</h5>
                    <?php if (!empty($stats['language_distribution'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0 small">
                                <thead>
                                    <tr class="border-bottom" style="font-size: 0.8rem;">
                                        <th class="py-1">Language</th>
                                        <th class="text-end py-1">Count</th>
                                        <th class="text-end py-1">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['language_distribution'] as $language => $count): ?>
                                        <tr>
                                            <td class="py-1 fw-semibold"><?= htmlspecialchars($language ?: 'Not Specified') ?></td>
                                            <td class="text-end py-1"><?= $count ?></td>
                                            <td class="text-end py-1 text-muted"><?= $stats['total'] > 0 ? number_format(($count / $stats['total']) * 100, 1) : 0 ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0 small">No language data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Group Distribution -->
        <?php if (!empty($stats['group_distribution'])): ?>
            <div class="mt-3">
                <h5 class="fw-bold mb-2 small" style="color: var(--md-sys-color-on-surface-variant);">Group Code Distribution</h5>
                <div class="row g-2">
                    <?php foreach ($stats['group_distribution'] as $group => $count): ?>
                        <div class="col-6 col-sm-4 col-md-2">
                            <div class="text-center p-2 rounded" style="background-color: var(--md-sys-color-surface-container-high); border: 1px solid var(--md-sys-color-outline-variant);">
                                <div class="small fw-semibold" style="font-size: 0.75rem;"><?= htmlspecialchars($group ? 'Group ' . $group : 'Ungrouped') ?></div>
                                <div class="h5 mb-0 fw-bold text-primary"><?= $count ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Participant List and Filter buttons -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="btn-group" role="group">
        <a href="/participants?filter=all" class="btn btn-sm <?= $currentFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
            All
        </a>
        <a href="/participants?filter=checked_in" class="btn btn-sm <?= $currentFilter === 'checked_in' ? 'btn-primary' : 'btn-outline-primary' ?>">
            Checked In
        </a>
        <a href="/participants?filter=not_checked_in" class="btn btn-sm <?= $currentFilter === 'not_checked_in' ? 'btn-primary' : 'btn-outline-primary' ?>">
            Not Checked In
        </a>
    </div>
</div>

<div class="card p-3 border-0" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important;">
    <div class="table-responsive">
        <table id="participants-table" class="table table-sm table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Student ID</th>
                <th>Email</th>
                <th>Programme</th>
                <th>Faculty</th>
                <th>Phone</th>
                <th>Language</th>
                <th>Registration</th>
                <th>Group</th>
                <th>Checked in?</th>
                <?php if (\App\Core\Auth::check()): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#participants-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/participants/data?filter=<?= urlencode($currentFilter) ?>',
                type: 'GET'
            },
            pageLength: 25,
            order: [[1, 'asc']], // Sort by Name column by default
            columnDefs: [
                { orderable: false, targets: 0 }, // Counter is not orderable
                <?php if (\App\Core\Auth::check()): ?>
                { orderable: false, targets: 11 } // Actions are not orderable
                <?php endif; ?>
            ],
            language: {
                search: "Search participants:",
                lengthMenu: "Show _MENU_ participants per page",
                info: "Showing _START_ to _END_ of _TOTAL_ participants",
                infoEmpty: "No participants found",
                infoFiltered: "(filtered from _MAX_ total participants)"
            }
        });

        // Toggle button text for Collapsible Insights
        const toggleBtn = document.getElementById('toggle-insights-btn');
        const collapseEl = document.getElementById('distributionCollapse');
        if (toggleBtn && collapseEl) {
            collapseEl.addEventListener('show.bs.collapse', function () {
                toggleBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 18px;">analytics</span> Hide Distributions & Insights';
            });
            collapseEl.addEventListener('hide.bs.collapse', function () {
                toggleBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 18px;">analytics</span> Show Distributions & Insights';
            });
        }
    });
</script>
