<?php
$message = $_SESSION['crew_message'] ?? null;
$messageType = $_SESSION['crew_message_type'] ?? 'info';
if ($message !== null) {
    unset($_SESSION['crew_message'], $_SESSION['crew_message_type']);
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Add Crew</h2>
    <a href="/operations/crew" class="btn btn-outline-secondary btn-sm">Back to Crew List</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="/operations/crew/store" class="row g-3">
            <div class="col-md-6">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="role" class="form-label">Role</label>
                <input type="text" id="role" name="role" class="form-control" placeholder="Facilitator, Crew, Medic, etc.">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="is_facilitator" name="is_facilitator" value="1">
                    <label class="form-check-label" for="is_facilitator">
                        Mark as Facilitator (Senior Buddy eligible)
                    </label>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Add Crew</button>
            </div>
        </form>
    </div>
</div>
