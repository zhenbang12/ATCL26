<?php
/**
 * @var array<string, int> $stats
 * @var array<int, array<string, mixed>> $sampleParticipants
 * @var bool $isDemoModeActive
 * @var array|null $currentUser
 * @var string|null $flash
 * @var string $flashType
 */
?>

<style>
.demo-hero {
    background: linear-gradient(135deg, var(--md-sys-color-primary-container) 0%, var(--md-sys-color-surface-container-high) 100%);
    border-radius: 28px;
    padding: clamp(2rem, 5vw, 3.5rem);
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px var(--md-sys-color-shadow);
}
.demo-hero::after {
    content: "";
    position: absolute;
    top: -50%;
    right: -20%;
    width: 450px;
    height: 450px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
    pointer-events: none;
}
.demo-card {
    transition: all 0.25s cubic-bezier(0.2, 0, 0, 1);
    border-radius: 20px !important;
    border: 1px solid var(--md-sys-color-outline-variant) !important;
    background-color: var(--md-sys-color-surface-container-low) !important;
}
.demo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px var(--md-sys-color-shadow) !important;
    border-color: var(--md-sys-color-primary) !important;
}
.demo-icon-bubble {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
}
.demo-pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
}
.demo-stat-badge {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(8px);
    border-radius: 14px;
    padding: 0.75rem 1.25rem;
    display: inline-flex;
    flex-direction: column;
    min-width: 130px;
}
.copy-pill {
    cursor: pointer;
    transition: background 0.15s;
    user-select: all;
}
.copy-pill:hover {
    filter: brightness(0.95);
}
</style>

