<?php
// Result page for QR lookup
$lookupFrom = ($lookupFrom ?? 'pre-reg') === 'walk-in' ? 'walk-in' : 'pre-reg';
$registrationHref = $lookupFrom === 'walk-in' ? '/participants/create-walkin' : '/participants/create';
$registrationLabel = $lookupFrom === 'walk-in' ? 'Back to walk-in' : 'Back to pre-reg';
?>
<h2>Find My QR Code</h2>

<?php if (!empty($participant) && !empty($qrImage)): ?>
    <p class="mt-3">
        Hello, <strong><?= htmlspecialchars($participant['full_name']) ?></strong>. Here is your QR code for check-in:
    </p>
    <div class="mt-4 text-center">
        <div class="d-inline-block bg-white p-2 border rounded">
            <img src="<?= htmlspecialchars($qrImage) ?>" alt="QR Code for check-in">
        </div>
        <p class="mt-2 text-muted">
            Code: <code><?= htmlspecialchars($participant['qr_code']) ?></code>
        </p>
    </div>
<?php else: ?>
    <div class="alert alert-danger mt-3">
        We could not find a participant matching the details provided. Please check your Student ID and email, or contact the committee.
    </div>
<?php endif; ?>

<p class="mt-4">
    <a href="/participants/lookup?from=<?= htmlspecialchars($lookupFrom) ?>" class="btn btn-outline-secondary btn-sm">Search again</a>
    <a href="<?= htmlspecialchars($registrationHref) ?>" class="btn btn-link btn-sm"><?= htmlspecialchars($registrationLabel) ?></a>
</p>
