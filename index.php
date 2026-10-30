<?php
// index.php - Public Lost & Found Catalog with AJAX Live Filtering
//
// WHAT THIS PAGE DOES:
// 1. Fetches categories from ItemController for the category dropdown filter.
// 2. Fetches overall portal stats from ItemRepository (lost, found, in claim, resolved).
// 3. Performs an initial server-side query for the newest items so the page renders instantly.
// 4. Renders the interactive filter bar (Keyword Search, Type radio pills, Category, Status).
// 5. Includes public/assets/js/items.js which debounces search typing and sends AJAX requests
//    to api/items.php without reloading the page.
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO CHANGE INITIAL ITEM LIMIT: In the $initialItems call below, change `'limit' => 60` to 20 or 100.
// - TO ADD A NEW FILTER OPTION (e.g. filter by Date):
//   1. Add an `<input type="date" id="filter_date" name="date">` inside #filterForm below.
//   2. In public/assets/js/items.js, include date in the params object.
//   3. In app/controllers/ItemController.php and ItemRepository.php, bind the date parameter.
// - TO CHANGE CARD DESIGN: Edit renderItemCard() in app/controllers/ItemController.php.

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/controllers/ItemController.php';

$itemController = new ItemController();
$categories = $itemController->getCategories();

$repo = new ItemRepository();
$stats = $repo->getStats();

// Initial server-side query for fastest first contentful paint (FCP)
$initialItems = $repo->searchAndFilter(['limit' => 60]);

$pageTitle = 'Catalog';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container my-3">
    <!-- Top Summary Header & Quick Actions -->
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

    <!-- Live Search & Filter Bar (Handled via jQuery in public/assets/js/items.js) -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3 bg-white">
        <form id="filterForm" class="row g-2 align-items-center" onsubmit="return false;">
            <!-- 1. Keyword Search Input -->
            <div class="col-12 col-md-4">
                <input type="text" class="form-control form-control-sm" id="filter_query" name="q" 
                       placeholder="Search title, description, location..." autocomplete="off">
            </div>

            <!-- 2. Type Filter Pills (All / Lost / Found) -->
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

            <!-- 3. Category Filter Dropdown -->
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" id="filter_category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 4. Status Filter Dropdown -->
            <!-- TO ADD NEW STATUS (e.g. Archived): Add <option value="Archived">Archived</option> -->
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" id="filter_status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Open">Open</option>
                    <option value="Claim Requested">Claim Requested</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>

            <!-- 5. Reset Button -->
            <div class="col-12 col-md-1">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="clear-all-filters-btn" title="Reset all filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Results Status Bar -->
    <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
        <div>Showing <strong id="results-count-badge" class="text-dark"><?= count($initialItems) ?></strong> items</div>
        <div id="loading-indicator" class="d-none text-primary">
            <i class="fa-solid fa-spinner fa-spin me-1"></i> Updating listings...
        </div>
    </div>

    <!-- Live Items Grid Container (Replaced dynamically by AJAX without page reload) -->
    <div class="row g-3" id="items-grid">
        <?php foreach ($initialItems as $item): ?>
            <?php $itemController->renderItemCard($item); ?>
        <?php endforeach; ?>
    </div>
</div>

<?php 
// Include items.js for AJAX filtering and status actions
$extraScripts = '<script src="' . url('public/assets/js/items.js') . '"></script>';
require_once __DIR__ . '/layouts/footer.php'; 
?>
