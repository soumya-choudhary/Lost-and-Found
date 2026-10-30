<?php
// register.php - Campus User Registration Interface
//
// WHAT THIS PAGE DOES:
// 1. Blocks authenticated users from re-registering (AuthMiddleware::guestOnly()).
// 2. Receives POST form data (name, email, phone, password, confirm_password).
// 3. Calls AuthController::handleRegister() to validate input, hash password, and insert into users table.
// 4. Automatically signs in the new user and redirects them to student/dashboard.php.
// 5. Connects with public/assets/js/auth.js for real-time password matching feedback.
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO CHANGE PASSWORD LENGTH REQUIREMENT:
//   1. Edit `minlength="6"` on the input tags below.
//   2. In app/controllers/AuthController.php, update `strlen($password) < 6`.
//   3. In public/assets/js/auth.js, update client-side validation check.

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

// Route Guard: Guests only
AuthMiddleware::guestOnly();

$errors = [];
$data = ['name' => '', 'email' => '', 'phone' => ''];

// Process POST submission
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

        <!-- Error List -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Registration Form (id="registerForm" hooked in public/assets/js/auth.js) -->
        <form action="<?= url('register.php') ?>" method="POST" id="registerForm">
            <!-- Full Name -->
            <div class="mb-2">
                <label for="reg_name" class="form-label small">Full Name *</label>
                <input type="text" class="form-control form-control-sm" id="reg_name" name="name" 
                       value="<?= sanitize($data['name']) ?>" placeholder="e.g. Alex Johnson" required autofocus>
            </div>

            <!-- Campus Email -->
            <div class="mb-2">
                <label for="reg_email" class="form-label small">Campus Email *</label>
                <input type="email" class="form-control form-control-sm" id="reg_email" name="email" 
                       value="<?= sanitize($data['email']) ?>" placeholder="name@campus.edu" required>
            </div>

            <!-- Phone Number -->
            <div class="mb-2">
                <label for="reg_phone" class="form-label small">Phone Number</label>
                <input type="tel" class="form-control form-control-sm" id="reg_phone" name="phone" 
                       value="<?= sanitize($data['phone']) ?>" placeholder="+1 (555) 012-3456">
            </div>

            <!-- Password -->
            <!-- TO CHANGE MIN LENGTH: Edit minlength="6" below -->
            <div class="mb-2">
                <label for="reg_password" class="form-label small">Password (Min 6 chars) *</label>
                <input type="password" class="form-control form-control-sm" id="reg_password" name="password" 
                       placeholder="Create password" required minlength="6">
            </div>

            <!-- Confirm Password (Real-time feedback via public/assets/js/auth.js) -->
            <div class="mb-3">
                <label for="reg_confirm" class="form-label small">Confirm Password *</label>
                <input type="password" class="form-control form-control-sm" id="reg_confirm" name="confirm_password" 
                       placeholder="Re-enter password" required minlength="6">
                <div id="password-match-feedback" class="form-text small mt-1"></div>
            </div>

            <button type="submit" class="btn btn-success w-100 fw-bold py-2 mb-3">
                <i class="fa-solid fa-user-check me-1"></i> Create Account
            </button>
        </form>

        <div class="text-center border-top pt-2 small text-muted">
            Already have an account? <a href="<?= url('login.php') ?>" class="text-primary text-decoration-none fw-bold">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
