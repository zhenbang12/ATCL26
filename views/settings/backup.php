<?php
// Admin: Database Backup & Restore page.
/** @var string|null $message */
/** @var string $messageType */
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Database Backup &amp; Restore</h2>
        <p class="text-muted small mb-0">Export system records or restore the database from a backup file.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert" style="border-radius: 12px;">
        <div class="d-flex align-items-center gap-2">
            <span class="material-symbols-outlined" style="font-size: 22px;">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <strong><?= htmlspecialchars($message) ?></strong>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Backup Card -->
    <div class="col-md-6">
        <div class="card h-100 p-4 border-0" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="card-body d-flex flex-column p-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary" style="font-size: 32px;">download</span>
                    <h5 class="fw-bold mb-0" style="color: var(--md-sys-color-on-surface);">Create Backup</h5>
                </div>
                <p class="text-muted mb-4 flex-grow-1" style="line-height: 1.6;">
                    Export all database tables, schemas, and configurations into a standard <code>.sql</code> file. You can download and save this file locally on your computer to keep a secure snapshot of your data.
                </p>
                
                <form method="post" action="/settings/backup/run" class="mt-auto m-0">
                    <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2.5 shadow-sm" style="border-radius: 12px; font-weight: 500;">
                        <span class="material-symbols-outlined">download</span>
                        Download SQL Backup
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Restore Card -->
    <div class="col-md-6">
        <div class="card h-100 p-4 border-0" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="card-body d-flex flex-column p-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-warning" style="font-size: 32px;">upload</span>
                    <h5 class="fw-bold mb-0" style="color: var(--md-sys-color-on-surface);">Restore Database</h5>
                </div>
                <p class="text-muted mb-4" style="line-height: 1.6;">
                    Upload a previously exported <code>.sql</code> backup file to restore your database. 
                </p>
                
                <div class="alert alert-warning d-flex align-items-start gap-2 py-3 px-3 mb-4" style="border-radius: 12px; font-size: 0.85rem; border: 1px solid rgba(255, 193, 7, 0.2); background-color: rgba(255, 193, 7, 0.05); color: #664d03;">
                    <span class="material-symbols-outlined mt-0.5" style="font-size: 20px; flex-shrink: 0;">warning</span>
                    <div>
                        <strong>WARNING:</strong> This action will overwrite all current data including active event sessions, participants, groupings, senior buddies, and configurations.
                    </div>
                </div>

                <form method="post" action="/settings/backup/restore" enctype="multipart/form-data" class="mt-auto m-0" onsubmit="return confirmRestore();">
                    <div class="mb-3">
                        <label for="backup_file" class="form-label fw-semibold small">Choose Backup File (.sql)</label>
                        <input type="file" name="backup_file" id="backup_file" class="form-control" accept=".sql" required style="border-radius: 8px;">
                    </div>
                    
                    <button type="submit" class="btn btn-warning w-100 d-flex align-items-center justify-content-center gap-2 py-2.5 shadow-sm" style="border-radius: 12px; font-weight: 500; background-color: var(--md-sys-color-tertiary-container, #ffd8e4); color: var(--md-sys-color-on-tertiary-container, #3e001d); border: 1px solid var(--md-sys-color-outline-variant);">
                        <span class="material-symbols-outlined">restore</span>
                        Restore SQL Backup
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmRestore() {
    return confirm("Are you absolutely sure you want to restore the database?\n\nThis will permanently delete all current configurations, participants, group assignments, and active logs. This action cannot be undone.");
}
</script>
