<?php
// app/controllers/AuthController.php - Authentication Logic (Login, Register, Logout)
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthController {
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    // Process user login
    public function handleLogin(): array {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors   = [];

        if (empty($email) || empty($password)) {
            $errors[] = 'Please enter both email and password.';
            return ['errors' => $errors, 'email' => $email];
        }

        $user = $this->userRepo->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email address or password.';
            return ['errors' => $errors, 'email' => $email];
        }

        // Establish session
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        setFlash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
        return [];
    }

    // Process user registration
    public function handleRegister(): array {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $errors   = [];

        if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        if ($this->userRepo->findByEmail($email)) $errors[] = 'An account with this email already exists.';

        if (empty($errors)) {
            $userId = $this->userRepo->create($name, $email, $password, $phone);
            if ($userId) {
                // Auto sign-in new student
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

    // Terminate session and redirect to homepage
    public function logout(): void {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        redirect('login.php');
    }
}
