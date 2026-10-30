<?php
// item-details.php - Item Details, Live Status Toggle & Claim Desk

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/repositories/ItemRepository.php';
require_once __DIR__ . '/app/repositories/ClaimRepository.php';

$itemId = (int)($_GET['id'] ?? 0);
$itemRepo = new ItemRepository();
$item = $itemRepo->findById($itemId);

if (!$item) {
    setFlash('danger', 'Item not found.');
    redirect('index.php');
}

$currentUser = currentUser();
$isReporter  = isItemOwner($item, $currentUser);
$canManage   = canManageItem($item, $currentUser);

$claimRepo = new ClaimRepository();
$claims = $claimRepo->getByItemId($itemId);
$activityLogs = $itemRepo->getActivityLogs($itemId);

$pageTitle = $item['title'];
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container my-3">
    <!-- Breadcrumb -->
    <nav class="small mb-3"><a href="<?= url('index.php') ?>" class="text-decoration-none">&larr; Back to Listings</a></nav>

    <div class="row g-4">
        <!-- Left: Image, Badges & Status Control -->
        <div class="col-lg-5">
            <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
                <div class="position-relative bg-light text-center p-2">
                    <img src="<?= itemImageUrl($item['image_url'] ?? null) ?>" alt="<?= sanitize($item['title']) ?>" class="img-fluid rounded" style="max-height: 320px; object-fit: contain;">
                    <span class="badge position-absolute top-0 start-0 m-2 <?= typeBadgeClass($item['item_type']) ?> text-uppercase"><?= sanitize($item['item_type']) ?></span>
                    <span class="badge position-absolute top-0 end-0 m-2 <?= statusBadgeClass($item['status']) ?>" id="detail-status-badge"><?= sanitize($item['status']) ?></span>
                </div>
                <div class="card-body p-3">
                    <div class="small text-muted mb-2">Category: <strong class="text-dark"><?= sanitize($item['category_name']) ?></strong> &bull; Ref #<?= $item['id'] ?></div>

                    <!-- Status Control Widget (Owner / Admin only) -->
                    <div class="p-2 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-secondary">STATUS CONTROL</span>
                            <small class="text-primary" id="status-update-spinner" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i></small>
                        </div>
                        <?php if ($canManage): ?>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-success flex-fill status-toggle-btn <?= $item['status']==='Open'?'active':'' ?>" data-status="Open" data-item-id="<?= $item['id'] ?>">Open</button>
                                <button type="button" class="btn btn-sm btn-outline-warning flex-fill status-toggle-btn <?= $item['status']==='Claim Requested'?'active':'' ?>" data-status="Claim Requested" data-item-id="<?= $item['id'] ?>">In Claim</button>
                                <button type="button" class="btn btn-sm btn-outline-info flex-fill status-toggle-btn <?= $item['status']==='Resolved'?'active':'' ?>" data-status="Resolved" data-item-id="<?= $item['id'] ?>">Resolved</button>
                            </div>
                            <small class="text-muted d-block mt-1">Authorized as <?= $isReporter ? 'Reporter' : 'Admin' ?> (1-click AJAX update).</small>
                        <?php else: ?>
                            <small class="text-muted">Status: <strong><?= sanitize($item['status']) ?></strong> (Editable by reporter/admin only).</small>
                        <?php endif; ?>
                    </div>

                    <!-- Action: Claim or Owner notice -->
                    <?php if ($isReporter): ?>
                        <div class="alert alert-info py-2 text-center small mb-0">You reported this item. Review claims below.</div>
                    <?php elseif ($item['status'] !== 'Resolved'): ?>
                        <button type="button" class="btn btn-warning w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#claimModal">
                            <i class="fa-solid fa-hand-holding-hand me-1"></i> Submit Ownership Claim
                        </button>
                    <?php else: ?>
                        <div class="alert alert-secondary py-2 text-center small mb-0">This item is resolved and reunited.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Details, Received Claims & Audit Log -->
        <div class="col-lg-7">
            <div class="card border shadow-sm rounded-3 p-4 bg-white mb-3">
                <h4 class="fw-bold mb-2"><?= sanitize($item['title']) ?></h4>
                <div class="row g-2 mb-3 small text-muted">
                    <div class="col-sm-6"><i class="fa-solid fa-location-dot text-danger me-1"></i> <?= sanitize($item['location']) ?></div>
                    <div class="col-sm-6"><i class="fa-regular fa-calendar text-primary me-1"></i> <?= date('M d, Y', strtotime($item['event_date'])) ?></div>
                </div>
                <p class="text-secondary small mb-3"><?= nl2br(sanitize($item['description'])) ?></p>

                <!-- Reporter Info -->
                <div class="p-2 bg-light rounded border mb-3 small">
                    <strong>Reported by:</strong> <?= sanitize($item['contact_name']) ?> &bull; 
                    <a href="mailto:<?= sanitize($item['contact_email']) ?>" class="text-decoration-none"><?= sanitize($item['contact_email']) ?></a>
                    <?= !empty($item['contact_phone']) ? ' &bull; ' . sanitize($item['contact_phone']) : '' ?>
                </div>

                <!-- Claims Received Section (Owner / Admin only) -->
                <?php if ($canManage): ?>
                    <div class="border rounded p-3 bg-light mb-3" id="claims-received-section">
                        <h6 class="fw-bold mb-2"><i class="fa-solid fa-clipboard-check text-warning me-1"></i> Received Claims (<?= count($claims) ?>)</h6>
                        <?php if (empty($claims)): ?>
                            <small class="text-muted">No claims submitted yet.</small>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($claims as $c): ?>
                                    <div class="p-2 bg-white rounded border small" id="claim-card-<?= $c['id'] ?>">
                                        <div class="d-flex justify-content-between mb-1">
                                            <strong><?= sanitize($c['claimant_name']) ?></strong> (<?= sanitize($c['claimant_email']) ?>)
                                            <span class="badge <?= claimBadgeClass($c['status']) ?>"><?= sanitize($c['status']) ?></span>
                                        </div>
                                        <div class="text-muted mb-1"><?= sanitize($c['proof_description']) ?></div>
                                        <?php if (!empty($c['proof_image_url'])): ?>
                                            <a href="<?= url($c['proof_image_url']) ?>" target="_blank" class="small text-primary d-inline-block mb-1">View Proof Photo</a>
                                        <?php endif; ?>
                                        <?php if ($c['status'] === 'Pending'): ?>
                                            <div class="d-flex gap-1 justify-content-end pt-1 border-top">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 user-claim-decision-btn" data-claim-id="<?= $c['id'] ?>" data-decision="Rejected">Reject</button>
                                                <button type="button" class="btn btn-sm btn-success py-0 px-2 user-claim-decision-btn" data-claim-id="<?= $c['id'] ?>" data-decision="Approved">Accept</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Audit Log History -->
                <h6 class="fw-bold mb-2 small text-secondary">ACTIVITY LOG</h6>
                <div class="list-group list-group-flush border rounded small" id="activity-log-list">
                    <?php if (empty($activityLogs)): ?>
                        <div class="list-group-item text-muted py-1">No activity logged.</div>
                    <?php else: ?>
                        <?php foreach ($activityLogs as $log): ?>
                            <div class="list-group-item py-1">
                                <div class="d-flex justify-content-between">
                                    <strong><?= sanitize($log['action']) ?></strong>
                                    <span class="text-muted"><?= date('M d, H:i', strtotime($log['created_at'])) ?></span>
                                </div>
                                <span class="text-muted"><?= sanitize($log['details']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Claim Modal (Non-owners only) -->
<?php if (!$isReporter): ?>
<div class="modal fade" id="claimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title fw-bold">Claim Ownership for Item #<?= $item['id'] ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="claimSubmitForm" enctype="multipart/form-data">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <div class="modal-body p-3">
                    <div id="claim-feedback" class="alert d-none py-1 small mb-2"></div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Your Full Name *</label>
                        <input type="text" class="form-control form-control-sm" name="claimant_name" value="<?= $currentUser['name'] ?? '' ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Campus Email *</label>
                        <input type="email" class="form-control form-control-sm" name="claimant_email" value="<?= $currentUser['email'] ?? '' ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Phone Number</label>
                        <input type="tel" class="form-control form-control-sm" name="claimant_phone">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Proof Description (unique stickers, wallpaper, serial #, contents) *</label>
                        <textarea class="form-control form-control-sm" name="proof_description" rows="3" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Attach Receipt / Photo (Optional)</label>
                        <input type="file" class="form-control form-control-sm" name="proof_image" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold" id="submit-claim-btn">Submit Claim</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
$extraScripts = '<script src="' . url('public/assets/js/items.js') . '"></script>';
require_once __DIR__ . '/layouts/footer.php'; 
?>
