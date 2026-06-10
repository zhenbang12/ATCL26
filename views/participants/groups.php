<?php
// Grouping overview with auto-assignment
$groups = $groups ?? [];
$ungrouped = (int)($ungrouped ?? 0);
$participantsByGroup = $participantsByGroup ?? [];
$ungroupedParticipants = $ungroupedParticipants ?? [];
$groupTypes = $groupTypes ?? [];
$groupMaxMap = $groupMaxMap ?? [];
$recentMoveLogs = $recentMoveLogs ?? [];
$facilitators = $facilitators ?? [];
$facilitatorByGroup = $facilitatorByGroup ?? [];
$currentMaxPerGroup = (int)($currentMaxPerGroup ?? 0);
$currentEnglishGroups = (int)($currentEnglishGroups ?? 0);
$currentMover = \App\Core\Auth::user()['username'] ?? 'Unknown';

$message = $_SESSION['grouping_message'] ?? null;
$messageType = $_SESSION['grouping_message_type'] ?? 'info';
if (isset($_SESSION['grouping_message'])) {
    unset($_SESSION['grouping_message'], $_SESSION['grouping_message_type']);
}
$maxPerGroupLabel = $currentMaxPerGroup > 0 ? (string)$currentMaxPerGroup : 'No limit';

$allParticipants = [];
foreach ($participantsByGroup as $gCode => $list) {
    foreach ($list as $p) {
        $p['group_code'] = (string)$gCode;
        $allParticipants[] = $p;
    }
}
foreach ($ungroupedParticipants as $p) {
    $p['group_code'] = '';
    $allParticipants[] = $p;
}
usort($allParticipants, function($a, $b) {
    return strcmp(strtolower($a['full_name'] ?? ''), strtolower($b['full_name'] ?? ''));
});
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">Grouping Overview</h2>
    <a href="/participants/export-groups" class="btn btn-success btn-sm" style="border-radius: 100px;">
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">download</span> Export Grouping to CSV
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <!-- Collapsible Header -->
            <div class="card-header d-flex justify-content-between align-items-center py-3" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#groupingOptionsCollapse" aria-expanded="<?= count($groups) > 0 ? 'false' : 'true' ?>" aria-controls="groupingOptionsCollapse">
                <h5 class="m-0 d-flex align-items-center gap-2" style="font-size: 1.15rem; color: var(--md-sys-color-on-surface);">
                    <span class="material-symbols-outlined text-primary">settings</span>
                    Grouping Configuration & Options
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary-container text-on-secondary-container small" style="font-size: 0.75rem;">
                        <?= count($groups) ?> Groups
                    </span>
                    <span class="material-symbols-outlined transition-icon" style="<?= count($groups) > 0 ? '' : 'transform: rotate(180deg);' ?>">expand_more</span>
                </div>
            </div>
            <!-- Collapsible Body -->
            <div class="collapse <?= count($groups) > 0 ? '' : 'show' ?>" id="groupingOptionsCollapse">
                <div class="card-body pt-3">
                    <h5 class="card-title d-none">Grouping Options</h5>
                <div class="card card-body bg-light border mb-2">
                    <h6 class="mb-2">Save empty group shells</h6>
                    <form method="post" action="/participants/groups/save-layout" class="d-flex flex-wrap align-items-end gap-2 mb-0">
                        <div>
                            <label for="num_groups_custom" class="form-label form-label-sm mb-1">Total groups</label>
                            <input type="number" name="num_groups" id="num_groups_custom" class="form-control form-control-sm" value="<?= max(1, count($groups) ?: 8) ?>" min="1" max="99" required style="width: 120px;">
                        </div>
                        <div>
                            <label for="english_groups" class="form-label form-label-sm mb-1">English pool (first N group numbers)</label>
                            <input type="number" name="english_groups" id="english_groups" class="form-control form-control-sm" value="<?= max(1, $currentEnglishGroups ?: 2) ?>" min="1" max="99" required style="width: 120px;">
                        </div>
                        <div>
                            <label for="max_per_group" class="form-label form-label-sm mb-1">Max per group at check-in (0 = no limit)</label>
                            <input type="number" name="max_per_group" id="max_per_group" class="form-control form-control-sm" value="<?= $currentMaxPerGroup ?>" min="0" max="300" style="width: 120px;">
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm">Save group layout</button>
                    </form>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                        <form method="post" action="/participants/groups/add-group" class="m-0">
                            <button type="submit" class="btn btn-outline-primary btn-sm">+1 Group</button>
                        </form>
                        <form method="post" action="/participants/groups/add-slot" class="m-0">
                            <button type="submit" class="btn btn-outline-primary btn-sm">+1 Slot Per Group</button>
                        </form>
                        <span class="small text-muted">
                            Current max per group: <strong><?= htmlspecialchars($maxPerGroupLabel) ?></strong>
                        </span>
                    </div>
                    <p class="small text-muted mb-0 mt-2">
                        This only creates empty group shells (Group 1, Group 2, …). Participants stay ungrouped until they check in;
                        then they are placed round-robin into the English or Mandarin pool matching their preferred language.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
                    <span><strong>Total Groups:</strong> <?= count($groups) ?></span>
                    <span><strong>Max Per Group:</strong> <?= htmlspecialchars($maxPerGroupLabel) ?></span>
                    <span><strong>Ungrouped Participants:</strong> <?= (int)$ungrouped ?></span>
                    <span><strong>Total Grouped:</strong> <?= array_sum(array_column($groups, 'count')) ?></span>
                </div>
                <hr>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <form method="post" action="/participants/clear-groups" class="d-inline m-0" onsubmit="return confirm('Clear every participant\'s group assignment? Group shells stay saved; only roster slots are cleared.');">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Clear participant group assignments</button>
                    </form>
                    <form method="post" action="/participants/clear-group-shells" class="d-inline m-0" onsubmit="return confirm('Remove the saved group layout (empty shells), reset max-per-group at check-in to unlimited, and clear senior buddy → group links? This does not clear participant group numbers—use the other button if you need that too.');">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Clear group shells</button>
                    </form>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<div id="drag-alert" class="alert d-none" role="alert"></div>

