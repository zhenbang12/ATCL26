<?php
// Show result of QR check-in including safety info
?>
<div class="mx-auto" style="max-width: 640px;">
    <h2 class="mb-3 fw-bold" style="color: var(--md-sys-color-on-surface);">
        <span class="material-symbols-outlined" style="font-size: 28px; vertical-align: text-bottom; color: var(--md-sys-color-primary);">qr_code_scanner</span>
        Check-In Result
    </h2>

    <?php if (!empty($participant)): ?>
        <?php if (!empty($checkinCriticalError)): ?>
            <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-error-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-error-container) !important;">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="material-symbols-outlined" style="font-size: 36px;">error</span>
                    <div>
                        <div class="fw-bold" style="font-size: 1.2rem;">Check-in could not be completed</div>
                        <p class="mb-0 mt-1"><?= htmlspecialchars($checkinCriticalError) ?></p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Participant Info Card -->
            <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="material-symbols-outlined" style="font-size: 40px; color: var(--md-sys-color-primary);">person</span>
                    <div>
                        <div class="fw-bold" style="font-size: 1.2rem; color: var(--md-sys-color-on-surface);">
                            <?= htmlspecialchars($participant['full_name']) ?>
                        </div>
                        <div class="text-muted" style="font-size: 0.85rem;">
                            <?= htmlspecialchars($participant['student_id'] ?? 'N/A') ?>
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
                    <div>
                        <strong>Language:</strong>
                        <span class="badge" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container);">
                            <?= htmlspecialchars($participant['preferred_language'] ?? 'N/A') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Success Card -->
            <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-success-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-success-container) !important;">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="material-symbols-outlined" style="font-size: 36px;">check_circle</span>
                    <div>
                        <div class="fw-bold" style="font-size: 1.2rem;">Check-In Successful</div>
                    </div>
                </div>
                <div style="font-size: 0.95rem; line-height: 1.8;">
                    <div>
                        <strong>Group:</strong>
                        <?php if (!empty($participant['group_code'])): ?>
                            <span class="badge bg-primary fs-6" style="padding: 4px 12px !important;"><?= htmlspecialchars($participant['group_code']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">Not assigned</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($checkinAssignmentNotice)): ?>
                <div class="card p-3 border-0 mb-3" style="background-color: var(--md-sys-color-tertiary-container) !important; border-radius: 16px !important; color: var(--md-sys-color-on-tertiary-container) !important;">
                    <div class="d-flex align-items-start gap-2">
                        <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                        <p class="mb-0 small"><?= htmlspecialchars($checkinAssignmentNotice) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($participant['medical_notes']) || !empty($participant['dietary_notes'])): ?>
                <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-error-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-error-container) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="material-symbols-outlined" style="font-size: 28px;">warning</span>
                        <strong style="font-size: 1.1rem;">Important Safety / Medical Info</strong>
                    </div>
                    <div style="font-size: 1rem; line-height: 1.6;">
                        <?php if (!empty($participant['medical_notes'])): ?>
                            <div class="mb-2"><strong>Medical:</strong> <?= nl2br(htmlspecialchars($participant['medical_notes'])) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($participant['dietary_notes'])): ?>
                            <div class="mb-2"><strong>Dietary:</strong> <?= nl2br(htmlspecialchars($participant['dietary_notes'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="card p-4 border-0 mb-3" style="background-color: var(--md-sys-color-error-container) !important; border-radius: 20px !important; color: var(--md-sys-color-on-error-container) !important;">
            <div class="d-flex align-items-center gap-3">
                <span class="material-symbols-outlined" style="font-size: 36px;">error</span>
                <div>
                    <div class="fw-bold" style="font-size: 1.1rem;">Check-in Failed</div>
                    <p class="mb-0 small mt-1">QR code not recognised. Please try again or enter the code manually.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <a href="/participants/checkin" class="btn btn-outline-primary mt-3">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">arrow_back</span> Back to check-in
    </a>
</div>