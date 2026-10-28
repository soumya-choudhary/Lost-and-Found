<?php
// app/middleware/AuthMiddleware.php

require_once __DIR__ . '/../../config/app.php';

class AuthMiddleware {
    /**
     * Ensure the user is authenticated. If not, redirect to login page.
     */
    public static function check(): void {
        if (!isLoggedIn()) {
            setFlash('warning', 'Please sign in to access that page.');
            redirect('login.php');
        }
    }

    /**
     * Ensure the user is a guest (not logged in). If logged in, redirect to home or dashboard.
     */
    public static function guestOnly(): void {
        if (isLoggedIn()) {
            if (isAdmin()) {
                redirect('admin/dashboard.php');
            } else {
                redirect('student/dashboard.php');
            }
        }
    }
}

