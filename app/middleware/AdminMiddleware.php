<?php
// app/middleware/AdminMiddleware.php - Administrator Role Route Guard
//
// WHAT THIS MIDDLEWARE DOES:
// Protects administrative pages (admin/dashboard.php, admin/claims.php, admin/items.php, admin/users.php):
// 1. First ensures the user is logged in.
// 2. Then verifies that $_SESSION['user_role'] === 'admin'.
// 3. If a regular student tries to access an admin URL, redirects them to student/dashboard.php with an error.
//
// HOW TO DEBUG & MODIFY:
// - TO ALLOW OTHER ROLES (e.g. 'staff'): Update the check condition to `if (!isAdmin() && !isStaff())`.
// - TO DEBUG ACCESS: Check what `currentUser()['role']` returns in the session.

require_once __DIR__ . '/../../config/app.php';

class AdminMiddleware {
    // Require user to be authenticated AND hold the 'admin' role
    public static function check(): void {
        if (!isLoggedIn()) {
            setFlash('warning', 'Please sign in with administrator credentials.');
            redirect('login.php');
        }
        if (!isAdmin()) {
            setFlash('danger', 'Administrator privileges are required for that area.');
            redirect('student/dashboard.php');
        }
    }
}
