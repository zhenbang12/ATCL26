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
    ['title' => 'Participants List', 'caption' => 'View all registered participants, edit details, and export CSV data.', 'href' => '/participants'],
    ['title' => 'QR Check-in Scanner', 'caption' => 'Scan participant QR codes or search manually for immediate check-in.', 'href' => '/participants/checkin'],
    ['title' => 'Grouping Overview', 'caption' => 'Manage participant groups, auto-allocate lanes, and assign buddies.', 'href' => '/participants/groups'],
    ['title' => 'Duplicate Detection', 'caption' => 'Review and resolve duplicate registrations by email, phone, or name.', 'href' => '/participants/duplicates'],
];
?>

<style>
    .staff-home {
        margin-top: -1rem;
    }
    .staff-hero {
        background: var(--md-sys-color-primary-container) !important;
        color: var(--md-sys-color-on-primary-container) !important;
        padding: 3rem 2rem;
        border-radius: 28px;
    }
    .staff-stat {
        border: 1px solid var(--md-sys-color-outline-variant) !important;
        border-radius: 16px !important;
        background-color: var(--md-sys-color-surface-container-low) !important;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .staff-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px var(--md-sys-color-shadow) !important;
    }
    .module-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
    }
    .module-row:last-child {
        border-bottom: 0;
    }
    @media (max-width: 575.98px) {
        .module-row {
            grid-template-columns: 1fr;
        }
    }

    .cursor-pointer {
        cursor: pointer;
    }
</style>

