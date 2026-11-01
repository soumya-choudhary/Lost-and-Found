<?php
// app/middleware/AdminMiddleware.php - Route Protection for Administrators
require_once __DIR__ . '/../../config/app.php';

class AdminMiddleware {
    public static function check(): void {
        if (!isAdmin()) {
            setFlash('danger', 'Unauthorized access. Administrator privileges required.');
            redirect('login.php');
        }
    }
}
