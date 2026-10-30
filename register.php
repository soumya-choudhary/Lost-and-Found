<?php
// register.php - Campus User Registration

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

AuthMiddleware::guestOnly();

$errors = [];
$data = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = (new AuthController())->handleRegister();
    $errors = $res['errors'];
    $data = $res['data'];
}

$pageTitle = 'Register';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container my-5" style="max-width: 440px;">
    <div class="card border shadow-sm rounded-3 bg-white p-4">
        <h4 class="fw-bold mb-3 text-center"><i class="fa-solid fa-user-plus text-success me-2"></i>Create Account</h4>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= url('register.php') ?>" method="POST">
            <div class="mb-2">
                <label for="name" class="form-label small">Full Name *</label>
                <input type="text" class="form-control form-control-sm" id="name" name="name" value="<?= sanitize($data['name']) ?>" required autofocus>
            </div>
            <div class="mb-2">
                <label for="email" class="form-label small">Campus Email *</label>
                <input type="email" class="form-control form-control-sm" id="email" name="email" value="<?= sanitize($data['email']) ?>" required>
            </div>
            <div class="mb-2">
                <label for="phone" class="form-label small">Phone Number</label>
                <input type="tel" class="form-control form-control-sm" id="phone" name="phone" value="<?= sanitize($data['phone']) ?>">
            </div>
            <div class="mb-2">
                <label for="password" class="form-label small">Password (Min 6 chars) *</label>
                <input type="password" class="form-control form-control-sm" id="password" name="password" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label small">Confirm Password *</label>
                <input type="password" class="form-control form-control-sm" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit" class="btn btn-success w-100 fw-bold py-2 mb-3">Create Account</button>
        </form>

        <div class="text-center border-top pt-2 small text-muted">
            Already have an account? <a href="<?= url('login.php') ?>" class="text-primary text-decoration-none fw-bold">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
