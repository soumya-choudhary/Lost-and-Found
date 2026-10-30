<?php
// app/middleware/AdminMiddleware.php - Administrator route guard

require_once __DIR__ . '/../../config/app.php';

class AdminMiddleware {
    // Require admin privileges
    public static function check(): void {
        if (!isLoggedIn()) {
            setFlash('warning', 'Please sign in first.');
            redirect('login.php');
        }
        if (!isAdmin()) {
            setFlash('danger', 'Admin privileges required.');
            redirect('student/dashboard.php');
        }
    }
}
