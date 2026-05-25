<?php
// Show registration success and QR code for check-in
?>
<div class="mx-auto" style="max-width: 640px;">
    <h2 class="mb-3 fw-bold" style="color: var(--md-sys-color-on-surface);">
        <span class="material-symbols-outlined" style="font-size: 28px; vertical-align: text-bottom; color: var(--md-sys-color-success);">check_circle</span>
        Registration Successful
    </h2>

    <?php if (!empty($participant)): ?>
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
                    <strong>Language:</strong>
                    <span class="badge" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container);">
                        <?= htmlspecialchars($participant['preferred_language'] ?? 'N/A') ?>
                    </span>
                </div>
            </div>
        </div>

        <p class="text-muted small mb-3">
            Your group is assigned when you check in at the event (not at registration).
        </p>

        <!-- QR Code Card -->
        <div class="card p-4 border-0 text-center" style="background-color: var(--md-sys-color-surface-container-lowest) !important; border-radius: 20px !important; border: 1px solid var(--md-sys-color-outline-variant) !important;">
            <?php $qrValue = $participant['qr_code'] ?? ''; ?>
            <?php if (!empty($qrImage)): ?>
                <div class="d-inline-block bg-white p-3 rounded-4 mx-auto" style="border: 1px solid var(--md-sys-color-outline-variant);">
                    <img src="<?= htmlspecialchars($qrImage) ?>" alt="QR Code for check-in" style="max-width: 200px;">
                </div>
            <?php endif; ?>
            <p class="mt-3 mb-0" style="color: var(--md-sys-color-on-surface-variant); font-size: 0.85rem;">
                Code: <code><?= htmlspecialchars($qrValue) ?></code>
            </p>
        </div>

        <div class="card p-3 border-0 mt-3" style="background-color: var(--md-sys-color-tertiary-container) !important; border-radius: 16px !important; color: var(--md-sys-color-on-tertiary-container) !important;">
            <div class="d-flex align-items-start gap-2">
                <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                <p class="mb-0 small">
                    Screenshot or download this QR. If there are issues scanning, the crew can also type the code above into the check-in system.
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mt-3" style="border-radius: 16px;">
            Registration data could not be loaded. Please contact the committee.
        </div>
    <?php endif; ?>
</div>