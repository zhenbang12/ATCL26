<?php
// Admin participant edit form
$message = $_SESSION['participants_message'] ?? null;
$messageType = $_SESSION['participants_message_type'] ?? 'danger';
if (isset($_SESSION['participants_message'])) {
    unset($_SESSION['participants_message'], $_SESSION['participants_message_type']);
}

// Fetch available groups for assignment
$db = \App\Core\Container::get('db');
$groups = [];
try {
    $stmt = $db->query('SELECT group_code, language_pool FROM event_groups ORDER BY sort_order ASC, CAST(group_code AS UNSIGNED), group_code');
    $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    // Fallback if event_groups table has issues
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Edit Participant</h2>
    <a href="/participants/list" class="btn btn-outline-secondary btn-sm">Back to List</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" action="/participants/update">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$participant['id']) ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="form-control" required value="<?= htmlspecialchars($participant['full_name']) ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="student_id">Student ID</label>
                    <input type="text" name="student_id" id="student_id" class="form-control" required pattern="^[0-9]{2}[A-Z]{3}[0-9]{5}$" placeholder="e.g. 25WMR09999" value="<?= htmlspecialchars($participant['student_id'] ?? '') ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="student_email">Student Email</label>
                    <input type="email" name="student_email" id="student_email" class="form-control" required pattern="^[a-zA-Z0-9._%+-]+(?:-?[a-zA-Z0-9._%+-]+)*@student\.tarc\.edu\.my$" title="Must be a valid student address ending with @student.tarc.edu.my" value="<?= htmlspecialchars($participant['student_email'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="ic_passport_no">IC / Passport No</label>
                    <input type="text" name="ic_passport_no" id="ic_passport_no" class="form-control" value="<?= htmlspecialchars($participant['ic_passport_no'] ?? '') ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="gender">Gender</label>
                    <select name="gender" id="gender" class="form-select" required>
                        <option value="" disabled>Select…</option>
                        <option value="Male" <?= ($participant['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($participant['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Prefer not to say" <?= ($participant['gender'] ?? '') === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="faculty">Faculty</label>
                    <select name="faculty" id="faculty" class="form-select" required>
                        <option value="" disabled>Select faculty…</option>
                        <?php foreach (['FAFB', 'FOAS', 'FOCS', 'FOBE', 'FOET', 'FCCI', 'FSSH', 'CPUS'] as $fac): ?>
                            <option value="<?= $fac ?>" <?= ($participant['faculty'] ?? '') === $fac ? 'selected' : '' ?>><?= $fac ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label" for="intake">Intake</label>
                    <input type="text" name="intake" id="intake" class="form-control" placeholder="e.g. 202605" value="<?= htmlspecialchars($participant['intake'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="programme_name">Programme Name</label>
                <input type="text" name="programme_name" id="programme_name" class="form-control" required value="<?= htmlspecialchars($participant['programme_name'] ?? '') ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="contact_no">Contact No</label>
                    <input type="text" name="contact_no" id="contact_no" class="form-control" required pattern="^(0|60)[0-9]{9,10}$" placeholder="0167719430" value="<?= htmlspecialchars($participant['contact_no'] ?? '') ?>">
                </div>
            </div>

            <input type="hidden" name="emergency_contact_no" value="<?= htmlspecialchars($participant['emergency_contact_no'] ?? '') ?>">
            <input type="hidden" name="emergency_contact_relationship" value="<?= htmlspecialchars($participant['emergency_contact_relationship'] ?? '') ?>">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="preferred_language">Preferred Language</label>
                    <select name="preferred_language" id="preferred_language" class="form-select" required>
                        <option value="" disabled>Select…</option>
                        <option value="Mandarin" <?= ($participant['preferred_language'] ?? '') === 'Mandarin' ? 'selected' : '' ?>>Mandarin</option>
                        <option value="English" <?= ($participant['preferred_language'] ?? '') === 'English' ? 'selected' : '' ?>>English</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label" for="registration_type">Registration Type</label>
                    <select name="registration_type" id="registration_type" class="form-select" required>
                        <option value="pre_register" <?= ($participant['registration_type'] ?? '') === 'pre_register' ? 'selected' : '' ?>>Pre-register</option>
                        <option value="walk_in" <?= ($participant['registration_type'] ?? '') === 'walk_in' ? 'selected' : '' ?>>Walk-in</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label" for="group_code">Group Assignment</label>
                    <select name="group_code" id="group_code" class="form-select">
                        <option value="">No Group</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= htmlspecialchars($g['group_code']) ?>" <?= ($participant['group_code'] ?? '') === $g['group_code'] ? 'selected' : '' ?>>
                                Group <?= htmlspecialchars($g['group_code']) ?> (<?= ucfirst($g['language_pool'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="blacklisted" name="blacklisted" value="1" <?= ($participant['blacklisted'] ?? 0) ? 'checked' : '' ?>>
                <label class="form-check-label" for="blacklisted">Blacklist participant (restricts check-in)</label>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/participants/list" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
