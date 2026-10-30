<?php
// ====================================================================================
// student/my-claims.php - Ownership Claims Submitted by the Current Student
// ====================================================================================
// Displays all ownership claims filed by the logged-in student across the portal.
// Features:
// - Shows claimed item title and links to its detail view.
// - Status badge (Pending in yellow, Approved in green, Rejected in red).
// - Submitter's proof explanation with CSS text-truncation for clean table presentation.
// - Resolution notes provided by the administrator or the item finder/owner upon review.
//
// ------------------------------------------------------------------------------------
// HOW TO MODIFY / PROFESSOR TASKS:
// - TO DISPLAY PROOF PHOTOS IN TABLE: Check if $c['proof_image_path'] is set and output an <img> thumbnail.
// - TO FILTER ONLY PENDING CLAIMS: Add $claims = array_filter($claims, fn($c) => $c['status'] === 'Pending');
// - TO ALLOW CANCELLING PENDING CLAIMS: Add a "Cancel Claim" button calling an API endpoint when status is 'Pending'.
// ====================================================================================

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

// Route Guard: Student must be signed in
AuthMiddleware::check();

$currentUser = currentUser();
// Fetch claims associated with the student's email address
$claims = (new ClaimRepository())->getByUserEmail($currentUser['email']);

$pageTitle = 'My Claims';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <!-- Header with Quick Action to Catalog -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">My Submitted Claims</h4>
        <a href="<?= url('index.php') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">Browse Items</a>
    </div>

    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <?php if (empty($claims)): ?>
            <div class="p-4 text-center text-muted">You have not submitted any ownership claims yet.</div>
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
                                <!-- Claimed Item Title -->
                                <td class="ps-3 fw-bold">
                                    <a href="<?= url('item-details.php?id=' . $c['item_id']) ?>" class="text-dark text-decoration-none">
                                        <?= sanitize($c['item_title']) ?>
                                    </a>
                                </td>

                                <!-- Claim Status Badge (Pending / Approved / Rejected) -->
                                <td>
                                    <span class="badge <?= claimBadgeClass($c['status']) ?>">
                                        <?= sanitize($c['status']) ?>
                                    </span>
                                </td>

                                <!-- Proof Description (Truncated for clean display) -->
                                <td class="text-truncate" style="max-width: 220px;" title="<?= sanitize($c['proof_description']) ?>">
                                    <?= sanitize($c['proof_description']) ?>
                                </td>

                                <!-- Review / Resolution Notes from Admin or Item Owner -->
                                <td>
                                    <?php if (!empty($c['admin_notes'])): ?>
                                        <span class="text-secondary"><?= sanitize($c['admin_notes']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Pending review</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Submission Timestamp -->
                                <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>

                                <!-- Action Link to Item Details -->
                                <td class="text-end pe-3">
                                    <a href="<?= url('item-details.php?id=' . $c['item_id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">
                                        View
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
