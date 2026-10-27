<?php
// Secure login form for advisor / committee
?>
<div class="d-flex justify-content-center align-items-center my-4" style="min-height: 60vh;">
    <div class="card p-4 border-0" style="width: 100%; max-width: 420px; background-color: var(--md-sys-color-surface-container-low) !important; box-shadow: 0 4px 24px var(--md-sys-color-shadow) !important;">
        <div class="card-body">
            <div class="text-center mb-4">
                <span class="material-symbols-outlined text-primary mb-2" style="font-size: 48px;">lock</span>
                <h2 class="h4 fw-bold">Sign In</h2>
                <p class="text-muted small">Advisor & Committee Portal</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-4">
                    Invalid credentials. Please try again.
                </div>
            <?php endif; ?>

            <form method="post" action="/login">
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" required autofocus autocomplete="username">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
            </form>

            <div class="position-relative my-4 text-center">
                <hr class="text-muted opacity-25 m-0">
                <span class="position-absolute top-50 start-50 translate-middle px-3 text-muted small" style="background-color: var(--md-sys-color-surface-container-low) !important;">or</span>
            </div>

            <div class="d-grid gap-2">
                <a href="/demo" id="demoLoginBtn" class="btn btn-outline-primary py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold" style="border-radius: var(--bs-border-radius, 8px);">
                    <span class="material-symbols-outlined" style="font-size: 20px;">science</span>
                    <span>Demo Login (1-Click)</span>
                </a>
            </div>

            <div class="d-flex align-items-center justify-content-center gap-2 mt-2 pt-1 text-muted small">
                <span>Quick demo:</span>
                <a href="/demo?role=committee" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none px-2 py-1 rounded-pill" title="1-Click Login as Demo Committee">
                    Committee
                </a>
                <a href="/demo?role=advisor" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-decoration-none px-2 py-1 rounded-pill" title="1-Click Login as Demo Advisor">
                    Advisor
                </a>
                <span class="mx-1 text-muted opacity-50">|</span>
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted" id="autofillDemoBtn" onclick="fillDemoCredentials('demo_committee', 'demopass2026')" title="Fill form with demo credentials">
                    Auto-fill
                </button>
            </div>
            
            <div class="text-center mt-4">
                <a href="/" class="btn btn-link btn-sm text-decoration-none fw-semibold" style="color: var(--md-sys-color-primary) !important;">
                    <span class="material-symbols-outlined align-middle" style="font-size: 16px;">arrow_back</span>
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemoCredentials(username, password) {
    const userInput = document.getElementById('username');
    const passInput = document.getElementById('password');
    if (userInput && passInput) {
        userInput.value = username;
        passInput.value = password;
        passInput.focus();
    }
}
</script>
