<?php
// app/middleware/AuthMiddleware.php - Route Protection for Signed-In Users
require_once __DIR__ . '/../../config/app.php';

class AuthMiddleware {
    public static function check(): void {
        if (!isLoggedIn()) {
            setFlash('warning', 'Please sign in to access this page.');
            redirect('login.php');
        }
    }

    public static function guestOnly(): void {
        if (isLoggedIn()) {
            redirect(isAdmin() ? 'admin/dashboard.php' : 'student/dashboard.php');
        }
    }
}
