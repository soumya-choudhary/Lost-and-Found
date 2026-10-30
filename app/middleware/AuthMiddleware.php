<?php
// app/middleware/AuthMiddleware.php - Authentication Route Guards
//
// WHAT THIS MIDDLEWARE DOES:
// 1. check(): Ensures the user is logged in. If not, saves a warning flash message and redirects to login.php.
//    Used on protected pages like student/dashboard.php, report-item.php, student/my-reports.php.
// 2. guestOnly(): Ensures the user is NOT logged in. If already logged in, redirects them to their dashboard.
//    Used on login.php and register.php to prevent authenticated users from seeing sign-up forms again.
//
// HOW TO DEBUG & MODIFY:
// - TO CHANGE LOGIN REDIRECT: In check(), edit `'login.php'`.
// - TO DEBUG ACCESS ISSUES: Check whether $_SESSION['user_id'] is being set by AuthController during login.

require_once __DIR__ . '/../../config/app.php';

class AuthMiddleware {
    // Require user to be authenticated
    public static function check(): void {
        if (!isLoggedIn()) {
            setFlash('warning', 'Please sign in to access that page.');
            redirect('login.php');
        }
    }

    // Require visitor to be a guest (redirects logged-in users away from login/register)
    public static function guestOnly(): void {
        if (isLoggedIn()) {
            // Direct user based on role
            redirect(isAdmin() ? 'admin/dashboard.php' : 'student/dashboard.php');
        }
    }
}
