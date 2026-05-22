<?php
// Public-facing edit form for self-correction
?>

<div class="row">
    <div class="col-md-8 mx-auto mt-4">
        <h2>Edit Registration Details</h2>
        <p class="text-muted">Update your registered information below. Student ID is read-only.</p>

        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="post" action="/participants/update-public">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="student_id_display">Student ID</label>
                            <input type="text" id="student_id_display" class="form-control bg-light" value="<?= htmlspecialchars($participant['student_id'] ?? '') ?>" readonly>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input 
                                type="text" 
                                name="full_name" 
                                id="full_name" 
                                class="form-control" 
                                required 
                                placeholder="e.g. Liow Zhen Bang"
                                value="<?= htmlspecialchars($participant['full_name'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="student_email">Student Email</label>
                            <input 
                                type="email" 
                                name="student_email" 
                                id="student_email" 
                                class="form-control" 
                                required 
                                pattern="^[a-zA-Z0-9._%+-]+(?:-?[a-zA-Z0-9._%+-]+)*@student\.tarc\.edu\.my$"
                                title="Must be a valid student address ending with @student.tarc.edu.my"
                                value="<?= htmlspecialchars($participant['student_email'] ?? '') ?>"
                            >
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="gender">Gender</label>
                            <select name="gender" id="gender" class="form-select" required>
                                <option value="" disabled>Select…</option>
                                <option value="Male" <?= ($participant['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($participant['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Prefer not to say" <?= ($participant['gender'] ?? '') === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="faculty">Faculty</label>
                            <select name="faculty" id="faculty" class="form-select" required>
                                <option value="" disabled>Select faculty…</option>
                                <?php foreach (['FAFB', 'FOAS', 'FOCS', 'FOBE', 'FOET', 'FCCI', 'FSSH', 'CPUS'] as $fac): ?>
                                    <option value="<?= $fac ?>" <?= ($participant['faculty'] ?? '') === $fac ? 'selected' : '' ?>><?= $fac ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="programme_name">Programme</label>
                            <input 
                                type="text" 
                                name="programme_name" 
                                id="programme_name" 
                                class="form-control" 
                                required 
                                placeholder="e.g. Bachelor of Computer Science (Hons)"
                                value="<?= htmlspecialchars($participant['programme_name'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="contact_no">Contact No</label>
                            <input 
                                type="text" 
                                name="contact_no" 
                                id="contact_no" 
                                class="form-control" 
                                required 
                                pattern="^(0|60)[0-9]{9,10}$" 
                                placeholder="0167719430"
                                value="<?= htmlspecialchars($participant['contact_no'] ?? '') ?>"
                            >
                            <small class="form-text text-muted">Format: 01XXXXXXXX or 601XXXXXXXX</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="preferred_language">Preferred Language</label>
                            <select name="preferred_language" id="preferred_language" class="form-select" required>
                                <option value="" disabled>Select…</option>
                                <option value="Mandarin" <?= ($participant['preferred_language'] ?? '') === 'Mandarin' ? 'selected' : '' ?>>Mandarin</option>
                                <option value="English" <?= ($participant['preferred_language'] ?? '') === 'English' ? 'selected' : '' ?>>English</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="emergency_contact_no" value="<?= htmlspecialchars($participant['emergency_contact_no'] ?? '') ?>">
                    <input type="hidden" name="emergency_contact_relationship" value="<?= htmlspecialchars($participant['emergency_contact_relationship'] ?? '') ?>">

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="/participants/create" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
