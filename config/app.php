<?php
// config/app.php - Core Application Configuration, Session Initialization & Global Helpers
//
// WHAT THIS FILE DOES:
// 1. Starts PHP session if not active (skips CLI to prevent permissions warnings in CLI scripts).
// 2. Loads database connection via require_once 'database.php'.
// 3. Defines APP_NAME and APP_URL constants.
// 4. Provides global utility functions used by all controllers, repositories, and views:
//    - url(): Generates absolute URLs.
//    - setFlash() / getFlash(): One-time notification messages stored in session.
//    - isLoggedIn() / isAdmin() / currentUser(): Authentication and role checks.
//    - redirect(): Safe HTTP redirection.
//    - sanitize(): Prevents XSS attacks via htmlspecialchars.
//    - isItemOwner() / canManageItem(): Centralized authorization logic for items.
//    - typeBadgeClass() / statusBadgeClass() / claimBadgeClass(): UI badge styling.
//    - itemImageUrl(): Resolves item image or fallback to placeholder.svg.
//
// HOW TO DEBUG & MODIFY:
// - TO CHANGE APP URL / NAME: Update .env (APP_URL=http://localhost/Lost-and-Found, APP_NAME=...)
// - TO ADD A NEW STATUS BADGE: Find statusBadgeClass() below and add a new case in the match block.
// - TO MODIFY WHO CAN EDIT AN ITEM: Find canManageItem() below and change the authorization rule.

// 1. Session Initialization
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

// 2. Database Connection
require_once __DIR__ . '/database.php';

// 3. Application Constants
defined('APP_NAME') or define('APP_NAME', getenv('APP_NAME') ?: 'Lost & Found Portal');
defined('APP_URL')  or define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/Lost-and-Found', '/'));

// =========================================================================
// URL & REDIRECTION HELPERS
// =========================================================================

// Generate absolute URL from relative path (e.g. url('index.php') -> http://localhost/Lost-and-Found/index.php)
// TO MODIFY BASE URL: Change APP_URL in .env or the fallback constant above.
function url(string $path = ''): string {
    return APP_URL . ($path ? '/' . ltrim($path, '/') : '');
}

// Safe HTTP Redirect
// If path starts with 'http', redirects to external URL; otherwise prepends APP_URL.
function redirect(string $path): void {
    header("Location: " . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

// =========================================================================
// FLASH MESSAGING HELPERS (One-time session notices)
// =========================================================================

// Set flash message for display on next page view
// $type options: 'success', 'danger', 'warning', 'info'
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Retrieve and clear flash message
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// =========================================================================
// AUTHENTICATION & ROLE HELPERS
// =========================================================================

// Check if user is logged in
// TO DEBUG: Check if $_SESSION['user_id'] is set after login.
function isLoggedIn(): bool { 
    return !empty($_SESSION['user_id']); 
}

// Check if logged-in user is an administrator
// TO CHANGE ADMIN ROLE NAME: Change 'admin' string below if role is named differently.
function isAdmin(): bool { 
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin'; 
}

// Return associative array of currently logged in user, or null if guest
function currentUser(): ?array {
    return isLoggedIn() ? [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'user'
    ] : null;
}

// =========================================================================
// SECURITY & INPUT SANITIZATION
// =========================================================================

// Sanitize string to prevent Cross-Site Scripting (XSS)
// Wraps htmlspecialchars with ENT_QUOTES and UTF-8 encoding.
function sanitize($val): string {
    return htmlspecialchars(trim((string)($val ?? '')), ENT_QUOTES, 'UTF-8');
}

// =========================================================================
// ITEM PERMISSION & OWNERSHIP HELPERS
// =========================================================================

// Check if current user is the original reporter of an item
// Matches either:
// 1. user_id in items table == user_id in session
// 2. contact_email in items table == user_email in session
// TO DEBUG: Ensure user logged in with same email as contact_email on report.
function isItemOwner(array $item, ?array $user = null): bool {
    $u = $user ?? currentUser();
    if (!$u) return false;
    return (!empty($item['user_id']) && (int)$item['user_id'] === (int)$u['id'])
        || (!empty($item['contact_email']) && strtolower($item['contact_email']) === strtolower($u['email']));
}

// Check if current user can manage/modify an item (Admin OR Reporter)
// TO ALLOW ONLY ADMINS TO MANAGE: Change return to `return isAdmin();`
// TO ALLOW ONLY REPORTERS: Change return to `return isItemOwner($item, $user);`
function canManageItem(array $item, ?array $user = null): bool {
    return isAdmin() || isItemOwner($item, $user);
}

// =========================================================================
// UI PRESENTATION HELPERS (Badge Colors & Fallback Images)
// =========================================================================

// Return Bootstrap badge class for item type ('lost' vs 'found')
// TO CHANGE COLORS: Change 'bg-danger' or 'bg-success' below.
function typeBadgeClass(string $type): string {
    return strtolower($type) === 'lost' ? 'bg-danger text-white' : 'bg-success text-white';
}

// Return CSS class for item status ('Open', 'Claim Requested', 'Resolved')
// TO ADD NEW STATUS (e.g. 'Archived'): Add `'Archived' => 'badge-status-archived',`
function statusBadgeClass(string $status): string {
    return match($status) {
        'Claim Requested' => 'badge-status-claim-requested',
        'Resolved'        => 'badge-status-resolved',
        default           => 'badge-status-open'
    };
}

// Return badge class for claim decision status ('Pending', 'Approved', 'Rejected')
function claimBadgeClass(string $status): string {
    return match($status) {
        'Approved' => 'bg-success text-white',
        'Rejected' => 'bg-danger text-white',
        default    => 'bg-warning text-dark'
    };
}

// Resolve item image URL with fallback to public/assets/img/placeholder.svg
// Accepts either an item record array (with 'image_url' key) or a direct string path.
function itemImageUrl($itemOrPath = null): string {
    $path = is_array($itemOrPath) ? ($itemOrPath['image_url'] ?? null) : $itemOrPath;
    return !empty($path) ? url($path) : url('public/assets/img/placeholder.svg');
}
