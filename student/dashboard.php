<?php
// student/dashboard.php - Student & Staff Portal Dashboard

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

AuthMiddleware::check();

$currentUser = currentUser();
$pageTitle = 'Student Dashboard';

$itemRepo = new ItemRepository();
$userItems = $itemRepo->getByUserId($currentUser['id']);

$claimRepo = new ClaimRepository();
$userClaims = $claimRepo->getByUserEmail($currentUser['email']);

$totalReported = count($userItems);
$totalClaims   = count($userClaims);
$resolvedItems = count(array_filter($userItems, fn($i) => $i['status'] === 'Resolved'));

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-4">
    <!-- Welcome Banner -->
    <div class="p-4 p-md-5 mb-4 rounded-4 bg-white border shadow-sm">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">
                    <i class="fa-solid fa-graduation-cap me-1"></i> Campus Member Portal
                </span>
                <h2 class="fw-bold text-dark mb-2">Welcome back, <?= sanitize($currentUser['name']) ?>!</h2>
                <p class="text-secondary lead fs-6 mb-3">
                    Search reported items across campus, report a lost or found belonging, or track status of your ownership claims.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= url('report-item.php') ?>" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                        <i class="fa-solid fa-plus-circle me-1"></i> Report an Item
                    </a>
                    <a href="<?= url('index.php') ?>" class="btn btn-outline-secondary px-3 py-2 rounded-3">
                        <i class="fa-solid fa-compass me-1"></i> Browse Listings
                    </a>
                    <a href="<?= url('logout.php') ?>" class="btn btn-outline-danger px-3 py-2 rounded-3">
                        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sign Out
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <div class="display-1 text-primary opacity-25">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-primary fs-3">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <span class="badge bg-primary fs-5 px-3 py-1 rounded-pill"><?= $totalReported ?></span>
                    </div>
                    <h5 class="card-title fw-bold">My Reported Items</h5>
                    <p class="card-text text-muted small">
                        Track the status of belongings you reported as lost or found on campus premises.
                    </p>
                    <a href="<?= url('student/my-reports.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill">Manage Reports &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-warning fs-3">
                            <i class="fa-solid fa-hand-holding-hand"></i>
                        </div>
                        <span class="badge bg-warning text-dark fs-5 px-3 py-1 rounded-pill"><?= $totalClaims ?></span>
                    </div>
                    <h5 class="card-title fw-bold">My Submitted Claims</h5>
                    <p class="card-text text-muted small">
                        Check review decisions and updates on ownership claims you submitted.
                    </p>
                    <a href="<?= url('student/my-claims.php') ?>" class="btn btn-sm btn-outline-warning rounded-pill">View My Claims &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-success fs-3">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <span class="badge bg-success fs-5 px-3 py-1 rounded-pill"><?= $resolvedItems ?></span>
                    </div>
                    <h5 class="card-title fw-bold">Items Reunited</h5>
                    <p class="card-text text-muted small">
                        Your reported belongings that have been successfully resolved and claimed.
                    </p>
                    <a href="<?= url('student/my-reports.php') ?>" class="btn btn-sm btn-outline-success rounded-pill">View Resolved &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reports Preview -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Recent Items You Reported</h5>
            <a href="<?= url('student/my-reports.php') ?>" class="small text-primary text-decoration-none fw-semibold">View All</a>
        </div>
        
        <?php if (empty($userItems)): ?>
            <div class="text-center py-4 text-muted small">
                You haven't reported any items yet. Found or lost something?
                <a href="<?= url('report-item.php') ?>" class="text-primary fw-semibold ms-1">Report here</a>.
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach (array_slice($userItems, 0, 4) as $item): ?>
                    <a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 px-2 border-0 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge <?= ($item['item_type'] === 'lost') ? 'bg-danger' : 'bg-success' ?> rounded-pill px-2 py-1">
                                <?= strtoupper($item['item_type']) ?>
                            </span>
                            <div>
                                <div class="fw-bold text-dark"><?= sanitize($item['title']) ?></div>
                                <small class="text-muted"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= sanitize($item['location']) ?> &bull; <?= date('M d, Y', strtotime($item['event_date'])) ?></small>
                            </div>
                        </div>
                        <span class="badge bg-light text-secondary border rounded-pill px-3 py-2"><?= sanitize($item['status']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
