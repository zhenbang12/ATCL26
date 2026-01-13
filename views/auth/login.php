<?php
// Simple login form for advisor / committee
?>
<h2>Login</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mt-3">
        Invalid credentials. Please try again.
    </div>
<?php endif; ?>

<form method="post" action="/login" class="mt-3" style="max-width: 320px;">
    <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Login</button>
</form>

