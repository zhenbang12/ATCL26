<?php
$c = $crewMember ?? [];
$message = $_SESSION['crew_message'] ?? null;
$messageType = $_SESSION['crew_message_type'] ?? 'info';
if ($message !== null) {
    unset($_SESSION['crew_message'], $_SESSION['crew_message_type']);
}
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Edit Crew Member</h2>
        <p class="text-muted small mb-0">Update details for <?= htmlspecialchars($c['full_name'] ?? '') ?>.</p>
    </div>
    <a href="/operations/crew" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
        Back to Crew List
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card" style="border-radius: 20px; border: 1px solid var(--md-sys-color-outline-variant);">
    <div class="card-body p-4">
        <form method="post" action="/operations/crew/update" class="row g-3">
            <input type="hidden" name="id" value="<?= (int)($c['id'] ?? 0) ?>">

            <div class="col-md-6">
                <label for="edit_full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input
                    type="text"
                    id="edit_full_name"
                    name="full_name"
                    class="form-control"
                    value="<?= htmlspecialchars($c['full_name'] ?? '') ?>"
                    required
                    placeholder="Full name"
                >
            </div>

            <div class="col-md-6">
                <label for="edit_email" class="form-label fw-semibold">Email</label>
                <input
                    type="email"
                    id="edit_email"
                    name="email"
                    class="form-control"
                    value="<?= htmlspecialchars($c['email'] ?? '') ?>"
                    placeholder="email@example.com"
                >
            </div>

            <div class="col-md-6">
                <label for="edit_role" class="form-label fw-semibold">Role</label>
                <input
                    type="text"
                    id="edit_role"
                    name="role"
                    class="form-control"
                    value="<?= htmlspecialchars($c['role'] ?? '') ?>"
                    placeholder="Facilitator, Crew, Medic, etc."
                >
            </div>

            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="edit_is_facilitator"
                        name="is_facilitator"
                        value="1"
                        <?= ((int)($c['is_facilitator'] ?? 0) === 1) ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="edit_is_facilitator">
                        Mark as <strong>Senior Buddy</strong> (facilitator eligible)
                    </label>
                </div>
            </div>

            <?php if (!empty($c['assigned_group_code'])): ?>
            <div class="col-12">
                <div class="alert alert-secondary d-flex align-items-center gap-2 py-2 mb-0" style="border-radius: 12px;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">info</span>
                    <span class="small">Currently assigned to <strong>Group <?= htmlspecialchars($c['assigned_group_code']) ?></strong>.
                    Unchecking Senior Buddy will clear this assignment.</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="col-12 d-flex gap-2 pt-2">
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                    Save Changes
                </button>
                <a href="/operations/crew" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
