<?php
// api/admin-claim-action.php - Ownership Claim Evaluation Endpoint (Admin or Reporter)
//
// WHAT THIS ENDPOINT DOES:
// 1. Receives POST parameters: claim_id, decision ('Approved' or 'Rejected'), and admin_notes.
// 2. Checks authentication: Caller must be signed in.
// 3. Verifies authorization: Must be either a Campus Admin OR the original Item Reporter.
// 4. Calls ClaimRepository::updateClaimStatus():
//    - If 'Approved', automatically marks the claim as Approved AND transitions the item to 'Resolved'.
//    - If 'Rejected', marks the claim as Rejected with the decision note.
// 5. Returns JSON: ['success' => bool, 'claim_id' => int, 'decision' => string, 'message' => string].
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO ALLOW ONLY ADMINS TO APPROVE/REJECT CLAIMS:
//   Replace `if (!isAdmin() && !$isReporter)` with `if (!isAdmin())`.
// - TO ALLOW ONLY REPORTERS:
//   Replace with `if (!$isReporter)`.

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request method.']);
    exit;
}

$currentUser = currentUser();
$claimId  = (int)($_POST['claim_id'] ?? 0);
$decision = trim($_POST['decision'] ?? '');
$notes    = trim($_POST['admin_notes'] ?? '');

// Validate decision value
// Permitted values: 'Approved', 'Rejected'
if ($claimId <= 0 || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid claim reference or decision value.']);
    exit;
}

$claimRepo = new ClaimRepository();
$claim = $claimRepo->findById($claimId);

if (!$claim) {
    echo json_encode(['success' => false, 'message' => 'Claim not found in database.']);
    exit;
}

// Authorization check: User must be either an Admin OR the original reporter of the item
$isReporter = ($claim['item_user_id'] && (int)$claim['item_user_id'] === (int)$currentUser['id']) ||
              (!empty($claim['item_contact_email']) && strtolower($claim['item_contact_email']) === strtolower($currentUser['email']));

if (!isAdmin() && !$isReporter) {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only an administrator or the item reporter can decide this claim.']);
    exit;
}

// Build resolution audit note
$note = "Decided by {$currentUser['name']} (" . (isAdmin() ? 'Admin' : 'Reporter') . "): {$notes}";

// Update claim status (approving automatically marks the parent item as Resolved)
$ok = $claimRepo->updateClaimStatus($claimId, $decision, $note);

echo json_encode([
    'success'  => $ok,
    'claim_id' => $claimId,
    'decision' => $decision,
    'message'  => $ok ? "Claim #{$claimId} marked as {$decision}!" : "Failed to update claim decision in database."
]);
