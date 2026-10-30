<?php
// admin/items.php - Admin Inventory Management

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

AdminMiddleware::check();
$items = (new ItemRepository())->searchAndFilter(['limit' => 200]);

$pageTitle = 'Manage Items';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Item Listings (<?= count($items) ?>)</h4>
        <div id="admin-item-alert" class="d-none small py-1 px-3 rounded"></div>
    </div>

    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="p-2 border-bottom bg-light">
            <input type="text" class="form-control form-control-sm" id="adminItemSearchInput" placeholder="Filter items by title or location..." style="max-width: 300px;">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="adminItemsTable">
                <thead class="table-light"><tr><th class="ps-3">#</th><th>Item</th><th>Type</th><th>Location</th><th>Date</th><th>Status (Live Toggle)</th><th class="text-end pe-3">Action</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr id="item-row-<?= $it['id'] ?>" class="item-table-row">
                            <td class="ps-3 text-muted"><?= $it['id'] ?></td>
                            <td class="fw-bold"><a href="<?= url('item-details.php?id=' . $it['id']) ?>" class="text-dark text-decoration-none"><?= sanitize($it['title']) ?></a></td>
                            <td><span class="badge <?= typeBadgeClass($it['item_type']) ?>"><?= strtoupper($it['item_type']) ?></span></td>
                            <td><?= sanitize($it['location']) ?></td>
                            <td><?= date('M d, Y', strtotime($it['event_date'])) ?></td>
                            <td>
                                <select class="form-select form-select-sm admin-status-select py-0" data-item-id="<?= $it['id'] ?>" style="width: auto;">
                                    <option value="Open" <?= $it['status']==='Open'?'selected':'' ?>>Open</option>
                                    <option value="Claim Requested" <?= $it['status']==='Claim Requested'?'selected':'' ?>>In Claim</option>
                                    <option value="Resolved" <?= $it['status']==='Resolved'?'selected':'' ?>>Resolved</option>
                                </select>
                            </td>
                            <td class="text-end pe-3">
                                <a href="<?= url('item-details.php?id=' . $it['id']) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2">View</a>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 admin-delete-item-btn" data-item-id="<?= $it['id'] ?>" data-item-title="<?= sanitize($it['title']) ?>">Delete</button>
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
