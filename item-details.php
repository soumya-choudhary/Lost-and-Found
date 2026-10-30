<?php
// item-details.php - Item Details, Live Status Toggle & Claim Desk
//
// WHAT THIS PAGE DOES:
// 1. Fetches item by ID using ItemRepository::findById(). Redirects if not found.
// 2. Evaluates user permissions:
//    - $isReporter: User who submitted the report (matched by user_id OR contact_email).
//    - $canManage: True if user is Admin OR Item Reporter.
// 3. Status Control Section:
//    - Accessible ONLY to Item Reporter or Admin.
//    - Single-click AJAX buttons trigger api/update-status.php without full page reload.
// 4. Claims Received Section:
//    - Visible ONLY to Item Reporter or Admin. Shows claims filed on this item with proof and Accept/Reject buttons.
// 5. Ownership Claim Modal:
//    - Available to other users. Form is hidden from the reporter of the item.
// 6. Activity Log:
//    - Shows audit trail of status changes and claim filings from activity_logs table.
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO ADD A 4TH STATUS BUTTON (e.g., 'Archived'):
//   Inside the `$canManage` block below, add:
//   `<button type="button" class="btn btn-sm btn-outline-secondary flex-fill status-toggle-btn <?= $item['status']==='Archived'?'active':'' ?>" data-status="Archived" data-item-id="<?= $item['id'] ?>">Archived</button>`
// - TO HIDE REPORTER PHONE FROM PUBLIC: Wrap the phone block in `if (isLoggedIn())`.
// - TO ALLOW ANY LOGGED IN USER TO UPDATE STATUS: Change `$canManage = canManageItem($item, $currentUser)` to `$canManage = isLoggedIn()`.

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/repositories/ItemRepository.php';
require_once __DIR__ . '/app/repositories/ClaimRepository.php';

$itemId = (int)($_GET['id'] ?? 0);
$itemRepo = new ItemRepository();
$item = $itemRepo->findById($itemId);

if (!$item) {
    setFlash('danger', 'The requested item was not found.');
    redirect('index.php');
}

$currentUser = currentUser();
// Authorization check: User is original reporter OR administrator
$isReporter  = isItemOwner($item, $currentUser);
$canManage   = canManageItem($item, $currentUser);

// Fetch received claims on this item (for owner/admin evaluation)
$claimRepo = new ClaimRepository();
$claims = $claimRepo->getByItemId($itemId);

// Fetch audit trail history
$activityLogs = $itemRepo->getActivityLogs($itemId);

