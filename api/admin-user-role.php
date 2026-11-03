<?php
// api/admin-user-role.php - Admin User Role Toggle (Promote/Demote)
//
// WHAT THIS FILE DOES:
// Receives an AJAX POST request from admin/users.php to update a user's role between 'user' and 'admin'.
// Includes a crucial security check: prevents an administrator from accidentally demoting their own current session.
//
// ACCESS: Administrator only
// METHOD: POST
// PARAMETERS: user_id (int), role (string: 'user' or 'admin')

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Guard: Method must be POST and user must have admin role
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Administrator access required.']);
    exit;
}

// 2. Validate input parameters
$userId  = (int)($_POST['user_id'] ?? 0);
$newRole = strtolower(trim($_POST['role'] ?? ''));

if ($userId <= 0 || !in_array($newRole, ['user', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID or role parameter.']);
    exit;
}

// 3. Security Guard: Prevent self-demotion lockout
if ($userId === (int)currentUser()['id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own administrator role while logged in.']);
    exit;
}

// 4. Update role in database
$ok = (new UserRepository())->updateRole($userId, $newRole);

// 5. Return JSON response
echo json_encode([
    'success' => $ok,
    'message' => $ok ? "User role changed to {$newRole}." : "Failed to update role in database."
]);
