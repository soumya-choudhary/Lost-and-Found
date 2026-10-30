<?php
// api/admin-user-role.php - Toggle User Role (Student / Admin) via AJAX
//
// WHAT THIS ENDPOINT DOES:
// 1. Receives POST parameters: user_id and role ('user' or 'admin').
// 2. Checks authorization: Caller must be an active administrator.
// 3. Safety Guard: Prevents an administrator from demoting their own current session account.
// 4. Calls UserRepository::updateRole() to update the users table in PostgreSQL.
// 5. Returns JSON: ['success' => bool, 'user_id' => int, 'new_role' => string, 'message' => string].
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO ADD A NEW ROLE (e.g. 'staff'):
//   1. In the validation array below, add 'staff': `in_array($newRole, ['user', 'admin', 'staff'])`.
//   2. Update the role CHECK constraint on the `users` table in PostgreSQL.

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure POST request and administrator privileges
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Administrator privileges required.']);
    exit;
}

$userId  = (int)($_POST['user_id'] ?? 0);
$newRole = trim($_POST['role'] ?? '');

// Validate input parameters
if ($userId <= 0 || !in_array($newRole, ['user', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID or unrecognized role.']);
    exit;
}

// Security Guard: Prevent administrator from accidentally demoting their own active account
if (currentUser()['id'] == $userId && $newRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Security restriction: You cannot demote your own administrator account.']);
    exit;
}

// Update role in database
$ok = (new UserRepository())->updateRole($userId, $newRole);

echo json_encode([
    'success'  => $ok, 
    'user_id'  => $userId, 
    'new_role' => $newRole, 
    'message'  => $ok ? "User #{$userId} role updated to {$newRole}." : "Failed to update user role in database."
]);
