<?php
// item-details.php - Item Details, Live Status Management & Claim Submission

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
$pageTitle = $item['title'];

// Authorization check: Is the current user an admin OR the original reporter of this item?
$isReporter = $currentUser && (
    (!empty($item['user_id']) && (int)$item['user_id'] === (int)$currentUser['id']) ||
    (!empty($item['contact_email']) && strtolower(trim($item['contact_email'])) === strtolower(trim($currentUser['email'])))
);
$canManage = isAdmin() || $isReporter;

$claimRepo = new ClaimRepository();
$claims = $claimRepo->getByItemId($itemId);

// Get item activity logs
$db = Database::getConnection();
$logStmt = $db->prepare("SELECT * FROM activity_logs WHERE item_id = :id ORDER BY created_at DESC LIMIT 10");
$logStmt->execute(['id' => $itemId]);
$activityLogs = $logStmt->fetchAll();

require_once __DIR__ . '/layouts/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-decoration-none">Browse Listings</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= sanitize($item['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Left Column: Item Image & Key Badges -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="position-relative bg-light text-center p-3">
                    <?php 
                    $imageSrc = !empty($item['image_url']) ? url($item['image_url']) : url('public/assets/img/placeholder.svg');
                    $typeBadgeClass = ($item['item_type'] === 'lost') ? 'bg-danger' : 'bg-success';
                    $statusBadgeClass = 'badge-status-open';
                    if ($item['status'] === 'Claim Requested') $statusBadgeClass = 'badge-status-claim-requested';
                    elseif ($item['status'] === 'Resolved') $statusBadgeClass = 'badge-status-resolved';
                    ?>
                    <img src="<?= $imageSrc ?>" alt="<?= sanitize($item['title']) ?>" class="img-fluid rounded-3 shadow-sm" style="max-height: 380px; object-fit: contain;">
                    
                    <span class="badge position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill <?= $typeBadgeClass ?> fs-6 shadow-sm">
                        <?= strtoupper($item['item_type']) ?> ITEM
                    </span>
                    
                    <span class="badge position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill fs-6 shadow-sm <?= $statusBadgeClass ?>" id="detail-status-badge">
                        <?= sanitize($item['status']) ?>
                    </span>
                </div>

                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                            <i class="fa-solid <?= sanitize($item['category_icon'] ?? 'fa-box') ?> me-1"></i>
                            <?= sanitize($item['category_name']) ?>
                        </span>
                        <span class="text-muted small">Ref #<?= $item['id'] ?></span>
                    </div>

                    <!-- Status Control Widget (Requirement 1: ONLY Reporter and Admin can update) -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold small text-secondary text-uppercase">
                                <i class="fa-solid fa-sliders me-1 text-primary"></i> Status Control
                            </span>
                            <small class="text-muted" id="status-update-spinner" style="display: none;">
                                <i class="fa-solid fa-spinner fa-spin text-primary"></i> Updating...
                            </small>
                        </div>

                        <?php if ($canManage): ?>
                            <!-- Authorized Reporter / Admin Control -->
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success flex-fill status-toggle-btn <?= ($item['status'] === 'Open') ? 'active' : '' ?>" data-status="Open" data-item-id="<?= $item['id'] ?>">
                                    <i class="fa-solid fa-circle-play me-1"></i> Open
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning flex-fill status-toggle-btn <?= ($item['status'] === 'Claim Requested') ? 'active' : '' ?>" data-status="Claim Requested" data-item-id="<?= $item['id'] ?>">
                                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Claim Requested
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info flex-fill status-toggle-btn <?= ($item['status'] === 'Resolved') ? 'active' : '' ?>" data-status="Resolved" data-item-id="<?= $item['id'] ?>">
                                    <i class="fa-solid fa-circle-check me-1"></i> Resolved
                                </button>
                            </div>
                            <div class="form-text small mt-2 text-muted">
                                <i class="fa-solid fa-shield-halved text-success me-1"></i> Authorized as <?= $isReporter ? 'Item Reporter' : 'Campus Admin' ?> (Single-click AJAX update).
                            </div>
                        <?php else: ?>
                            <!-- Non-owners / Random visitors -->
                            <div class="small text-muted">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span>Current Status:</span>
                                    <strong class="text-dark"><?= sanitize($item['status']) ?></strong>
                                </div>
                                <div class="fst-italic">
                                    <i class="fa-solid fa-lock text-secondary me-1"></i> Status can only be modified by the student who reported this item or a campus administrator.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action buttons -->
                    <div class="d-grid gap-2">
                        <?php if ($item['status'] !== 'Resolved'): ?>
                            <button type="button" class="btn btn-warning btn-lg rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#claimModal">
                                <i class="fa-solid fa-hand-holding-hand me-2"></i> Submit Ownership Claim
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-lg rounded-pill fw-bold" disabled>
                                <i class="fa-solid fa-check-double me-2"></i> Item Resolved &amp; Reunited
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Item Specifications, Description & Reporter Details -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
                <h3 class="fw-bold text-dark mb-3"><?= sanitize($item['title']) ?></h3>

                <!-- Meta Details Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="text-secondary small fw-semibold"><i class="fa-solid fa-location-dot text-danger me-1"></i> Campus Location</div>
                            <div class="fw-bold text-dark"><?= sanitize($item['location']) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="text-secondary small fw-semibold"><i class="fa-regular fa-calendar text-primary me-1"></i> Date of Occurrence</div>
                            <div class="fw-bold text-dark"><?= date('F d, Y', strtotime($item['event_date'])) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <h5 class="fw-bold mb-2">Description &amp; Identifying Details</h5>
                <p class="text-secondary mb-4 leading-relaxed" style="white-space: pre-line;">
                    <?= sanitize($item['description']) ?>
                </p>

                <hr class="text-muted my-3">

                <!-- Reporter Contact Info -->
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-address-card me-2 text-primary"></i>Reporter Contact Information</h6>
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Reported By:</small>
                            <span class="fw-bold"><?= sanitize($item['contact_name']) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Email Address:</small>
                            <a href="mailto:<?= sanitize($item['contact_email']) ?>" class="text-primary text-decoration-none fw-semibold">
                                <?= sanitize($item['contact_email']) ?>
                            </a>
                        </div>
                        <?php if (!empty($item['contact_phone'])): ?>
                            <div class="col-sm-6 mt-2">
                                <small class="text-muted d-block">Phone:</small>
                                <a href="tel:<?= sanitize($item['contact_phone']) ?>" class="text-dark text-decoration-none fw-semibold">
                                    <?= sanitize($item['contact_phone']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Requirement 2: CLAIMS RECEIVED SECTION (Visible to Reporter of item and Admin) -->
                <?php if ($canManage): ?>
                    <div class="card border border-warning-subtle rounded-4 p-4 bg-light mb-4" id="claims-received-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-clipboard-check text-warning me-2"></i>Claims Received on This Item (<?= count($claims) ?>)
                            </h5>
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1">
                                <?= $isReporter ? 'Owner View' : 'Admin View' ?>
                            </span>
                        </div>

                        <?php if (empty($claims)): ?>
                            <div class="text-center py-4 bg-white rounded-3 border text-muted small">
                                <i class="fa-regular fa-folder-open fa-2x mb-2 d-block text-secondary opacity-50"></i>
                                No ownership claims have been submitted on this item yet.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($claims as $c): ?>
                                    <?php
                                    $claimBadgeClass = 'bg-warning text-dark';
                                    if ($c['status'] === 'Approved') $claimBadgeClass = 'bg-success text-white';
                                    elseif ($c['status'] === 'Rejected') $claimBadgeClass = 'bg-danger text-white';
                                    ?>
                                    <div class="p-3 bg-white rounded-3 border shadow-sm" id="claim-card-<?= $c['id'] ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                            <div>
                                                <strong class="text-dark fs-6"><?= sanitize($c['claimant_name']) ?></strong>
                                                <span class="text-muted small ms-1">(<?= sanitize($c['claimant_email']) ?> <?= !empty($c['claimant_phone']) ? '&bull; ' . sanitize($c['claimant_phone']) : '' ?>)</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge rounded-pill px-3 py-1 <?= $claimBadgeClass ?> claim-card-status-badge">
                                                    <?= sanitize($c['status']) ?>
                                                </span>
                                                <small class="text-muted"><?= date('M d, Y', strtotime($c['created_at'])) ?></small>
                                            </div>
                                        </div>

                                        <!-- Proof of Ownership Description -->
                                        <div class="p-3 bg-light rounded-2 small text-secondary mb-2 border">
                                            <strong class="d-block text-dark mb-1"><i class="fa-solid fa-fingerprint text-primary me-1"></i> Claimant's Proof of Ownership:</strong>
                                            <?= nl2br(sanitize($c['proof_description'])) ?>
                                        </div>

                                        <!-- Proof Attachment if any -->
                                        <?php if (!empty($c['proof_image_url'])): ?>
                                            <div class="mb-3">
                                                <a href="<?= url($c['proof_image_url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill">
                                                    <i class="fa-solid fa-paperclip me-1"></i> View Supporting Document / Photo
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Review Notes if decided -->
                                        <?php if (!empty($c['admin_notes'])): ?>
                                            <div class="small text-muted mb-2 fst-italic">
                                                <i class="fa-solid fa-check-double text-success me-1"></i> Resolution Note: <?= sanitize($c['admin_notes']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Reporter / Admin Actions on this claim -->
                                        <?php if ($c['status'] === 'Pending'): ?>
                                            <div class="d-flex gap-2 justify-content-end pt-2 border-top">
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 user-claim-decision-btn" data-claim-id="<?= $c['id'] ?>" data-decision="Rejected">
                                                    <i class="fa-solid fa-xmark me-1"></i> Reject Claim
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold user-claim-decision-btn" data-claim-id="<?= $c['id'] ?>" data-decision="Approved">
                                                    <i class="fa-solid fa-check me-1"></i> Accept &amp; Confirm Owner
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Activity Timeline / Audit Trail -->
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-timeline me-2 text-primary"></i>Status Activity Trail</h6>
                <div class="list-group list-group-flush border rounded-3 overflow-hidden" id="activity-log-list">
                    <?php if (empty($activityLogs)): ?>
                        <div class="list-group-item text-muted small py-3">No activity recorded yet.</div>
                    <?php else: ?>
                        <?php foreach ($activityLogs as $log): ?>
                            <div class="list-group-item py-2 small">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-dark"><?= sanitize($log['action']) ?></strong>
                                    <span class="text-muted"><?= date('M d, H:i', strtotime($log['created_at'])) ?></span>
                                </div>
                                <div class="text-secondary"><?= sanitize($log['details']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Claim Submission Modal -->
<div class="modal fade" id="claimModal" tabindex="-1" aria-labelledby="claimModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-warning-subtle text-warning-emphasis border-0 py-3">
                <h5 class="modal-title fw-bold" id="claimModalLabel">
                    <i class="fa-solid fa-hand-holding-hand me-2"></i> Submit Ownership Claim for Item #<?= $item['id'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="claimSubmitForm" enctype="multipart/form-data">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">

                <div class="modal-body p-4">
                    <div id="claim-feedback" class="alert d-none py-2 small mb-3"></div>

                    <p class="text-muted small mb-3">
                        To protect belongings against fraudulent claims, please provide specific verifying details (e.g., contents inside the bag/wallet, wallpaper on screen, serial numbers, password patterns, or purchase receipt).
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="claimant_name" class="form-label small fw-semibold text-secondary">Your Full Name *</label>
                            <input type="text" class="form-control rounded-3" id="claimant_name" name="claimant_name" 
                                   value="<?= $currentUser ? sanitize($currentUser['name']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="claimant_email" class="form-label small fw-semibold text-secondary">Campus Email *</label>
                            <input type="email" class="form-control rounded-3" id="claimant_email" name="claimant_email" 
                                   value="<?= $currentUser ? sanitize($currentUser['email']) : '' ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="claimant_phone" class="form-label small fw-semibold text-secondary">Contact Phone</label>
                        <input type="tel" class="form-control rounded-3" id="claimant_phone" name="claimant_phone" placeholder="+1 (555) 012-3456">
                    </div>

                    <div class="mb-3">
                        <label for="proof_description" class="form-label small fw-semibold text-secondary">Proof of Ownership / Distinct Identifiers *</label>
                        <textarea class="form-control rounded-3" id="proof_description" name="proof_description" rows="4" 
                                  placeholder="Describe hidden scratches, exact stickers, specific items inside, screen lock wallpaper, etc." required minlength="10"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="proof_image" class="form-label small fw-semibold text-secondary">Supporting Document / Receipt / Photo (Optional)</label>
                        <input type="file" class="form-control rounded-3" id="proof_image" name="proof_image" accept="image/png, image/jpeg, image/webp">
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm" id="submit-claim-btn">
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extraScripts = '<script src="' . url('public/assets/js/items.js') . '"></script>';
require_once __DIR__ . '/layouts/footer.php'; 
?>
