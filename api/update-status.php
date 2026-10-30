<?php
// api/update-status.php - AJAX Item Status Update (Without Full Page Reload)
//
// WHAT THIS ENDPOINT DOES:
// 1. Receives POST parameters: item_id, status ('Open', 'Claim Requested', 'Resolved'), and optional remarks.
// 2. Checks authentication: User must be signed in.
// 3. Verifies authorization: Only the original Item Reporter OR a Campus Admin can change item status.
// 4. Calls ItemRepository::updateStatus() to persist status change and log an activity trail entry.
// 5. Returns JSON response: ['success' => bool, 'item_id' => int, 'new_status' => string, 'message' => string].
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO ADD A NEW STATUS (e.g. 'Archived'):
//   1. Add 'Archived' to the allowed array: `in_array($newStatus, ['Open', 'Claim Requested', 'Resolved', 'Archived'])`.
//   2. Update ItemRepository::updateStatus() allowed list and PostgreSQL items_status_check constraint.
// - TO ALLOW ANY LOGGED-IN USER TO UPDATE STATUS (relax permissions):
//   Remove or comment out `if (!canManageItem($item))`.
// - TO ALLOW ONLY ADMINS:
//   Replace `if (!canManageItem($item))` with `if (!isAdmin())`.

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure POST request and authenticated session
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request method.']);
    exit;
}

$itemId    = (int)($_POST['item_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$remarks   = trim($_POST['remarks'] ?? 'Updated via portal');

// Validate item ID and permitted status transitions
// TO ADD NEW STATUS (e.g. 'Archived'): Add 'Archived' to the array below
if ($itemId <= 0 || !in_array($newStatus, ['Open', 'Claim Requested', 'Resolved'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value or item reference.']);
    exit;
}

$repo = new ItemRepository();
$item = $repo->findById($itemId);

// Authorization check: Only Admin OR the reporter who created the listing
// TO CHANGE AUTHORIZATION: canManageItem() is defined in config/app.php
if (!$item || !canManageItem($item)) {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only the reporter of this item or an administrator can modify its status.']);
    exit;
}

$user = currentUser();
$note = "Changed by {$user['name']} (" . (isAdmin() ? 'Admin' : 'Reporter') . "). {$remarks}";
$ok = $repo->updateStatus($itemId, $newStatus, $note);

echo json_encode([
    'success'    => $ok,
    'item_id'    => $itemId,
    'new_status' => $newStatus,
    'message'    => $ok ? "Status updated to {$newStatus}!" : "Failed to update item status in database."
]);
