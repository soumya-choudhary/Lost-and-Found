<?php
// api/admin-item-delete.php - Admin Item Deletion Endpoint
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    exit;
}

$ok = (new ItemRepository())->delete($itemId);
echo json_encode([
    'success' => $ok,
    'message' => $ok ? "Item #{$itemId} permanently deleted." : "Failed to delete item."
]);
