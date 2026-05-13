<?php
// Public message shown when a registration path is disabled.
$closedTitle = $closedTitle ?? 'Registration is currently closed';
?>

<div class="text-center py-5">
    <h2><?= htmlspecialchars($closedTitle) ?></h2>
    <p class="text-muted mb-4">
        Please check back later or contact the committee for more information.
    </p>
    <a href="/participants/lookup" class="btn btn-primary">Find My QR</a>
</div>
