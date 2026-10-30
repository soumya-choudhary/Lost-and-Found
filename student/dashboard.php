<?php
// ====================================================================================
// student/dashboard.php - Student & Staff Personal Dashboard
// ====================================================================================
// This page provides a personalized hub for logged-in students/staff members:
// - Summary Counters: Number of items reported, submitted claims, and resolved items.
// - Quick Links: Fast navigation to report a new item or browse the public catalog.
// - Recent Reports Preview: Displays the most recent 4 items reported by this student.
//
// ------------------------------------------------------------------------------------
// HOW TO MODIFY / PROFESSOR TASKS:
// - TO CHANGE THE RECENT REPORTS PREVIEW COUNT: In array_slice($userItems, 0, 4), change 4 to any number (e.g. 10).
// - TO ADD A FOURTH STAT CARD (e.g. Pending Claims): Filter $userClaims by fn($c) => $c['status'] === 'Pending'
//   and add a new col-sm-3 card in the "Personal Stats" section.
// - TO RESTRICT DASHBOARD ACCESS: AuthMiddleware::check() ensures only logged-in users enter.
// ====================================================================================

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

// Ensure the user is signed in; redirects to login.php if session does not exist
AuthMiddleware::check();

$currentUser = currentUser();

// 1. Fetch all items reported by the currently authenticated user
$userItems   = (new ItemRepository())->getByUserId($currentUser['id']);

// 2. Fetch all ownership claims filed by this user's email address
$userClaims  = (new ClaimRepository())->getByUserEmail($currentUser['email']);

// 3. Compute count of successfully reunited/resolved items
$resolved    = count(array_filter($userItems, fn($i) => $i['status'] === 'Resolved'));

// [HOW TO ADD A PENDING CLAIMS COUNTER]:
// $pendingClaims = count(array_filter($userClaims, fn($c) => $c['status'] === 'Pending'));

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <!-- Welcome Header & Quick Action Buttons -->
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

    <!-- Personal Stats Counters -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Reported Items by this User -->
        <div class="col-sm-4">
            <div class="card border shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-secondary small fw-bold">REPORTED ITEMS</div>
                <div class="fs-3 fw-bold text-primary"><?= count($userItems) ?></div>
                <a href="<?= url('student/my-reports.php') ?>" class="small text-decoration-none">View My Reports &rarr;</a>
            </div>
        </div>

        <!-- 2. Total Ownership Claims filed by this User -->
        <div class="col-sm-4">
            <div class="card border shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-secondary small fw-bold">SUBMITTED CLAIMS</div>
                <div class="fs-3 fw-bold text-warning"><?= count($userClaims) ?></div>
                <a href="<?= url('student/my-claims.php') ?>" class="small text-decoration-none">View My Claims &rarr;</a>
            </div>
        </div>

        <!-- 3. Total Items Successfully Resolved / Reunited -->
        <div class="col-sm-4">
            <div class="card border shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-secondary small fw-bold">RESOLVED ITEMS</div>
                <div class="fs-3 fw-bold text-success"><?= $resolved ?></div>
                <span class="small text-muted">Successfully reunited</span>
            </div>
        </div>
    </div>

    <!-- Recent Reports Preview List -->
    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Recent Reports</h6>
            <a href="<?= url('student/my-reports.php') ?>" class="small text-decoration-none">All Reports &rarr;</a>
        </div>
        <?php if (empty($userItems)): ?>
            <div class="p-4 text-center text-muted small">You haven't reported any lost or found items yet.</div>
        <?php else: ?>
            <ul class="list-group list-group-flush small">
                <!-- Change second parameter (4) below if professor asks to display more or fewer preview items -->
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
