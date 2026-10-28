<?php
// api/admin-item-delete.php - Admin Delete Item Report (AJAX)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin privileges required.']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    exit;
}

$itemRepo = new ItemRepository();
$success = $itemRepo->delete($itemId);

if ($success) {
    echo json_encode([
        'success' => true,
        'item_id' => $itemId,
        'message' => "Item #{$itemId} has been removed from campus directory."
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete item from database.']);
}

