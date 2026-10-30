<?php
// api/admin-item-delete.php - Admin Delete Item (AJAX)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Admin privileges required.']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$ok = (new ItemRepository())->delete($itemId);

echo json_encode(['success' => $ok, 'item_id' => $itemId, 'message' => $ok ? "Item #{$itemId} deleted." : "Failed to delete item."]);
