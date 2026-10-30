<?php
// admin/users.php - User Directory & Role Management

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

AdminMiddleware::check();
$users = (new UserRepository())->getAll(100);
$currentUser = currentUser();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-users text-success me-2"></i>Campus Users (<?= count($users) ?>)</h4>
        <div id="admin-user-alert" class="d-none small py-1 px-3 rounded"></div>
    </div>

    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light"><tr><th class="ps-3">Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th><th class="text-end pe-3">Role Toggle</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr id="user-row-<?= $u['id'] ?>">
                            <td class="ps-3 fw-bold"><?= sanitize($u['name']) ?> <?= $u['id']==$currentUser['id'] ? '<span class="badge bg-secondary">You</span>' : '' ?></td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td><?= sanitize($u['phone'] ?: '—') ?></td>
                            <td><span class="badge <?= $u['role']==='admin'?'bg-primary':'bg-secondary' ?>" id="user-badge-<?= $u['id'] ?>"><?= strtoupper($u['role']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end pe-3">
                                <?php if ($u['id'] != $currentUser['id']): ?>
                                    <select class="form-select form-select-sm d-inline-block py-0 admin-role-select" data-user-id="<?= $u['id'] ?>" style="width: auto;">
                                        <option value="user" <?= $u['role']==='user'?'selected':'' ?>>Student</option>
                                        <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                                    </select>
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
$extraScripts = '<script src="' . url('public/assets/js/admin.js') . '"></script>';
require_once __DIR__ . '/../layouts/footer.php'; 
?>
