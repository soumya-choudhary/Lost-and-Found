<?php
// index.php - Public Lost & Found Portal Directory with jQuery AJAX Live Filtering

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/controllers/ItemController.php';

$itemController = new ItemController();
$categories = $itemController->getCategories();

$repo = new ItemRepository();
$stats = $repo->getStats();

// Initial server-side items load
$initialItems = $repo->searchAndFilter(['limit' => 60]);

$pageTitle = 'Campus Lost & Found Directory';
require_once __DIR__ . '/layouts/header.php';
?>

<!-- Hero Banner -->
<div class="bg-white border-bottom shadow-sm py-4 py-md-5 mb-4">
    <div class="container text-center">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">
            <i class="fa-solid fa-graduation-cap me-1"></i> University Campus Directory
        </span>
        <h1 class="fw-bold text-dark display-6 mb-2">Campus Lost &amp; Found Portal</h1>
        <p class="text-secondary mx-auto mb-4" style="max-width: 650px;">
            Search all reported lost and found belongings across university premises. Submit claims with proof of ownership or report items you discovered.
        </p>

        <!-- Live Statistics Counter -->
        <div class="row g-2 justify-content-center mb-4">
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2 border rounded-3 bg-light">
                    <div class="fs-4 fw-bold text-danger"><?= $stats['total_lost'] ?></div>
                    <small class="text-muted fw-semibold">Lost Items</small>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2 border rounded-3 bg-light">
                    <div class="fs-4 fw-bold text-success"><?= $stats['total_found'] ?></div>
                    <small class="text-muted fw-semibold">Found Items</small>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2 border rounded-3 bg-light">
                    <div class="fs-4 fw-bold text-warning"><?= $stats['total_claim_requested'] ?></div>
                    <small class="text-muted fw-semibold">In Claim</small>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2 border rounded-3 bg-light">
                    <div class="fs-4 fw-bold text-info"><?= $stats['total_resolved'] ?></div>
                    <small class="text-muted fw-semibold">Reunited</small>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="<?= url('report-item.php?type=lost') ?>" class="btn btn-danger rounded-pill px-4 py-2 shadow-sm">
                <i class="fa-solid fa-circle-question me-1"></i> Report Lost Item
            </a>
            <a href="<?= url('report-item.php?type=found') ?>" class="btn btn-success rounded-pill px-4 py-2 shadow-sm">
                <i class="fa-solid fa-hand-holding-heart me-1"></i> Report Found Item
            </a>
        </div>
    </div>
</div>

<div class="container my-4">
    <!-- Live Search & Filter Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4 bg-white">
        <form id="filterForm" class="row g-3 align-items-center" onsubmit="return false;">
            
            <!-- Search Keyword Input -->
            <div class="col-12 col-md-4">
                <label for="filter_query" class="form-label small fw-bold text-secondary text-uppercase mb-1">Search Keywords</label>
                <div class="input-icon-group">
                    <i class="fa-solid fa-magnifying-glass form-icon"></i>
                    <input type="text" class="form-control" id="filter_query" name="q" placeholder="Search by title, description, or campus building..." autocomplete="off">
                </div>
            </div>

            <!-- Item Type Pills (All / Lost / Found) -->
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Type</label>
                <div class="btn-group w-100" role="group" id="typeButtonGroup">
                    <input type="radio" class="btn-check filter-type-radio" name="type" id="type_all" value="" checked>
                    <label class="btn btn-outline-secondary py-2" for="type_all">All</label>

                    <input type="radio" class="btn-check filter-type-radio" name="type" id="type_filter_lost" value="lost">
                    <label class="btn btn-outline-danger py-2" for="type_filter_lost">Lost</label>

                    <input type="radio" class="btn-check filter-type-radio" name="type" id="type_filter_found" value="found">
                    <label class="btn btn-outline-success py-2" for="type_filter_found">Found</label>
                </div>
            </div>

            <!-- Category Dropdown -->
            <div class="col-6 col-md-2">
                <label for="filter_category" class="form-label small fw-bold text-secondary text-uppercase mb-1">Category</label>
                <select class="form-select" id="filter_category" name="category" style="height: 48px; border-radius: 10px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Dropdown -->
            <div class="col-6 col-md-2">
                <label for="filter_status" class="form-label small fw-bold text-secondary text-uppercase mb-1">Status</label>
                <select class="form-select" id="filter_status" name="status" style="height: 48px; border-radius: 10px;">
                    <option value="">All Statuses</option>
                    <option value="Open">Open</option>
                    <option value="Claim Requested">Claim Requested</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>

            <!-- Sort Dropdown -->
            <div class="col-12 col-md-1">
                <label for="filter_sort" class="form-label small fw-bold text-secondary text-uppercase mb-1">Sort</label>
                <select class="form-select" id="filter_sort" name="sort" style="height: 48px; border-radius: 10px;">
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="date_desc">Event Date</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Results Status Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold mb-0">Browse Listings</h5>
            <span class="badge bg-secondary rounded-pill" id="results-count-badge"><?= count($initialItems) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="loading-indicator" class="d-none text-primary small">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Updating listings...
            </span>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" id="clear-all-filters-btn">
                <i class="fa-solid fa-rotate-left me-1"></i> Clear Filters
            </button>
        </div>
    </div>

    <!-- Live Items Grid Container (Updated by jQuery AJAX without page reload) -->
    <div class="row g-4" id="items-grid">
        <?php foreach ($initialItems as $item): ?>
            <?php $itemController->renderItemCard($item); ?>
        <?php endforeach; ?>
    </div>
</div>

<?php 
$extraScripts = '<script src="' . url('public/assets/js/items.js') . '"></script>';
require_once __DIR__ . '/layouts/footer.php'; 
?>
