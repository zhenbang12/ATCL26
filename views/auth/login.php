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
            
            <div class="text-center mt-4">
                <a href="/" class="btn btn-link btn-sm text-decoration-none fw-semibold" style="color: var(--md-sys-color-primary) !important;">
                    <span class="material-symbols-outlined align-middle" style="font-size: 16px;">arrow_back</span>
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</div>
