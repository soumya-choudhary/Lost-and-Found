<?php
// api/admin-item-delete.php - Item Deletion Endpoint (AJAX)
//
// WHAT THIS ENDPOINT DOES:
// 1. Receives POST parameter: item_id.
// 2. Checks authentication & authorization: Only campus administrators can delete items.
// 3. Calls ItemRepository::delete() to permanently remove the item from the items table.
//    Related claims and activity_logs cascade automatically via PostgreSQL foreign keys.
// 4. Returns JSON: ['success' => bool, 'item_id' => int, 'message' => string].
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO ALLOW THE ITEM REPORTER TO DELETE THEIR OWN ITEM (VERY COMMON PROFESSOR REQUEST):
//   Replace `if (!isAdmin())` with:
//   ```php
//   $item = (new ItemRepository())->findById($itemId);
//   if (!canManageItem($item)) {
//       echo json_encode(['success' => false, 'message' => 'Permission denied.']);
//       exit;
//   }
//   ```

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure POST request and administrator role
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Administrator privileges required.']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    exit;
}

// Delete item via ItemRepository
$ok = (new ItemRepository())->delete($itemId);

echo json_encode([
    'success' => $ok, 
    'item_id' => $itemId, 
    'message' => $ok ? "Item #{$itemId} deleted successfully." : "Failed to delete item from database."
]);