<style>
    .group-lane {
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 16px;
        background: var(--md-sys-color-surface-container-low);
    }

    .drop-zone {
        min-height: 140px;
        transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        border: 2px dashed transparent;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .drop-zone.drag-active {
        border-color: var(--md-sys-color-primary);
        background: var(--md-sys-color-primary-container);
        box-shadow: inset 0 0 0 1px rgba(103, 80, 164, 0.2);
    }

    .participant-item {
        cursor: grab;
        border: 1px solid var(--md-sys-color-outline-variant) !important;
        border-radius: 12px !important;
        background-color: var(--md-sys-color-surface-container-lowest) !important;
        box-shadow: 0 1px 2px var(--md-sys-color-shadow) !important;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .participant-item:hover {
        box-shadow: 0 4px 8px var(--md-sys-color-shadow) !important;
    }

    .member-number {
        min-width: 28px;
        text-align: center;
        font-weight: 600;
        font-size: 12px;
    }

    .facilitator-pill {
        font-size: 12px;
        border: 1px solid var(--md-sys-color-outline-variant);
        background: var(--md-sys-color-secondary-container);
        color: var(--md-sys-color-on-secondary-container);
        border-radius: 999px;
        padding: 4px 12px;
        display: inline-block;
        margin-bottom: 6px;
        font-weight: 500;
    }

    .checkin-pill {
        font-size: 11px;
        border-radius: 999px;
        padding: 2px 8px;
        display: inline-block;
        margin-top: 4px;
        font-weight: 500;
    }

    .checkin-pill.checked {
        background: var(--md-sys-color-success-container);
        color: var(--md-sys-color-on-success-container);
        border: 1px solid var(--md-sys-color-outline-variant);
    }

    .checkin-pill.pending {
        background: var(--md-sys-color-tertiary-container);
        color: var(--md-sys-color-on-tertiary-container);
        border: 1px solid var(--md-sys-color-outline-variant);
    }

    .participant-item.dragging {
        opacity: 0.55;
        transform: scale(0.98);
        cursor: grabbing;
    }

    .participant-item.selected {
        border: 2px solid var(--md-sys-color-primary) !important;
        background: var(--md-sys-color-primary-container) !important;
    }

    .drag-hint {
        font-size: 12px;
        color: var(--md-sys-color-on-surface-variant);
    }

    .lane-toggle {
        font-size: 12px;
        padding: 2px 8px;
    }

    .drop-zone.collapsed {
        display: none;
    }

    .group-lane.minimized {
        height: auto !important;
        padding-bottom: 8px !important;
        background: var(--md-sys-color-surface-container);
    }

    .group-lane.minimized .lane-header {
        margin-bottom: 0 !important;
    }

    .group-lane.header-drag-active .lane-header {
        background: var(--md-sys-color-primary-container);
        border: 1px dashed var(--md-sys-color-primary);
        border-radius: 8px;
        padding: 6px;
    }

    .transition-icon {
        transition: transform 0.2s ease;
    }
    [aria-expanded="true"] .transition-icon {
        transform: rotate(180deg) !important;
    }
</style>

<ul class="nav nav-tabs mb-3" id="groupingTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-bulk-table-tab" data-bs-toggle="tab" data-bs-target="#tab-bulk-table" type="button" role="tab" aria-controls="tab-bulk-table" aria-selected="true">
            Bulk Assign Table
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-workspace-tab" data-bs-toggle="tab" data-bs-target="#tab-workspace" type="button" role="tab" aria-controls="tab-workspace" aria-selected="false">
            Drag &amp; Drop Workspace
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-reports-tab" data-bs-toggle="tab" data-bs-target="#tab-reports" type="button" role="tab" aria-controls="tab-reports" aria-selected="false">
            Distribution &amp; Lists
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- 1. Bulk Assign Table Pane -->
    <div class="tab-pane fade show active" id="tab-bulk-table" role="tabpanel" aria-labelledby="tab-bulk-table-tab">
        <div class="card p-3 border-0 mb-4" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 20px !important;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Bulk Assignment Console</h5>
            </div>

            <!-- Toolbar -->
            <div class="row g-3 mb-3 align-items-center">
                <div class="col-md-3">
                    <input type="text" id="bulkSearchInput" class="form-control form-control-sm" placeholder="Search name or ID..." style="border-radius: 8px !important;">
                </div>
                <div class="col-md-2">
                    <select id="bulkLanguageFilter" class="form-select form-select-sm" style="border-radius: 8px !important;">
                        <option value="all">All Languages</option>
                        <option value="english-speaking group">English-speaking Group</option>
                        <option value="mandarin-speaking group">Mandarin-speaking Group</option>
                        <option value="both language speaking group">Both language speaking group</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="bulkGroupFilter" class="form-select form-select-sm" style="border-radius: 8px !important;">
                        <option value="all">All Groups</option>
                        <option value="ungrouped">Ungrouped</option>
                        <?php foreach ($participantsByGroup as $gCode => $unused): ?>
                            <option value="<?= htmlspecialchars($gCode) ?>">Group <?= htmlspecialchars($gCode) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Controls -->
                <div class="col-md-5 d-flex gap-2 justify-content-md-end align-items-center flex-wrap">
                    <span class="small text-muted fw-semibold" id="bulkSelectedCount">0 selected</span>
                    <select id="bulkTargetGroup" class="form-select form-select-sm" style="width: 140px; border-radius: 8px !important;">
                        <option value="">Move to group…</option>
                        <option value="ungrouped">Ungrouped</option>
                        <?php foreach ($participantsByGroup as $gCode => $unused): ?>
                            <option value="<?= htmlspecialchars($gCode) ?>">Group <?= htmlspecialchars($gCode) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="bulkMoveBtn" class="btn btn-primary btn-sm" disabled style="border-radius: 8px;">Move Selected</button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" id="bulkTable">
                    <thead>
                        <tr>
                            <th style="width: 36px;">
                                <input type="checkbox" id="bulkSelectAll" class="form-check-input">
                            </th>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Language</th>
                            <th>Current Group</th>
                            <th>Checked In?</th>
                        </tr>
                    </thead>
                    <tbody id="bulkTableBody">
                        <?php foreach ($allParticipants as $p): ?>
                            <tr
                                data-id="<?= (int)$p['id'] ?>"
                                data-name="<?= htmlspecialchars(strtolower($p['full_name'] ?? '')) ?>"
                                data-student-id="<?= htmlspecialchars(strtolower($p['student_id'] ?? '')) ?>"
                                data-language="<?= htmlspecialchars(strtolower($p['preferred_language'] ?? '')) ?>"
                                data-group="<?= htmlspecialchars($p['group_code'] ?? '') ?>"
                            >
                                <td><input type="checkbox" class="form-check-input bulk-checkbox" value="<?= (int)$p['id'] ?>"></td>
                                <td class="fw-semibold"><?= htmlspecialchars($p['full_name'] ?? '') ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($p['student_id'] ?? '—') ?></td>
                                <td>
                                    <?php
                                        $lang = strtolower($p['preferred_language'] ?? '');
                                        if (strpos($lang, 'both') !== false) {
                                            $langClass = 'bg-dark';
                                        } elseif (strpos($lang, 'mandarin') !== false) {
                                            $langClass = 'bg-tertiary';
                                        } else {
                                            $langClass = 'bg-secondary';
                                        }
                                    ?>
                                    <span class="badge <?= $langClass ?>" style="font-size:0.72rem"><?= htmlspecialchars($p['preferred_language'] ?? '—') ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($p['group_code'])): ?>
                                        <span class="badge bg-primary" style="font-size:0.72rem">Group <?= htmlspecialchars($p['group_code']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-surface" style="font-size:0.72rem">Ungrouped</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($p['checked_in_at'])): ?>
                                        <span class="badge bg-success" style="font-size:0.72rem">✓ Checked In</span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        (function() {
            const searchInput   = document.getElementById('bulkSearchInput');
            const langFilter    = document.getElementById('bulkLanguageFilter');
            const groupFilter   = document.getElementById('bulkGroupFilter');
            const targetGroup   = document.getElementById('bulkTargetGroup');
            const moveBtn       = document.getElementById('bulkMoveBtn');
            const selectAll     = document.getElementById('bulkSelectAll');
            const countLabel    = document.getElementById('bulkSelectedCount');
            const tableBody     = document.getElementById('bulkTableBody');

            function visibleRows() {
                return Array.from(tableBody.querySelectorAll('tr')).filter(r => r.style.display !== 'none');
            }

            function updateSelectAllState() {
                const vr = visibleRows();
                const checked = vr.filter(r => r.querySelector('.bulk-checkbox').checked);
                selectAll.checked = vr.length > 0 && checked.length === vr.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < vr.length;
                const totalChecked = tableBody.querySelectorAll('.bulk-checkbox:checked').length;
                countLabel.textContent = totalChecked + ' selected';
                moveBtn.disabled = totalChecked === 0 || !targetGroup.value;
            }

            function applyFilters() {
                const q  = searchInput.value.toLowerCase().trim();
                const lg = langFilter.value;
                const gf = groupFilter.value;

                Array.from(tableBody.querySelectorAll('tr')).forEach(row => {
                    const name   = row.dataset.name || '';
                    const sid    = row.dataset.studentId || '';
                    const lang   = row.dataset.language || '';
                    const group  = row.dataset.group || '';

                    const matchQ  = !q || name.includes(q) || sid.includes(q);
                    const matchL  = lg === 'all' || lang === lg || lang.includes(lg.replace('-speaking group', '')) || lg.includes(lang);
                    const matchG  = gf === 'all' || (gf === 'ungrouped' ? group === '' : group === gf);

                    row.style.display = matchQ && matchL && matchG ? '' : 'none';
                });

                updateSelectAllState();
            }

            searchInput.addEventListener('input', applyFilters);
            langFilter.addEventListener('change', applyFilters);
            groupFilter.addEventListener('change', applyFilters);
            targetGroup.addEventListener('change', updateSelectAllState);

            selectAll.addEventListener('change', function() {
                visibleRows().forEach(r => r.querySelector('.bulk-checkbox').checked = this.checked);
                updateSelectAllState();
            });

            tableBody.addEventListener('change', function(e) {
                if (e.target.classList.contains('bulk-checkbox')) updateSelectAllState();
            });

            // Bulk move result alert container (created once)
            const bulkResultContainer = document.createElement('div');
            bulkResultContainer.id = 'bulk-move-result';
            moveBtn.closest('.card').querySelector('.d-flex.justify-content-between').insertAdjacentElement('afterend', bulkResultContainer);

            moveBtn.addEventListener('click', async function() {
                const target = targetGroup.value;
                if (!target) return;
                const checkedBoxes = tableBody.querySelectorAll('.bulk-checkbox:checked');
                const ids = Array.from(checkedBoxes).map(c => c.value);
                if (!ids.length) return;

                moveBtn.disabled = true;
                moveBtn.textContent = 'Moving…';

                try {
                    const params = new URLSearchParams();
                    params.append('target_group', target === 'ungrouped' ? '' : target);
                    ids.forEach(id => params.append('participant_ids[]', id));

                    const res = await fetch('/participants/groups/bulk-move', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                        body: params.toString()
                    });
                    const data = await res.json();
                    if (data.success) {
                        // Build success alert with move details
                        const targetLabel = target === 'ungrouped' ? 'Ungrouped' : 'Group ' + target;
                        const movedNames = Array.from(checkedBoxes).map(cb => {
                            const row = cb.closest('tr');
                            return row ? row.querySelector('td:nth-child(2)')?.textContent?.trim() : '';
                        }).filter(Boolean);

                        let logHtml = '<ul class="mb-0" style="font-size:0.82rem; padding-left: 1.2rem;">';
                        const now = new Date();
                        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        movedNames.forEach(name => {
                            logHtml += '<li>' + escapeHtmlBulk(name) + ' moved to ' + escapeHtmlBulk(targetLabel) + ' at ' + timeStr + '</li>';
                        });
                        logHtml += '</ul>';

                        bulkResultContainer.innerHTML =
                            '<div class="alert alert-success alert-dismissible fade show mt-3 mb-0" role="alert">' +
                            '<div class="d-flex align-items-center gap-2 mb-2">' +
                            '<span class="material-symbols-outlined" style="font-size: 22px;">check_circle</span>' +
                            '<strong>' + ids.length + ' participant(s) moved to ' + escapeHtmlBulk(targetLabel) + '</strong>' +
                            '</div>' +
                            '<div class="mb-1"><strong>Move Log:</strong></div>' +
                            logHtml +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>';

                        // Update the table rows in-place
                        checkedBoxes.forEach(cb => {
                            const row = cb.closest('tr');
                            if (!row) return;
                            // Update data-group attribute
                            row.dataset.group = target === 'ungrouped' ? '' : target;
                            // Update the Current Group cell (5th column)
                            const groupCell = row.querySelector('td:nth-child(5)');
                            if (groupCell) {
                                if (target === 'ungrouped') {
                                    groupCell.innerHTML = '<span class="badge bg-surface" style="font-size:0.72rem">Ungrouped</span>';
                                } else {
                                    groupCell.innerHTML = '<span class="badge bg-primary" style="font-size:0.72rem">Group ' + escapeHtmlBulk(target) + '</span>';
                                }
                            }
                            // Uncheck
                            cb.checked = false;
                        });

                        // Reset controls
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                        targetGroup.value = '';
                        countLabel.textContent = '0 selected';
                        moveBtn.disabled = true;
                        moveBtn.textContent = 'Move Selected';
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                        moveBtn.disabled = false;
                        moveBtn.textContent = 'Move Selected';
                    }
                } catch (err) {
                    alert('Network error. Please try again.');
                    moveBtn.disabled = false;
                    moveBtn.textContent = 'Move Selected';
                }
            });

            function escapeHtmlBulk(s) {
                if (!s) return '';
                const map = { '&': '\u0026amp;', '<': '\u0026lt;', '>': '\u0026gt;', '"': '\u0026quot;', "'": '\u0026#039;' };
                return s.replace(/[&<>"']/g, c => map[c]);
            }
        })();
        </script>
    </div>

    <!-- 2. Drag & Drop Workspace Pane -->
    <div class="tab-pane fade" id="tab-workspace" role="tabpanel" aria-labelledby="tab-workspace-tab">
        <div class="row mb-4" data-current-mover="<?= htmlspecialchars($currentMover) ?>" data-latest-move-log-id="<?= (int)($latestMoveLogId ?? 0) ?>" id="group-editor-root">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div>
                        <h5 class="card-title mb-1">Drag-and-Drop Group Editor</h5>
                        <p class="text-muted small mb-0">Drag participant cards between lanes. Every move is tracked below and can be undone.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="minimize-all-lanes" type="button" class="btn btn-outline-secondary btn-sm">Minimize All</button>
                        <button id="expand-all-lanes" type="button" class="btn btn-outline-primary btn-sm">Expand All</button>
                        <span id="selected-count" class="badge bg-success align-self-center">0 selected</span>
                        <button id="clear-selection" type="button" class="btn btn-outline-secondary btn-sm">Clear Selection</button>
                        <button id="undo-last-move" type="button" class="btn btn-outline-warning btn-sm" disabled>Undo Last Move</button>
                    </div>
                </div>
                <p class="drag-hint mb-3">Tip: click cards to multi-select, then drag one selected card to move all selected participants together.</p>

                <div class="border rounded p-2 mb-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <strong class="small">Visible Groups</strong>
                        <div class="d-flex gap-2">
                            <button type="button" id="show-all-groups" class="btn btn-outline-primary btn-sm">Show All</button>
                            <button type="button" id="hide-all-groups" class="btn btn-outline-secondary btn-sm">Hide All</button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($participantsByGroup as $groupCode => $participants): ?>
                            <label class="form-check-label small">
                                <input class="form-check-input me-1 group-visibility-toggle" type="checkbox" data-group-column="group-col-<?= htmlspecialchars($groupCode) ?>" checked>
                                Group <?= htmlspecialchars($groupCode) ?>
                            </label>
                        <?php endforeach; ?>
                        <label class="form-check-label small">
                            <input class="form-check-input me-1 group-visibility-toggle" type="checkbox" data-group-column="group-col-ungrouped" checked>
                            Ungrouped
                        </label>
                    </div>
                </div>

                <div class="row g-3">
                    <?php foreach ($participantsByGroup as $groupCode => $participants): ?>
                        <div class="col-md-4 group-column" id="group-col-<?= htmlspecialchars($groupCode) ?>">
                            <div class="group-lane p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2 lane-header">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <strong>Group <?= htmlspecialchars($groupCode) ?></strong>
                                        <?php
                                            $gMax = $groupMaxMap[$groupCode] ?? 0;
                                            $effectiveGMax = $gMax > 0 ? $gMax : $currentMaxPerGroup;
                                            $gMaxLabel = $effectiveGMax > 0 ? (string)$effectiveGMax : 'No limit';
                                        ?>
                                        <span class="badge bg-info text-dark">Max <?= htmlspecialchars($gMaxLabel) ?></span>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($groupTypes[$groupCode] ?? 'Mixed') ?></span>
                                        <span class="badge bg-primary group-count" data-count-group="<?= htmlspecialchars($groupCode) ?>"><?= count($participants) ?></span>
                                        <button type="button" class="btn btn-sm p-0 d-flex align-items-center justify-content-center slot-adjust-btn" data-group="<?= htmlspecialchars($groupCode) ?>" data-delta="-1" style="width:20px;height:20px;border-radius:50%;background:var(--md-sys-color-surface-container-high);border:1px solid var(--md-sys-color-outline-variant);font-size:14px;line-height:1;" title="Remove 1 slot from Group <?= htmlspecialchars($groupCode) ?>">&#8722;</button>
                                        <button type="button" class="btn btn-sm p-0 d-flex align-items-center justify-content-center slot-adjust-btn" data-group="<?= htmlspecialchars($groupCode) ?>" data-delta="1" style="width:20px;height:20px;border-radius:50%;background:var(--md-sys-color-primary-container);border:1px solid var(--md-sys-color-outline-variant);font-size:14px;line-height:1;" title="Add 1 slot to Group <?= htmlspecialchars($groupCode) ?>">&#43;</button>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm lane-toggle" data-toggle-target="group-zone-<?= htmlspecialchars($groupCode) ?>">Minimize</button>
                                </div>
                                <div class="drop-zone rounded p-2 bg-light" id="group-zone-<?= htmlspecialchars($groupCode) ?>" data-target-group="<?= htmlspecialchars($groupCode) ?>" data-group-label="Group <?= htmlspecialchars($groupCode) ?>">
                                    <?php foreach (($facilitatorByGroup[$groupCode] ?? []) as $buddy): ?>
                                        <div class="facilitator-pill">
                                            Senior Buddy: <?= htmlspecialchars($buddy['full_name'] ?? '-') ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php foreach ($participants as $index => $p): ?>
                                        <div class="participant-item card mb-2" draggable="true" data-participant-id="<?= (int)$p['id'] ?>" data-participant-name="<?= htmlspecialchars($p['full_name']) ?>">
                                            <div class="card-body p-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-secondary member-number"><?= (int)$index + 1 ?></span>
                                                    <div class="fw-semibold"><?= htmlspecialchars($p['full_name']) ?></div>
                                                </div>
                                                <div class="small text-muted">
                                                    <?= htmlspecialchars($p['student_id'] ?? '') ?>
                                                    <?php if (!empty($p['preferred_language'])): ?>
                                                        · <?= htmlspecialchars($p['preferred_language']) ?>
                                                    <?php endif; ?>
                                                    · <?= (($p['registration_type'] ?? 'pre_register') === 'walk_in') ? 'Walk-in' : 'Pre-register' ?>
                                                </div>
                                                <?php if (!empty($p['checked_in_at'])): ?>
                                                    <div class="checkin-pill checked">Checked in</div>
                                                <?php else: ?>
                                                    <div class="checkin-pill pending">Not checked in</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="col-md-4 group-column" id="group-col-ungrouped">
                        <div class="group-lane p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2 lane-header">
                                <div class="d-flex align-items-center gap-1">
                                    <strong>Ungrouped</strong>
                                    <span class="badge bg-secondary group-count" data-count-group=""><?= count($ungroupedParticipants ?? []) ?></span>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm lane-toggle" data-toggle-target="group-zone-ungrouped">Minimize</button>
                            </div>
                            <div class="drop-zone rounded p-2 bg-light" id="group-zone-ungrouped" data-target-group="" data-group-label="Ungrouped">
                                <?php foreach (($ungroupedParticipants ?? []) as $index => $p): ?>
                                    <div class="participant-item card mb-2" draggable="true" data-participant-id="<?= (int)$p['id'] ?>" data-participant-name="<?= htmlspecialchars($p['full_name']) ?>">
                                        <div class="card-body p-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-secondary member-number"><?= (int)$index + 1 ?></span>
                                                <div class="fw-semibold"><?= htmlspecialchars($p['full_name']) ?></div>
                                            </div>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($p['student_id'] ?? '') ?>
                                                <?php if (!empty($p['preferred_language'])): ?>
                                                    · <?= htmlspecialchars($p['preferred_language']) ?>
                                                <?php endif; ?>
                                                · <?= (($p['registration_type'] ?? 'pre_register') === 'walk_in') ? 'Walk-in' : 'Pre-register' ?>
                                            </div>
                                            <?php if (!empty($p['checked_in_at'])): ?>
                                                <div class="checkin-pill checked">Checked in</div>
                                            <?php else: ?>
                                                <div class="checkin-pill pending">Not checked in</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Recent Moves</h6>
                        <button type="button" id="toggle-recent-moves" class="btn btn-outline-secondary btn-sm">Collapse</button>
                    </div>
                    <div id="recentMovesCollapse" class="collapse show">
                        <div class="border rounded p-2 bg-light">
                            <ul id="move-history-list" class="mb-0 small">
                            <?php if (empty($recentMoveLogs)): ?>
                                <li class="text-muted">No moves yet.</li>
                            <?php else: ?>
                                <?php foreach ($recentMoveLogs as $log): ?>
                                    <?php
                                    $fromLabel = !empty($log['from_group_code']) ? 'Group ' . $log['from_group_code'] : 'Ungrouped';
                                    $toLabel = !empty($log['to_group_code']) ? 'Group ' . $log['to_group_code'] : 'Ungrouped';
                                    $verb = ($log['action_type'] ?? 'move') === 'undo' ? 'restored' : 'moved';
                                    $isAutoAssign = ($log['moved_by'] ?? '') === 'System Auto-Assign';
                                    ?>
                                    <li>
                                        <?php if ($isAutoAssign): ?>
                                            <?= htmlspecialchars(($log['participant_name'] ?? 'Participant') . ' automatically assigned to ' . $toLabel . ' at ' . ($log['moved_at'] ?? '')) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars(($log['participant_name'] ?? 'Participant') . ' ' . $verb . ' from ' . $fromLabel . ' to ' . $toLabel . ' by ' . ($log['moved_by'] ?? 'Unknown') . ' at ' . ($log['moved_at'] ?? '')) ?>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="tab-reports" role="tabpanel" aria-labelledby="tab-reports-tab">
        <div class="row">
            <div class="col-md-4">
                <h4>Group Distribution</h4>
                <table class="table table-sm table-striped">
                    <thead>
                    <tr>
                        <th>Group</th>
                        <th>Count</th>
                        <th>Max</th>
                        <th>Senior Buddy</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($groups)): ?>
                        <tr>
                            <td colspan="4" class="text-muted">No groups assigned yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groups as $g): ?>
                            <?php
                                $rMax = $groupMaxMap[$g['group_code']] ?? 0;
                                $rEffectiveMax = $rMax > 0 ? $rMax : $currentMaxPerGroup;
                                $rMaxLabel = $rEffectiveMax > 0 ? (string)$rEffectiveMax : '-';
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($g['group_code']) ?></strong>
                                    <span class="badge bg-surface"><?= htmlspecialchars($groupTypes[$g['group_code']] ?? 'Mixed') ?></span>
                                </td>
                                <td><?= (int)$g['count'] ?></td>
                                <td><?= htmlspecialchars($rMaxLabel) ?></td>
                                <td>
                                    <?php $buddies = $facilitatorByGroup[$g['group_code']] ?? []; ?>
                                    <?php if (!empty($buddies)): ?>
                                        <?php foreach ($buddies as $buddy): ?>
                                            <span class="badge bg-primary-filled" style="font-size:0.7rem;margin-bottom:2px;display:inline-block"><?= htmlspecialchars($buddy['full_name']) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="col-md-8">
                <h4>Participants by Group</h4>
                <?php if (empty($participantsByGroup)): ?>
                    <p class="text-muted">No participants assigned to groups yet. Use the auto-assign function above.</p>
                <?php else: ?>
                    <?php foreach ($participantsByGroup as $groupCode => $participants): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <strong>Group <?= htmlspecialchars($groupCode) ?></strong>
                                    <span class="badge bg-secondary">Max <?= htmlspecialchars($maxPerGroupLabel) ?></span>
                                    <span class="badge bg-surface"><?= htmlspecialchars($groupTypes[$groupCode] ?? 'Mixed') ?></span>
                                    <span class="text-muted small">(<?= count($participants) ?> participants)</span>
                                </div>
                                <?php $buddies = $facilitatorByGroup[$groupCode] ?? []; ?>
                                <?php if (!empty($buddies)): ?>
                                    <div class="mt-1 d-flex flex-wrap align-items-center gap-1">
                                        <span class="small fw-semibold" style="color:var(--md-sys-color-on-surface-variant);">Senior Buddy:</span>
                                        <?php foreach ($buddies as $buddy): ?>
                                            <span class="badge bg-primary-filled" style="font-size:0.72rem"><?= htmlspecialchars($buddy['full_name']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-1">
                                        <span class="small text-muted">Senior Buddy: <em>Unassigned</em></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Student ID</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php $counter = 1; foreach ($participants as $p): ?>
                                            <tr>
                                                <td><?= $counter++ ?></td>
                                                <td><?= htmlspecialchars($p['full_name']) ?></td>
                                                <td><?= htmlspecialchars($p['student_id'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        let draggedItem = null;
        let sourceZone = null;
        let lastPointerY = null;
        let autoScrollTimer = null;
        const moveHistory = [];
        const undoButton = document.getElementById('undo-last-move');
        const clearSelectionButton = document.getElementById('clear-selection');
        const selectedCountBadge = document.getElementById('selected-count');
        const historyList = document.getElementById('move-history-list');
        const editorRoot = document.getElementById('group-editor-root');
        const minimizeAllButton = document.getElementById('minimize-all-lanes');
        const expandAllButton = document.getElementById('expand-all-lanes');
        const showAllGroupsButton = document.getElementById('show-all-groups');
        const hideAllGroupsButton = document.getElementById('hide-all-groups');
        const currentMover = (editorRoot && editorRoot.dataset.currentMover) ? editorRoot.dataset.currentMover : 'Unknown';
        let latestKnownMoveLogId = (editorRoot && editorRoot.dataset.latestMoveLogId) ? parseInt(editorRoot.dataset.latestMoveLogId, 10) || 0 : 0;
        const selectedIds = new Set();
        const laneStateStorageKey = 'participants.groupLaneState.v1';
        const groupVisibilityStorageKey = 'participants.groupVisibility.v1';
        const recentMovesCollapsedKey = 'participants.recentMovesCollapsed.v1';
        const persistedHistory = Array.from(historyList.querySelectorAll('li'))
            .map((item) => item.textContent.trim())
            .filter((text) => text !== '' && text !== 'No moves yet.');

        function showAlert(type, message) {
            const alertBox = document.getElementById('drag-alert');
            alertBox.className = 'alert alert-' + type + ' mb-3';
            alertBox.textContent = message;
            alertBox.classList.remove('d-none');
        }

        function getGroupLabel(zone) {
            return zone.dataset.groupLabel || 'Ungrouped';
        }

        function updateZoneCount(zone, delta) {
            const group = zone.dataset.targetGroup || '';
            const badge = document.querySelector('.group-count[data-count-group="' + group + '"]');
            if (!badge) {
                return;
            }
            const current = parseInt(badge.textContent, 10) || 0;
            badge.textContent = String(Math.max(0, current + delta));
        }

        function renumberAllLanes() {
            document.querySelectorAll('.drop-zone').forEach((zone) => {
                const cards = zone.querySelectorAll('.participant-item');
                cards.forEach((card, index) => {
                    const numberBadge = card.querySelector('.member-number');
                    if (numberBadge) {
                        numberBadge.textContent = String(index + 1);
                    }
                });
            });
        }

        function renderMoveHistory() {
            const combinedHistory = persistedHistory.concat(moveHistory.map((entry) => entry.description));

            if (combinedHistory.length === 0) {
                historyList.innerHTML = '<li class="text-muted">No moves yet.</li>';
                undoButton.disabled = true;
                return;
            }

            undoButton.disabled = false;
            historyList.innerHTML = '';
            combinedHistory.slice().reverse().slice(0, 25).forEach((description) => {
                const item = document.createElement('li');
                item.textContent = description;
                historyList.appendChild(item);
            });
        }

        function renderSelectionCount() {
            selectedCountBadge.textContent = selectedIds.size + ' selected';
        }

        function clearSelection() {
            selectedIds.clear();
            document.querySelectorAll('.participant-item.selected').forEach((item) => {
                item.classList.remove('selected');
            });
            renderSelectionCount();
        }

        function toggleSelection(item) {
            const id = item.dataset.participantId;
            if (!id) {
                return;
            }
            if (selectedIds.has(id)) {
                selectedIds.delete(id);
                item.classList.remove('selected');
            } else {
                selectedIds.add(id);
                item.classList.add('selected');
            }
            renderSelectionCount();
        }

        async function moveParticipant(participantId, targetGroup, moveAction, expectedFromGroup) {
            const response = await fetch('/participants/groups/move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    participant_id: String(participantId),
                    target_group: targetGroup,
                    move_action: moveAction || 'move',
                    expected_from_group: expectedFromGroup || ''
                }).toString()
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to move participant.');
            }
            return data;
        }

        function getTimeStamp() {
            return new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'});
        }

        function bindDragItem(item) {
            item.addEventListener('click', (event) => {
                // Keep single-click intuitive: select without starting drag.
                if (event.defaultPrevented) {
                    return;
                }
                toggleSelection(item);
            });

            item.addEventListener('dragstart', (event) => {
                draggedItem = event.currentTarget;
                sourceZone = draggedItem.closest('.drop-zone');
                if (autoScrollTimer === null) {
                    autoScrollTimer = window.setInterval(() => {
                        if (draggedItem === null || lastPointerY === null) {
                            return;
                        }
                        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                        const edgeThreshold = 120;
                        const scrollStep = 22;
                        if (lastPointerY < edgeThreshold) {
                            window.scrollBy(0, -scrollStep);
                        } else if (lastPointerY > viewportHeight - edgeThreshold) {
                            window.scrollBy(0, scrollStep);
                        }
                    }, 16);
                }
                const draggedId = draggedItem.dataset.participantId;
                if (!selectedIds.has(draggedId)) {
                    clearSelection();
                    selectedIds.add(draggedId);
                    draggedItem.classList.add('selected');
                    renderSelectionCount();
                }

                document.querySelectorAll('.participant-item.selected').forEach((selectedItem) => {
                    selectedItem.classList.add('dragging');
                });
                event.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', () => {
                document.querySelectorAll('.participant-item.dragging').forEach((draggingItem) => {
                    draggingItem.classList.remove('dragging');
                });
                lastPointerY = null;
                if (autoScrollTimer !== null) {
                    window.clearInterval(autoScrollTimer);
                    autoScrollTimer = null;
                }
            });
        }

        document.querySelectorAll('.participant-item').forEach(bindDragItem);

        function setLaneCollapsed(zone, collapsed) {
            zone.classList.toggle('collapsed', collapsed);
            const lane = zone.closest('.group-lane');
            if (lane) {
                lane.classList.toggle('minimized', collapsed);
            }
            const toggleButton = document.querySelector('[data-toggle-target="' + zone.id + '"]');
            if (toggleButton) {
                toggleButton.textContent = collapsed ? 'Expand' : 'Minimize';
            }
        }

        function readLaneState() {
            try {
                const raw = localStorage.getItem(laneStateStorageKey);
                if (!raw) {
                    return {};
                }
                const parsed = JSON.parse(raw);
                return (parsed && typeof parsed === 'object') ? parsed : {};
            } catch (error) {
                return {};
            }
        }

        function persistLaneState() {
            const state = {};
            document.querySelectorAll('.drop-zone').forEach((zone) => {
                state[zone.id] = zone.classList.contains('collapsed');
            });
            localStorage.setItem(laneStateStorageKey, JSON.stringify(state));
        }

        function readGroupVisibility() {
            try {
                const raw = localStorage.getItem(groupVisibilityStorageKey);
                if (!raw) {
                    return {};
                }
                const parsed = JSON.parse(raw);
                return (parsed && typeof parsed === 'object') ? parsed : {};
            } catch (error) {
                return {};
            }
        }

        function persistGroupVisibility() {
            const state = {};
            document.querySelectorAll('.group-visibility-toggle').forEach((toggle) => {
                state[toggle.dataset.groupColumn] = toggle.checked;
            });
            localStorage.setItem(groupVisibilityStorageKey, JSON.stringify(state));
        }

        function applyGroupVisibility(groupColumnId, visible) {
            const column = document.getElementById(groupColumnId);
            if (column) {
                column.classList.toggle('d-none', !visible);
            }
        }

        function setRecentMovesCollapsed(collapsed) {
            const collapseEl = document.getElementById('recentMovesCollapse');
            const toggleBtn = document.getElementById('toggle-recent-moves');
            if (!collapseEl || !toggleBtn) {
                return;
            }
            if (collapsed) {
                collapseEl.classList.remove('show');
                toggleBtn.textContent = 'Expand';
            } else {
                collapseEl.classList.add('show');
                toggleBtn.textContent = 'Collapse';
            }
            localStorage.setItem(recentMovesCollapsedKey, collapsed ? '1' : '0');
        }

        async function processDropToZone(zone) {
            if (!draggedItem || !sourceZone) {
                return;
            }

            const targetGroup = zone.dataset.targetGroup || '';
            const currentGroup = sourceZone.dataset.targetGroup || '';
            if (targetGroup === currentGroup) {
                return;
            }

            const fromLabel = getGroupLabel(sourceZone);
            const toLabel = getGroupLabel(zone);
            const selectedCards = Array.from(document.querySelectorAll('.participant-item.selected'))
                .filter((item) => (item.closest('.drop-zone') === sourceZone));
            const cardsToMove = selectedCards.length > 0 ? selectedCards : [draggedItem];
            const moveRecords = [];

            try {
                for (const card of cardsToMove) {
                    const participantId = card.dataset.participantId;
                    const participantName = card.dataset.participantName || 'Participant';
                    const result = await moveParticipant(participantId, targetGroup, 'move', currentGroup);
                    zone.appendChild(card);
                    moveRecords.push({
                        participantId: participantId,
                        name: participantName,
                        card: card
                    });
                    if (result.log_entry) {
                        persistedHistory.push(result.log_entry);
                    }
                    if (result.latest_move_log_id) {
                        latestKnownMoveLogId = Math.max(latestKnownMoveLogId, Number(result.latest_move_log_id) || 0);
                    }
                }

                updateZoneCount(sourceZone, -moveRecords.length);
                updateZoneCount(zone, moveRecords.length);
                renumberAllLanes();

                const movedNames = moveRecords.map((record) => record.name).join(', ');
                moveHistory.push({
                    type: 'batch',
                    moves: moveRecords,
                    movedBy: currentMover,
                    fromGroup: currentGroup,
                    toGroup: targetGroup,
                    fromLabel: fromLabel,
                    toLabel: toLabel,
                    fromZone: sourceZone,
                    toZone: zone,
                    time: getTimeStamp(),
                    description: movedNames + ' moved from ' + fromLabel + ' to ' + toLabel + ' by ' + currentMover + ' at ' + getTimeStamp()
                });
                if (moveHistory.length > 20) {
                    moveHistory.shift();
                }
                renderMoveHistory();
                showAlert('success', movedNames + ' moved from ' + fromLabel + ' to ' + toLabel + '.');
                clearSelection();
            } catch (error) {
                showAlert('danger', error.message);
            } finally {
                document.querySelectorAll('.participant-item.dragging').forEach((draggingItem) => {
                    draggingItem.classList.remove('dragging');
                });
                draggedItem = null;
                sourceZone = null;
                lastPointerY = null;
                if (autoScrollTimer !== null) {
                    window.clearInterval(autoScrollTimer);
                    autoScrollTimer = null;
                }
            }
        }

        document.querySelectorAll('.lane-toggle').forEach((toggleButton) => {
            toggleButton.addEventListener('click', () => {
                const targetId = toggleButton.dataset.toggleTarget;
                const targetZone = document.getElementById(targetId);
                if (!targetZone) {
                    return;
                }
                const willCollapse = !targetZone.classList.contains('collapsed');
                setLaneCollapsed(targetZone, willCollapse);
                persistLaneState();
            });
        });

        document.querySelectorAll('.group-visibility-toggle').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                applyGroupVisibility(toggle.dataset.groupColumn, toggle.checked);
                persistGroupVisibility();
            });
        });

        showAllGroupsButton.addEventListener('click', () => {
            document.querySelectorAll('.group-visibility-toggle').forEach((toggle) => {
                toggle.checked = true;
                applyGroupVisibility(toggle.dataset.groupColumn, true);
            });
            persistGroupVisibility();
        });

        hideAllGroupsButton.addEventListener('click', () => {
            document.querySelectorAll('.group-visibility-toggle').forEach((toggle) => {
                toggle.checked = false;
                applyGroupVisibility(toggle.dataset.groupColumn, false);
            });
            persistGroupVisibility();
        });

        minimizeAllButton.addEventListener('click', () => {
            document.querySelectorAll('.drop-zone').forEach((zone) => setLaneCollapsed(zone, true));
            persistLaneState();
        });

        expandAllButton.addEventListener('click', () => {
            document.querySelectorAll('.drop-zone').forEach((zone) => setLaneCollapsed(zone, false));
            persistLaneState();
        });

        document.querySelectorAll('.drop-zone').forEach((zone) => {
            zone.addEventListener('dragover', (event) => {
                event.preventDefault();
                lastPointerY = event.clientY;
                zone.classList.add('drag-active');
            });

            zone.addEventListener('dragleave', () => {
                zone.classList.remove('drag-active');
            });

            zone.addEventListener('drop', async (event) => {
                event.preventDefault();
                zone.classList.remove('drag-active');
                await processDropToZone(zone);
            });
        });

        document.querySelectorAll('.lane-header').forEach((header) => {
            const lane = header.closest('.group-lane');
            const zone = lane ? lane.querySelector('.drop-zone') : null;
            if (!zone) {
                return;
            }

            header.addEventListener('dragover', (event) => {
                event.preventDefault();
                lastPointerY = event.clientY;
                lane.classList.add('header-drag-active');
            });

            header.addEventListener('dragleave', () => {
                lane.classList.remove('header-drag-active');
            });

            header.addEventListener('drop', async (event) => {
                event.preventDefault();
                lane.classList.remove('header-drag-active');
                await processDropToZone(zone);
            });
        });

        // Track pointer position even outside drop targets for smooth page autoscroll during drag.
        document.addEventListener('dragover', (event) => {
            if (draggedItem) {
                lastPointerY = event.clientY;
            }
        });

        undoButton.addEventListener('click', async () => {
            const lastMove = moveHistory[moveHistory.length - 1];
            if (!lastMove) {
                return;
            }

            undoButton.disabled = true;
            try {
                const movedCards = [];
                for (const moved of (lastMove.moves || [])) {
                    const participantCard = document.querySelector('.participant-item[data-participant-id="' + moved.participantId + '"]');
                    if (!participantCard) {
                        continue;
                    }
                        const result = await moveParticipant(moved.participantId, lastMove.fromGroup, 'undo', lastMove.toGroup);
                    lastMove.fromZone.appendChild(participantCard);
                    movedCards.push(participantCard);
                        if (result.log_entry) {
                            persistedHistory.push(result.log_entry);
                        }
                        if (result.latest_move_log_id) {
                            latestKnownMoveLogId = Math.max(latestKnownMoveLogId, Number(result.latest_move_log_id) || 0);
                        }
                }

                updateZoneCount(lastMove.toZone, -movedCards.length);
                updateZoneCount(lastMove.fromZone, movedCards.length);
                renumberAllLanes();
                moveHistory.pop();
                renderMoveHistory();
                const restoredNames = (lastMove.moves || []).map((moved) => moved.name).join(', ');
                showAlert('warning', 'Undo complete: ' + restoredNames + ' moved back to ' + lastMove.fromLabel + '.');
                if (movedCards.length > 0) {
                    moveHistory.push({
                        type: 'undo',
                        moves: [],
                        movedBy: currentMover,
                        fromGroup: lastMove.toGroup,
                        toGroup: lastMove.fromGroup,
                        fromLabel: lastMove.toLabel,
                        toLabel: lastMove.fromLabel,
                        fromZone: lastMove.toZone,
                        toZone: lastMove.fromZone,
                        time: getTimeStamp(),
                        description: 'Undo by ' + currentMover + ': restored ' + restoredNames + ' to ' + lastMove.fromLabel + ' at ' + getTimeStamp()
                    });
                    if (moveHistory.length > 20) {
                        moveHistory.shift();
                    }
                    renderMoveHistory();
                }
            } catch (error) {
                showAlert('danger', 'Undo failed: ' + error.message);
                undoButton.disabled = false;
            }
        });

        clearSelectionButton.addEventListener('click', () => {
            clearSelection();
            showAlert('info', 'Selection cleared.');
        });

        const recentMovesToggleBtn = document.getElementById('toggle-recent-moves');
        if (recentMovesToggleBtn) {
            recentMovesToggleBtn.addEventListener('click', () => {
                const collapseEl = document.getElementById('recentMovesCollapse');
                const isCollapsed = collapseEl ? !collapseEl.classList.contains('show') : false;
                setRecentMovesCollapsed(!isCollapsed);
            });
        }

        const savedLaneState = readLaneState();
        document.querySelectorAll('.drop-zone').forEach((zone) => {
            setLaneCollapsed(zone, savedLaneState[zone.id] === true);
        });

        const savedGroupVisibility = readGroupVisibility();
        document.querySelectorAll('.group-visibility-toggle').forEach((toggle) => {
            const savedValue = savedGroupVisibility[toggle.dataset.groupColumn];
            if (typeof savedValue === 'boolean') {
                toggle.checked = savedValue;
            }
            applyGroupVisibility(toggle.dataset.groupColumn, toggle.checked);
        });

        setRecentMovesCollapsed(localStorage.getItem(recentMovesCollapsedKey) === '1');

        renderSelectionCount();
        renderMoveHistory();
        renumberAllLanes();

        // Per-group slot adjustment buttons (AJAX, no page reload)
        document.querySelectorAll('.slot-adjust-btn').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                const groupCode = btn.dataset.group;
                const delta = btn.dataset.delta;
                btn.disabled = true;
                try {
                    const res = await fetch('/participants/groups/adjust-slot', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new URLSearchParams({ group_code: groupCode, delta: delta }).toString()
                    });
                    const data = await res.json();
                    if (data.success) {
                        // Find the Max badge for this group in the lane header and update it
                        const lane = btn.closest('.lane-header') || btn.closest('.group-lane');
                        if (lane) {
                            const maxBadge = lane.querySelector('.badge.bg-info.text-dark');
                            if (maxBadge) {
                                maxBadge.textContent = 'Max ' + data.max_label;
                            }
                        }
                        showAlert('success', data.message);
                    } else {
                        showAlert('danger', data.message || 'Failed to adjust slot.');
                    }
                } catch (err) {
                    showAlert('danger', 'Network error adjusting slot.');
                } finally {
                    btn.disabled = false;
                }
            });
        });

        window.setInterval(async () => {
            try {
                const response = await fetch('/participants/groups/state', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) {
                    return;
                }
                const data = await response.json();
                if (!data || !data.success) {
                    return;
                }

                const serverLatestId = Number(data.latest_move_log_id || 0);
                if (serverLatestId > latestKnownMoveLogId) {
                    latestKnownMoveLogId = serverLatestId;
                    if (draggedItem) {
                        showAlert('info', 'Another user updated groups. Page will refresh after your drag ends.');
                        return;
                    }
                    window.location.reload();
                }
            } catch (error) {
                // Ignore transient polling errors.
            }
        }, 3000);
    })();
</script>
