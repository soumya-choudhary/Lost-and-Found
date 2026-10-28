<?php
// student/my-claims.php - Claims Submitted by Logged-in User

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

AuthMiddleware::check();

$currentUser = currentUser();
$pageTitle = 'My Ownership Claims';

$claimRepo = new ClaimRepository();
$claims = $claimRepo->getByUserEmail($currentUser['email']);

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= url('student/dashboard.php') ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Claims</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-hand-holding-hand text-warning me-2"></i>My Ownership Claims</h3>
            <p class="text-muted small mb-0">Track verification status and administrator responses for items you have claimed.</p>
        </div>
        <div>
            <a href="<?= url('index.php') ?>" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-compass me-1"></i> Browse Campus Items
            </a>
        </div>
    </div>

    <!-- Claims List -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <?php if (empty($claims)): ?>
            <div class="text-center py-5 p-4">
                <div class="text-secondary display-4 mb-3">
                    <i class="fa-solid fa-clipboard-question"></i>
                </div>
                <h5 class="fw-bold text-dark">No Claims Submitted Yet</h5>
                <p class="text-muted small mb-4">You have not submitted an ownership claim on any campus lost or found items.</p>
                <a href="<?= url('index.php') ?>" class="btn btn-primary btn-sm rounded-pill px-4">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Search Campus Catalog
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase fw-semibold">
                        <tr>
                            <th class="ps-4">Claimed Item</th>
                            <th>Claim Status</th>
                            <th>Proof Details Submitted</th>
                            <th>Admin Review Note</th>
                            <th>Claim Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($claims as $claim): ?>
                            <?php 
                            $statusBadge = 'bg-warning text-dark';
                            if ($claim['status'] === 'Approved') $statusBadge = 'bg-success text-white';
                            elseif ($claim['status'] === 'Rejected') $statusBadge = 'bg-danger text-white';
                            $imageSrc = !empty($claim['image_url']) ? url($claim['image_url']) : url('public/assets/img/placeholder.svg');
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= $imageSrc ?>" alt="" class="rounded-3 shadow-sm border" style="width: 48px; height: 48px; object-fit: cover;">
                                        <div>
                                            <a href="<?= url('item-details.php?id=' . $claim['item_id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                <?= sanitize($claim['item_title']) ?>
                                            </a>
                                            <div class="text-muted small">
                                                <i class="fa-solid fa-location-dot text-danger me-1"></i> <?= sanitize($claim['location']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 <?= $statusBadge ?> shadow-sm">
                                        <?= sanitize($claim['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-secondary text-truncate" style="max-width: 250px;" title="<?= sanitize($claim['proof_description']) ?>">
                                        <?= sanitize($claim['proof_description']) ?>
                                    </div>
                                    <?php if (!empty($claim['proof_image_url'])): ?>
                                        <a href="<?= url($claim['proof_image_url']) ?>" target="_blank" class="small text-primary text-decoration-none">
                                            <i class="fa-solid fa-paperclip me-1"></i> View Attached Proof
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($claim['admin_notes'])): ?>
                                        <div class="small text-dark fw-medium">
                                            <?= sanitize($claim['admin_notes']) ?>
                                        </div>
                                        <?php if (!empty($claim['resolved_at'])): ?>
                                            <small class="text-muted d-block"><?= date('M d, Y H:i', strtotime($claim['resolved_at'])) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">Pending administrator review</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small text-muted"><?= date('M d, Y', strtotime($claim['created_at'])) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?= url('item-details.php?id=' . $claim['item_id']) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                        View Item <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
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

