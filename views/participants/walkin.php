<?php
// Walk-in registration form.
$registrationSettings = $registrationSettings ?? [
    'pre_register_enabled' => true,
    'walk_in_enabled' => true,
];
$errorMessage = $_SESSION['registration_error'] ?? null;
if (isset($_SESSION['registration_error'])) {
    unset($_SESSION['registration_error']);
}
$duplicateId = $_SESSION['registration_duplicate_id'] ?? null;
if (isset($_SESSION['registration_duplicate_id'])) {
    unset($_SESSION['registration_duplicate_id']);
}
$savedInput = $_SESSION['registration_input'] ?? [];
if (isset($_SESSION['registration_input'])) {
    unset($_SESSION['registration_input']);
}
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" aria-current="page" href="/participants/create-walkin">Walk-in</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/participants/lookup?from=walk-in">Find My QR</a>
    </li>
</ul>

<h2>Walk-in Participant Registration</h2>
<p class="text-muted mb-3">Use this form for on-the-spot participants.</p>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($errorMessage) ?>
        <?php if ($duplicateId): ?>
            <br>
            If you wish to edit your registered information, <a href="/participants/verify-edit?student_id=<?= urlencode($duplicateId) ?>" class="alert-link">click here to edit your details</a>.
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="post" action="/participants/store" class="mt-3">
    <input type="hidden" name="registration_type" value="walk_in">

    <div class="mb-3">
        <label class="form-label" for="full_name">Full name as per IC/ Passport</label>
        <input type="text" name="full_name" id="full_name" placeholder="e.g. Liow Zhen Bang" class="form-control" required autocomplete="name" value="<?= htmlspecialchars($savedInput['full_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="gender">Gender</label>
        <select name="gender" id="gender" class="form-select" required>
            <option value="" disabled <?= empty($savedInput['gender']) ? 'selected' : '' ?>>Select...</option>
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
        <label class="form-label" for="study_level">Study Level</label>
        <select name="study_level" id="study_level" class="form-select" required onchange="updateIntakePeriodWalkin()">
            <option value="" disabled <?= empty($savedInput['study_level']) ? 'selected' : '' ?>>Select study level…</option>
            <option value="Foundation" <?= ($savedInput['study_level'] ?? '') === 'Foundation' ? 'selected' : '' ?>>Foundation</option>
            <option value="Diploma" <?= ($savedInput['study_level'] ?? '') === 'Diploma' ? 'selected' : '' ?>>Diploma</option>
            <option value="Degree" <?= ($savedInput['study_level'] ?? '') === 'Degree' ? 'selected' : '' ?>>Degree</option>
            <option value="Degree (Other Campus)" <?= ($savedInput['study_level'] ?? '') === 'Degree (Other Campus)' ? 'selected' : '' ?>>Degree from other campus (Johor, Penang, Sabah etc)</option>
        </select>
    </div>
    <div class="mb-3" id="intake_period_group_walkin" style="display: none;">
        <label class="form-label" for="intake_period">Intake Period</label>
        <select name="intake_period" id="intake_period_walkin" class="form-select">
            <option value="" disabled selected>Select intake period…</option>
        </select>
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
            title="Enter 10-12 digits starting with 0 or 60"
            autocomplete="tel"
            value="<?= htmlspecialchars($savedInput['contact_no'] ?? '') ?>"
        >
        <small class="form-text text-muted">Saved as 60XXXXXXXXX</small>
    </div>
    <div class="mb-3">
        <label class="form-label" for="preferred_language">Preferred Grouping Language</label>
        <select name="preferred_language" id="preferred_language" class="form-select" required>
            <option value="" disabled <?= empty($savedInput['preferred_language']) ? 'selected' : '' ?>>Select...</option>
            <option value="Mandarin-speaking Group" <?= ($savedInput['preferred_language'] ?? '') === 'Mandarin-speaking Group' ? 'selected' : '' ?>>Mandarin-speaking Group</option>
            <option value="English-speaking Group" <?= ($savedInput['preferred_language'] ?? '') === 'English-speaking Group' ? 'selected' : '' ?>>English-speaking Group</option>
            <option value="Both language speaking Group" <?= ($savedInput['preferred_language'] ?? '') === 'Both language speaking Group' ? 'selected' : '' ?>>Both language speaking Group</option>
        </select>
    </div>
    <button type="submit" class="btn btn-dark">Save Walk-in</button>
</form>

<script>
function updateIntakePeriodWalkin() {
    var level = document.getElementById('study_level').value;
    var group = document.getElementById('intake_period_group_walkin');
    var select = document.getElementById('intake_period_walkin');
    var savedValue = '<?= htmlspecialchars($savedInput['intake_period'] ?? '') ?>';

    if (!level) {
        group.style.display = 'none';
        select.innerHTML = '<option value="" disabled selected>Select intake period…</option>';
        return;
    }

    group.style.display = 'block';
    select.innerHTML = '<option value="" disabled' + (savedValue === '' ? ' selected' : '') + '>Select intake period…</option>';

    var now = new Date();
    var thisYear = now.getFullYear();
    var lastYear = thisYear - 1;

    var options = [];
    if (level === 'Foundation') {
        options = [
            { value: 'Foundation May ' + lastYear, label: 'Foundation May ' + lastYear },
            { value: 'Foundation September ' + lastYear, label: 'Foundation September ' + lastYear },
            { value: 'Foundation May ' + thisYear, label: 'Foundation May ' + thisYear },
            { value: 'Foundation September ' + thisYear, label: 'Foundation September ' + thisYear }
        ];
    } else {
        options = [
            { value: level + ' June ' + lastYear, label: level + ' June ' + lastYear },
            { value: level + ' November ' + lastYear, label: level + ' November ' + lastYear },
            { value: level + ' June ' + thisYear, label: level + ' June ' + thisYear },
            { value: level + ' November ' + thisYear, label: level + ' November ' + thisYear }
        ];
    }

    for (var i = 0; i < options.length; i++) {
        var opt = document.createElement('option');
        opt.value = options[i].value;
        opt.textContent = options[i].label;
        if (savedValue === options[i].value) opt.selected = true;
        select.appendChild(opt);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var savedLevel = '<?= htmlspecialchars($savedInput['study_level'] ?? '') ?>';
    if (savedLevel) {
        updateIntakePeriodWalkin();
    }
});
</script>
