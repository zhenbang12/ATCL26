    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important; box-shadow: 0 1px 3px var(--md-sys-color-shadow);">
                <div class="text-center mb-4">
                    <span class="material-symbols-outlined mb-3" style="font-size: 48px; color: var(--md-sys-color-primary);">shield</span>
                    <h4 class="fw-bold" style="color: var(--md-sys-color-on-surface);">ATCL Admin Login</h4>
                </div>

                <?php if (session()->has('error')): ?>
                    <div class="alert alert-danger mb-3" style="border-radius: 12px !important; background-color: var(--md-sys-color-error-container) !important; color: var(--md-sys-color-on-error-container) !important; border: none !important;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">error</span>
                        <?= session()->get('error') ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->has('success')): ?>
                    <div class="alert alert-success mb-3" style="border-radius: 12px !important; background-color: var(--md-sys-color-success-container) !important; color: var(--md-sys-color-on-success-container) !important; border: none !important;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">check_circle</span>
                        <?= session()->get('success') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/login">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold small" style="color: var(--md-sys-color-on-surface);">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">person</span> Username
                        </label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?= session()->get('old_username', '') ?>" required autofocus
                               placeholder="Enter your username"
                               style="border-radius: 12px !important; border: 1px solid var(--md-sys-color-outline-variant) !important; background-color: var(--md-sys-color-surface-container-lowest) !important; padding: 10px 14px;">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small" style="color: var(--md-sys-color-on-surface);">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">lock</span> Password
                        </label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control" 
                                   required placeholder="Enter your password"
                                   style="border-radius: 12px 0 0 12px !important; border: 1px solid var(--md-sys-color-outline-variant) !important; background-color: var(--md-sys-color-surface-container-lowest) !important; padding: 10px 14px;">
                            <button type="button" id="togglePassword" class="btn d-flex align-items-center justify-content-center" 
                                    style="border-radius: 0 12px 12px 0 !important; border: 1px solid var(--md-sys-color-outline-variant) !important; border-left: none !important; background-color: var(--md-sys-color-surface-container) !important; min-width: 48px; cursor: pointer;">
                                <span class="material-symbols-outlined" id="toggleIcon" style="font-size: 20px; color: var(--md-sys-color-on-surface-variant);">visibility</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn w-100 mb-3 fw-semibold" style="border-radius: 12px !important; background-color: var(--md-sys-color-primary) !important; color: var(--md-sys-color-on-primary) !important; border: none !important; padding: 10px 16px; transition: background-color 0.2s;"
                            onmouseover="this.style.backgroundColor='var(--md-sys-color-primary-hover)'" 
                            onmouseout="this.style.backgroundColor='var(--md-sys-color-primary)'">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">login</span> Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const toggleIcon = document.getElementById('toggleIcon');

        passwordInput.type = isPassword ? 'text' : 'password';
        toggleIcon.classList.toggle('fa-eye', !isPassword);
        toggleIcon.classList.toggle('fa-eye-slash', isPassword);
    });
</script>