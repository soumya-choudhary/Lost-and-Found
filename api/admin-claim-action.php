<?php
// api/admin-claim-action.php - Claim Decision (Admin or Reporter)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$currentUser = currentUser();
$claimId = (int)($_POST['claim_id'] ?? 0);
$decision = trim($_POST['decision'] ?? '');
$notes = trim($_POST['admin_notes'] ?? '');

if ($claimId <= 0 || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid claim or decision.']);
    exit;
}

$claimRepo = new ClaimRepository();
$claim = $claimRepo->findById($claimId);

if (!$claim) {
    echo json_encode(['success' => false, 'message' => 'Claim not found.']);
    exit;
}

$isReporter = ($claim['item_user_id'] && (int)$claim['item_user_id'] === (int)$currentUser['id']) ||
              (!empty($claim['item_contact_email']) && strtolower($claim['item_contact_email']) === strtolower($currentUser['email']));

if (!isAdmin() && !$isReporter) {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only admin or reporter can decide this claim.']);
    exit;
}

$note = "Decided by {$currentUser['name']} (" . (isAdmin() ? 'Admin' : 'Reporter') . "): {$notes}";
$ok = $claimRepo->updateClaimStatus($claimId, $decision, $note);

echo json_encode(['success' => $ok, 'claim_id' => $claimId, 'decision' => $decision, 'message' => "Claim marked as {$decision}!"]);
