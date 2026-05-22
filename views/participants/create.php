<?php
$registrationSettings = $registrationSettings ?? [
    'pre_register_enabled' => true,
    'walk_in_enabled' => true,
];
// Participant registration form (pre-register — minimal fields)
$errorMessage = $_SESSION['registration_error'] ?? null;
if (isset($_SESSION['registration_error'])) {
    unset($_SESSION['registration_error']);
}
$savedInput = $_SESSION['registration_input'] ?? [];
if (isset($_SESSION['registration_input'])) {
    unset($_SESSION['registration_input']);
}
?>
<ul class="nav nav-tabs mb-3">
    <?php if ($registrationSettings['pre_register_enabled'] || \App\Core\Auth::check()): ?>
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="/participants/create">Pre-register</a>
        </li>
    <?php endif; ?>
    <li class="nav-item">
        <a class="nav-link" href="/participants/lookup?from=pre-reg">Find My QR</a>
    </li>
</ul>

<h2>Pre-register Participant</h2>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($errorMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="post" action="/participants/store" class="mt-3">
    <input type="hidden" name="registration_type" value="pre_register">
    <div class="mb-3">
        <label class="form-label" for="full_name">Name</label>
        <input type="text" name="full_name" id="full_name" placeholder="e.g. Liow Zhen Bang" class="form-control" required autocomplete="name" value="<?= htmlspecialchars($savedInput['full_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="gender">Gender</label>
        <select name="gender" id="gender" class="form-select" required>
            <option value="" disabled <?= empty($savedInput['gender']) ? 'selected' : '' ?>>Select…</option>
            <option value="Male" <?= ($savedInput['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($savedInput['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Prefer not to say" <?= ($savedInput['gender'] ?? '') === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label" for="student_id">Student ID</label>
        <input
            type="text"
            name="student_id"
            id="student_id"
            class="form-control"
            required
            placeholder="e.g. 25WMR09999"
            pattern="^[0-9]{2}[A-Z]{3}[0-9]{5}$"
            autocomplete="username"
            value="<?= htmlspecialchars($savedInput['student_id'] ?? '') ?>"
        >
    </div>
    <div class="mb-3">
        <label class="form-label" for="student_email">Student email</label>
        <input
            type="email"
            name="student_email"
            id="student_email"
            class="form-control"
            required
            placeholder="e.g. liowzb-wm23@student.tarc.edu.my"
            pattern="^[a-zA-Z0-9._%+-]+(?:-?[a-zA-Z0-9._%+-]+)*@student\.tarc\.edu\.my$"
            title="Must be a valid student address ending with @student.tarc.edu.my"
            autocomplete="email"
            value="<?= htmlspecialchars($savedInput['student_email'] ?? '') ?>"
        >
    </div>
    <div class="mb-3">
        <label class="form-label" for="faculty">Faculty</label>
        <select name="faculty" id="faculty" class="form-select" required>
            <option value="" disabled <?= empty($savedInput['faculty']) ? 'selected' : '' ?>>Select faculty…</option>
            <?php foreach (['FAFB', 'FOAS', 'FOCS', 'FOBE', 'FOET', 'FCCI', 'FSSH', 'CPUS'] as $fac): ?>
                <option value="<?= $fac ?>" <?= ($savedInput['faculty'] ?? '') === $fac ? 'selected' : '' ?>><?= $fac ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label" for="programme_name">Programme</label>
        <input
            type="text"
            name="programme_name"
            id="programme_name"
            class="form-control"
            required
            placeholder="e.g. Bachelor of Computer Science (Hons)"
            autocomplete="off"
            value="<?= htmlspecialchars($savedInput['programme_name'] ?? '') ?>"
        >
    </div>
    <div class="mb-3">
        <label class="form-label" for="contact_no">Contact</label>
        <input
            type="text"
            name="contact_no"
            id="contact_no"
            class="form-control"
            required
            placeholder="0167719430 or 60167719430"
            pattern="^(0|60)[0-9]{9,10}$"
            title="Enter 10–12 digits starting with 0 or 60"
            autocomplete="tel"
            value="<?= htmlspecialchars($savedInput['contact_no'] ?? '') ?>"
        >
        <small class="form-text text-muted">Saved as 60XXXXXXXXX</small>
    </div>
    <div class="mb-3">
        <label class="form-label" for="preferred_language">Language</label>
        <select name="preferred_language" id="preferred_language" class="form-select" required>
            <option value="" disabled <?= empty($savedInput['preferred_language']) ? 'selected' : '' ?>>Select…</option>
            <option value="Mandarin" <?= ($savedInput['preferred_language'] ?? '') === 'Mandarin' ? 'selected' : '' ?>>Mandarin</option>
            <option value="English" <?= ($savedInput['preferred_language'] ?? '') === 'English' ? 'selected' : '' ?>>English</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
