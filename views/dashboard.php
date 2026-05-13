<?php
// Standalone home page for logged-in committee/advisors.
/** @var array<string, mixed> $stats */
$stats = $stats ?? [];
/** @var array{pre_register_enabled: bool, walk_in_enabled: bool} $registrationSettings */
$registrationSettings = $registrationSettings ?? [
    'pre_register_enabled' => true,
    'walk_in_enabled' => true,
];

$registrationMessage = $_SESSION['registration_settings_message'] ?? null;
$registrationMessageType = $_SESSION['registration_settings_message_type'] ?? 'info';
if (isset($_SESSION['registration_settings_message'])) {
    unset($_SESSION['registration_settings_message'], $_SESSION['registration_settings_message_type']);
}

$modules = [
    ['title' => 'Participants', 'caption' => 'Registration, check-in, QR lookup, and grouping.', 'href' => '/participants'],
    ['title' => 'Finance', 'caption' => 'Claims, buying requests, budget, and approvals.', 'href' => '/finance'],
    ['title' => 'Forms', 'caption' => 'Build forms, collect responses, and review summaries.', 'href' => '/forms'],
    ['title' => 'Operations', 'caption' => 'Crew records, facilitators, and games coordination.', 'href' => '/operations'],
    ['title' => 'Logistics', 'caption' => 'Venues, equipment, and inventory planning.', 'href' => '/logistics'],
    ['title' => 'Governance', 'caption' => 'Proposals, decisions, and advisor review.', 'href' => '/governance'],
];
?>

<style>
    .staff-home {
        margin-top: -1rem;
    }
    .staff-hero {
        background: #101820;
        color: #fff;
        padding: 2rem;
        border-radius: 8px;
    }
    .staff-stat {
        border-left: 4px solid #0d6efd;
        background: #fff;
    }
    .module-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #e9ecef;
    }
    .module-row:last-child {
        border-bottom: 0;
    }
    @media (max-width: 575.98px) {
        .module-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="staff-home">
    <section class="staff-hero mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <p class="text-uppercase small mb-2" style="letter-spacing: .08em;">Advisor / Committee Home</p>
                <h1 class="h2 mb-2">ATCL Management System</h1>
                <p class="mb-0 text-white-50">
                    A focused workspace for event coordination, participant flow, finance, forms, and operations.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="/settings/landing" class="btn btn-light">Landing Settings</a>
                <a href="/public" class="btn btn-outline-light ms-2" target="_blank" rel="noopener">Preview Public Page</a>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="staff-stat p-3 border h-100">
                <div class="text-muted small text-uppercase">Participants</div>
                <div class="fs-3 fw-semibold"><?= $stats['participants']['total'] ?? 0 ?></div>
                <div class="small text-muted"><?= $stats['participants']['checked_in'] ?? 0 ?> checked in</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="staff-stat p-3 border h-100">
                <div class="text-muted small text-uppercase">Claims</div>
                <div class="fs-3 fw-semibold"><?= $stats['claims'] ?? 0 ?></div>
                <div class="small text-muted">Financial records</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="staff-stat p-3 border h-100">
                <div class="text-muted small text-uppercase">Buying Requests</div>
                <div class="fs-3 fw-semibold"><?= $stats['requests'] ?? 0 ?></div>
                <div class="small text-muted">Purchase workflow</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="staff-stat p-3 border h-100">
                <div class="text-muted small text-uppercase">Forms</div>
                <div class="fs-3 fw-semibold"><?= $stats['forms'] ?? 0 ?></div>
                <div class="small text-muted">Evaluation and surveys</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="border rounded p-4 bg-white">
                <h2 class="h5 mb-3">Modules</h2>
                <?php foreach ($modules as $module): ?>
                    <div class="module-row">
                        <div>
                            <h3 class="h6 mb-1"><?= htmlspecialchars($module['title']) ?></h3>
                            <p class="text-muted mb-0"><?= htmlspecialchars($module['caption']) ?></p>
                        </div>
                        <a href="<?= htmlspecialchars($module['href']) ?>" class="btn btn-outline-primary btn-sm">Open</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <?php if ($registrationMessage): ?>
                <div class="alert alert-<?= htmlspecialchars($registrationMessageType) ?> alert-dismissible fade show">
                    <?= htmlspecialchars($registrationMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="border rounded p-4 bg-white mb-4">
                <h2 class="h5 mb-3">Registration Controls</h2>
                <form method="post" action="/settings/registration/save">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="pre_register_enabled" value="1" id="pre_register_enabled" <?= $registrationSettings['pre_register_enabled'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pre_register_enabled">Show Pre-register</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="walk_in_enabled" value="1" id="walk_in_enabled" <?= $registrationSettings['walk_in_enabled'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="walk_in_enabled">Show Walk-in Register</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Save Registration Controls</button>
                </form>
            </div>

            <div class="border rounded p-4 bg-light">
                <h2 class="h5 mb-3">Quick Actions</h2>
                <div class="d-grid gap-2">
                    <a href="/participants/create-walkin" class="btn btn-primary">Add Walk-in Participant</a>
                    <a href="/participants/checkin" class="btn btn-outline-primary">Open QR Check-in</a>
                    <a href="/participants/groups" class="btn btn-outline-primary">Manage Groups</a>
                    <a href="/forms/create" class="btn btn-outline-primary">Create Form</a>
                    <a href="/finance/buying-requests" class="btn btn-outline-primary">Review Buying Requests</a>
                </div>
            </div>
        </div>
    </div>
</div>
