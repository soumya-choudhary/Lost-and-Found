<?php
// register.php - Campus User Registration Portal

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

// If user is already authenticated, redirect to appropriate dashboard
AuthMiddleware::guestOnly();

$errors = [];
$formData = [
    'name'  => '',
    'email' => '',
    'phone' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    $result = $authController->handleRegister();
    $errors = $result['errors'];
    $formData = $result['data'];
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container auth-wrapper">
    <div class="row justify-content-center w-100 my-4">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            
            <div class="auth-card">
                <!-- Header -->
                <div class="auth-header">
                    <div class="auth-icon-circle bg-success">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Create Student / Staff Account</h4>
                    <p class="text-muted small mb-0">Join the campus network to easily report and recover lost belongings</p>
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

                    <form action="<?= url('register.php') ?>" method="POST" id="registerForm" novalidate>
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="reg_name" class="form-label fw-semibold small text-secondary">Full Name</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-user form-icon"></i>
                                <input type="text" class="form-control" id="reg_name" name="name" 
                                       placeholder="e.g. Alex Johnson" value="<?= sanitize($formData['name']) ?>" required autofocus>
                            </div>
                        </div>

                        <!-- Campus Email -->
                        <div class="mb-3">
                            <label for="reg_email" class="form-label fw-semibold small text-secondary">Campus Email Address</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-envelope form-icon"></i>
                                <input type="email" class="form-control" id="reg_email" name="email" 
                                       placeholder="alex.johnson@campus.edu" value="<?= sanitize($formData['email']) ?>" required>
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div class="mb-3">
                            <label for="reg_phone" class="form-label fw-semibold small text-secondary">Contact Phone Number (Optional)</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-phone form-icon"></i>
                                <input type="tel" class="form-control" id="reg_phone" name="phone" 
                                       placeholder="+1 (555) 012-3456" value="<?= sanitize($formData['phone']) ?>">
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="reg_password" class="form-label fw-semibold small text-secondary">Password (Min 6 Characters)</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-lock form-icon"></i>
                                <input type="password" class="form-control" id="reg_password" name="password" 
                                       placeholder="Create a strong password" required minlength="6">
                                <button type="button" class="toggle-password-btn" data-target="#reg_password" aria-label="Toggle password visibility">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="reg_confirm" class="form-label fw-semibold small text-secondary">Confirm Password</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-shield-check form-icon"></i>
                                <input type="password" class="form-control" id="reg_confirm" name="confirm_password" 
                                       placeholder="Re-enter your password" required minlength="6">
                                <button type="button" class="toggle-password-btn" data-target="#reg_confirm" aria-label="Toggle password visibility">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <div id="password-match-feedback" class="form-text small mt-1"></div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg fs-6 py-2 shadow-sm">
                                <i class="fa-solid fa-user-check me-2"></i> Register Account
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Footer link to login -->
                <div class="bg-light text-center py-3 border-top small text-muted">
                    Already registered on the campus portal? 
                    <a href="<?= url('login.php') ?>" class="fw-bold text-primary text-decoration-none ms-1">Sign in here</a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>