$pageTitle = $item['title'];
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container my-3">
    <!-- Breadcrumb Link -->
    <nav class="small mb-3"><a href="<?= url('index.php') ?>" class="text-decoration-none">&larr; Back to Listings</a></nav>

    <div class="row g-4">
        <!-- ===================================================================== -->
        <!-- LEFT COLUMN: Item Photo, Badges & AJAX Status Controls                -->
        <!-- ===================================================================== -->
        <div class="col-lg-5">
            <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
                <!-- Image Container with Badges -->
                <div class="position-relative bg-light text-center p-2">
                    <img src="<?= itemImageUrl($item['image_url'] ?? null) ?>" alt="<?= sanitize($item['title']) ?>" 
                         class="img-fluid rounded" style="max-height: 320px; object-fit: contain;">
                    <span class="badge position-absolute top-0 start-0 m-2 <?= typeBadgeClass($item['item_type']) ?> text-uppercase"><?= sanitize($item['item_type']) ?></span>
                    <span class="badge position-absolute top-0 end-0 m-2 <?= statusBadgeClass($item['status']) ?>" id="detail-status-badge"><?= sanitize($item['status']) ?></span>
                </div>

                <div class="card-body p-3">
                    <div class="small text-muted mb-2">Category: <strong class="text-dark"><?= sanitize($item['category_name']) ?></strong> &bull; Ref #<?= $item['id'] ?></div>

                    <!-- Single-Click AJAX Status Control Widget (Reporter / Admin Only) -->
                    <div class="p-2 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-secondary">STATUS CONTROL</span>
                            <small class="text-primary" id="status-update-spinner" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i> Updating...</small>
                        </div>

                        <?php if ($canManage): ?>
                            <!-- Authorized Action Buttons (triggers public/assets/js/items.js via AJAX) -->
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-success flex-fill status-toggle-btn <?= $item['status']==='Open'?'active':'' ?>" 
                                        data-status="Open" data-item-id="<?= $item['id'] ?>">Open</button>
                                <button type="button" class="btn btn-sm btn-outline-warning flex-fill status-toggle-btn <?= $item['status']==='Claim Requested'?'active':'' ?>" 
                                        data-status="Claim Requested" data-item-id="<?= $item['id'] ?>">In Claim</button>
                                <button type="button" class="btn btn-sm btn-outline-info flex-fill status-toggle-btn <?= $item['status']==='Resolved'?'active':'' ?>" 
                                        data-status="Resolved" data-item-id="<?= $item['id'] ?>">Resolved</button>
                            </div>
                            <small class="text-muted d-block mt-1">Authorized as <?= $isReporter ? 'Item Reporter' : 'Campus Admin' ?> (1-click AJAX update).</small>
                        <?php else: ?>
                            <!-- Read-only notice for visitors -->
                            <small class="text-muted">Status: <strong><?= sanitize($item['status']) ?></strong> (Editable by reporter/admin only).</small>
                        <?php endif; ?>
                    </div>

                    <!-- Claim Action Button or Owner Information Notice -->
                    <?php if ($isReporter): ?>
                        <div class="alert alert-info py-2 text-center small mb-0">
                            <i class="fa-solid fa-circle-check me-1"></i> You reported this item. Review received claims below.
                        </div>
                    <?php elseif ($item['status'] !== 'Resolved'): ?>
                        <!-- Modal Trigger Button for Claimants -->
                        <button type="button" class="btn btn-warning w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#claimModal">
                            <i class="fa-solid fa-hand-holding-hand me-1"></i> Submit Ownership Claim
                        </button>
                    <?php else: ?>
                        <div class="alert alert-secondary py-2 text-center small mb-0">This item is resolved and reunited.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- RIGHT COLUMN: Details, Contact, Received Claims & Audit History       -->
        <!-- ===================================================================== -->
        <div class="col-lg-7">
            <div class="card border shadow-sm rounded-3 p-4 bg-white mb-3">
                <h4 class="fw-bold mb-2"><?= sanitize($item['title']) ?></h4>
                <div class="row g-2 mb-3 small text-muted">
                    <div class="col-sm-6"><i class="fa-solid fa-location-dot text-danger me-1"></i> <?= sanitize($item['location']) ?></div>
                    <div class="col-sm-6"><i class="fa-regular fa-calendar text-primary me-1"></i> <?= date('M d, Y', strtotime($item['event_date'])) ?></div>
                </div>
                <p class="text-secondary small mb-3"><?= nl2br(sanitize($item['description'])) ?></p>

                <!-- Reporter Contact Details -->
                <div class="p-2 bg-light rounded border mb-3 small">
                    <strong>Reported by:</strong> <?= sanitize($item['contact_name']) ?> &bull; 
                    <a href="mailto:<?= sanitize($item['contact_email']) ?>" class="text-decoration-none"><?= sanitize($item['contact_email']) ?></a>
                    <?= !empty($item['contact_phone']) ? ' &bull; ' . sanitize($item['contact_phone']) : '' ?>
                </div>

                <!-- CLAIMS RECEIVED SECTION (Visible ONLY to Item Reporter and Admin) -->
                <?php if ($canManage): ?>
                    <div class="border rounded p-3 bg-light mb-3" id="claims-received-section">
                        <h6 class="fw-bold mb-2"><i class="fa-solid fa-clipboard-check text-warning me-1"></i> Received Claims (<?= count($claims) ?>)</h6>
                        <?php if (empty($claims)): ?>
                            <small class="text-muted">No ownership claims submitted yet.</small>
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
                                        <!-- Review Decision Actions for Pending Claims -->
                                        <?php if ($c['status'] === 'Pending'): ?>
                                            <div class="d-flex gap-1 justify-content-end pt-1 border-top">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 user-claim-decision-btn" 
                                                        data-claim-id="<?= $c['id'] ?>" data-decision="Rejected">Reject</button>
                                                <button type="button" class="btn btn-sm btn-success py-0 px-2 user-claim-decision-btn" 
                                                        data-claim-id="<?= $c['id'] ?>" data-decision="Approved">Accept</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Audit Trail Activity Log -->
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

<!-- ========================================================================= -->
<!-- CLAIM SUBMISSION MODAL (Available to non-owners)                          -->
<!-- ========================================================================= -->
<?php if (!$isReporter): ?>
<div class="modal fade" id="claimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title fw-bold">Claim Ownership for Item #<?= $item['id'] ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <!-- Handled via jQuery AJAX in public/assets/js/items.js -->
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
// Include items.js for AJAX status toggle and claim handling
$extraScripts = '<script src="' . url('public/assets/js/items.js') . '"></script>';
require_once __DIR__ . '/layouts/footer.php'; 
?>
