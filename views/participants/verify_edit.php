<?php
// Public identity verification page before self-correction
?>

<div class="row">
    <div class="col-md-6 mx-auto mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Verify Your Identity</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    To edit your registration details, please enter the registered student email address associated with the Student ID below.
                </p>

                <?php if ($errorMessage !== null): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($errorMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post" action="/participants/verify-edit">
                    <div class="mb-3">
                        <label class="form-label" for="student_id_display">Student ID</label>
                        <input type="text" id="student_id_display" class="form-control bg-light" value="<?= htmlspecialchars($studentId) ?>" readonly>
                        <input type="hidden" name="student_id" value="<?= htmlspecialchars($studentId) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="student_email">Registered Student Email</label>
                        <input 
                            type="email" 
                            name="student_email" 
                            id="student_email" 
                            class="form-control" 
                            placeholder="e.g. liowzb-wm23@student.tarc.edu.my" 
                            required
                            pattern="^[a-zA-Z0-9._%+-]+(?:-?[a-zA-Z0-9._%+-]+)*@student\.tarc\.edu\.my$"
                            title="Must be a valid student address ending with @student.tarc.edu.my"
                        >
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Verify & Edit</button>
                        <a href="/participants/create" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
