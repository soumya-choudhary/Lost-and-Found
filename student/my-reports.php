<?php
// student/my-reports.php - Items Reported by Logged-in User

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

AuthMiddleware::check();

$currentUser = currentUser();
$pageTitle = 'My Reported Items';

$repo = new ItemRepository();
$items = $repo->getByUserId($currentUser['id']);

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= url('student/dashboard.php') ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Reported Items</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-box-open text-primary me-2"></i>My Reported Belongings</h3>
            <p class="text-muted small mb-0">Track all campus lost or found items you have posted.</p>
        </div>
        <div>
            <a href="<?= url('report-item.php') ?>" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-plus-circle me-1"></i> Report New Item
            </a>
        </div>
    </div>

    <!-- Reports Table / Card Container -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <?php if (empty($items)): ?>
            <div class="text-center py-5 p-4">
                <div class="text-secondary display-4 mb-3">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h5 class="fw-bold text-dark">No Items Reported Yet</h5>
                <p class="text-muted small mb-4">You have not submitted any lost or found reports under your account.</p>
                <a href="<?= url('report-item.php') ?>" class="btn btn-primary btn-sm rounded-pill px-4">
                    <i class="fa-solid fa-plus me-1"></i> Report an Item Now
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase fw-semibold">
                        <tr>
                            <th class="ps-4">Item Details</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Claims</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php 
                            $typeBadgeClass = ($item['item_type'] === 'lost') ? 'bg-danger text-white' : 'bg-success text-white';
                            $statusBadgeClass = 'badge-status-open';
                            if ($item['status'] === 'Claim Requested') $statusBadgeClass = 'badge-status-claim-requested';
                            elseif ($item['status'] === 'Resolved') $statusBadgeClass = 'badge-status-resolved';
                            $imageSrc = !empty($item['image_url']) ? url($item['image_url']) : url('public/assets/img/placeholder.svg');
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= $imageSrc ?>" alt="" class="rounded-3 shadow-sm border" style="width: 52px; height: 52px; object-fit: cover;">
                                        <div>
                                            <a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                <?= sanitize($item['title']) ?>
                                            </a>
                                            <div class="text-muted small">Ref #<?= $item['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= $typeBadgeClass ?> text-uppercase px-2 py-1">
                                        <?= sanitize($item['item_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-secondary fw-semibold">
                                        <i class="fa-solid <?= sanitize($item['category_icon'] ?? 'fa-box') ?> me-1 text-primary"></i>
                                        <?= sanitize($item['category_name']) ?>
                                    </span>
                                </td>
                                <td><span class="small text-muted"><?= sanitize($item['location']) ?></span></td>
                                <td><span class="small text-muted"><?= date('M d, Y', strtotime($item['event_date'])) ?></span></td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 <?= $statusBadgeClass ?>">
                                        <?= sanitize($item['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($item['total_claims'] > 0): ?>
                                        <a href="<?= url('item-details.php?id=' . $item['id'] . '#claims-received-section') ?>" class="badge bg-warning text-dark text-decoration-none rounded-pill px-2 py-1 shadow-sm hover-shadow" title="Click to review submitted claims">
                                            <i class="fa-solid fa-clipboard-check me-1"></i> <?= $item['total_claims'] ?> claim(s)
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">0 claims</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                        View Details <i class="fa-solid fa-arrow-right ms-1"></i>
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

