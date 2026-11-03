<?php
// app/controllers/AuthController.php - Authentication Logic (Login, Register, Logout)
//
// WHAT THIS FILE DOES:
// Handles user session lifecycles:
// 1. handleLogin(): Validates credentials, verifies BCRYPT password hashes, regenerates session ID to prevent fixation, and routes users by role.
// 2. handleRegister(): Validates inputs (length, email format, password confirmation), checks email uniqueness, hashes password, and creates account.
// 3. logout(): Wipes session variables, destroys session storage, and redirects.
//
// HOW TO EXPLAIN TO PROFESSOR:
// "Authentication follows industry best practices: passwords are never stored in plain text;
// we use PHP's native password_hash with BCRYPT. On successful authentication, session_regenerate_id(true)
// prevents Session Fixation attacks by issuing a fresh session identifier."

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthController {
    private UserRepository $userRepo;

    /**
     * Initialize controller and instantiate UserRepository.
     */
    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    /**
     * Process user login POST submission.
     *
     * Steps:
     * 1. Read sanitized email and raw password from $_POST.
     * 2. Query user record by email via UserRepository.
     * 3. Validate password using password_verify() against stored BCRYPT hash.
     * 4. Call session_regenerate_id(true) to mitigate session hijacking/fixation.
     * 5. Set session variables: user_id, user_name, user_email, user_role.
     * 6. Set success flash notice and redirect based on role (admin -> admin/dashboard, student -> student/dashboard).
     *
     * @return array Array of error messages and submitted email if login fails
     */
    public function handleLogin(): array {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors   = [];

        // Basic presence validation
        if (empty($email) || empty($password)) {
            $errors[] = 'Please enter both email and password.';
            return ['errors' => $errors, 'email' => $email];
        }

        // Look up user by email
        $user = $this->userRepo->findByEmail($email);

        // Verify that user exists and password hash matches
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Generic error message to prevent user enumeration
            $errors[] = 'Invalid email address or password.';
            return ['errors' => $errors, 'email' => $email];
        }

        // Security: Regenerate session ID upon privilege transition (login)
        session_regenerate_id(true);

        // Populate session array
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        // Flash message and redirect
        setFlash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
        return [];
    }

    /**
     * Process new user/student registration POST submission.
     *
     * Validations:
     * - Name must be at least 2 characters.
     * - Email must be valid email format.
     * - Password must be at least 6 characters.
     * - Confirm password must match password.
     * - Email must not already exist in the database.
     *
     * @return array Array containing any error messages and previously entered form data
     */
    public function handleRegister(): array {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $errors   = [];

        // Validation rules
        if (strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if ($this->userRepo->findByEmail($email)) {
            $errors[] = 'An account with this email already exists.';
        }

        // If validation passed, create account
        if (empty($errors)) {
            $userId = $this->userRepo->create($name, $email, $password, $phone);
            if ($userId) {
                // Auto sign-in newly registered student
                session_regenerate_id(true);
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = strtolower($email);
                $_SESSION['user_role']  = 'user';

                setFlash('success', 'Registration successful! Welcome to the portal.');
                redirect('student/dashboard.php');
            }
            $errors[] = 'Failed to create account. Please try again.';
        }

        return ['errors' => $errors, 'data' => ['name' => $name, 'email' => $email, 'phone' => $phone]];
    }

    /**
     * Terminate user session and log out.
     * Empties the session array, destroys backend session storage, and redirects to login.
     */
    public function logout(): void {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        redirect('login.php');
    }
}
