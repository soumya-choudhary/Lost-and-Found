<?php
// app/middleware/AdminMiddleware.php

require_once __DIR__ . '/../../config/app.php';

class AdminMiddleware {
    /**
     * Ensure the user is an authenticated Admin.
     */
    public static function check(): void {
        if (!isLoggedIn()) {
            setFlash('warning', 'Admin authentication required.');
            redirect('login.php');
        }

        if (!isAdmin()) {
            setFlash('danger', 'Access denied. You do not have permission to view that page.');
            redirect('index.php');
        }
    }
}

