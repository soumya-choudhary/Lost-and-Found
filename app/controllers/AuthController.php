<?php
// app/controllers/AuthController.php - Authentication logic

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthController {
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    // Process Login
    public function handleLogin(): array {
        $errors = [];
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (empty($password)) $errors[] = 'Password is required.';

        if (empty($errors)) {
            $user = $this->userRepo->findByEmail($email);
            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                setFlash('success', "Welcome back, " . htmlspecialchars($user['name']) . "!");
                redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
        return ['errors' => $errors, 'email' => $email];
    }

    // Process Registration
    public function handleRegister(): array {
        $errors = [];
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $phone    = trim($_POST['phone'] ?? '');

        if (strlen($name) < 2) $errors[] = 'Name is required (min 2 chars).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        elseif ($this->userRepo->emailExists($email)) $errors[] = 'Email is already registered.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $user = $this->userRepo->create(['name' => $name, 'email' => $email, 'password' => $password, 'phone' => $phone, 'role' => 'user']);
            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];
                setFlash('success', 'Registration successful! Welcome to the portal.');
                redirect('student/dashboard.php');
            }
            $errors[] = 'Failed to create account.';
        }
        return ['errors' => $errors, 'data' => ['name' => $name, 'email' => $email, 'phone' => $phone]];
    }

    // Process Logout
    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }
        session_destroy();
        session_start();
        setFlash('info', 'Logged out successfully.');
        redirect('login.php');
    }
}
