<?php
// app/controllers/AuthController.php - Authentication & User Registration Controller
//
// WHAT THIS CONTROLLER DOES:
// 1. handleLogin():
//    - Validates email format and non-empty password.
//    - Fetches user by email from UserRepository.
//    - Verifies password using PHP password_verify() with stored bcrypt hash.
//    - Prevents session fixation via session_regenerate_id(true).
//    - Populates $_SESSION with user_id, user_name, user_email, and user_role.
//    - Redirects based on role (admin -> admin/dashboard.php, student -> student/dashboard.php).
// 2. handleRegister():
//    - Validates full name (min 2 chars), valid email, email uniqueness, password strength (min 6 chars), and password match.
//    - Creates user in database via UserRepository::create() with bcrypt hash.
//    - Automatically signs the user in and redirects to student/dashboard.php.
// 3. logout():
//    - Destroys session variables and cookie, redirects to login.php.
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO CHANGE PASSWORD LENGTH: In handleRegister(), change `strlen($password) < 6` to 8 or your desired length.
// - TO RESTRICT EMAIL DOMAIN (e.g., only @campus.edu):
//   In handleRegister(), add:
//   `elseif (!str_ends_with(strtolower($email), '@campus.edu')) $errors[] = 'Must be a campus.edu email.';`
// - TO CHANGE DASHBOARD REDIRECTS: Check handleLogin() and handleRegister() redirect lines.

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthController {
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    // =========================================================================
    // LOGIN PROCESSING
    // =========================================================================

    // Process user sign-in form submission
    // Returns ['errors' => [...], 'email' => string]
    public function handleLogin(): array {
        $errors = [];
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Input Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        // Credential Verification
        if (empty($errors)) {
            $user = $this->userRepo->findByEmail($email);

            // Verify password against stored bcrypt hash
            if ($user && password_verify($password, $user['password_hash'])) {
                // SECURITY: Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Populate session variables
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                setFlash('success', "Welcome back, " . htmlspecialchars($user['name']) . "!");

                // Role-based redirection
                // TO CHANGE DESTINATIONS: Edit URLs below
                redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
            } else {
                $errors[] = 'Invalid email or password. Please try again.';
            }
        }
        return ['errors' => $errors, 'email' => $email];
    }

    // =========================================================================
    // REGISTRATION PROCESSING
    // =========================================================================

    // Process new account registration form
    // Returns ['errors' => [...], 'data' => [...]]
    public function handleRegister(): array {
        $errors = [];
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $phone    = trim($_POST['phone'] ?? '');

        // Validation Rules
        if (strlen($name) < 2) {
            $errors[] = 'Full name is required (minimum 2 characters).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } elseif ($this->userRepo->emailExists($email)) {
            $errors[] = 'An account with this email already exists. Please sign in instead.';
        }

        // TO RESTRICT EMAIL DOMAIN TO UNIVERSITY (e.g. @campus.edu):
        // if (!str_ends_with(strtolower($email), '@campus.edu')) {
        //     $errors[] = 'Only university email addresses (@campus.edu) are permitted.';
        // }

        // TO CHANGE MINIMUM PASSWORD LENGTH: Edit 6 below
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // Account Persistence
        if (empty($errors)) {
            $user = $this->userRepo->create([
                'name'     => $name,
                'email'    => $email,
                'password' => $password,
                'phone'    => $phone,
                'role'     => 'user' // Default role for new signups
            ]);

            if ($user) {
                // Auto-login newly registered member
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                setFlash('success', 'Registration successful! Welcome to the portal.');
                redirect('student/dashboard.php');
            }
            $errors[] = 'Failed to create account in database. Please try again.';
        }
        return ['errors' => $errors, 'data' => ['name' => $name, 'email' => $email, 'phone' => $phone]];
    }

    // =========================================================================
    // LOGOUT PROCESSING
    // =========================================================================

    // Destroy user session, delete cookie, and redirect to login page
    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }
        session_destroy();

        // Restart clean session for the flash notification
        session_start();
        setFlash('info', 'You have been logged out successfully.');
        redirect('login.php');
    }
}
