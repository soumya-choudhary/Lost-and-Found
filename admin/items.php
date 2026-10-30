<?php
// admin/items.php - Campus Administrator Inventory Management Desk
//
// WHAT THIS PAGE DOES:
// 1. Ensures administrator access (AdminMiddleware::check()).
// 2. Fetches items up to limit 200 using ItemRepository::searchAndFilter().
// 3. Provides live in-place quick search by keyword (filters table rows instantly).
// 4. In-Place AJAX Status Dropdown:
//    - Selecting a new status immediately triggers api/update-status.php via AJAX without reloading.
// 5. In-Place AJAX Item Deletion:
//    - Prompts confirmation, then sends request to api/admin-item-delete.php and fades out the deleted row.
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO ADD A STATUS OPTION TO THE DROPDOWN:
//   Add `<option value="Archived">Archived</option>` to the `<select>` inside the loop below.
// - TO CHANGE WHICH FIELDS ARE DISPLAYED:
//   Edit the `<th>` and `<td>` columns inside the table below.

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

// Route Guard: Admin only
AdminMiddleware::check();

$items = (new ItemRepository())->searchAndFilter(['limit' => 200]);

$pageTitle = 'Manage Items';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <!-- Header with AJAX Notification Alert -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Item Listings (<?= count($items) ?>)</h4>
        <div id="admin-item-alert" class="d-none small py-1 px-3 rounded"></div>
    </div>

    <!-- Inventory Table Card -->
    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <!-- Quick search filter input (hooked in public/assets/js/admin.js) -->
        <div class="p-2 border-bottom bg-light">
            <input type="text" class="form-control form-control-sm" id="adminItemSearchInput" 
                   placeholder="Filter items by title or location..." style="max-width: 300px;">
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="adminItemsTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Item Details</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Status (Live 1-Click Update)</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr id="item-row-<?= $it['id'] ?>" class="item-table-row">
                            <td class="ps-3 text-muted">#<?= $it['id'] ?></td>
                            <td class="fw-bold">
                                <a href="<?= url('item-details.php?id=' . $it['id']) ?>" class="text-dark text-decoration-none">
                                    <?= sanitize($it['title']) ?>
                                </a>
                            </td>
                            <td><span class="badge <?= typeBadgeClass($it['item_type']) ?>"><?= strtoupper($it['item_type']) ?></span></td>
                            <td><?= sanitize($it['location']) ?></td>
                            <td><?= date('M d, Y', strtotime($it['event_date'])) ?></td>
                            <td>
                                <!-- Live in-place AJAX status dropdown (hooked in public/assets/js/admin.js) -->
                                <select class="form-select form-select-sm admin-status-select py-0" data-item-id="<?= $it['id'] ?>" style="width: auto;">
                                    <option value="Open" <?= $it['status']==='Open'?'selected':'' ?>>Open</option>
                                    <option value="Claim Requested" <?= $it['status']==='Claim Requested'?'selected':'' ?>>In Claim</option>
                                    <option value="Resolved" <?= $it['status']==='Resolved'?'selected':'' ?>>Resolved</option>
                                </select>
                            </td>
                            <td class="text-end pe-3">
                                <a href="<?= url('item-details.php?id=' . $it['id']) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="View details">View</a>
                                <!-- AJAX Delete button (hooked in public/assets/js/admin.js) -->
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 admin-delete-item-btn" 
                                        data-item-id="<?= $it['id'] ?>" data-item-title="<?= sanitize($it['title']) ?>" title="Delete item">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// Include admin.js for AJAX status dropdown, search filter, and delete handlers
$extraScripts = '<script src="' . url('public/assets/js/admin.js') . '"></script>';
require_once __DIR__ . '/../layouts/footer.php'; 
?>
