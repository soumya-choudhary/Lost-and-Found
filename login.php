<?php
// login.php - Campus User & Administrator Sign-In Interface
//
// WHAT THIS PAGE DOES:
// 1. Blocks already logged-in users from accessing the sign-in form (via AuthMiddleware::guestOnly()).
// 2. Receives POST form data (email, password) and calls AuthController::handleLogin().
// 3. Displays error messages if authentication fails (e.g., wrong password, unregistered email).
// 4. Provides 1-click Quick Demo buttons for Admin and Student accounts for presentation demos.
//
// HOW TO DEBUG:
// - If demo login button doesn't fill fields: Check public/assets/js/auth.js and ensure input IDs match #login_email & #login_password.
// - If login fails with "Invalid email or password": Verify that users table in PostgreSQL has the corresponding email and bcrypt hash.
// - Default seed credentials: admin@campus.edu / admin123  AND  student@campus.edu / admin123

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

// GUARD: Redirect if user is already authenticated
// - Admin goes to admin/dashboard.php
// - Student/user goes to student/dashboard.php
AuthMiddleware::guestOnly();

$errors = [];
$email = '';

// Handle POST sign-in submission
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

        <!-- Validation & Error Notice Block -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Sign-in Form -->
        <form action="<?= url('login.php') ?>" method="POST" id="loginForm">
            <!-- Email Field -->
            <div class="mb-3">
                <label for="login_email" class="form-label small">Campus Email</label>
                <!-- TO CHANGE DEFAULT PLACEHOLDER: Edit placeholder attribute below -->
                <input type="email" class="form-control" id="login_email" name="email" 
                       value="<?= sanitize($email) ?>" placeholder="user@campus.edu" required autofocus>
            </div>

            <!-- Password Field -->
            <div class="mb-3">
                <label for="login_password" class="form-label small">Password</label>
                <input type="password" class="form-control" id="login_password" name="password" 
                       placeholder="Enter password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mb-3">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Sign In
            </button>
        </form>

        <!-- Quick Demo Login Buttons (Click fills form via public/assets/js/auth.js) -->
        <div class="border-top pt-3 text-center small text-muted">
            <div class="mb-2 fw-semibold">Quick Presentation Demo:</div>
            <div class="d-flex justify-content-center gap-2">
                <!-- TO CHANGE DEMO USERS: Check public/assets/js/auth.js fill-demo-btn handler -->
                <button type="button" class="btn btn-sm btn-outline-secondary fill-demo-btn" data-role="student" title="Fill student credentials">
                    <i class="fa-solid fa-user-graduate me-1"></i> Student
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary fill-demo-btn" data-role="admin" title="Fill admin credentials">
                    <i class="fa-solid fa-shield-halved me-1"></i> Admin
                </button>
            </div>
        </div>

        <!-- Registration Link -->
        <div class="text-center mt-3 pt-2 border-top small">
            Need an account? <a href="<?= url('register.php') ?>" class="text-primary text-decoration-none fw-bold">Register</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
