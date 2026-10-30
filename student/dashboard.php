<?php
// student/dashboard.php - Student Portal Dashboard

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

AuthMiddleware::check();

$currentUser = currentUser();
$userItems   = (new ItemRepository())->getByUserId($currentUser['id']);
$userClaims  = (new ClaimRepository())->getByUserEmail($currentUser['email']);
$resolved    = count(array_filter($userItems, fn($i) => $i['status'] === 'Resolved'));

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <!-- Welcome Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
        <div>
            <h4 class="fw-bold mb-0">Welcome, <?= sanitize($currentUser['name']) ?>!</h4>
            <small class="text-muted">Student &amp; Staff Dashboard</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('report-item.php') ?>" class="btn btn-primary btn-sm rounded-pill px-3">+ Report Item</a>
            <a href="<?= url('index.php') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Browse Catalog</a>
        </div>
    </div>

    <!-- Personal Stats -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-secondary small fw-bold">REPORTED ITEMS</div>
                <div class="fs-3 fw-bold text-primary"><?= count($userItems) ?></div>
                <a href="<?= url('student/my-reports.php') ?>" class="small text-decoration-none">View My Reports &rarr;</a>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-secondary small fw-bold">SUBMITTED CLAIMS</div>
                <div class="fs-3 fw-bold text-warning"><?= count($userClaims) ?></div>
                <a href="<?= url('student/my-claims.php') ?>" class="small text-decoration-none">View My Claims &rarr;</a>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-secondary small fw-bold">RESOLVED ITEMS</div>
                <div class="fs-3 fw-bold text-success"><?= $resolved ?></div>
                <span class="small text-muted">Successfully reunited</span>
            </div>
        </div>
    </div>

    <!-- Recent Reports Preview -->
    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Recent Reports</h6>
            <a href="<?= url('student/my-reports.php') ?>" class="small text-decoration-none">All Reports &rarr;</a>
        </div>
        <?php if (empty($userItems)): ?>
            <div class="p-4 text-center text-muted small">You haven't reported any lost or found items yet.</div>
        <?php else: ?>
            <ul class="list-group list-group-flush small">
                <?php foreach (array_slice($userItems, 0, 4) as $it): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge <?= typeBadgeClass($it['item_type']) ?> me-2"><?= strtoupper($it['item_type']) ?></span>
                            <a href="<?= url('item-details.php?id=' . $it['id']) ?>" class="fw-bold text-dark text-decoration-none"><?= sanitize($it['title']) ?></a>
                            <small class="text-muted ms-2"><?= sanitize($it['location']) ?></small>
                        </div>
                        <span class="badge <?= statusBadgeClass($it['status']) ?>"><?= sanitize($it['status']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
