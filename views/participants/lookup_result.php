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

    <?php if (!empty($participant['checked_in_at'])): ?>
        <div class="alert alert-success my-4 p-3 text-center shadow-sm">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
                <span class="fs-4 fw-bold text-success align-middle">Checked In</span>
            </div>
            <p class="mb-2 text-muted small">
                Checked in at: <strong><?= htmlspecialchars(date('d M Y, h:i A', strtotime($participant['checked_in_at']))) ?></strong>
            </p>
            <hr class="my-2">
            <p class="mb-0 fs-5">
                Group Number: <strong class="fs-3 text-primary"><?= htmlspecialchars($participant['group_code'] ?? 'Pending') ?></strong>
            </p>
        </div>
    <?php else: ?>
        <div class="alert alert-warning my-4 p-3 text-center shadow-sm">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-triangle-fill text-warning me-2" viewBox="0 0 16 16">
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                <span class="fs-5 fw-semibold text-dark align-middle">Not Checked In Yet</span>
            </div>
            <p class="mb-0 text-muted small">
                Please present this QR code to the registration counter for check-in.
            </p>
        </div>
    <?php endif; ?>

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
