<div class="mx-auto" style="max-width: 560px;">
    <!-- Error Icon & Title -->
    <div class="text-center mb-4">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:72px; height:72px; border-radius:50%; background:var(--md-sys-color-error-container);">
            <span class="material-symbols-outlined" style="font-size:36px; color:var(--md-sys-color-error);">block</span>
        </div>
        <h2 class="fw-bold mb-1" style="color:var(--md-sys-color-on-error-container);">Registration Blocked</h2>
        <p class="text-muted mb-0">Your registration could not be completed because it does not meet the current admission requirements.</p>
    </div>

    <!-- Block Reasons -->
    <div class="card p-4 border-0 mb-3" style="background:var(--md-sys-color-error-container) !important; border-radius:20px !important;">
        <h6 class="fw-semibold mb-3" style="color:var(--md-sys-color-on-error-container);">
            <span class="material-symbols-outlined" style="font-size:20px; vertical-align:text-bottom;">warning</span>
            Block Reason<?= count($reasons) > 1 ? 's' : '' ?>
        </h6>
        <ul class="list-unstyled mb-0">
            <?php foreach ($reasons as $reason): ?>
            <li class="d-flex align-items-start gap-2 mb-2" style="font-size:0.9rem; color:var(--md-sys-color-on-error-container);">
                <span class="material-symbols-outlined" style="font-size:18px; flex-shrink:0; margin-top:2px; color:var(--md-sys-color-error);">error</span>
                <span><?= htmlspecialchars($reason) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if (!empty($allowedIntakes)): ?>
    <!-- Allowed Intakes -->
    <div class="card p-4 border-0 mb-3" style="background:var(--md-sys-color-surface-container-low) !important; border-radius:20px !important;">
        <h6 class="fw-semibold mb-2" style="color:var(--md-sys-color-on-surface);">
            <span class="material-symbols-outlined" style="font-size:20px; vertical-align:text-bottom; color:var(--md-sys-color-primary);">check_circle</span>
            Currently Allowed Intakes
        </h6>
        <p class="text-muted mb-3" style="font-size:0.85rem;">Only the following intake periods are currently accepting registrations:</p>
        <div class="d-flex flex-wrap gap-2">
            <?php
            $grouped = [];
            foreach ($allowedIntakes as $ai) {
                // Split at the month name boundary (May, June, September, November)
                if (preg_match('/^(.+?)\s+((?:May|June|September|November)\s+\d{4})$/', $ai, $m)) {
                    $level = trim($m[1]);
                    $period = trim($m[2]);
                } else {
                    $level = $ai;
                    $period = '';
                }
                if (!isset($grouped[$level])) $grouped[$level] = [];
                if ($period !== '') $grouped[$level][] = $period;
            }
            foreach ($grouped as $level => $periods): ?>
                <span class="d-inline-flex align-items-center gap-1 px-3 py-2" style="border-radius:20px; background:var(--md-sys-color-primary-container); color:var(--md-sys-color-on-primary-container); font-size:0.82rem; font-weight:500;">
                    <span class="material-symbols-outlined" style="font-size:16px;">school</span>
                    <?= htmlspecialchars($level) ?> &mdash; <?= htmlspecialchars(implode(', ', $periods)) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="card p-4 border-0 mb-3" style="background:var(--md-sys-color-surface-container-low) !important; border-radius:20px !important;">
        <p class="text-muted mb-0" style="font-size:0.9rem;">
            <span class="material-symbols-outlined" style="font-size:18px; vertical-align:text-bottom;">info</span>
            No intake periods are currently open for registration. Please contact the administrator.
        </p>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="d-flex gap-2">
        <a href="/participants/create" class="btn btn-outline-secondary flex-fill py-2" style="border-radius:20px;">
            <span class="material-symbols-outlined" style="font-size:18px; vertical-align:text-bottom;">arrow_back</span> Back to Registration
        </a>
        <a href="/participants/lookup?from=pre-reg" class="btn btn-primary flex-fill py-2" style="border-radius:20px;">
            <span class="material-symbols-outlined" style="font-size:18px; vertical-align:text-bottom;">search</span> Find My QR
        </a>
    </div>
</div>