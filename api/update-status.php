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

// Check permission: user is logged in or admin
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please sign in to update item status.']);
    exit;
}

$user = currentUser();
$userNote = "Changed by " . $user['name'] . " (" . $user['role'] . "). " . $remarks;

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

