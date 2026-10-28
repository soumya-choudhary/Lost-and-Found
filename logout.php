<?php
// logout.php - Logout Entry Point

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$authController = new AuthController();
$authController->logout();

