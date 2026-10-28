<?php
// admin/items.php - Admin Item Inventory & Status Management

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

AdminMiddleware::check();

$itemRepo = new ItemRepository();
$items = $itemRepo->searchAndFilter(['limit' => 200]);

$pageTitle = 'Manage Items - Admin';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= url('admin/dashboard.php') ?>" class="text-decoration-none">Admin Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Manage Items</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Manage Item Listings</h3>
            <p class="text-muted small mb-0">Directly oversee, change status, or purge campus listings.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('report-item.php') ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> New Item Report
            </a>
            <a href="<?= url('admin/claims.php') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-clipboard-check me-1"></i> Review Claims
            </a>
        </div>
    </div>

    <!-- Inventory Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="form-control form-control-sm rounded-pill" id="adminItemSearchInput" placeholder="Quick filter items..." style="max-width: 260px;">
                <span class="badge bg-secondary rounded-pill" id="admin-item-count"><?= count($items) ?> items</span>
            </div>
            <div id="admin-item-alert" class="d-none small py-1 px-3 rounded-pill"></div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="adminItemsTable">
                <thead class="table-light text-secondary small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Item Details</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Incident Date</th>
                        <th>Status (Single-Click Update)</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $imageSrc = !empty($item['image_url']) ? url($item['image_url']) : url('public/assets/img/placeholder.svg');
                        $typeBadgeClass = ($item['item_type'] === 'lost') ? 'bg-danger text-white' : 'bg-success text-white';
                        ?>
                        <tr id="item-row-<?= $item['id'] ?>" class="item-table-row">
                            <td class="ps-4 fw-bold text-muted">#<?= $item['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= $imageSrc ?>" alt="" class="rounded-3 border" style="width: 44px; height: 44px; object-fit: cover;">
                                    <div>
                                        <a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                            <?= sanitize($item['title']) ?>
                                        </a>
                                        <div class="text-muted small">
                                            Reporter: <?= sanitize($item['contact_name']) ?> (<?= sanitize($item['contact_email']) ?>)
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= $typeBadgeClass ?> text-uppercase px-2 py-1">
                                    <?= sanitize($item['item_type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="small text-secondary fw-semibold">
                                    <?= sanitize($item['category_name']) ?>
                                </span>
                            </td>
                            <td><span class="small text-muted"><?= sanitize($item['location']) ?></span></td>
                            <td><span class="small text-muted"><?= date('M d, Y', strtotime($item['event_date'])) ?></span></td>
                            <td>
                                <!-- Live AJAX Status Selector without full page reload -->
                                <select class="form-select form-select-sm rounded-pill admin-status-select" 
                                        data-item-id="<?= $item['id'] ?>" style="min-width: 145px; font-weight: 600;">
                                    <option value="Open" <?= ($item['status'] === 'Open') ? 'selected' : '' ?>>Open</option>
                                    <option value="Claim Requested" <?= ($item['status'] === 'Claim Requested') ? 'selected' : '' ?>>Claim Requested</option>
                                    <option value="Resolved" <?= ($item['status'] === 'Resolved') ? 'selected' : '' ?>>Resolved</option>
                                </select>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill" title="View details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill admin-delete-item-btn" 
                                            data-item-id="<?= $item['id'] ?>" data-item-title="<?= sanitize($item['title']) ?>" title="Delete listing">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$extraScripts = '<script src="' . url('public/assets/js/admin.js') . '"></script>';
require_once __DIR__ . '/../layouts/footer.php'; 
?>

