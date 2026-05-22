<?php
// views/users/index.php — User Management (superuser only)
/** @var array $users */
/** @var string|null $message */
/** @var string $messageType */
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <span class="material-symbols-outlined text-primary" style="font-size: 28px;">manage_accounts</span>
    <div>
        <h1 class="h4 mb-0 fw-bold">User Management</h1>
        <p class="text-muted small mb-0">Reset passwords for system accounts</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> d-flex align-items-center gap-2 rounded-3 mb-4" role="alert">
        <span class="material-symbols-outlined" style="font-size: 20px;">
            <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
        </span>
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
<?php foreach ($users as $u): ?>
    <?php
        $roleBadgeColor = match($u['role']) {
            'superuser' => 'var(--md-sys-color-primary)',
            'advisor'   => 'var(--md-sys-color-tertiary)',
            'committee' => 'var(--md-sys-color-secondary)',
            default     => 'var(--md-sys-color-outline)',
        };
        $roleBg = match($u['role']) {
            'superuser' => 'var(--md-sys-color-primary-container)',
            'advisor'   => 'var(--md-sys-color-tertiary-container)',
            'committee' => 'var(--md-sys-color-secondary-container)',
            default     => 'var(--md-sys-color-surface-container)',
        };
        $isSelf = (int)$u['id'] === (int)((\App\Core\Auth::user())['id'] ?? 0);
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="material-symbols-outlined" style="font-size: 40px; color: var(--md-sys-color-on-surface-variant);">account_circle</span>
                <div>
                    <div class="fw-bold" style="color: var(--md-sys-color-on-surface);">
                        <?= htmlspecialchars($u['username']) ?>
                        <?php if ($isSelf): ?>
                            <span class="badge ms-1" style="font-size: 0.65rem; background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary);">You</span>
                        <?php endif; ?>
                    </div>
                    <span class="badge rounded-pill px-2 py-1 mt-1" style="font-size: 0.7rem; background-color: <?= $roleBg ?>; color: <?= $roleBadgeColor ?>;">
                        <?= htmlspecialchars(ucfirst($u['role'])) ?>
                    </span>
                </div>
            </div>

            <div class="small text-muted mb-3">
                <span class="material-symbols-outlined align-middle me-1" style="font-size: 14px;">schedule</span>
                Last updated: <?= htmlspecialchars($u['updated_at'] ?? $u['created_at'] ?? '—') ?>
            </div>

            <!-- Reset Password Form -->
            <form method="post" action="/users/reset-password" class="reset-password-form" data-username="<?= htmlspecialchars($u['username']) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">

                <div class="mb-2">
                    <label class="form-label small fw-semibold text-muted" style="letter-spacing: .4px; text-transform: uppercase; font-size: 0.7rem;">
                        New Password
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="new_password" id="pw_<?= (int)$u['id'] ?>"
                               class="form-control form-control-sm"
                               placeholder="Min. 8 characters"
                               minlength="8" required
                               autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary btn-sm toggle-pw" data-target="pw_<?= (int)$u['id'] ?>" title="Show/hide">
                            <span class="material-symbols-outlined" style="font-size: 16px; line-height: 1;">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted" style="letter-spacing: .4px; text-transform: uppercase; font-size: 0.7rem;">
                        Confirm Password
                    </label>
                    <input type="password" name="confirm_password" id="cpw_<?= (int)$u['id'] ?>"
                           class="form-control form-control-sm"
                           placeholder="Repeat password"
                           minlength="8" required
                           autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                    <span class="material-symbols-outlined" style="font-size: 16px;">lock_reset</span>
                    Reset Password
                </button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php if (empty($users)): ?>
    <div class="col-12">
        <div class="card p-5 text-center text-muted">
            <span class="material-symbols-outlined mb-2" style="font-size: 48px; opacity: 0.3;">group_off</span>
            <p class="mb-0">No users found in the database.</p>
        </div>
    </div>
<?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.querySelectorAll('.toggle-pw').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = document.getElementById(this.getAttribute('data-target'));
            const icon  = this.querySelector('.material-symbols-outlined');
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        });
    });

    // Confirm before submitting
    document.querySelectorAll('.reset-password-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const username = this.getAttribute('data-username');
            const pw  = this.querySelector('[name="new_password"]').value;
            const cpw = this.querySelector('[name="confirm_password"]').value;

            if (pw !== cpw) {
                e.preventDefault();
                alert('Passwords do not match. Please re-enter.');
                return;
            }

            if (!confirm(`Reset password for "${username}"?\n\nMake sure you communicate the new password to them.`)) {
                e.preventDefault();
            }
        });
    });
});
</script>
