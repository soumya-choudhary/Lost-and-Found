<?php
// admin/claims.php - Claims Review Desk

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

AdminMiddleware::check();
$claims = (new ClaimRepository())->getAll(100);

$pageTitle = 'Manage Claims';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-clipboard-check text-warning me-2"></i>Claims Review Desk</h4>
        <div id="admin-claim-alert" class="d-none small py-1 px-3 rounded"></div>
    </div>

    <!-- Filter Buttons & Table -->
    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="p-2 border-bottom bg-light">
            <div class="btn-group btn-group-sm" role="group" id="claimFilterButtons">
                <button type="button" class="btn btn-outline-secondary active" data-claim-status="all">All (<?= count($claims) ?>)</button>
                <button type="button" class="btn btn-outline-warning" data-claim-status="Pending">Pending</button>
                <button type="button" class="btn btn-outline-success" data-claim-status="Approved">Approved</button>
                <button type="button" class="btn btn-outline-danger" data-claim-status="Rejected">Rejected</button>
            </div>
        </div>

        <?php if (empty($claims)): ?>
            <div class="p-4 text-center text-muted small">No claims to review.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small" id="adminClaimsTable">
                    <thead class="table-light"><tr><th class="ps-3">#</th><th>Item</th><th>Claimant</th><th>Proof</th><th>Status</th><th>Date</th><th class="text-end pe-3">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($claims as $c): ?>
                            <tr class="claim-row" data-status="<?= sanitize($c['status']) ?>" id="claim-row-<?= $c['id'] ?>">
                                <td class="ps-3 fw-bold text-muted"><?= $c['id'] ?></td>
                                <td><a href="<?= url('item-details.php?id=' . $c['item_id']) ?>" class="fw-bold text-dark text-decoration-none"><?= sanitize($c['item_title']) ?></a></td>
                                <td><?= sanitize($c['claimant_name']) ?><br><small class="text-muted"><?= sanitize($c['claimant_email']) ?></small></td>
                                <td class="text-truncate" style="max-width: 200px;">
                                    <?= sanitize($c['proof_description']) ?>
                                    <?php if (!empty($c['proof_image_url'])): ?>
                                        <br><a href="<?= url($c['proof_image_url']) ?>" target="_blank" class="small text-primary">View Photo</a>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= claimBadgeClass($c['status']) ?> claim-status-badge"><?= sanitize($c['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 open-decision-modal-btn"
                                            data-claim-id="<?= $c['id'] ?>" data-item-title="<?= sanitize($c['item_title']) ?>"
                                            data-claimant="<?= sanitize($c['claimant_name']) ?>" data-proof="<?= sanitize($c['proof_description']) ?>"
                                            data-notes="<?= sanitize($c['admin_notes'] ?? '') ?>" data-current-status="<?= sanitize($c['status']) ?>">Review</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="decisionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light"><h6 class="modal-title fw-bold">Review Claim</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="adminDecisionForm">
                <input type="hidden" name="claim_id" id="modal_claim_id">
                <div class="modal-body p-3">
                    <div class="mb-2"><strong id="modal_item_title"></strong> &bull; Claimant: <span id="modal_claimant_name"></span></div>
                    <div class="p-2 bg-light rounded border small mb-2" id="modal_proof_text"></div>
                    <div>
                        <label class="form-label small mb-1">Resolution Note</label>
                        <textarea class="form-control form-control-sm" id="modal_admin_notes" name="admin_notes" rows="2" placeholder="e.g. Verified student ID and serial number match."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <div>
                        <button type="button" class="btn btn-danger btn-sm admin-submit-decision-btn" data-decision="Rejected">Reject</button>
                        <button type="button" class="btn btn-success btn-sm admin-submit-decision-btn" data-decision="Approved">Approve</button>
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
