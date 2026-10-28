<?php
// student/dashboard.php - Student & Staff Portal Dashboard

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';

AuthMiddleware::check();

$currentUser = currentUser();
$pageTitle = 'Student Dashboard';
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

    <!-- Quick Action Cards -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="card-body">
                    <div class="text-primary fs-3 mb-3">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <h5 class="card-title fw-bold">Report Lost Belonging</h5>
                    <p class="card-text text-muted small">
                        Lost something on campus? File a report with photos, location, and details so the campus community can help locate it.
                    </p>
                    <a href="<?= url('report-item.php?type=lost') ?>" class="btn btn-sm btn-outline-primary rounded-pill">Report Lost &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="card-body">
                    <div class="text-success fs-3 mb-3">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                    <h5 class="card-title fw-bold">Report Found Item</h5>
                    <p class="card-text text-muted small">
                        Found an unattended phone, calculator, or keys? Report it here so the rightful owner can verify and claim it.
                    </p>
                    <a href="<?= url('report-item.php?type=found') ?>" class="btn btn-sm btn-outline-success rounded-pill">Report Found &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="card-body">
                    <div class="text-warning fs-3 mb-3">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <h5 class="card-title fw-bold">Track Your Claims</h5>
                    <p class="card-text text-muted small">
                        Review the real-time status of your ownership verification requests and administrator approvals.
                    </p>
                    <a href="<?= url('student/my-claims.php') ?>" class="btn btn-sm btn-outline-warning rounded-pill">View My Claims &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

