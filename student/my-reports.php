<?php
// student/my-reports.php - Items reported by the current student

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

AuthMiddleware::check();

$currentUser = currentUser();
$items = (new ItemRepository())->getByUserId($currentUser['id']);

$pageTitle = 'My Reports';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">My Reported Items</h4>
        <a href="<?= url('report-item.php') ?>" class="btn btn-primary btn-sm rounded-pill px-3">+ Report Item</a>
    </div>

    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <?php if (empty($items)): ?>
            <div class="p-4 text-center text-muted">You have not reported any items yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Item</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Claims</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="text-dark text-decoration-none"><?= sanitize($item['title']) ?></a></td>
                                <td><span class="badge <?= typeBadgeClass($item['item_type']) ?>"><?= strtoupper($item['item_type']) ?></span></td>
                                <td><?= sanitize($item['category_name']) ?></td>
                                <td><?= sanitize($item['location']) ?></td>
                                <td><?= date('M d, Y', strtotime($item['event_date'])) ?></td>
                                <td><span class="badge <?= statusBadgeClass($item['status']) ?>"><?= sanitize($item['status']) ?></span></td>
                                <td><?= $item['total_claims'] > 0 ? "<a href='" . url('item-details.php?id=' . $item['id']) . "#claims-received-section' class='badge bg-warning text-dark text-decoration-none'>{$item['total_claims']} claim(s)</a>" : '0' ?></td>
                                <td class="text-end pe-3"><a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
