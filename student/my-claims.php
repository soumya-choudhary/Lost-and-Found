<?php
// student/my-claims.php - Claims submitted by the current student

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

AuthMiddleware::check();

$currentUser = currentUser();
$claims = (new ClaimRepository())->getByUserEmail($currentUser['email']);

$pageTitle = 'My Claims';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">My Submitted Claims</h4>
        <a href="<?= url('index.php') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">Browse Items</a>
    </div>

    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <?php if (empty($claims)): ?>
            <div class="p-4 text-center text-muted">You have not submitted any ownership claims.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Claimed Item</th>
                            <th>Status</th>
                            <th>Proof Details</th>
                            <th>Resolution Notes</th>
                            <th>Date</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($claims as $c): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><a href="<?= url('item-details.php?id=' . $c['item_id']) ?>" class="text-dark text-decoration-none"><?= sanitize($c['item_title']) ?></a></td>
                                <td><span class="badge <?= claimBadgeClass($c['status']) ?>"><?= sanitize($c['status']) ?></span></td>
                                <td class="text-truncate" style="max-width: 200px;"><?= sanitize($c['proof_description']) ?></td>
                                <td><?= !empty($c['admin_notes']) ? sanitize($c['admin_notes']) : '<span class="text-muted fst-italic">Pending review</span>' ?></td>
                                <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                <td class="text-end pe-3"><a href="<?= url('item-details.php?id=' . $c['item_id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
