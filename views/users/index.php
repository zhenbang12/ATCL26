<?php
// views/users/index.php — User Management (superuser only)
/** @var array $users */
/** @var string|null $message */
/** @var string $messageType */

$totalUsers = count($users);
$superuserCount = 0;
$advisorCount = 0;
$committeeCount = 0;
foreach ($users as $u) {
    if ($u['role'] === 'superuser') {
        $superuserCount++;
    } elseif ($u['role'] === 'advisor') {
        $advisorCount++;
    } elseif ($u['role'] === 'committee' || $u['role'] === 'treasurer') {
        $committeeCount++;
    }
}
?>

<style>
    /* Compact user card styles */
    .user-card-item {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .user-card-item:hover {
        transform: translateY(-2px);
    }
    .modal-backdrop {
        background-color: var(--md-sys-color-shadow) !important;
    }
    .toggle-pw {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        border: 1px solid var(--md-sys-color-outline) !important;
        border-left: none !important;
        background-color: var(--md-sys-color-surface-container-lowest) !important;
        color: var(--md-sys-color-on-surface-variant) !important;
    }
    .toggle-pw:hover {
        background-color: var(--md-sys-color-surface-container-high) !important;
    }
    .password-input-field {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
</style>

<!-- Header Block -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-2">
        <span class="material-symbols-outlined text-primary" style="font-size: 32px;">manage_accounts</span>
        <div>
            <h1 class="h4 mb-0 fw-bold">User Management</h1>
            <p class="text-muted small mb-0">Add, delete, and manage user accounts</p>
        </div>
    </div>
</div>

<!-- Session Message Alerts -->
<?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show d-flex align-items-center gap-2 rounded-3 mb-4" role="alert">
        <span class="material-symbols-outlined" style="font-size: 22px;">
            <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
        </span>
        <div class="flex-grow-1"><?= htmlspecialchars($message) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="border: none !important; background: none;"></button>
    </div>
<?php endif; ?>

<!-- Stats Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card p-3 h-100" style="background-color: var(--md-sys-color-surface-container-low) !important; border: 1px solid var(--md-sys-color-outline-variant) !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Users</div>
                    <div class="fs-4 fw-bold text-primary mt-1"><?= $totalUsers ?></div>
                </div>
                <span class="material-symbols-outlined text-primary" style="font-size: 32px; opacity: 0.8;">group</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card p-3 h-100" style="background-color: var(--md-sys-color-surface-container-low) !important; border: 1px solid var(--md-sys-color-outline-variant) !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Superusers</div>
                    <div class="fs-4 fw-bold mt-1" style="color: var(--md-sys-color-primary);"><?= $superuserCount ?></div>
                </div>
                <span class="material-symbols-outlined" style="font-size: 32px; opacity: 0.8; color: var(--md-sys-color-primary);">admin_panel_settings</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card p-3 h-100" style="background-color: var(--md-sys-color-surface-container-low) !important; border: 1px solid var(--md-sys-color-outline-variant) !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Advisors</div>
                    <div class="fs-4 fw-bold mt-1" style="color: var(--md-sys-color-tertiary);"><?= $advisorCount ?></div>
                </div>
                <span class="material-symbols-outlined" style="font-size: 32px; opacity: 0.8; color: var(--md-sys-color-tertiary);">supervisor_account</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card p-3 h-100" style="background-color: var(--md-sys-color-surface-container-low) !important; border: 1px solid var(--md-sys-color-outline-variant) !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Committee / Treasurer</div>
                    <div class="fs-4 fw-bold mt-1" style="color: var(--md-sys-color-secondary);"><?= $committeeCount ?></div>
                </div>
                <span class="material-symbols-outlined" style="font-size: 32px; opacity: 0.8; color: var(--md-sys-color-secondary);">group</span>
            </div>
        </div>
    </div>
</div>

<!-- Toolbar Card (Search, Filter, Create) -->
<div class="card p-3 mb-4" style="background-color: var(--md-sys-color-surface-container-low) !important; border: 1px solid var(--md-sys-color-outline-variant) !important;">
    <div class="row g-3 align-items-center">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-radius: 8px 0 0 8px !important; border-color: var(--md-sys-color-outline) !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px;">search</span>
                </span>
                <input type="text" id="userSearchInput" class="form-control border-start-0 ps-0" placeholder="Search username..." style="border-radius: 0 8px 8px 0 !important; border-color: var(--md-sys-color-outline) !important;">
            </div>
        </div>
        <div class="col-md-3">
            <select id="roleFilterSelect" class="form-select" style="border-color: var(--md-sys-color-outline) !important;">
                <option value="all">All Roles</option>
                <option value="superuser">Superuser</option>
                <option value="advisor">Advisor</option>
                <option value="committee">Committee</option>
                <option value="treasurer">Treasurer</option>
            </select>
        </div>
        <div class="col-md-3 text-md-end">
            <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <span class="material-symbols-outlined" style="font-size: 20px;">person_add</span>
                Add User
            </button>
        </div>
    </div>
</div>

<!-- Users Cards Container -->
<div class="row g-4" id="usersContainer">
<?php foreach ($users as $u): ?>
    <?php
        $roleBadgeColor = match($u['role']) {
            'superuser' => 'var(--md-sys-color-primary)',
            'advisor'   => 'var(--md-sys-color-tertiary)',
            'committee' => 'var(--md-sys-color-secondary)',
            'treasurer' => 'var(--md-sys-color-error)',
            default     => 'var(--md-sys-color-outline)',
        };
        $roleBg = match($u['role']) {
            'superuser' => 'var(--md-sys-color-primary-container)',
            'advisor'   => 'var(--md-sys-color-tertiary-container)',
            'committee' => 'var(--md-sys-color-secondary-container)',
            'treasurer' => 'var(--md-sys-color-error-container)',
            default     => 'var(--md-sys-color-surface-container)',
        };
        $isSelf = (int)$u['id'] === (int)((\App\Core\Auth::user())['id'] ?? 0);
    ?>
    <div class="col-md-6 col-xl-4 user-card-item" data-username="<?= htmlspecialchars(strtolower($u['username'])) ?>" data-role="<?= htmlspecialchars($u['role']) ?>">
        <div class="card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <!-- Top info -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="material-symbols-outlined text-secondary" style="font-size: 40px; color: var(--md-sys-color-on-surface-variant);">
                        <?= $u['role'] === 'superuser' ? 'admin_panel_settings' : ($u['role'] === 'advisor' ? 'supervisor_account' : ($u['role'] === 'treasurer' ? 'payments' : 'person')) ?>
                    </span>
                    <div class="overflow-hidden">
                        <div class="fw-bold text-truncate" style="color: var(--md-sys-color-on-surface); font-size: 1.1rem;" title="<?= htmlspecialchars($u['username']) ?>">
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

                <!-- Timestamps -->
                <div class="small text-muted mb-3 d-flex flex-column gap-1">
                    <div class="d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                        <span class="material-symbols-outlined" style="font-size: 14px; opacity: 0.7;">calendar_today</span>
                        <span>Created: <?= htmlspecialchars($u['created_at'] ?? '—') ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                        <span class="material-symbols-outlined" style="font-size: 14px; opacity: 0.7;">schedule</span>
                        <span>Updated: <?= htmlspecialchars($u['updated_at'] ?? '—') ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 pt-3 border-top mt-2">
                <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-1"
                        data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                        data-user-id="<?= (int)$u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>">
                    <span class="material-symbols-outlined" style="font-size: 16px;">lock_reset</span>
                    Reset Password
                </button>
                
                <?php if ($isSelf): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center"
                            disabled title="You cannot delete yourself" style="cursor: not-allowed; opacity: 0.5;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center"
                            data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                            data-user-id="<?= (int)$u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>"
                            title="Delete User">
                        <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Empty / No Filter Results State -->
<div class="col-12 d-none" id="noUsersFilteredMessage">
    <div class="card p-5 text-center text-muted" style="border: 1px dashed var(--md-sys-color-outline-variant) !important; background: transparent !important;">
        <span class="material-symbols-outlined mb-2" style="font-size: 48px; opacity: 0.3;">group_off</span>
        <p class="mb-0">No users match your filter criteria.</p>
    </div>
</div>

<?php if (empty($users)): ?>
    <div class="col-12">
        <div class="card p-5 text-center text-muted">
            <span class="material-symbols-outlined mb-2" style="font-size: 48px; opacity: 0.3;">group_off</span>
            <p class="mb-0">No users found in the database.</p>
        </div>
    </div>
<?php endif; ?>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="addUserForm" method="post" action="/users/create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="border: none !important; background: none;"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_username" class="form-label small fw-semibold text-muted" style="letter-spacing: .4px; text-transform: uppercase; font-size: 0.75rem;">Username</label>
                        <input type="text" name="username" id="add_username" class="form-control" placeholder="Letters, numbers, underscores (3-30 chars)" minlength="3" maxlength="30" pattern="^[a-zA-Z0-9_]+$" required autocomplete="off">
                        <div class="form-text small" style="font-size: 0.7rem;">Only alphanumeric characters and underscores allowed.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_role" class="form-label small fw-semibold text-muted" style="letter-spacing: .4px; text-transform: uppercase; font-size: 0.75rem;">System Role</label>
                        <select name="role" id="add_role" class="form-select" required>
                            <option value="committee" selected>Committee</option>
                            <option value="treasurer">Treasurer</option>
                            <option value="advisor">Advisor</option>
                            <option value="superuser">Superuser</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add_password" class="form-label small fw-semibold text-muted" style="letter-spacing: .4px; text-transform: uppercase; font-size: 0.75rem;">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="add_password" class="form-control password-input-field" placeholder="Min. 8 characters" minlength="8" required autocomplete="new-password">
                            <button type="button" class="btn toggle-pw d-flex align-items-center" data-target="add_password" title="Toggle visibility">
                                <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="add_confirm_password" class="form-label small fw-semibold text-muted" style="letter-spacing: .4px; text-transform: uppercase; font-size: 0.75rem;">Confirm Password</label>
                        <input type="password" name="confirm_password" id="add_confirm_password" class="form-control" placeholder="Repeat password" minlength="8" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 18px;">person_add</span>
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="resetPasswordForm" method="post" action="/users/reset-password">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="border: none !important; background: none;"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reset_username_display" class="form-label small fw-semibold text-muted" style="letter-spacing: .4px; text-transform: uppercase; font-size: 0.75rem;">User Account</label>
                        <input type="text" id="reset_username_display" class="form-control" readonly style="font-weight: 600;">
                    </div>
                    <div class="mb-3">
                        <label for="reset_new_password" class="form-label small fw-semibold text-muted" style="letter-spacing: .4px; text-transform: uppercase; font-size: 0.75rem;">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="reset_new_password" class="form-control password-input-field" placeholder="Min. 8 characters" minlength="8" required autocomplete="new-password">
                            <button type="button" class="btn toggle-pw d-flex align-items-center" data-target="reset_new_password" title="Toggle visibility">
                                <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reset_confirm_password" class="form-label small fw-semibold text-muted" style="letter-spacing: .4px; text-transform: uppercase; font-size: 0.75rem;">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="reset_confirm_password" class="form-control" placeholder="Repeat password" minlength="8" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 18px;">lock_reset</span>
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. Delete User Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="deleteUserForm" method="post" action="/users/delete">
                <input type="hidden" name="user_id" id="delete_user_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="deleteUserModalLabel">
                        <span class="material-symbols-outlined" style="font-size: 28px;">warning</span>
                        Delete User Account
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="border: none !important; background: none;"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to permanently delete the user account <strong id="delete_username_display" class="text-primary"></strong>?</p>
                    <p class="text-danger small mt-2 mb-0"><strong>Warning:</strong> This account will lose all access to the system. This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger d-flex align-items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                        Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Password Visibility Toggle
    document.querySelectorAll('.toggle-pw').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const inputId = this.getAttribute('data-target');
            const input = document.getElementById(inputId);
            const icon  = this.querySelector('.material-symbols-outlined');
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.textContent = 'visibility_off';
                } else {
                    input.type = 'password';
                    icon.textContent = 'visibility';
                }
            }
        });
    });

    // 2. Setup Reset Password Modal Values
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    if (resetPasswordModal) {
        resetPasswordModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const username = button.getAttribute('data-username');
            
            const modalTitle = resetPasswordModal.querySelector('.modal-title');
            const userIdInput = resetPasswordModal.querySelector('#reset_user_id');
            const usernameDisplay = resetPasswordModal.querySelector('#reset_username_display');
            
            if (modalTitle) modalTitle.textContent = `Reset Password: ${username}`;
            if (userIdInput) userIdInput.value = userId;
            if (usernameDisplay) usernameDisplay.value = username;
            
            // Clear inputs
            const pwInput = resetPasswordModal.querySelector('#reset_new_password');
            const cpwInput = resetPasswordModal.querySelector('#reset_confirm_password');
            if (pwInput) pwInput.value = '';
            if (cpwInput) cpwInput.value = '';
        });
    }

    // 3. Setup Delete Confirmation Modal Values
    const deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const username = button.getAttribute('data-username');
            
            const userIdInput = deleteUserModal.querySelector('#delete_user_id');
            const usernameDisplay = deleteUserModal.querySelector('#delete_username_display');
            
            if (userIdInput) userIdInput.value = userId;
            if (usernameDisplay) usernameDisplay.textContent = username;
        });
    }

    // 4. Form Client-side Validations
    const addForm = document.getElementById('addUserForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const password = document.getElementById('add_password').value;
            const confirm = document.getElementById('add_confirm_password').value;
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match. Please enter them again.');
            }
        });
    }

    const resetForm = document.getElementById('resetPasswordForm');
    if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
            const password = document.getElementById('reset_new_password').value;
            const confirm = document.getElementById('reset_confirm_password').value;
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match. Please enter them again.');
            }
        });
    }

    // 5. Client-Side Search & Filter
    const searchInput = document.getElementById('userSearchInput');
    const roleFilter = document.getElementById('roleFilterSelect');
    const cards = document.querySelectorAll('.user-card-item');
    const noResultsMsg = document.getElementById('noUsersFilteredMessage');

    function filterUsers() {
        if (!searchInput || !roleFilter) return;
        const searchQuery = searchInput.value.toLowerCase().trim();
        const selectedRole = roleFilter.value;
        let visibleCount = 0;

        cards.forEach(card => {
            const cardUsername = card.getAttribute('data-username') || '';
            const cardRole = card.getAttribute('data-role') || '';

            const matchesSearch = cardUsername.includes(searchQuery);
            const matchesRole = selectedRole === 'all' || cardRole === selectedRole;

            if (matchesSearch && matchesRole) {
                card.style.setProperty('display', '', 'important');
                visibleCount++;
            } else {
                card.style.setProperty('display', 'none', 'important');
            }
        });

        if (noResultsMsg) {
            if (visibleCount === 0 && cards.length > 0) {
                noResultsMsg.classList.remove('d-none');
            } else {
                noResultsMsg.classList.add('d-none');
            }
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterUsers);
    if (roleFilter) roleFilter.addEventListener('change', filterUsers);
});
</script>
