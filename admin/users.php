<?php
// admin/users.php - Campus User Directory & Role Management
//
// WHAT THIS PAGE DOES:
// 1. Verifies administrator privileges via AdminMiddleware::check().
// 2. Fetches all registered campus users via UserRepository::getAll().
// 3. Displays user name, email, phone number, current role, and registration date.
// 4. In-Place Role Toggle Dropdown:
//    - For other users, provides a dropdown to switch between 'Student' and 'Admin'.
//    - Sends request to api/admin-user-role.php via AJAX without page reload.
//    - Prevents the currently signed-in admin from demoting themselves.
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO ADD A 3RD ROLE (e.g. 'Staff' / 'Security'):
//   Add `<option value="staff">Campus Staff</option>` inside the role select below.
// - TO DISPLAY EXTRA USER COLUMNS (e.g. Student ID): Add `<th>` and `<td>` columns.

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

// Route Guard: Admin only
AdminMiddleware::check();

$users = (new UserRepository())->getAll(100);
$currentUser = currentUser();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <!-- Header with AJAX Notification Alert -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-users text-success me-2"></i>Campus Users (<?= count($users) ?>)</h4>
        <div id="admin-user-alert" class="d-none small py-1 px-3 rounded"></div>
    </div>

    <!-- Users Directory Table -->
    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th class="text-end pe-3">Role Toggle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr id="user-row-<?= $u['id'] ?>">
                            <td class="ps-3 fw-bold">
                                <?= sanitize($u['name']) ?> 
                                <?= $u['id']==$currentUser['id'] ? '<span class="badge bg-secondary ms-1">You</span>' : '' ?>
                            </td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td><?= sanitize($u['phone'] ?: '—') ?></td>
                            <td>
                                <span class="badge <?= $u['role']==='admin'?'bg-primary':'bg-secondary' ?>" id="user-badge-<?= $u['id'] ?>">
                                    <?= strtoupper($u['role']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end pe-3">
                                <?php if ($u['id'] != $currentUser['id']): ?>
                                    <!-- In-place AJAX role dropdown (hooked in public/assets/js/admin.js) -->
                                    <select class="form-select form-select-sm d-inline-block py-0 admin-role-select" 
                                            data-user-id="<?= $u['id'] ?>" style="width: auto;">
                                        <option value="user" <?= $u['role']==='user'?'selected':'' ?>>Student</option>
                                        <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                                    </select>
                                <?php else: ?>
                                    <small class="text-muted fst-italic">Current Session</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// Include admin.js for AJAX role toggle handler
$extraScripts = '<script src="' . url('public/assets/js/admin.js') . '"></script>';
require_once __DIR__ . '/../layouts/footer.php'; 
?>
