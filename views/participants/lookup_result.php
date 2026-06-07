<?php
// Result page for QR lookup
$lookupFrom = ($lookupFrom ?? 'pre-reg') === 'walk-in' ? 'walk-in' : 'pre-reg';
$registrationHref = $lookupFrom === 'walk-in' ? '/participants/create-walkin' : '/participants/create';
$registrationLabel = $lookupFrom === 'walk-in' ? 'Back to walk-in' : 'Back to pre-reg';
?>
<div class="mx-auto" style="max-width: 640px;">
    <h2 class="mb-3 fw-bold" style="color: var(--md-sys-color-on-surface);">
        <span class="material-symbols-outlined" style="font-size: 28px; vertical-align: text-bottom; color: var(--md-sys-color-primary);">qr_code</span>
        Find My QR Code
    </h2>

    <?php if (!empty($participant) && !empty($qrImage)): ?>
        <!-- Participant Info Card -->
        <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="material-symbols-outlined" style="font-size: 40px; color: var(--md-sys-color-primary);">person</span>
                <div>
                    <div class="fw-bold" style="font-size: 1.2rem; color: var(--md-sys-color-on-surface);">
                        <?= htmlspecialchars($participant['full_name']) ?>
                    </div>
                    <div class="text-muted" style="font-size: 0.85rem;">
                        <?= htmlspecialchars($participant['student_id']) ?>
                    </div>
                </div>
            </div>
            <div style="font-size: 0.95rem; line-height: 1.8; color: var(--md-sys-color-on-surface-variant);">
                <div class="mb-1">
                    <strong>Email:</strong> <?= htmlspecialchars($participant['student_email'] ?? 'N/A') ?>
                </div>
                <div class="mb-1">
                    <strong>Faculty:</strong> <?= htmlspecialchars($participant['faculty'] ?? 'N/A') ?>
                </div>
                <div class="mb-1">
                    <strong>Programme:</strong> <?= htmlspecialchars($participant['programme_name'] ?? 'N/A') ?>
                </div>
                <div>
                    <strong>Preferred Grouping Language:</strong>
                    <span class="badge" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container);">
                        <?= htmlspecialchars($participant['preferred_language'] ?? 'N/A') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Check-in Status -->
        <?php if (!empty($participant['checked_in_at'])): ?>
            <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-success-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-success-container) !important;">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="material-symbols-outlined" style="font-size: 36px;">check_circle</span>
                    <div>
                        <div class="fw-bold" style="font-size: 1.2rem;">Checked In</div>
                    </div>
                </div>
                <div style="font-size: 0.95rem; line-height: 1.8;">
                    <div class="mb-1">
                        <strong>Checked in at:</strong> <?= htmlspecialchars(date('d M Y, h:i A', strtotime($participant['checked_in_at']))) ?>
                    </div>
                    <div>
                        <strong>Group Number:</strong>
                        <span class="badge bg-primary fs-6" style="padding: 4px 12px !important;"><?= htmlspecialchars($participant['group_code'] ?? 'Pending') ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-tertiary-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-tertiary-container) !important;">
                <div class="d-flex align-items-center gap-3">
                    <span class="material-symbols-outlined" style="font-size: 36px;">pending</span>
                    <div>
                        <div class="fw-bold" style="font-size: 1.1rem;">Not Checked In Yet</div>
                        <p class="mb-0 small mt-1">Please present this QR code to the registration counter for check-in.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- QR Code Card -->
        <div class="card p-4 border-0 text-center" style="background-color: var(--md-sys-color-surface-container-lowest) !important; border-radius: 20px !important; border: 1px solid var(--md-sys-color-outline-variant) !important;">
            <div class="d-inline-block bg-white p-3 rounded-4 mx-auto" style="border: 1px solid var(--md-sys-color-outline-variant);">
                <img src="<?= htmlspecialchars($qrImage) ?>" alt="QR Code for check-in" style="max-width: 200px;">
            </div>
            <p class="mt-3 mb-0" style="color: var(--md-sys-color-on-surface-variant); font-size: 0.85rem;">
                Code: <code><?= htmlspecialchars($participant['qr_code']) ?></code>
            </p>
        </div>
    <?php else: ?>
        <div class="card p-4 border-0" style="background-color: var(--md-sys-color-error-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-error-container) !important;">
            <div class="d-flex align-items-center gap-3">
                <span class="material-symbols-outlined" style="font-size: 36px;">error</span>
                <div>
                    <div class="fw-bold" style="font-size: 1.05rem;">Participant Not Found</div>
                    <p class="mb-0 small mt-1">We could not find a participant matching the details provided. Please check your Student ID and email, or contact the committee.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-4 flex-wrap">
        <a href="/participants/lookup?from=<?= htmlspecialchars($lookupFrom) ?>" class="btn btn-outline-primary btn-sm">
            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">search</span> Search again
        </a>
        <a href="<?= htmlspecialchars($registrationHref) ?>" class="btn btn-outline-secondary btn-sm">
            <?= htmlspecialchars($registrationLabel) ?>
        </a>
    </div>
</div>