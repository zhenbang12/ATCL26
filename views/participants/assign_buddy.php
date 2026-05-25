<?php
// Senior Buddy Assignment Page (Optimized with Bulk Saving)
$groups = $groups ?? [];
$facilitators = $facilitators ?? [];
$facilitatorByGroup = $facilitatorByGroup ?? [];

$message = $_SESSION['grouping_message'] ?? null;
$messageType = $_SESSION['grouping_message_type'] ?? 'info';
if (isset($_SESSION['grouping_message'])) {
    unset($_SESSION['grouping_message'], $_SESSION['grouping_message_type']);
}

// Calculate unassigned facilitators
$assignedFacilitatorIds = [];
foreach ($facilitatorByGroup as $gCode => $buddies) {
    foreach ($buddies as $b) {
        $assignedFacilitatorIds[] = (int)$b['id'];
    }
}
$unassignedFacilitators = array_filter($facilitators, function($f) use ($assignedFacilitatorIds) {
    return !in_array((int)$f['id'], $assignedFacilitatorIds, true);
});
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Title Panel -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Assign Senior Buddies</h2>
        <p class="text-muted small mb-0">Assign senior buddies (facilitators) to active group pools.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/operations/crew" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <span class="material-symbols-outlined" style="font-size: 18px;">manage_accounts</span>
            Senior Buddy Info
        </a>
        <a href="/participants/groups" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <span class="material-symbols-outlined" style="font-size: 18px;">group_work</span>
            Grouping Console
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Unassigned Facilitators Sidebar/Info Panel -->
    <div class="col-lg-3 col-md-4">
        <div class="card h-100 p-3 border-0" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-1" style="font-size: 0.95rem; color: var(--md-sys-color-on-surface);">
                <span class="material-symbols-outlined text-primary" style="font-size: 20px;">account_circle</span>
                Unassigned Buddies
            </h5>
            <?php if (!empty($unassignedFacilitators)): ?>
                <div class="d-flex flex-column gap-2" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($unassignedFacilitators as $f): ?>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background-color: var(--md-sys-color-surface-container); border: 1px solid var(--md-sys-color-outline-variant);">
                            <span class="small fw-semibold" style="font-size: 0.8rem;"><?= htmlspecialchars($f['full_name']) ?></span>
                            <span class="badge" style="font-size: 0.7rem; padding: 0.35em 0.65em; background-color: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; font-weight: 600;">Available</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted small">
                    <span class="material-symbols-outlined" style="font-size: 32px; opacity: 0.5;">done_all</span>
                    <p class="mt-2 mb-0">All senior buddies are currently assigned!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Groups Grid -->
    <div class="col-lg-9 col-md-8">
        <?php if (empty($groups)): ?>
            <div class="card p-5 border-0 text-center text-muted" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important;">
                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.5;">group_work</span>
                <p class="mt-2 mb-0">No group shells exist. Create group shells in the Grouping Console first.</p>
                <a href="/participants/groups" class="btn btn-primary btn-sm mt-3">Go to Grouping Console</a>
            </div>
        <?php else: ?>
            <form method="post" action="/participants/groups/assign-facilitators-bulk">
                <div class="card p-4 border-0 mb-3" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important;">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <h5 class="fw-bold mb-0" style="color: var(--md-sys-color-on-surface);">Group Assignment Matrix</h5>
                        <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" style="border-radius: 8px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                            Save All Assignments
                        </button>
                    </div>
                    
                    <div class="row g-3">
                        <?php foreach ($groups as $groupRow): ?>
                            <?php 
                            $groupCode = (string)($groupRow['group_code'] ?? '');
                            if ($groupCode === '') { continue; }
                            $assignedBuddies = $facilitatorByGroup[$groupCode] ?? [];
                            $slot1Id = isset($assignedBuddies[0]['id']) ? (int)$assignedBuddies[0]['id'] : 0;
                            $slot2Id = isset($assignedBuddies[1]['id']) ? (int)$assignedBuddies[1]['id'] : 0;
                            $languagePool = strtolower($groupRow['language_pool'] ?? '');
                            if ($languagePool === 'english') {
                                $badgeStyle = 'background-color: #e3f2fd; color: #0d47a1; border: 1px solid #bbdefb;';
                            } else {
                                $badgeStyle = 'background-color: #fff3e0; color: #e65100; border: 1px solid #ffe0b2;';
                            }
                            ?>
                            
                            <div class="col-xl-4 col-md-6 col-sm-12">
                                <div class="card h-100 border p-3" style="border-radius: 16px; background-color: var(--md-sys-color-surface-container-high) !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">Group <?= htmlspecialchars($groupCode) ?></h6>
                                        <span class="badge text-uppercase" style="font-size: 0.7rem; padding: 0.35em 0.65em; font-weight: 600; <?= $badgeStyle ?>"><?= htmlspecialchars($languagePool) ?></span>
                                    </div>
                                    
                                    <div class="d-flex flex-column gap-2">
                                        <div>
                                            <label class="form-label mb-1 text-muted" style="font-size: 0.72rem;">Senior Buddy 1</label>
                                            <select name="assignments[<?= htmlspecialchars($groupCode) ?>][]" class="form-select form-select-sm" style="border-radius: 8px;">
                                                <option value="0">Unassigned</option>
                                                <?php foreach ($facilitators as $facilitator): ?>
                                                    <?php
                                                    $facilitatorId = (int)$facilitator['id'];
                                                    $assignedGroup = trim((string)($facilitator['assigned_group_code'] ?? ''));
                                                    if ($assignedGroup !== '' && $assignedGroup !== $groupCode && $slot1Id !== $facilitatorId) {
                                                        continue;
                                                    }
                                                    ?>
                                                    <option value="<?= $facilitatorId ?>" <?= $slot1Id === $facilitatorId ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($facilitator['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="form-label mb-1 text-muted" style="font-size: 0.72rem;">Senior Buddy 2</label>
                                            <select name="assignments[<?= htmlspecialchars($groupCode) ?>][]" class="form-select form-select-sm" style="border-radius: 8px;">
                                                <option value="0">Unassigned</option>
                                                <?php foreach ($facilitators as $facilitator): ?>
                                                    <?php
                                                    $facilitatorId = (int)$facilitator['id'];
                                                    $assignedGroup = trim((string)($facilitator['assigned_group_code'] ?? ''));
                                                    if ($assignedGroup !== '' && $assignedGroup !== $groupCode && $slot2Id !== $facilitatorId) {
                                                        continue;
                                                    }
                                                    ?>
                                                    <option value="<?= $facilitatorId ?>" <?= $slot2Id === $facilitatorId ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($facilitator['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
