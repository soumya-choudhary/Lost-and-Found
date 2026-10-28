<?php
// api/admin-user-role.php - Toggle User Role (AJAX)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin privileges required.']);
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);
$newRole = trim($_POST['role'] ?? '');

if ($userId <= 0 || !in_array($newRole, ['user', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID or role.']);
    exit;
}

$currentUser = currentUser();
if ($currentUser['id'] == $userId && $newRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'You cannot demote your own admin account.']);
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("UPDATE users SET role = :role WHERE id = :id");
$success = $stmt->execute(['role' => $newRole, 'id' => $userId]);

if ($success) {
    echo json_encode([
        'success'  => true,
        'user_id'  => $userId,
        'new_role' => $newRole,
        'message'  => "User #{$userId} role changed to {$newRole}."
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user role.']);
}

