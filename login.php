<?php
// login.php - Campus Sign-In Page

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

AuthMiddleware::guestOnly();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = (new AuthController())->handleLogin();
    $errors = $res['errors'];
    $email = $res['email'];
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container my-5" style="max-width: 400px;">
    <div class="card border shadow-sm rounded-3 bg-white p-4">
        <h4 class="fw-bold mb-3 text-center"><i class="fa-solid fa-lock text-primary me-2"></i>Sign In</h4>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= url('login.php') ?>" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label small">Campus Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= sanitize($email) ?>" placeholder="user@campus.edu" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label small">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mb-3">Sign In</button>
        </form>

        <!-- Quick Demo Quickfill -->
        <div class="border-top pt-3 text-center small text-muted">
            <div class="mb-2">Quick Demo:</div>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary fill-demo-btn" data-role="student">Student</button>
                <button type="button" class="btn btn-sm btn-outline-primary fill-demo-btn" data-role="admin">Admin</button>
            </div>
        </div>

        <div class="text-center mt-3 pt-2 border-top small">
            Need an account? <a href="<?= url('register.php') ?>" class="text-primary text-decoration-none fw-bold">Register</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
