<?php
// app/controllers/AuthController.php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthController {
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    /**
     * Handle Login Submission
     */
    public function handleLogin(): array {
        $errors = [];
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        if (empty($errors)) {
            $user = $this->userRepo->findByEmail($email);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                setFlash('success', "Welcome back, " . htmlspecialchars($user['name']) . "!");

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('student/dashboard.php');
                }
            } else {
                $errors[] = 'Invalid email or password. Please try again.';
            }
        }

        return ['errors' => $errors, 'email' => $email];
    }

    /**
     * Handle Registration Submission
     */
    public function handleRegister(): array {
        $errors = [];
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $phone    = trim($_POST['phone'] ?? '');

        // Validation
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Full name is required (minimum 2 characters).';
        }

        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid campus or personal email.';
        } elseif ($this->userRepo->emailExists($email)) {
            $errors[] = 'An account with this email already exists. Please login instead.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $newUser = $this->userRepo->create([
                'name'     => $name,
                'email'    => $email,
                'password' => $password,
                'phone'    => $phone,
                'role'     => 'user'
            ]);

            if ($newUser) {
                // Automatically log in newly registered user
                session_regenerate_id(true);
                $_SESSION['user_id']    = $newUser['id'];
                $_SESSION['user_name']  = $newUser['name'];
                $_SESSION['user_email'] = $newUser['email'];
                $_SESSION['user_role']  = $newUser['role'];

                setFlash('success', 'Registration successful! Welcome to the Campus Lost & Found Portal.');
                redirect('student/dashboard.php');
            } else {
                $errors[] = 'Failed to create account. Please try again.';
            }
        }

        return [
            'errors' => $errors,
            'data'   => ['name' => $name, 'email' => $email, 'phone' => $phone]
        ];
    }

    /**
     * Handle User Logout
     */
    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        setFlash('info', 'You have been logged out successfully.');
        redirect('login.php');
    }
}