<div class="staff-home">
    <section class="staff-hero mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <p class="text-uppercase small mb-2 fw-semibold" style="letter-spacing: .08em; color: var(--md-sys-color-on-primary-container);">Advisor / Committee Home</p>
                <h1 class="h2 mb-2 fw-bold" style="color: var(--md-sys-color-on-primary-container);">ATCL Management System</h1>
                <p class="mb-0 text-secondary" style="color: var(--md-sys-color-on-primary-container); opacity: 0.8;">
                    A focused workspace for participant flow, registration controls, check-in, and grouping.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="/settings/landing" class="btn btn-primary">Landing Settings</a>
                <a href="/public" class="btn btn-outline-primary ms-2" target="_blank" rel="noopener">Preview Public Page</a>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="staff-stat p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important;">Total Registered</div>
                <div class="fs-3 fw-bold text-primary" style="color: var(--md-sys-color-primary) !important;"><?= $stats['participants']['total'] ?? 0 ?></div>
                <div class="small text-muted">Registered for ATCL</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="staff-stat p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important;">Checked In</div>
                <div class="fs-3 fw-bold text-success" style="color: var(--md-sys-color-success) !important;"><?= $stats['participants']['checked_in'] ?? 0 ?></div>
                <div class="small text-muted">Checked in at desk</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="staff-stat p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important;">Pending Check-In</div>
                <div class="fs-3 fw-bold text-warning" style="color: var(--md-sys-color-tertiary) !important;"><?= max(0, ($stats['participants']['total'] ?? 0) - ($stats['participants']['checked_in'] ?? 0)) ?></div>
                <div class="small text-muted">Awaiting check-in</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card p-4">
                <h2 class="h5 mb-3 fw-bold">Modules</h2>
                <?php foreach ($modules as $module): ?>
                    <div class="module-row">
                        <div>
                            <h3 class="h6 mb-1 fw-semibold"><?= htmlspecialchars($module['title']) ?></h3>
                            <p class="text-muted mb-0 small"><?= htmlspecialchars($module['caption']) ?></p>
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

            <div class="card p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0 fw-bold">Registration Controls</h2>
                    <span id="regSaveStatus" class="badge rounded-pill d-none align-items-center gap-1" style="font-size: 0.75rem; padding: 0.25rem 0.75rem; background-color: var(--md-sys-color-primary-container) !important; color: var(--md-sys-color-on-primary-container) !important;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span>
                        Saved
                    </span>
                    <span id="regSaveLoading" class="badge rounded-pill d-none align-items-center gap-1 text-muted" style="font-size: 0.75rem; padding: 0.25rem 0.75rem; background-color: var(--md-sys-color-surface-container) !important;">
                        <span class="spinner-border spinner-border-sm" role="status" style="width: 10px; height: 10px;"></span>
                        Saving...
                    </span>
                </div>
                <form id="registrationSettingsForm" method="post" action="/settings/registration/save">
                    <input type="hidden" name="theme" id="regFormThemeVal" value="<?= htmlspecialchars($registrationSettings['theme'] ?? 'violet') ?>">
                    <label class="form-label d-block mb-2 fw-semibold text-muted small" style="letter-spacing: .5px; text-transform: uppercase;">Active registration mode</label>
                    <div class="btn-group w-100 mb-3" role="group" aria-label="Registration Mode Selection">
                        <input type="radio" class="btn-check" name="reg_mode" id="regModePreReg" value="pre_reg" <?= ($registrationSettings['pre_register_enabled'] && !$registrationSettings['walk_in_enabled']) || ($registrationSettings['pre_register_enabled'] && $registrationSettings['walk_in_enabled']) ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="regModePreReg">Pre-reg Active</label>

                        <input type="radio" class="btn-check" name="reg_mode" id="regModeWalkIn" value="walk_in" <?= (!$registrationSettings['pre_register_enabled'] && $registrationSettings['walk_in_enabled']) ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="regModeWalkIn">Walk-in Active</label>

                        <input type="radio" class="btn-check" name="reg_mode" id="regModeClosed" value="closed" <?= (!$registrationSettings['pre_register_enabled'] && !$registrationSettings['walk_in_enabled']) ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="regModeClosed">All Closed</label>
                    </div>
                    <button type="submit" id="saveRegBtn" class="btn btn-primary btn-sm w-100">Save Registration Controls</button>
                </form>
            </div>



            <div class="card p-4" style="background-color: var(--md-sys-color-surface-container) !important;">
                <h2 class="h5 mb-3 fw-bold">Quick Actions</h2>
                <div class="d-grid gap-2">
                    <a href="/participants/create-walkin" class="btn btn-primary">Add Walk-in Participant</a>
                    <a href="/participants/checkin" class="btn btn-outline-primary">Open QR Check-in</a>
                    <a href="/participants/groups" class="btn btn-outline-primary">Manage Groups</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationSettingsForm');
    const saveBtn = document.getElementById('saveRegBtn');
    const statusSaved = document.getElementById('regSaveStatus');
    const statusLoading = document.getElementById('regSaveLoading');

    if (form) {
        // Hide manual save button so we auto-save via AJAX
        if (saveBtn) {
            saveBtn.style.display = 'none';
        }

        const radios = form.querySelectorAll('input[type="radio"][name="reg_mode"]');
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Show loading indicator
                statusSaved.classList.add('d-none');
                statusSaved.classList.remove('d-flex');
                statusLoading.classList.remove('d-none');
                statusLoading.classList.add('d-flex');

                const formData = new FormData(form);
                
                fetch('/settings/registration/save?format=json', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Update window.currentRegMode dynamically
                    if (data.success) {
                        window.currentRegMode = (data.pre_register_enabled && !data.walk_in_enabled) ? 'pre_reg' : ((data.walk_in_enabled && !data.pre_register_enabled) ? 'walk_in' : 'closed');
                    }

                    // Show saved status
                    statusLoading.classList.add('d-none');
                    statusLoading.classList.remove('d-flex');
                    statusSaved.classList.remove('d-none');
                    statusSaved.classList.add('d-flex');
                    
                    // Auto-hide the saved badge after 2 seconds
                    setTimeout(() => {
                        statusSaved.style.opacity = '1';
                        (function fade() {
                            if ((statusSaved.style.opacity -= .1) < 0) {
                                statusSaved.classList.add('d-none');
                                statusSaved.classList.remove('d-flex');
                                statusSaved.style.opacity = '1';
                            } else {
                                requestAnimationFrame(fade);
                            }
                        })();
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error saving registration settings:', error);
                    statusLoading.classList.add('d-none');
                    statusLoading.classList.remove('d-flex');
                    alert('Failed to save registration settings. Please try again.');
                });
            });
        });
    }
});
</script>
