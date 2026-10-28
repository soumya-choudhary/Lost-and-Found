<?php
// admin/dashboard.php - Campus Administrator Dashboard

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

AdminMiddleware::check();

$currentUser = currentUser();
$pageTitle = 'Admin Dashboard';

// Quick DB Stats
try {
    $db = Database::getConnection();
    $totalLost     = (int)$db->query("SELECT COUNT(*) FROM items WHERE item_type = 'lost'")->fetchColumn();
    $totalFound    = (int)$db->query("SELECT COUNT(*) FROM items WHERE item_type = 'found'")->fetchColumn();
    $totalPending  = (int)$db->query("SELECT COUNT(*) FROM claims WHERE status = 'Pending'")->fetchColumn();
    $totalResolved = (int)$db->query("SELECT COUNT(*) FROM items WHERE status = 'Resolved'")->fetchColumn();
} catch (Exception $e) {
    $totalLost = $totalFound = $totalPending = $totalResolved = 0;
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-4">
    <!-- Admin Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-shield-halved text-primary me-2"></i>Campus Admin Control Center</h2>
            <p class="text-muted small mb-0">Logged in as <?= sanitize($currentUser['name']) ?> (<?= sanitize($currentUser['email']) ?>)</p>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="<?= url('index.php') ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-eye me-1"></i> Public View</a>
            <a href="<?= url('logout.php') ?>" class="btn btn-danger btn-sm"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sign Out</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Lost Items</div>
                        <h3 class="fw-bold mb-0 text-danger"><?= $totalLost ?></h3>
                    </div>
                    <div class="bg-danger-subtle text-danger p-3 rounded-circle fs-4">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Found Items</div>
                        <h3 class="fw-bold mb-0 text-success"><?= $totalFound ?></h3>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle fs-4">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Pending Claims</div>
                        <h3 class="fw-bold mb-0 text-warning"><?= $totalPending ?></h3>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-4">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Resolved Items</div>
                        <h3 class="fw-bold mb-0 text-info"><?= $totalResolved ?></h3>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle fs-4">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Admin Actions -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold mb-3">Administrative Operations</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="<?= url('admin/claims.php') ?>" class="text-decoration-none text-dark">
                    <div class="p-3 border rounded-3 bg-light hover-shadow">
                        <div class="fw-bold text-primary mb-1"><i class="fa-solid fa-clipboard-check me-2"></i>Review Ownership Claims</div>
                        <small class="text-muted">Review claimant submitted proof details and approve or reject claims.</small>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= url('admin/items.php') ?>" class="text-decoration-none text-dark">
                    <div class="p-3 border rounded-3 bg-light hover-shadow">
                        <div class="fw-bold text-success mb-1"><i class="fa-solid fa-boxes-stacked me-2"></i>Manage Item Listings</div>
                        <small class="text-muted">Update item statuses directly between Open, Claim Requested, and Resolved.</small>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= url('admin/users.php') ?>" class="text-decoration-none text-dark">
                    <div class="p-3 border rounded-3 bg-light hover-shadow">
                        <div class="fw-bold text-secondary mb-1"><i class="fa-solid fa-users me-2"></i>View Registered Users</div>
                        <small class="text-muted">Inspect registered student and staff accounts.</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

