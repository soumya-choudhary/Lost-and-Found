<?php
// app/middleware/AdminMiddleware.php - Route Protection for Administrators
//
// WHAT THIS FILE DOES:
// Ensures that only users with role === 'admin' can access sensitive admin control pages
// (such as admin/dashboard.php, admin/claims.php, admin/items.php, and admin/users.php).
//
// HOW TO EXPLAIN TO PROFESSOR:
// "Role-based access control (RBAC). It checks both that the user is logged in AND that their
// session role equals 'admin'. Regular users and guests are kicked back to login.php with an alert."

require_once __DIR__ . '/../../config/app.php';

class AdminMiddleware {
    /**
     * Checks if current user is an administrator.
     * Evaluates isAdmin() (defined in config/app.php as $_SESSION['user_role'] === 'admin').
     * If false, shows an unauthorized error flash and redirects to login.php.
     */
    public static function check(): void {
        if (!isAdmin()) {
            setFlash('danger', 'Unauthorized access. Administrator privileges required.');
            redirect('login.php');
        }
    }
}
