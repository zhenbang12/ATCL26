<?php
// Public form to look up QR code after registration
$errorMessage = $_SESSION['registration_error'] ?? null;
$prefilledStudentId = $_GET['student_id'] ?? '';
if (isset($_SESSION['registration_error'])) {
    unset($_SESSION['registration_error']);
}
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link" href="/participants/create">Register</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" aria-current="page" href="/participants/lookup">Find My QR</a>
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

