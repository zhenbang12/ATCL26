<?php
// Public form to look up QR code after registration
$registrationSettings = $registrationSettings ?? [
    'pre_register_enabled' => true,
    'walk_in_enabled' => true,
];
$errorMessage = $_SESSION['registration_error'] ?? null;
$prefilledStudentId = $_GET['student_id'] ?? '';
$lookupFrom = ($lookupFrom ?? ($_GET['from'] ?? 'pre-reg')) === 'walk-in' ? 'walk-in' : 'pre-reg';
$registrationHref = $lookupFrom === 'walk-in' ? '/participants/create-walkin' : '/participants/create';
$registrationLabel = $lookupFrom === 'walk-in' ? 'Walk-in' : 'Pre-register';
if (isset($_SESSION['registration_error'])) {
    unset($_SESSION['registration_error']);
}
?>
<div class="mx-auto" style="max-width: 540px;">
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="<?= htmlspecialchars($registrationHref) ?>"><?= htmlspecialchars($registrationLabel) ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="/participants/lookup?from=<?= htmlspecialchars($lookupFrom) ?>">Find My QR</a>
        </li>
    </ul>

    <div class="card p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; box-shadow: 0 4px 20px var(--md-sys-color-shadow) !important;">
        <div class="card-body">
            <h2 class="h4 fw-bold mb-3">Find My QR Code</h2>

            <?php if ($errorMessage): 
                $isSuccess = (strpos(strtolower($errorMessage), 'success') !== false || strpos(strtolower($errorMessage), 'updated') !== false);
                $alertClass = $isSuccess ? 'alert-success' : 'alert-warning';
                $alertTitle = $isSuccess ? 'Success!' : 'Already Registered!';
            ?>
                <div class="alert <?= $alertClass ?> alert-dismissible fade show mb-4" role="alert">
                    <strong><?= $alertTitle ?></strong><br>
                    <?= htmlspecialchars($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <p class="text-muted small mb-4">
                Enter your Student ID exactly as used during registration to retrieve your QR code.
            </p>

            <form method="post" action="/participants/lookup">
                <input type="hidden" name="from" value="<?= htmlspecialchars($lookupFrom) ?>">
                <div class="mb-4">
                    <label class="form-label" for="student_id">Student ID</label>
                    <input
                        type="text"
                        name="student_id"
                        id="student_id"
                        class="form-control"
                        placeholder="e.g. 25WMR09999"
                        value="<?= htmlspecialchars($prefilledStudentId) ?>"
                        required
                    >
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Find My QR</button>
            </form>
        </div>
    </div>
</div>
