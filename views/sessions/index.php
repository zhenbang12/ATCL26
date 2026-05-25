<?php
/**
 * Event Sessions management page
 * Variables available: $sessions (array), $activeSessionId (int)
 */
use App\Core\SessionHelper;

$sessionMessage = $_SESSION['session_message'] ?? null;
$sessionMessageType = $_SESSION['session_message_type'] ?? 'info';
if (isset($_SESSION['session_message'])) {
    unset($_SESSION['session_message'], $_SESSION['session_message_type']);
}
$currentSession = SessionHelper::currentSession();
?>

<?php if ($sessionMessage): ?>
    <div class="alert alert-<?= htmlspecialchars($sessionMessageType) ?> alert-dismissible fade show" role="alert">
        <?= $sessionMessage ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Title and Top Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">
            <span class="material-symbols-outlined" style="font-size: 28px; vertical-align: text-bottom; color: var(--md-sys-color-primary);">event</span>
            Event Sessions
        </h2>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Create and switch between different event sessions. Each session has its own isolated participants, groups, check-ins, and crew.</p>
    </div>
</div>

<!-- Active Session Banner -->
<?php if ($currentSession): ?>
<div class="card mb-4" style="border: 1px solid var(--md-sys-color-primary) !important; border-radius: 16px; background: var(--md-sys-color-primary-container) !important;">
    <div class="card-body d-flex align-items-center gap-3 py-3">
        <span class="material-symbols-outlined" style="font-size: 32px; color: var(--md-sys-color-on-primary-container);">radio_button_checked</span>
        <div>
            <div class="fw-semibold" style="color: var(--md-sys-color-on-primary-container); font-size: 0.95rem;">Active Session</div>
            <div class="fw-bold" style="color: var(--md-sys-color-on-primary-container); font-size: 1.1rem;"><?= htmlspecialchars($currentSession['name']) ?></div>
            <?php if (!empty($currentSession['description'])): ?>
                <small style="color: var(--md-sys-color-on-primary-container); opacity: 0.8;"><?= htmlspecialchars($currentSession['description']) ?></small>
            <?php endif; ?>
        </div>
        <a href="#create-session" class="btn btn-sm ms-auto" style="background: var(--md-sys-color-on-primary-container); color: var(--md-sys-color-primary-container); border-radius: 100px;">
            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">add</span> New Session
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Create New Session -->
<div class="card mb-4" id="create-session" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background: var(--md-sys-color-surface-container-low) !important;">
    <div class="card-body p-4">
        <h5 class="mb-3 fw-semibold d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
            <span class="material-symbols-outlined" style="font-size: 22px; color: var(--md-sys-color-primary);">add_circle</span>
            Create New Session
        </h5>
        <form method="POST" action="/sessions/store">
            <div class="row g-3">
                <div class="col-md-5">
                    <label for="sessionName" class="form-label small fw-semibold" style="color: var(--md-sys-color-on-surface-variant);">Session Name <span style="color: var(--md-sys-color-error);">*</span></label>
                    <input type="text" class="form-control" id="sessionName" name="name" placeholder="e.g., November 2026, Testing Day 1" required maxlength="255" style="border-radius: 12px; border: 1px solid var(--md-sys-color-outline-variant);">
                </div>
                <div class="col-md-5">
                    <label for="sessionDesc" class="form-label small fw-semibold" style="color: var(--md-sys-color-on-surface-variant);">Description</label>
                    <input type="text" class="form-control" id="sessionDesc" name="description" placeholder="Optional description" maxlength="1000" style="border-radius: 12px; border: 1px solid var(--md-sys-color-outline-variant);">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" style="border-radius: 100px;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">add</span> Create
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Sessions List -->
<div class="card" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background: var(--md-sys-color-surface-container-low) !important;">
    <div class="card-body p-0">
        <div class="d-flex align-items-center justify-content-between px-4 pt-3 pb-2">
            <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2" style="color: var(--md-sys-color-on-surface);">
                <span class="material-symbols-outlined" style="font-size: 22px; color: var(--md-sys-color-primary);">event_note</span>
                All Sessions
            </h5>
            <span class="badge" style="background: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); border-radius: 100px; font-size: 0.75rem;"><?= count($sessions) ?> total</span>
        </div>

        <?php if (empty($sessions)): ?>
            <div class="text-center py-5">
                <span class="material-symbols-outlined" style="font-size: 56px; color: var(--md-sys-color-outline);">event_busy</span>
                <p class="mt-2 mb-0" style="color: var(--md-sys-color-on-surface-variant);">No sessions yet. Create one above to get started.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr style="background: var(--md-sys-color-surface-container);">
                            <th class="small fw-semibold text-uppercase" style="color: var(--md-sys-color-on-surface-variant); font-size: 0.7rem; letter-spacing: 0.5px; border: none;">Session</th>
                            <th class="small fw-semibold text-uppercase" style="color: var(--md-sys-color-on-surface-variant); font-size: 0.7rem; letter-spacing: 0.5px; border: none;">Description</th>
                            <th class="small fw-semibold text-uppercase" style="color: var(--md-sys-color-on-surface-variant); font-size: 0.7rem; letter-spacing: 0.5px; width: 160px; border: none;">Created</th>
                            <th class="small fw-semibold text-uppercase" style="color: var(--md-sys-color-on-surface-variant); font-size: 0.7rem; letter-spacing: 0.5px; width: 200px; border: none;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $s): ?>
                        <?php $isActive = (int)$s['id'] === $activeSessionId; ?>
                        <?php $isGlobalDefault = (bool)($s['is_active'] ?? 0); ?>
                        <tr style="border-bottom: 1px solid var(--md-sys-color-outline-variant); <?= $isActive ? 'background: var(--md-sys-color-primary-container);' : '' ?>">
                            <td class="align-middle" style="border: none;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="material-symbols-outlined" style="color: <?= $isActive ? 'var(--md-sys-color-primary)' : 'var(--md-sys-color-outline)' ?>; font-size: 22px;"><?= $isActive ? 'radio_button_checked' : 'radio_button_unchecked' ?></span>
                                    <div>
                                        <div class="fw-semibold" style="color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($s['name']) ?></div>
                                        <div class="d-flex gap-1 mt-1">
                                            <?php if ($isActive): ?>
                                                <span class="badge" style="background: var(--md-sys-color-primary); color: var(--md-sys-color-on-primary); border-radius: 100px; font-size: 0.6rem;">Your Active</span>
                                            <?php endif; ?>
                                            <?php if ($isGlobalDefault): ?>
                                                <span class="badge" style="background: var(--md-sys-color-tertiary-container); color: var(--md-sys-color-on-tertiary-container); border-radius: 100px; font-size: 0.6rem;">
                                                    <span class="material-symbols-outlined" style="font-size: 10px; vertical-align: text-bottom;">star</span> Global Default
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle" style="color: var(--md-sys-color-on-surface-variant); border: none; font-size: 0.85rem;"><?= htmlspecialchars($s['description'] ?? '') ?: '<span style="color: var(--md-sys-color-outline);">—</span>' ?></td>
                            <td class="align-middle" style="color: var(--md-sys-color-on-surface-variant); border: none; font-size: 0.85rem;"><?= htmlspecialchars($s['created_at'] ?? '') ?></td>
                            <td class="align-middle" style="border: none;">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <?php if (!$isActive): ?>
                                    <form method="POST" action="/sessions/activate" class="d-inline m-0">
                                        <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 100px; font-size: 0.75rem; padding: 4px 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 15px; vertical-align: text-bottom;">swap_horiz</span> Switch to this
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="btn btn-sm disabled" style="background: var(--md-sys-color-primary); color: var(--md-sys-color-on-primary); border-radius: 100px; font-size: 0.75rem; padding: 4px 14px; opacity: 0.7; cursor: default;">
                                            <span class="material-symbols-outlined" style="font-size: 15px; vertical-align: text-bottom;">check</span> Currently Viewing
                                        </span>
                                    <?php endif; ?>
                                    <?php if (\App\Core\Auth::isSuperuser() && !$isGlobalDefault): ?>
                                    <form method="POST" action="/sessions/set-default" class="d-inline m-0">
                                        <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" style="border-radius: 100px; font-size: 0.75rem; padding: 4px 14px;" title="Set as the global default session for public pages and new logins">
                                            <span class="material-symbols-outlined" style="font-size: 15px; vertical-align: text-bottom;">star</span> Set as Default
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (\App\Core\Auth::isSuperuser() && (int)$s['id'] !== 1 && !$isActive): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" style="border-radius: 100px; font-size: 0.75rem; padding: 4px 14px;" data-bs-toggle="modal" data-bs-target="#deleteSessionModal<?= (int)$s['id'] ?>" title="Permanently delete this session and all its data">
                                        <span class="material-symbols-outlined" style="font-size: 15px; vertical-align: text-bottom;">delete</span> Delete
                                    </button>
                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteSessionModal<?= (int)$s['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content" style="border-radius: 20px; border: 1px solid var(--md-sys-color-outline-variant);">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" style="color: var(--md-sys-color-error);">
                                                        <span class="material-symbols-outlined">warning</span>
                                                        Delete Session
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body pt-2">
                                                    <p class="mb-3" style="color: var(--md-sys-color-on-surface);">
                                                        You are about to permanently delete <strong>"<?= htmlspecialchars($s['name']) ?>"</strong> and <strong>all of its data</strong> (participants, groups, crew, check-ins, move logs).
                                                    </p>
                                                    <p class="mb-3 text-danger fw-semibold" style="font-size: 0.85rem;">This action cannot be undone.</p>
                                                    <form method="POST" action="/sessions/delete">
                                                        <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold" style="color: var(--md-sys-color-on-surface-variant);">Enter your password to confirm</label>
                                                            <input type="password" class="form-control" name="password" required placeholder="Your login password" style="border-radius: 12px; border: 1px solid var(--md-sys-color-outline-variant);">
                                                        </div>
                                                        <div class="d-flex gap-2 justify-content-end">
                                                            <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="border-radius: 100px; border: 1px solid var(--md-sys-color-outline); color: var(--md-sys-color-on-surface);">Cancel</button>
                                                            <button type="submit" class="btn btn-danger btn-sm" style="border-radius: 100px;">
                                                                <span class="material-symbols-outlined" style="font-size: 15px; vertical-align: text-bottom;">delete_forever</span> Delete Permanently
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>