<?php
// admin/users.php - User Account Management

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

AdminMiddleware::check();

$userRepo = new UserRepository();
$users = $userRepo->getAll(100);

$currentUser = currentUser();
$pageTitle = 'Manage Users - Admin';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= url('admin/dashboard.php') ?>" class="text-decoration-none">Admin Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Manage Users</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-users text-success me-2"></i>Campus Portal Users</h3>
            <p class="text-muted small mb-0">Overview of registered students, faculty, and administrative staff accounts.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-sliders me-1"></i> Admin Dashboard
            </a>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="badge bg-secondary rounded-pill"><?= count($users) ?> registered users</span>
            <div id="admin-user-alert" class="d-none small py-1 px-3 rounded-pill"></div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Campus Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined Date</th>
                        <th class="text-end pe-4">Role Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr id="user-row-<?= $u['id'] ?>">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle bg-primary text-white" style="width: 36px; height: 36px;">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= sanitize($u['name']) ?></div>
                                        <?php if ($u['id'] == $currentUser['id']): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small">You</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><span class="small text-secondary"><?= sanitize($u['email']) ?></span></td>
                            <td><span class="small text-muted"><?= sanitize($u['phone'] ?: '—') ?></span></td>
                            <td>
                                <span class="badge rounded-pill px-3 py-1 text-uppercase <?= ($u['role'] === 'admin') ? 'bg-primary text-white' : 'bg-secondary text-white' ?>" id="user-badge-<?= $u['id'] ?>">
                                    <?= sanitize($u['role']) ?>
                                </span>
                            </td>
                            <td><span class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></span></td>
                            <td class="text-end pe-4">
                                <?php if ($u['id'] == $currentUser['id']): ?>
                                    <span class="small text-muted fst-italic">Current Session</span>
                                <?php else: ?>
                                    <select class="form-select form-select-sm d-inline-block rounded-pill admin-role-select" 
                                            data-user-id="<?= $u['id'] ?>" style="width: auto;">
                                        <option value="user" <?= ($u['role'] === 'user') ? 'selected' : '' ?>>Student / User</option>
                                        <option value="admin" <?= ($u['role'] === 'admin') ? 'selected' : '' ?>>Campus Admin</option>
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

