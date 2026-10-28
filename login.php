<?php
// login.php - Campus User & Admin Login Portal

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

// If user is already authenticated, redirect to appropriate dashboard
AuthMiddleware::guestOnly();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    $result = $authController->handleLogin();
    $errors = $result['errors'];
    $email = $result['email'];
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container auth-wrapper">
    <div class="row justify-content-center w-100 my-4">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            
            <div class="auth-card">
                <!-- Header -->
                <div class="auth-header">
                    <div class="auth-icon-circle">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Campus Portal Sign In</h4>
                    <p class="text-muted small mb-0">Sign in to report, track, or claim campus items</p>
                </div>

                <!-- Card Body -->
                <div class="p-4">
                    <!-- Error messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2 small mb-3">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= sanitize($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('login.php') ?>" method="POST" id="loginForm" novalidate>
                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="login_email" class="form-label fw-semibold small text-secondary">Campus Email Address</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-envelope form-icon"></i>
                                <input type="email" class="form-control" id="login_email" name="email" 
                                       placeholder="name@campus.edu" value="<?= sanitize($email) ?>" required autofocus>
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="login_password" class="form-label fw-semibold small text-secondary">Password</label>
                            </div>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-key form-icon"></i>
                                <input type="password" class="form-control" id="login_password" name="password" 
                                       placeholder="Enter your password" required>
                                <button type="button" class="toggle-password-btn" data-target="#login_password" aria-label="Toggle password visibility">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fs-6 py-2 shadow-sm">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to Portal
                            </button>
                        </div>
                    </form>

                    <!-- Quick Demo Credentials Fill -->
                    <div class="demo-box mt-4 text-center">
                        <div class="fw-semibold text-secondary mb-1">
                            <i class="fa-solid fa-flask-vial me-1 text-primary"></i> Quick Demo Login
                        </div>
                        <div class="d-flex justify-content-center gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary fill-demo-btn" data-role="student">
                                <i class="fa-solid fa-user-graduate me-1"></i> Student Demo
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary fill-demo-btn" data-role="admin">
                                <i class="fa-solid fa-shield-halved me-1"></i> Admin Demo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer link to register -->
                <div class="bg-light text-center py-3 border-top small text-muted">
                    Don't have an account yet? 
                    <a href="<?= url('register.php') ?>" class="fw-bold text-primary text-decoration-none ms-1">Create one here</a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>

