<?php
// config/app.php - Application Configuration & Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load .env variables
require_once __DIR__ . '/database.php';

// App constants
defined('APP_NAME') or define('APP_NAME', getenv('APP_NAME') ?: 'Campus Lost & Found Portal');
defined('APP_URL') or define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/Lost-and-Found', '/'));

/**
 * Return the base URL or append a path
 */
function url($path = '') {
    $cleanPath = ltrim($path, '/');
    return APP_URL . ($cleanPath ? '/' . $cleanPath : '');
}

/**
 * Set a flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if a user is currently logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the currently logged in user is an admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get the current logged in user details
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'user'
    ];
}

/**
 * Redirect to a specific URL
 */
function redirect($path) {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header("Location: {$path}");
    } else {
        header("Location: " . url($path));
    }
    exit;
}

/**
 * Sanitize output string
 */
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

