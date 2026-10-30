<?php
// admin/dashboard.php - Campus Admin Overview

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

AdminMiddleware::check();

$itemRepo = new ItemRepository();
$stats = $itemRepo->getStats();
$claimRepo = new ClaimRepository();
$recentClaims = $claimRepo->getAll(5);

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
        <div>
            <h4 class="fw-bold mb-0"><i class="fa-solid fa-shield-halved text-primary me-2"></i>Admin Dashboard</h4>
            <small class="text-muted">Campus Staff Portal</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('admin/claims.php') ?>" class="btn btn-outline-warning btn-sm rounded-pill px-3">Claims Desk</a>
            <a href="<?= url('admin/items.php') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">Manage Items</a>
            <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Users</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-2 mb-3 text-center">
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm p-3 bg-white rounded-3">
                <small class="text-secondary fw-bold">TOTAL LOST</small>
                <div class="fs-3 fw-bold text-danger"><?= $stats['total_lost'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm p-3 bg-white rounded-3">
                <small class="text-secondary fw-bold">TOTAL FOUND</small>
                <div class="fs-3 fw-bold text-success"><?= $stats['total_found'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm p-3 bg-white rounded-3">
                <small class="text-secondary fw-bold">PENDING CLAIMS</small>
                <div class="fs-3 fw-bold text-warning"><?= $claimRepo->getPendingCount() ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm p-3 bg-white rounded-3">
                <small class="text-secondary fw-bold">RESOLVED</small>
                <div class="fs-3 fw-bold text-primary"><?= $stats['total_resolved'] ?></div>
            </div>
        </div>
    </div>

    <!-- Recent Claims -->
    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Recent Claims</h6>
            <a href="<?= url('admin/claims.php') ?>" class="small text-decoration-none">Manage All &rarr;</a>
        </div>
        <?php if (empty($recentClaims)): ?>
            <div class="p-4 text-center text-muted small">No claims submitted yet.</div>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light"><tr><th class="ps-3">Item</th><th>Claimant</th><th>Status</th><th>Date</th><th class="text-end pe-3">Action</th></tr></thead>
                <tbody>
                    <?php foreach ($recentClaims as $c): ?>
                        <tr>
                            <td class="ps-3 fw-bold"><a href="<?= url('item-details.php?id=' . $c['item_id']) ?>" class="text-dark text-decoration-none"><?= sanitize($c['item_title']) ?></a></td>
                            <td><?= sanitize($c['claimant_name']) ?> <small class="text-muted">(<?= sanitize($c['claimant_email']) ?>)</small></td>
                            <td><span class="badge <?= claimBadgeClass($c['status']) ?>"><?= sanitize($c['status']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                            <td class="text-end pe-3"><a href="<?= url('admin/claims.php') ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Review</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
