<?php
// api/update-status.php - AJAX Item Status Update (Without Page Reload)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request.']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$remarks = trim($_POST['remarks'] ?? 'Updated via portal');

if ($itemId <= 0 || !in_array($newStatus, ['Open', 'Claim Requested', 'Resolved'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status or item ID.']);
    exit;
}

$repo = new ItemRepository();
$item = $repo->findById($itemId);

if (!$item || !canManageItem($item)) {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only item reporter or admin can change status.']);
    exit;
}

$user = currentUser();
$note = "Changed by {$user['name']} (" . (isAdmin() ? 'Admin' : 'Reporter') . "). {$remarks}";
$ok = $repo->updateStatus($itemId, $newStatus, $note);

echo json_encode(['success' => $ok, 'item_id' => $itemId, 'new_status' => $newStatus, 'message' => $ok ? "Status updated to {$newStatus}!" : "Update failed."]);
