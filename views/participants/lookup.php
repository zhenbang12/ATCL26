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
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link" href="<?= htmlspecialchars($registrationHref) ?>"><?= htmlspecialchars($registrationLabel) ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" aria-current="page" href="/participants/lookup?from=<?= htmlspecialchars($lookupFrom) ?>">Find My QR</a>
    </li>
</ul>

<h2>Find My QR Code</h2>

<?php if ($errorMessage): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Already Registered!</strong><br>
        <?= htmlspecialchars($errorMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<p class="text-muted">
    Enter your Student ID exactly as used during registration to retrieve your QR code.
</p>

<form method="post" action="/participants/lookup" class="mt-3" style="max-width: 480px;">
    <input type="hidden" name="from" value="<?= htmlspecialchars($lookupFrom) ?>">
    <div class="mb-3">
        <label class="form-label">Student ID</label>
        <input
            type="text"
            name="student_id"
            class="form-control"
            placeholder="25WMR09999"
            value="<?= htmlspecialchars($prefilledStudentId) ?>"
            required
        >
    </div>
    <button type="submit" class="btn btn-primary">Find my QR</button>
</form>
