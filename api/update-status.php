<?php
// api/update-status.php - AJAX Item Status Update (Without Page Reload)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$remarks = trim($_POST['remarks'] ?? 'Updated via portal interface');

if ($itemId <= 0 || !in_array($newStatus, ['Open', 'Claim Requested', 'Resolved'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID or status value.']);
    exit;
}

$repo = new ItemRepository();
$item = $repo->findById($itemId);

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found.']);
    exit;
}

// 1. Must be logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please sign in to update item status.']);
    exit;
}

$user = currentUser();

// 2. Authorization check: Only Admin OR the reporter of the item
$isReporter = ($item['user_id'] && (int)$item['user_id'] === (int)$user['id']) || 
              (!empty($item['contact_email']) && strtolower(trim($item['contact_email'])) === strtolower(trim($user['email'])));

if (!isAdmin() && !$isReporter) {
    echo json_encode([
        'success' => false, 
        'message' => 'Permission denied. Only the reporter of this item or a campus administrator can change its status.'
    ]);
    exit;
}

$userNote = "Changed by " . $user['name'] . " (" . (isAdmin() ? 'Admin' : 'Reporter') . "). " . $remarks;

$success = $repo->updateStatus($itemId, $newStatus, $userNote);

if ($success) {
    echo json_encode([
        'success'    => true,
        'item_id'    => $itemId,
        'new_status' => $newStatus,
        'message'    => "Item status successfully changed to {$newStatus}!"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update item status in database.'
    ]);
}
