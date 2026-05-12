 <?php
// Home dashboard showing all modules
use App\Core\Container;
use App\Core\Auth;

$db = Container::get('db');
$stats = [];

if (Auth::check()) {
    // Get participant statistics
    $stmt = $db->query('SELECT COUNT(*) as total, 
        SUM(CASE WHEN checked_in_at IS NOT NULL THEN 1 ELSE 0 END) as checked_in
        FROM participants');
    $participantStats = $stmt->fetch(\PDO::FETCH_ASSOC);
    $stats['participants'] = [
        'total' => (int)($participantStats['total'] ?? 0),
        'checked_in' => (int)($participantStats['checked_in'] ?? 0)
    ];
    
    // Get finance statistics
    $stmt = $db->query("SELECT COUNT(*) as total FROM claims");
    $stats['claims'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM buying_requests");
    $stats['requests'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM forms");
    $stats['forms'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['total'];
}
?>

<div class="mb-5">
    <h1 class="mb-2">ATCL Management System</h1>
    <p class="text-muted mb-0">
        -
    </p>
</div>

<?php if (Auth::check()): ?>
<!-- Quick Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-2">Total Participants</h6>
                <h3 class="mb-0"><?= $stats['participants']['total'] ?? 0 ?></h3>
                <small class="text-muted"><?= $stats['participants']['checked_in'] ?? 0 ?> checked in</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-2">Total Claims</h6>
                <h3 class="mb-0"><?= $stats['claims'] ?? 0 ?></h3>
                <small class="text-muted">Financial claims</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-2">Buying Requests</h6>
                <h3 class="mb-0"><?= $stats['requests'] ?? 0 ?></h3>
                <small class="text-muted">Purchase requests</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-2">Active Forms</h6>
                <h3 class="mb-0"><?= $stats['forms'] ?? 0 ?></h3>
                <small class="text-muted">Evaluation forms</small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Module Cards -->
<div class="row g-4">
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-3">Participants & Admission</h5>
                <p class="card-text text-muted mb-4">
                    Registration, QR check-in, grouping logic, medical/dietary pop-ups, and feedback.
                </p>
                <a href="/participants" class="btn btn-primary btn-sm">Open Module</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-3">Finance & Procurement</h5>
                <p class="card-text text-muted mb-4">
                    Claims workflow, budget tracking, vendor records, and batch payments.
                </p>
                <a href="/finance" class="btn btn-primary btn-sm">Open Module</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-3">Forms & Evaluations</h5>
                <p class="card-text text-muted mb-4">
                    Create and manage evaluation forms, surveys, and feedback forms for participants, crew, and committee.
                </p>
                <a href="/forms" class="btn btn-primary btn-sm">Open Module</a>
            </div>
        </div>
    </div>
</div>
