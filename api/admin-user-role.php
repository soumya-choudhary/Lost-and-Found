<?php
// api/admin-user-role.php - Admin User Role Toggle
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
    exit;
}

$userId  = (int)($_POST['user_id'] ?? 0);
$newRole = strtolower(trim($_POST['role'] ?? ''));

if ($userId <= 0 || !in_array($newRole, ['user', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID or role.']);
    exit;
}

// Prevent self-demotion
if ($userId === (int)currentUser()['id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own admin role.']);
    exit;
}

$ok = (new UserRepository())->updateRole($userId, $newRole);
echo json_encode([
    'success' => $ok,
    'message' => $ok ? "User role changed to {$newRole}." : "Failed to update role."
]);
