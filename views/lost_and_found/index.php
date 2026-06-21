<?php
// Admin: Lost & Found management list
// Available: $items, $counts, $filter, $sort
$filter = $filter ?? 'all';
$sort = $sort ?? 'newest';
$counts = $counts ?? ['all' => 0, 'unclaimed' => 0, 'claimed' => 0];
?>

<?php if (!empty($_SESSION['lf_message'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['lf_message_type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['lf_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['lf_message'], $_SESSION['lf_message_type']); ?>
<?php endif; ?>

<!-- Title and Top Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold" style="color: var(--md-sys-color-on-surface);">
            <span class="material-symbols-outlined" style="font-size: 28px; vertical-align: text-bottom;">search_check_2</span>
            Lost & Found
        </h2>
        <p class="text-muted small mb-0 mt-1">Manage lost items, upload photos, and track claims.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/lost-and-found/create" class="btn btn-primary btn-sm">
            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">add</span> Add Item
        </a>
        <a href="/lost-and-found/public" class="btn btn-outline-secondary btn-sm" target="_blank">
            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">visibility</span> Public View
        </a>
    </div>
</div>

<!-- Compact Statistics Banner -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Total Items</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-primary) !important;"><?= $counts['all'] ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Unclaimed</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-tertiary) !important;"><?= $counts['unclaimed'] ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3 h-100" style="border: 1px solid var(--md-sys-color-outline-variant) !important; border-radius: 16px; background-color: var(--md-sys-color-surface-container-low) !important;">
            <div class="text-muted small text-uppercase fw-semibold" style="color: var(--md-sys-color-on-surface-variant) !important; font-size: 0.7rem; letter-spacing: 0.5px;">Claimed</div>
            <h2 class="mb-0 mt-1 fw-bold" style="color: var(--md-sys-color-success) !important;"><?= $counts['claimed'] ?></h2>
        </div>
    </div>
</div>

<!-- Filter Chips & Sort -->
<div class="card p-3 mb-4 border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <!-- Filter Chips (M3 Filter Chip Style) -->
        <div class="d-flex flex-wrap gap-2">
            <a href="/lost-and-found?filter=all&sort=<?= htmlspecialchars($sort) ?>"
               class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               style="border-radius: 100px !important;">
                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">list</span>
                All <span class="badge <?= $filter === 'all' ? 'bg-light text-dark' : 'bg-surface' ?> ms-1"><?= $counts['all'] ?></span>
            </a>
            <a href="/lost-and-found?filter=unclaimed&sort=<?= htmlspecialchars($sort) ?>"
               class="btn btn-sm <?= $filter === 'unclaimed' ? 'btn-warning' : 'btn-outline-warning' ?>"
               style="border-radius: 100px !important;">
                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">pending</span>
                Unclaimed <span class="badge <?= $filter === 'unclaimed' ? 'bg-light text-dark' : 'bg-warning' ?> ms-1"><?= $counts['unclaimed'] ?></span>
            </a>
            <a href="/lost-and-found?filter=claimed&sort=<?= htmlspecialchars($sort) ?>"
               class="btn btn-sm <?= $filter === 'claimed' ? 'btn-success' : 'btn-outline-success' ?>"
               style="border-radius: 100px !important;">
                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">check_circle</span>
                Claimed <span class="badge <?= $filter === 'claimed' ? 'bg-light text-dark' : 'bg-success' ?> ms-1"><?= $counts['claimed'] ?></span>
            </a>
        </div>

        <!-- Sort Dropdown (M3 style) -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" style="border-radius: 100px !important;">
                <span class="material-symbols-outlined" style="font-size: 16px;">sort</span>
                <?= match($sort) { 'oldest' => 'Oldest First', 'caption' => 'Caption A–Z', default => 'Newest First' } ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item <?= $sort === 'newest' ? 'active' : '' ?>" href="/lost-and-found?filter=<?= htmlspecialchars($filter) ?>&sort=newest">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">arrow_downward</span> Newest First</a></li>
                <li><a class="dropdown-item <?= $sort === 'oldest' ? 'active' : '' ?>" href="/lost-and-found?filter=<?= htmlspecialchars($filter) ?>&sort=oldest">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">arrow_upward</span> Oldest First</a></li>
                <li><a class="dropdown-item <?= $sort === 'caption' ? 'active' : '' ?>" href="/lost-and-found?filter=<?= htmlspecialchars($filter) ?>&sort=caption">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">sort_by_alpha</span> Caption A–Z</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Items Grid -->
<?php if (empty($items)): ?>
    <div class="card p-5 text-center border-0" style="background-color: var(--md-sys-color-surface-container-low) !important; border-radius: 20px !important;">
        <span class="material-symbols-outlined" style="font-size: 56px; color: var(--md-sys-color-outline);">inventory_2</span>
        <h5 class="mt-2 fw-bold" style="color: var(--md-sys-color-on-surface);">
            <?php if ($filter === 'unclaimed'): ?>
                No Unclaimed Items
            <?php elseif ($filter === 'claimed'): ?>
                No Claimed Items
            <?php else: ?>
                No Items Yet
            <?php endif; ?>
        </h5>
        <p class="text-muted mb-3">
            <?php if ($filter === 'all'): ?>
                Click "Add Item" to add a lost & found item with a photo.
            <?php else: ?>
                No items match the current filter.
            <?php endif; ?>
        </p>
        <?php if ($filter === 'all'): ?>
            <div>
                <a href="/lost-and-found/create" class="btn btn-primary btn-sm">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom;">add</span> Add Item
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100" style="border-radius: 20px !important; overflow: hidden;">
                    <?php if ($item['photo_filename']): ?>
                        <div style="position: relative;">
                            <img src="/uploads/lost_and_found/<?= htmlspecialchars($item['photo_filename']) ?>"
                                 class="card-img-top" alt="<?= htmlspecialchars($item['caption']) ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div style="position: absolute; top: 12px; right: 12px;">
                                <?php if ($item['status'] === 'claimed'): ?>
                                    <span class="badge bg-success" style="font-size: 0.75rem;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: text-bottom;">check_circle</span> Claimed
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning" style="font-size: 0.75rem;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: text-bottom;">pending</span> Unclaimed
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center" style="height: 180px; background-color: var(--md-sys-color-surface-container); position: relative;">
                            <span class="material-symbols-outlined" style="font-size: 56px; color: var(--md-sys-color-outline-variant);">image_not_supported</span>
                            <div style="position: absolute; top: 12px; right: 12px;">
                                <?php if ($item['status'] === 'claimed'): ?>
                                    <span class="badge bg-success" style="font-size: 0.75rem;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: text-bottom;">check_circle</span> Claimed
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning" style="font-size: 0.75rem;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: text-bottom;">pending</span> Unclaimed
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column" style="padding: 1rem 1.25rem;">
                        <h6 class="card-title fw-bold mb-1" style="color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($item['caption']) ?></h6>
                        <?php if ($item['description']): ?>
                            <p class="card-text small mb-2" style="color: var(--md-sys-color-on-surface-variant);"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                        <?php endif; ?>

                        <?php if ($item['status'] === 'claimed'): ?>
                            <div class="mt-auto pt-2" style="border-top: 1px solid var(--md-sys-color-outline-variant);">
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="material-symbols-outlined" style="font-size: 16px; color: var(--md-sys-color-on-surface-variant);">person</span>
                                    <span class="small fw-semibold" style="color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($item['claimed_by_name'] ?? '') ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="material-symbols-outlined" style="font-size: 16px; color: var(--md-sys-color-on-surface-variant);">phone</span>
                                    <span class="small" style="color: var(--md-sys-color-on-surface-variant);"><?= htmlspecialchars($item['claimed_by_phone'] ?? '') ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="material-symbols-outlined" style="font-size: 16px; color: var(--md-sys-color-on-surface-variant);">schedule</span>
                                    <span class="small" style="color: var(--md-sys-color-on-surface-variant);"><?= htmlspecialchars($item['claimed_at'] ?? '') ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer d-flex flex-wrap gap-2" style="background-color: var(--md-sys-color-surface-container-lowest); border-top: 1px solid var(--md-sys-color-outline-variant); padding: 0.75rem 1.25rem;">
                        <a href="/lost-and-found/edit?id=<?= (int)$item['id'] ?>" class="btn btn-outline-secondary btn-sm">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">edit</span> Edit
                        </a>
                        <?php if ($item['status'] === 'claimed'): ?>
                            <form method="POST" action="/lost-and-found/mark-returned" class="d-inline">
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-outline-warning btn-sm">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">undo</span> Mark Unclaimed
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="/lost-and-found/delete" class="d-inline ms-auto"
                              onsubmit="return confirm('Delete this item permanently?')">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: text-bottom;">delete</span> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>