<div class="container py-4" style="max-width: 1140px;">
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show rounded-4 mb-4 shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
            <span class="material-symbols-outlined"><?= $flashType === 'success' ? 'check_circle' : 'info' ?></span>
            <div class="flex-grow-1"><?= htmlspecialchars($flash) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Hero Header -->
    <div class="demo-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="demo-pill-badge" style="background-color: var(--md-sys-color-primary); color: var(--md-sys-color-on-primary);">
                        <span class="material-symbols-outlined" style="font-size: 16px;">science</span>
                        Interactive Demonstration
                    </span>
                    <span class="demo-pill-badge" style="background: rgba(0,0,0,0.06); color: var(--md-sys-color-on-surface-variant);">
                        <span class="material-symbols-outlined" style="font-size: 16px;">shield</span>
                        Isolated Sandbox
                    </span>
                    <?php if ($isDemoModeActive): ?>
                        <span class="demo-pill-badge bg-success text-white">
                            <span class="material-symbols-outlined" style="font-size: 16px;">fiber_manual_record</span>
                            Active Now
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="display-6 fw-bold mb-3" style="color: var(--md-sys-color-on-primary-container);">
                    ATCL 2026 Demonstration Portal
                </h1>
                <p class="lead mb-4" style="color: var(--md-sys-color-on-surface-variant); max-width: 650px; font-size: 1.05rem; line-height: 1.6;">
                    Explore and present all capabilities of the Camp Management System using this dedicated demo entry. 
                    Includes realistic sample participants, groups, and senior buddies with <strong>zero risk</strong> to live event records.
                </p>

                <!-- Sandbox Live Stats -->
                <div class="d-flex flex-wrap gap-2 pt-2">
                    <div class="demo-stat-badge shadow-sm">
                        <span class="small text-muted fw-semibold">Sample Campers</span>
                        <span class="fs-4 fw-bold" style="color: var(--md-sys-color-primary);"><?= (int)$stats['total_participants'] ?></span>
                    </div>
                    <div class="demo-stat-badge shadow-sm">
                        <span class="small text-muted fw-semibold">Checked-In</span>
                        <span class="fs-4 fw-bold text-success"><?= (int)$stats['checked_in'] ?> <small class="fs-6 fw-normal text-muted">(<?= $stats['total_participants'] > 0 ? round(($stats['checked_in'] / $stats['total_participants']) * 100) : 0 ?>%)</small></span>
                    </div>
                    <div class="demo-stat-badge shadow-sm">
                        <span class="small text-muted fw-semibold">Event Groups</span>
                        <span class="fs-4 fw-bold" style="color: var(--md-sys-color-tertiary);"><?= (int)$stats['groups_count'] ?></span>
                    </div>
                    <div class="demo-stat-badge shadow-sm">
                        <span class="small text-muted fw-semibold">Senior Buddies</span>
                        <span class="fs-4 fw-bold text-secondary"><?= (int)$stats['crew_count'] ?></span>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 text-center d-none d-lg-block">
                <span class="material-symbols-outlined text-primary" style="font-size: 160px; opacity: 0.85;">dashboard_customize</span>
            </div>
        </div>
    </div>

    <!-- Section: 1-Click Role Launchers -->
    <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: var(--md-sys-color-on-background);">Choose Your Experience</h2>
                <p class="text-muted small mb-0">Select a role below to launch directly into the system with 1-click access</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Card 1: Committee Member -->
            <div class="col-md-4">
                <div class="card h-100 demo-card p-4 shadow-sm">
                    <div class="card-body d-flex flex-column p-0">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="demo-icon-bubble" style="background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container);">
                                <span class="material-symbols-outlined" style="font-size: 32px;">group_work</span>
                            </div>
                            <span class="badge rounded-pill px-3 py-2" style="background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); font-size: 0.75rem;">
                                Operational
                            </span>
                        </div>

                        <h3 class="h5 fw-bold mb-2">Committee Member</h3>
                        <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.5;">
                            Full access to manage camp participants, run auto-grouping, operate the QR check-in scanner, and assign Senior Buddies.
                        </p>

                        <ul class="list-unstyled small text-muted mb-4">
                            <li class="d-flex align-items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 18px;">check</span>
                                Interactive Grouping &amp; Auto-allocation
                            </li>
                            <li class="d-flex align-items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 18px;">check</span>
                                Live QR scanner &amp; Walk-in check-in
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 18px;">check</span>
                                Senior Buddies &amp; Facilitators
                            </li>
                        </ul>

                        <form method="post" action="/demo/login" class="mt-auto">
                            <input type="hidden" name="role" value="committee">
                            <input type="hidden" name="target" value="/dashboard">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm">
                                <span>Enter as Committee</span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Card 2: Advisor -->
            <div class="col-md-4">
                <div class="card h-100 demo-card p-4 shadow-sm">
                    <div class="card-body d-flex flex-column p-0">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="demo-icon-bubble" style="background-color: var(--md-sys-color-tertiary-container); color: var(--md-sys-color-on-tertiary-container);">
                                <span class="material-symbols-outlined" style="font-size: 32px;">analytics</span>
                            </div>
                            <span class="badge rounded-pill px-3 py-2" style="background-color: var(--md-sys-color-tertiary-container); color: var(--md-sys-color-on-tertiary-container); font-size: 0.75rem;">
                                Executive
                            </span>
                        </div>

                        <h3 class="h5 fw-bold mb-2">Camp Advisor</h3>
                        <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.5;">
                            High-level overview with executive stats, demographic breakdown graphs, audit logs, and theme appearance controls.
                        </p>

                        <ul class="list-unstyled small text-muted mb-4">
                            <li class="d-flex align-items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 18px;">check</span>
                                Insights &amp; Faculty Demographic Charts
                            </li>
                            <li class="d-flex align-items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 18px;">check</span>
                                System Audit Trail &amp; Anomaly Logs
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 18px;">check</span>
                                Material 3 Dynamic Theme Switching
                            </li>
                        </ul>

                        <form method="post" action="/demo/login" class="mt-auto">
                            <input type="hidden" name="role" value="advisor">
                            <input type="hidden" name="target" value="/dashboard">
                            <button type="submit" class="btn btn-outline-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 rounded-3">
                                <span>Enter as Advisor</span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Card 3: Participant View -->
            <div class="col-md-4">
                <div class="card h-100 demo-card p-4 shadow-sm">
                    <div class="card-body d-flex flex-column p-0">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="demo-icon-bubble" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container);">
                                <span class="material-symbols-outlined" style="font-size: 32px;">how_to_reg</span>
                            </div>
                            <span class="badge rounded-pill px-3 py-2" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); font-size: 0.75rem;">
                                Public Camper
                            </span>
                        </div>

                        <h3 class="h5 fw-bold mb-2">Participant Experience</h3>
                        <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.5;">
                            Test the public journey from the student perspective: online registration, instant QR badge generation, and QR lookup.
                        </p>

                        <ul class="list-unstyled small text-muted mb-4">
                            <li class="d-flex align-items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 18px;">check</span>
                                Responsive student registration form
                            </li>
                            <li class="d-flex align-items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 18px;">check</span>
                                Instant personal check-in QR badge
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="material-symbols-outlined text-success" style="font-size: 18px;">check</span>
                                "Find My QR" student retrieval portal
                            </li>
                        </ul>

                        <a href="/demo/participant?target=/participants/create" class="btn btn-outline-secondary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 rounded-3 mt-auto">
                            <span>Test Camper Registration</span>
                            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Key Demonstration Scenarios (Direct Shortcuts) -->
    <div class="card p-4 border-0 mb-5 shadow-sm" style="background-color: var(--md-sys-color-surface-container); border-radius: 24px;">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div>
                    <h3 class="h5 fw-bold mb-1">Key Demonstration Scenarios</h3>
                    <p class="text-muted small mb-0">Jump directly to specific features during your presentation or evaluation</p>
                </div>
            </div>

            <div class="row g-3">
                <!-- Scenario 1: Grouping Board -->
                <div class="col-sm-6 col-lg-4">
                    <form method="post" action="/demo/login" class="h-100">
                        <input type="hidden" name="role" value="committee">
                        <input type="hidden" name="target" value="/participants/groups">
                        <button type="submit" class="btn w-100 h-100 p-3 text-start bg-white rounded-3 border-0 shadow-sm d-flex align-items-center gap-3">
                            <span class="material-symbols-outlined text-primary" style="font-size: 28px;">drag_indicator</span>
                            <div>
                                <div class="fw-semibold text-dark">Visual Grouping Board</div>
                                <div class="text-muted small">Drag-and-drop &amp; auto-grouping</div>
                            </div>
                        </button>
                    </form>
                </div>

                <!-- Scenario 2: QR Scanner -->
                <div class="col-sm-6 col-lg-4">
                    <form method="post" action="/demo/login" class="h-100">
                        <input type="hidden" name="role" value="committee">
                        <input type="hidden" name="target" value="/participants/checkin">
                        <button type="submit" class="btn w-100 h-100 p-3 text-start bg-white rounded-3 border-0 shadow-sm d-flex align-items-center gap-3">
                            <span class="material-symbols-outlined text-success" style="font-size: 28px;">qr_code_scanner</span>
                            <div>
                                <div class="fw-semibold text-dark">QR Check-in Scanner</div>
                                <div class="text-muted small">Live counter check-in flow</div>
                            </div>
                        </button>
                    </form>
                </div>

                <!-- Scenario 3: Insights -->
                <div class="col-sm-6 col-lg-4">
                    <form method="post" action="/demo/login" class="h-100">
                        <input type="hidden" name="role" value="advisor">
                        <input type="hidden" name="target" value="/insights">
                        <button type="submit" class="btn w-100 h-100 p-3 text-start bg-white rounded-3 border-0 shadow-sm d-flex align-items-center gap-3">
                            <span class="material-symbols-outlined text-info" style="font-size: 28px;">insights</span>
                            <div>
                                <div class="fw-semibold text-dark">Demographic Insights</div>
                                <div class="text-muted small">Faculties, intakes &amp; attendance</div>
                            </div>
                        </button>
                    </form>
                </div>

                <!-- Scenario 4: Senior Buddies -->
                <div class="col-sm-6 col-lg-4">
                    <form method="post" action="/demo/login" class="h-100">
                        <input type="hidden" name="role" value="committee">
                        <input type="hidden" name="target" value="/participants/assign-buddy">
                        <button type="submit" class="btn w-100 h-100 p-3 text-start bg-white rounded-3 border-0 shadow-sm d-flex align-items-center gap-3">
                            <span class="material-symbols-outlined text-warning" style="font-size: 28px;">assignment_ind</span>
                            <div>
                                <div class="fw-semibold text-dark">Senior Buddy Assignment</div>
                                <div class="text-muted small">Pair facilitators to groups</div>
                            </div>
                        </button>
                    </form>
                </div>

                <!-- Scenario 5: Lost & Found -->
                <div class="col-sm-6 col-lg-4">
                    <form method="post" action="/demo/login" class="h-100">
                        <input type="hidden" name="role" value="committee">
                        <input type="hidden" name="target" value="/lost-and-found">
                        <button type="submit" class="btn w-100 h-100 p-3 text-start bg-white rounded-3 border-0 shadow-sm d-flex align-items-center gap-3">
                            <span class="material-symbols-outlined text-danger" style="font-size: 28px;">search_check_2</span>
                            <div>
                                <div class="fw-semibold text-dark">Lost &amp; Found Portal</div>
                                <div class="text-muted small">Item logging &amp; claim verification</div>
                            </div>
                        </button>
                    </form>
                </div>

                <!-- Scenario 6: Theme Appearance -->
                <div class="col-sm-6 col-lg-4">
                    <form method="post" action="/demo/login" class="h-100">
                        <input type="hidden" name="role" value="advisor">
                        <input type="hidden" name="target" value="/settings/theme">
                        <button type="submit" class="btn w-100 h-100 p-3 text-start bg-white rounded-3 border-0 shadow-sm d-flex align-items-center gap-3">
                            <span class="material-symbols-outlined text-purple" style="font-size: 28px; color: #6750A4;">palette</span>
                            <div>
                                <div class="fw-semibold text-dark">Theme Customizer</div>
                                <div class="text-muted small">Live Material 3 color generator</div>
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Demonstrator Cheatsheet (Sample Student Records) -->
    <div class="card p-4 border-0 mb-5 shadow-sm" style="background-color: var(--md-sys-color-surface-container-low); border-radius: 24px;">
        <div class="card-body p-0">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div>
                    <h3 class="h5 fw-bold mb-1">Demonstration Cheatsheet</h3>
                    <p class="text-muted small mb-0">Click any Student ID below to copy it for testing Check-in or QR Lookup</p>
                </div>
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                    <span class="material-symbols-outlined align-middle" style="font-size: 14px;">content_copy</span>
                    Click to copy
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                    <thead>
                        <tr class="text-muted small border-bottom">
                            <th>Student ID</th>
                            <th>Participant Name</th>
                            <th>Faculty</th>
                            <th>Language</th>
                            <th>Assigned Group</th>
                            <th>Status / Suggested Test</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sampleParticipants)): ?>
                            <?php foreach ($sampleParticipants as $sp): ?>
                                <tr>
                                    <td>
                                        <code class="copy-pill bg-light text-primary px-2 py-1 rounded fw-bold border" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($sp['student_id']) ?>'); alert('Copied <?= htmlspecialchars($sp['student_id']) ?> to clipboard!');" title="Click to copy">
                                            <?= htmlspecialchars($sp['student_id']) ?>
                                        </code>
                                    </td>
                                    <td class="fw-semibold"><?= htmlspecialchars($sp['full_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sp['faculty']) ?></span></td>
                                    <td><?= htmlspecialchars($sp['preferred_language']) ?></td>
                                    <td>
                                        <?php if (!empty($sp['group_code'])): ?>
                                            <span class="badge bg-primary text-white"><?= htmlspecialchars($sp['group_code']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($sp['checked_in_at'])): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <span class="material-symbols-outlined align-middle" style="font-size: 14px;">check_circle</span>
                                                Checked In
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                                Ready to test Check-in
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No sample participants available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section: Sandbox Management & Reset Controls -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 p-4 rounded-4" style="background: var(--md-sys-color-surface-container); border: 1px dashed var(--md-sys-color-outline-variant);">
        <div>
            <h4 class="h6 fw-bold mb-1">Sandbox Environment Controls</h4>
            <p class="text-muted small mb-0">Reset sample records back to initial state, or exit demo mode to return to production.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="post" action="/demo/reset" onsubmit="return confirm('Are you sure you want to restore all demo participants, groups, and senior buddies to their default state?');">
                <button type="submit" class="btn btn-outline-warning text-dark bg-white border-1 fw-semibold d-flex align-items-center gap-1 shadow-sm px-3 py-2 rounded-3">
                    <span class="material-symbols-outlined" style="font-size: 18px;">restart_alt</span>
                    Reset Demo Data
                </button>
            </form>
            <a href="/demo/exit" class="btn btn-outline-secondary bg-white fw-semibold d-flex align-items-center gap-1 shadow-sm px-3 py-2 rounded-3 text-decoration-none">
                <span class="material-symbols-outlined" style="font-size: 18px;">logout</span>
                Exit Demo Mode
            </a>
        </div>
    </div>
</div>
