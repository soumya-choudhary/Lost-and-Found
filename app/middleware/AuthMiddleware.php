<?php
// app/middleware/AuthMiddleware.php - Route access guards

require_once __DIR__ . '/../../config/app.php';

class AuthMiddleware {
    // Require logged in user
    public static function check(): void {
        if (!isLoggedIn()) {
            setFlash('warning', 'Please sign in first.');
            redirect('login.php');
        }
    }

    // Require guest (not logged in)
    public static function guestOnly(): void {
        if (isLoggedIn()) {
            redirect(isAdmin() ? 'admin/dashboard.php' : 'student/dashboard.php');
        }
    }
}
