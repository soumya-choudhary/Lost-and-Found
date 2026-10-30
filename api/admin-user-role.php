<?php
// api/admin-user-role.php - Toggle User Role (AJAX)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Admin privileges required.']);
    exit;
}

$userId  = (int)($_POST['user_id'] ?? 0);
$newRole = trim($_POST['role'] ?? '');

if ($userId <= 0 || !in_array($newRole, ['user', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

if (currentUser()['id'] == $userId && $newRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot demote your own account.']);
    exit;
}

$ok = (new UserRepository())->updateRole($userId, $newRole);
echo json_encode(['success' => $ok, 'user_id' => $userId, 'new_role' => $newRole, 'message' => "Role updated to {$newRole}."]);
