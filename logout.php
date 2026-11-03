<?php
// logout.php - User Session Termination & Sign-Out Entry Point
//
// WHAT THIS PAGE DOES:
// 1. Loads configuration and AuthController.
// 2. Invokes AuthController::logout() which clears the $_SESSION array and destroys the session.
// 3. Redirects user back to login.php.

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$authController = new AuthController();
$authController->logout();
