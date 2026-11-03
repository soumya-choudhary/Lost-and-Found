<?php
// api/admin-claim-action.php - Ownership Claim Decision Endpoint (Admin or Item Reporter)
//
// WHAT THIS FILE DOES:
// Receives the decision ('Approved' or 'Rejected') for an ownership claim.
// Authorization: Either a Campus Administrator OR the original reporter of the item can decide the claim.
// If Approved: marks the item as 'Resolved'.
// If Rejected: if no other pending claims exist, reverts the item to 'Open'.
//
// ACCESS: Signed in (Admin OR Item Reporter)
// METHOD: POST
// PARAMETERS: claim_id (int), decision ('Approved' / 'Rejected'), admin_notes (optional string)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Guard: Check request method and authentication
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please sign in.']);
    exit;
}

$currentUser = currentUser();
$claimId  = (int)($_POST['claim_id'] ?? 0);
$decision = trim($_POST['decision'] ?? '');
$notes    = trim($_POST['admin_notes'] ?? '');

// 2. Validate decision value
if ($claimId <= 0 || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid claim reference or decision value.']);
    exit;
}

$claimRepo = new ClaimRepository();
$claim = $claimRepo->findById($claimId);

if (!$claim) {
    echo json_encode(['success' => false, 'message' => 'Claim record not found.']);
    exit;
}

// 3. Authorization Check: User must be an Admin OR the original reporter of the item
$isReporter = ($claim['item_user_id'] && (int)$claim['item_user_id'] === (int)$currentUser['id']) ||
              (!empty($claim['item_contact_email']) && strtolower($claim['item_contact_email']) === strtolower($currentUser['email']));

if (!isAdmin() && !$isReporter) {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only an administrator or the item reporter can decide this claim.']);
    exit;
}

// 4. Update claim status in database transaction
$note = "Decided by {$currentUser['name']} (" . (isAdmin() ? 'Admin' : 'Reporter') . "): {$notes}";
$ok   = $claimRepo->updateClaimStatus($claimId, $decision, $note);

// 5. Fetch updated item status to return in response
$itemStatusStmt = Database::getConnection()->prepare("SELECT status FROM items WHERE id = :iid");
$itemStatusStmt->execute(['iid' => (int)$claim['item_id']]);
$newItemStatus  = $itemStatusStmt->fetchColumn() ?: 'Open';

// 6. Return JSON response
echo json_encode([
    'success'     => $ok,
    'claim_id'    => $claimId,
    'item_id'     => (int)$claim['item_id'],
    'item_status' => $newItemStatus,
    'decision'    => $decision,
    'message'     => $ok ? "Claim {$decision} successfully!" : "Failed to update claim."
]);
