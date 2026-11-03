<?php
// api/admin-item-delete.php - Admin Item Deletion Endpoint
//
// WHAT THIS FILE DOES:
// Receives an AJAX POST request from admin/items.php to permanently delete an item.
// Enforces isAdmin() authorization. Foreign keys in schema.sql with ON DELETE CASCADE
// ensure that all related claims and activity logs are automatically purged by PostgreSQL.
//
// ACCESS: Administrator only
// METHOD: POST
// PARAMETERS: item_id (int)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Guard: Method must be POST and user must have admin role
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Administrator access required.']);
    exit;
}

// 2. Validate item ID
$itemId = (int)($_POST['item_id'] ?? 0);
if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    exit;
}

// 3. Execute delete
$ok = (new ItemRepository())->delete($itemId);

// 4. Return JSON response
echo json_encode([
    'success' => $ok,
    'message' => $ok ? "Item #{$itemId} permanently deleted." : "Failed to delete item."
]);
