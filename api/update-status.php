<?php
// api/update-status.php - AJAX Item Status Update (Owner or Admin Only)
//
// WHAT THIS FILE DOES:
// Receives an asynchronous POST request to update an item's status to 'Open', 'Claim Requested', or 'Resolved'.
// Enforces strict authorization: only an administrator OR the student who originally reported the item
// can modify its status. Rejects unauthorized attempts with HTTP JSON error response.
//
// ACCESS: Signed in (Reporter OR Admin)
// METHOD: POST
// PARAMETERS: item_id (int), status (string), remarks (optional string)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Guard: Ensure request is POST and user is authenticated
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please sign in.']);
    exit;
}

// 2. Read and sanitize input parameters
$itemId    = (int)($_POST['item_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$remarks   = trim($_POST['remarks'] ?? 'Updated via portal');

// 3. Validate status input against allowed whitelist
if ($itemId <= 0 || !in_array($newStatus, ['Open', 'Claim Requested', 'Resolved'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

$repo = new ItemRepository();
$item = $repo->findById($itemId);

// 4. Authorization Check: Admin OR the student who reported the item
if (!$item || !canManageItem($item)) {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only the reporter or an administrator can change status.']);
    exit;
}

// 5. Execute status update and append to audit log
$user = currentUser();
$note = "Changed by {$user['name']} (" . (isAdmin() ? 'Admin' : 'Reporter') . "). {$remarks}";
$ok   = $repo->updateStatus($itemId, $newStatus, $note);

// 6. Return JSON response
echo json_encode([
    'success'    => $ok,
    'item_id'    => $itemId,
    'new_status' => $newStatus,
    'message'    => $ok ? "Status updated to {$newStatus}!" : "Failed to update status."
]);
