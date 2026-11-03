<?php
// app/middleware/AuthMiddleware.php - Route Protection for Signed-In Users
//
// WHAT THIS FILE DOES:
// Acts as a security checkpoint (guard) for web pages.
// 1. check(): Ensures only authenticated users can access protected pages (like student dashboard/reports).
// 2. guestOnly(): Prevents logged-in users from accessing login/register pages again.
//
// HOW TO EXPLAIN TO PROFESSOR:
// "Middleware functions run at the very top of a page script before any HTML is rendered.
// If the user does not meet the session requirement, they are redirected immediately with an error message."

require_once __DIR__ . '/../../config/app.php';

class AuthMiddleware {
    /**
     * Protects routes requiring a signed-in user.
     * Checks if $_SESSION['user_id'] is present via isLoggedIn().
     * If not logged in, stores a warning flash message and redirects to login.php.
     */
    public static function check(): void {
        if (!isLoggedIn()) {
            setFlash('warning', 'Please sign in to access this page.');
            redirect('login.php');
        }
    }

    /**
     * Protects guest-only routes (login.php and register.php).
     * If user is already logged in, redirects them directly to their appropriate dashboard:
     * - Admins go to admin/dashboard.php
     * - Regular students go to student/dashboard.php
     */
    public static function guestOnly(): void {
        if (isLoggedIn()) {
            redirect(isAdmin() ? 'admin/dashboard.php' : 'student/dashboard.php');
        }
    }
}
