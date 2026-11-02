<?php
// api/admin-claim-action.php - Ownership Claim Decision Endpoint (Admin or Item Reporter)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$currentUser = currentUser();
$claimId  = (int)($_POST['claim_id'] ?? 0);
$decision = trim($_POST['decision'] ?? '');
$notes    = trim($_POST['admin_notes'] ?? '');

if ($claimId <= 0 || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid claim reference or decision value.']);
    exit;
}

$claimRepo = new ClaimRepository();
$claim = $claimRepo->findById($claimId);

if (!$claim) {
    echo json_encode(['success' => false, 'message' => 'Claim not found.']);
    exit;
}

// User must be either an Admin OR the reporter of the claimed item
$isReporter = ($claim['item_user_id'] && (int)$claim['item_user_id'] === (int)$currentUser['id']) ||
              (!empty($claim['item_contact_email']) && strtolower($claim['item_contact_email']) === strtolower($currentUser['email']));

if (!isAdmin() && !$isReporter) {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only an admin or the item reporter can decide this claim.']);
    exit;
}

$note = "Decided by {$currentUser['name']} (" . (isAdmin() ? 'Admin' : 'Reporter') . "): {$notes}";
$ok   = $claimRepo->updateClaimStatus($claimId, $decision, $note);

// Retrieve updated item status
$itemStatusStmt = Database::getConnection()->prepare("SELECT status FROM items WHERE id = :iid");
$itemStatusStmt->execute(['iid' => (int)$claim['item_id']]);
$newItemStatus  = $itemStatusStmt->fetchColumn() ?: 'Open';

echo json_encode([
    'success'     => $ok,
    'claim_id'    => $claimId,
    'item_id'     => (int)$claim['item_id'],
    'item_status' => $newItemStatus,
    'decision'    => $decision,
    'message'     => $ok ? "Claim {$decision} successfully!" : "Failed to update claim."
]);
