<?php
// api/admin-claim-action.php - Claim Decision Endpoint (Admin or Item Reporter)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please sign in to perform this action.']);
    exit;
}

$currentUser = currentUser();
$claimId = (int)($_POST['claim_id'] ?? 0);
$decision = trim($_POST['decision'] ?? '');
$adminNotes = trim($_POST['admin_notes'] ?? '');

if ($claimId <= 0 || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid claim ID or decision.']);
    exit;
}

$claimRepo = new ClaimRepository();
$claim = $claimRepo->findById($claimId);

if (!$claim) {
    echo json_encode(['success' => false, 'message' => 'Claim not found.']);
    exit;
}

// Check authorization: Must be Admin OR the reporter of the item
$isReporter = ($claim['item_user_id'] && (int)$claim['item_user_id'] === (int)$currentUser['id']) ||
              (!empty($claim['item_contact_email']) && strtolower(trim($claim['item_contact_email'])) === strtolower(trim($currentUser['email'])));

if (!isAdmin() && !$isReporter) {
    echo json_encode([
        'success' => false,
        'message' => 'Permission denied. Only a campus administrator or the reporter of this item can decide on this claim.'
    ]);
    exit;
}

$reviewerRole = isAdmin() ? 'Campus Admin' : 'Item Reporter';
$resolutionNote = "Decided by " . $currentUser['name'] . " (" . $reviewerRole . "): " . $adminNotes;

$success = $claimRepo->updateClaimStatus($claimId, $decision, $resolutionNote);

if ($success) {
    echo json_encode([
        'success'   => true,
        'claim_id'  => $claimId,
        'decision'  => $decision,
        'message'   => "Claim #{$claimId} marked as {$decision}!"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error updating claim decision.'
    ]);
}
