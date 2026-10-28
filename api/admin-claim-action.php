<?php
// api/admin-claim-action.php - Admin Claim Decision Endpoint (AJAX)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin privileges required.']);
    exit;
}

$claimId = (int)($_POST['claim_id'] ?? 0);
$decision = trim($_POST['decision'] ?? '');
$adminNotes = trim($_POST['admin_notes'] ?? '');

if ($claimId <= 0 || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid claim ID or decision.']);
    exit;
}

$claimRepo = new ClaimRepository();
$success = $claimRepo->updateClaimStatus($claimId, $decision, $adminNotes);

if ($success) {
    echo json_encode([
        'success'   => true,
        'claim_id'  => $claimId,
        'decision'  => $decision,
        'message'   => "Claim #{$claimId} marked as {$decision}."
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error updating claim decision.'
    ]);
}

