<?php
// Show registration success and QR code for check-in
?>
<h2>Registration Successful</h2>

<?php if (!empty($participant)): ?>
    <p class="mt-3">
        Thank you, <strong><?= htmlspecialchars($participant['full_name']) ?></strong>.
        Please save the QR code below and present it during check-in.
    </p>
    <?php if (($participant['registration_type'] ?? 'pre_register') === 'walk_in' && !empty($participant['group_code'])): ?>
        <div class="alert alert-info mt-2">
            You are automatically assigned to <strong>Group <?= htmlspecialchars((string)$participant['group_code']) ?></strong>.
        </div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <?php $qrValue = $participant['qr_code'] ?? ''; ?>
        <?php if (!empty($qrImage)): ?>
            <div class="d-inline-block bg-white p-2 border rounded">
                <img src="<?= htmlspecialchars($qrImage) ?>" alt="QR Code for check-in">
            </div>
        <?php endif; ?>
        <p class="mt-2 text-muted">
            Code: <code><?= htmlspecialchars($qrValue) ?></code>
        </p>
    </div>

    <p class="mt-4">
        You can screenshot or download this QR. If there are issues scanning, the crew can also type the code above
        into the check-in system.
    </p>
<?php else: ?>
    <div class="alert alert-warning mt-3">
        Registration data could not be loaded. Please contact the committee.
    </div>
<?php endif; ?>

