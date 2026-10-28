<?php
// admin/dashboard.php - Campus Administrator Control Center

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

AdminMiddleware::check();

$currentUser = currentUser();
$pageTitle = 'Admin Dashboard';

// Live statistics
$db = Database::getConnection();
$totalLost     = (int)$db->query("SELECT COUNT(*) FROM items WHERE item_type = 'lost'")->fetchColumn();
$totalFound    = (int)$db->query("SELECT COUNT(*) FROM items WHERE item_type = 'found'")->fetchColumn();
$totalPending  = (int)$db->query("SELECT COUNT(*) FROM claims WHERE status = 'Pending'")->fetchColumn();
$totalResolved = (int)$db->query("SELECT COUNT(*) FROM items WHERE status = 'Resolved'")->fetchColumn();

// Recent pending claims
$claimRepo = new ClaimRepository();
$allClaims = $claimRepo->getAll(6);

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-4">
    <!-- Admin Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-2">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-shield-halved text-primary me-2"></i>Campus Admin Control Center</h2>
            <p class="text-muted small mb-0">Logged in as <?= sanitize($currentUser['name']) ?> (<?= sanitize($currentUser['email']) ?>)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('index.php') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-eye me-1"></i> Public Directory
            </a>
            <a href="<?= url('logout.php') ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sign Out
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <a href="<?= url('admin/items.php') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow">
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
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="<?= url('admin/items.php') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow">
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
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="<?= url('admin/claims.php') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow">
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
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="<?= url('admin/items.php') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow">
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
            </a>
        </div>
    </div>

    <!-- Quick Admin Action Cards -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h5 class="fw-bold mb-3">Management Sections</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="<?= url('admin/claims.php') ?>" class="text-decoration-none text-dark">
                    <div class="p-3 border rounded-3 bg-light hover-shadow h-100">
                        <div class="fw-bold text-warning mb-1">
                            <i class="fa-solid fa-clipboard-check me-2"></i>Review Ownership Claims
                        </div>
                        <small class="text-muted">Review claimant proof details, approve or reject claims, and automatically resolve items.</small>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= url('admin/items.php') ?>" class="text-decoration-none text-dark">
                    <div class="p-3 border rounded-3 bg-light hover-shadow h-100">
                        <div class="fw-bold text-primary mb-1">
                            <i class="fa-solid fa-boxes-stacked me-2"></i>Manage Item Listings
                        </div>
                        <small class="text-muted">Quick-toggle item statuses (`Open`, `Claim Requested`, `Resolved`) or delete invalid reports.</small>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= url('admin/users.php') ?>" class="text-decoration-none text-dark">
                    <div class="p-3 border rounded-3 bg-light hover-shadow h-100">
                        <div class="fw-bold text-success mb-1">
                            <i class="fa-solid fa-users me-2"></i>User Accounts
                        </div>
                        <small class="text-muted">Inspect registered campus members and toggle administrative permissions.</small>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Claims Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Recent Submitted Claims</h6>
            <a href="<?= url('admin/claims.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All Claims</a>
        </div>

        <?php if (empty($allClaims)): ?>
            <div class="text-center py-4 text-muted small">No claims submitted yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3">Item</th>
                            <th>Claimant</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allClaims as $c): ?>
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    <a href="<?= url('item-details.php?id=' . $c['item_id']) ?>" class="text-dark text-decoration-none hover-primary">
                                        <?= sanitize($c['item_title']) ?>
                                    </a>
                                </td>
                                <td><?= sanitize($c['claimant_name']) ?> <small class="text-muted">(<?= sanitize($c['claimant_email']) ?>)</small></td>
                                <td>
                                    <span class="badge rounded-pill px-2 py-1 <?= ($c['status'] === 'Approved') ? 'bg-success' : (($c['status'] === 'Rejected') ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                        <?= sanitize($c['status']) ?>
                                    </span>
                                </td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($c['created_at'])) ?></small></td>
                                <td class="text-end pe-3">
                                    <a href="<?= url('admin/claims.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
