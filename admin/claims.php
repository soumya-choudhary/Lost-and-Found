<?php
// admin/claims.php - Admin Claim Review & Verification Panel

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

AdminMiddleware::check();

$claimRepo = new ClaimRepository();
$claims = $claimRepo->getAll(100);

$pageTitle = 'Manage Claims - Admin';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= url('admin/dashboard.php') ?>" class="text-decoration-none">Admin Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Manage Claims</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-clipboard-check text-warning me-2"></i>Review Ownership Claims</h3>
            <p class="text-muted small mb-0">Inspect student submitted proof of ownership and verify legitimate claims.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('admin/items.php') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Manage Items
            </a>
            <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-users me-1"></i> Users
            </a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="btn-group" role="group" id="claimFilterButtons">
                <button type="button" class="btn btn-sm btn-outline-secondary active" data-claim-status="all">All Claims (<?= count($claims) ?>)</button>
                <button type="button" class="btn btn-sm btn-outline-warning" data-claim-status="Pending">Pending</button>
                <button type="button" class="btn btn-sm btn-outline-success" data-claim-status="Approved">Approved</button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-claim-status="Rejected">Rejected</button>
            </div>
            <div id="admin-claim-alert" class="d-none small py-1 px-3 rounded-pill"></div>
        </div>

        <?php if (empty($claims)): ?>
            <div class="text-center py-5 p-4 text-muted">
                <div class="display-4 text-secondary mb-3"><i class="fa-solid fa-clipboard-check"></i></div>
                <h5>No Claims Submitted Yet</h5>
                <p class="small">When users submit ownership verification requests, they will appear here for review.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="adminClaimsTable">
                    <thead class="table-light text-secondary small text-uppercase fw-semibold">
                        <tr>
                            <th class="ps-4">Claim #</th>
                            <th>Related Item</th>
                            <th>Claimant Details</th>
                            <th>Proof Details</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end pe-4">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($claims as $c): ?>
                            <?php
                            $badgeClass = 'bg-warning text-dark';
                            if ($c['status'] === 'Approved') $badgeClass = 'bg-success text-white';
                            elseif ($c['status'] === 'Rejected') $badgeClass = 'bg-danger text-white';
                            ?>
                            <tr class="claim-row" data-status="<?= sanitize($c['status']) ?>" id="claim-row-<?= $c['id'] ?>">
                                <td class="ps-4 fw-bold text-muted">#<?= $c['id'] ?></td>
                                <td>
                                    <a href="<?= url('item-details.php?id=' . $c['item_id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                        <?= sanitize($c['item_title']) ?>
                                    </a>
                                    <div class="small text-muted">
                                        <span class="badge text-uppercase <?= ($c['item_type'] === 'lost') ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>">
                                            <?= $c['item_type'] ?>
                                        </span>
                                        &bull; <?= sanitize($c['location']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= sanitize($c['claimant_name']) ?></div>
                                    <div class="small text-muted"><?= sanitize($c['claimant_email']) ?></div>
                                    <?php if (!empty($c['claimant_phone'])): ?>
                                        <div class="small text-muted"><?= sanitize($c['claimant_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small text-secondary text-truncate" style="max-width: 220px;" title="<?= sanitize($c['proof_description']) ?>">
                                        <?= sanitize($c['proof_description']) ?>
                                    </div>
                                    <?php if (!empty($c['proof_image_url'])): ?>
                                        <a href="<?= url($c['proof_image_url']) ?>" target="_blank" class="small text-primary text-decoration-none">
                                            <i class="fa-solid fa-image me-1"></i> View Proof Photo
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-1 <?= $badgeClass ?> claim-status-badge">
                                        <?= sanitize($c['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-muted"><?= date('M d, Y', strtotime($c['created_at'])) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill open-decision-modal-btn" 
                                            data-claim-id="<?= $c['id'] ?>"
                                            data-item-title="<?= sanitize($c['item_title']) ?>"
                                            data-claimant="<?= sanitize($c['claimant_name']) ?>"
                                            data-proof="<?= sanitize($c['proof_description']) ?>"
                                            data-notes="<?= sanitize($c['admin_notes'] ?? '') ?>"
                                            data-current-status="<?= sanitize($c['status']) ?>">
                                        <i class="fa-solid fa-gavel me-1"></i> Review
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Admin Decision Modal -->
<div class="modal fade" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="decisionModalLabel">
                    <i class="fa-solid fa-gavel me-2"></i> Claim Decision &amp; Verification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="adminDecisionForm">
                <input type="hidden" name="claim_id" id="modal_claim_id">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Item Title</label>
                        <div class="fw-bold fs-6" id="modal_item_title"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Claimant</label>
                        <div id="modal_claimant_name" class="fw-semibold text-dark"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Submitted Proof Details</label>
                        <div class="p-3 bg-light rounded-3 small border" id="modal_proof_text"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_admin_notes" class="form-label small fw-semibold text-secondary">Administrator Resolution Notes</label>
                        <textarea class="form-control rounded-3" id="modal_admin_notes" name="admin_notes" rows="3" 
                                  placeholder="e.g. Identity verified via student card; ownership proved by serial number match."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light py-3 rounded-bottom-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-danger rounded-pill px-3 fw-semibold admin-submit-decision-btn" data-decision="Rejected">
                            <i class="fa-solid fa-xmark me-1"></i> Reject Claim
                        </button>
                        <button type="button" class="btn btn-success rounded-pill px-3 fw-semibold admin-submit-decision-btn" data-decision="Approved">
                            <i class="fa-solid fa-check me-1"></i> Approve &amp; Resolve
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extraScripts = '<script src="' . url('public/assets/js/admin.js') . '"></script>';
require_once __DIR__ . '/../layouts/footer.php'; 
?>

