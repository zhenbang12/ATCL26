<?php
// Finance module landing page
use App\Core\Container;
$db = Container::get('db');

// Get quick statistics
$stats = [];

// Total claims
$stmt = $db->query("SELECT COUNT(*) as total, 
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status IN ('submitted', 'verified') THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts
    FROM claims");
$claimStats = $stmt->fetch(\PDO::FETCH_ASSOC);
$stats['claims'] = [
    'total' => (int)($claimStats['total'] ?? 0),
    'approved' => (int)($claimStats['approved'] ?? 0),
    'pending' => (int)($claimStats['pending'] ?? 0),
    'drafts' => (int)($claimStats['drafts'] ?? 0)
];

// Total buying requests
$stmt = $db->query("SELECT COUNT(*) as total, 
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts
    FROM buying_requests");
$requestStats = $stmt->fetch(\PDO::FETCH_ASSOC);
$stats['requests'] = [
    'total' => (int)($requestStats['total'] ?? 0),
    'approved' => (int)($requestStats['approved'] ?? 0),
    'pending' => (int)($requestStats['pending'] ?? 0),
    'drafts' => (int)($requestStats['drafts'] ?? 0)
];
?>

<div class="mb-4">
    <h2 class="mb-2">Finance & Procurement</h2>
    <p class="text-muted mb-0">Manage claims, budgets, vendors and payments.</p>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-2">Total Claims</h6>
                <h3 class="mb-0"><?= $stats['claims']['total'] ?></h3>
                <small class="text-muted">
                    <?= $stats['claims']['approved'] ?> approved, 
                    <?= $stats['claims']['pending'] ?> pending
                    <?php if ($stats['claims']['drafts'] > 0): ?>
                        , <?= $stats['claims']['drafts'] ?> drafts
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-2">Buying Requests</h6>
                <h3 class="mb-0"><?= $stats['requests']['total'] ?></h3>
                <small class="text-muted">
                    <?= $stats['requests']['approved'] ?> approved, 
                    <?= $stats['requests']['pending'] ?> pending
                    <?php if ($stats['requests']['drafts'] > 0): ?>
                        , <?= $stats['requests']['drafts'] ?> drafts
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-2">Quick Actions</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="/finance/claims" class="btn btn-sm btn-outline-primary">View Claims</a>
                    <a href="/finance/buying-requests" class="btn btn-sm btn-outline-success">View Requests</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Actions -->
<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card h-100 border">
            <div class="card-body">
                <h5 class="card-title">Claims</h5>
                <p class="card-text text-muted small mb-3">
                    Submit → Verify → Approve → Pay
                </p>
                <a href="/finance/claims" class="btn btn-primary btn-sm">Manage Claims</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100 border">
            <div class="card-body">
                <h5 class="card-title">Buying Requests</h5>
                <p class="card-text text-muted small mb-3">
                    Purchase requests before buying
                </p>
                <a href="/finance/buying-requests" class="btn btn-success btn-sm">Manage Requests</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100 border">
            <div class="card-body">
                <h5 class="card-title">Budget Dashboard</h5>
                <p class="card-text text-muted small mb-3">
                    Allocated vs Spent
                </p>
                <a href="/finance/budget" class="btn btn-info btn-sm">View Dashboard</a>
            </div>
        </div>
    </div>
</div>
