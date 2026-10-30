<?php
// index.php - Public Lost & Found Catalog with AJAX Live Filtering

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/controllers/ItemController.php';

$itemController = new ItemController();
$categories = $itemController->getCategories();
$repo = new ItemRepository();
$stats = $repo->getStats();
$initialItems = $repo->searchAndFilter(['limit' => 60]);

$pageTitle = 'Catalog';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container my-3">
    <!-- Top Summary & Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
        <div>
            <h4 class="fw-bold mb-0">Campus Lost &amp; Found</h4>
            <small class="text-muted"><?= $stats['total_lost'] ?> Lost &bull; <?= $stats['total_found'] ?> Found &bull; <?= $stats['total_resolved'] ?> Reunited</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('report-item.php?type=lost') ?>" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="fa-solid fa-circle-question me-1"></i> Report Lost
            </a>
            <a href="<?= url('report-item.php?type=found') ?>" class="btn btn-success btn-sm rounded-pill px-3">
                <i class="fa-solid fa-hand-holding-heart me-1"></i> Report Found
            </a>
        </div>
    </div>

    <!-- Minimal Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3 bg-white">
        <form id="filterForm" class="row g-2 align-items-center" onsubmit="return false;">
            <div class="col-12 col-md-4">
                <input type="text" class="form-control form-control-sm" id="filter_query" name="q" placeholder="Search title, description, location...">
            </div>
            <div class="col-12 col-md-3">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <input type="radio" class="btn-check filter-type-radio" name="type" id="type_all" value="" checked>
                    <label class="btn btn-outline-secondary" for="type_all">All</label>
                    <input type="radio" class="btn-check filter-type-radio" name="type" id="type_filter_lost" value="lost">
                    <label class="btn btn-outline-danger" for="type_filter_lost">Lost</label>
                    <input type="radio" class="btn-check filter-type-radio" name="type" id="type_filter_found" value="found">
                    <label class="btn btn-outline-success" for="type_filter_found">Found</label>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" id="filter_category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" id="filter_status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Open">Open</option>
                    <option value="Claim Requested">Claim Requested</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
            <div class="col-12 col-md-1">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="clear-all-filters-btn" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Results Header -->
    <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
        <div>Showing <strong id="results-count-badge" class="text-dark"><?= count($initialItems) ?></strong> items</div>
        <div id="loading-indicator" class="d-none text-primary"><i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...</div>
    </div>

    <!-- Live Items Grid -->
    <div class="row g-3" id="items-grid">
        <?php foreach ($initialItems as $item): ?>
            <?php $itemController->renderItemCard($item); ?>
        <?php endforeach; ?>
    </div>
</div>

<?php 
$extraScripts = '<script src="' . url('public/assets/js/items.js') . '"></script>';
require_once __DIR__ . '/layouts/footer.php'; 
?>
