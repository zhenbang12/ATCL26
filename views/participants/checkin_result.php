<?php
// Show result of QR check-in including safety info
?>
<h2>Check-In Result</h2>

<?php if (!empty($participant)): ?>
    <div class="alert alert-success">
        <h5 class="mb-2">Check-in Successful</h5>
        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($participant['full_name']) ?></p>
        <p class="mb-1"><strong>Student ID:</strong> <?= htmlspecialchars($participant['student_id'] ?? 'N/A') ?></p>
        <?php if (!empty($participant['group_code'])): ?>
            <p class="mb-0"><strong>Group:</strong> <span class="badge bg-primary fs-6"><?= htmlspecialchars($participant['group_code']) ?></span></p>
        <?php else: ?>
            <p class="mb-0"><strong>Group:</strong> <span class="text-muted">Not assigned</span></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($participant['medical_notes']) || !empty($participant['dietary_notes'])): ?>
        <div class="alert alert-warning">
            <strong>Safety / Medical info:</strong><br>
            <?php if (!empty($participant['medical_notes'])): ?>
                <div><strong>Medical:</strong> <?= nl2br(htmlspecialchars($participant['medical_notes'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($participant['dietary_notes'])): ?>
                <div><strong>Dietary:</strong> <?= nl2br(htmlspecialchars($participant['dietary_notes'])) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-danger">
        <h5 class="mb-2">✗ Check-in Failed</h5>
        <p class="mb-0">QR code not recognised. Please try again or enter the code manually.</p>
    </div>
<?php endif; ?>

<a href="/participants/checkin" class="btn btn-secondary mt-3">Back to check-in</a>

