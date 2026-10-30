<?php
// config/app.php - Core configuration, session & global helpers

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

require_once __DIR__ . '/database.php';

defined('APP_NAME') or define('APP_NAME', getenv('APP_NAME') ?: 'Lost & Found Portal');
defined('APP_URL')  or define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/Lost-and-Found', '/'));

// Generate full URL from relative path
function url(string $path = ''): string {
    return APP_URL . ($path ? '/' . ltrim($path, '/') : '');
}

// Flash messages (one-time alerts)
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// Auth & Role helpers
function isLoggedIn(): bool { return !empty($_SESSION['user_id']); }
function isAdmin(): bool { return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin'; }

function currentUser(): ?array {
    return isLoggedIn() ? [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'user'
    ] : null;
}

// Redirect helper
function redirect(string $path): void {
    header("Location: " . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

// XSS sanitize helper
function sanitize($val): string {
    return htmlspecialchars(trim((string)($val ?? '')), ENT_QUOTES, 'UTF-8');
}

// Authorization: check if user is the reporter of an item
function isItemOwner(array $item, ?array $user = null): bool {
    $u = $user ?? currentUser();
    if (!$u) return false;
    return (!empty($item['user_id']) && (int)$item['user_id'] === (int)$u['id'])
        || (!empty($item['contact_email']) && strtolower($item['contact_email']) === strtolower($u['email']));
}

// Authorization: check if user is Admin OR the reporter
function canManageItem(array $item, ?array $user = null): bool {
    return isAdmin() || isItemOwner($item, $user);
}

// UI Badge helpers (Item Type, Item Status, Claim Status)
function typeBadgeClass(string $type): string {
    return strtolower($type) === 'lost' ? 'bg-danger text-white' : 'bg-success text-white';
}

function statusBadgeClass(string $status): string {
    return match($status) {
        'Claim Requested' => 'badge-status-claim-requested',
        'Resolved'        => 'badge-status-resolved',
        default           => 'badge-status-open'
    };
}

function claimBadgeClass(string $status): string {
    return match($status) {
        'Approved' => 'bg-success text-white',
        'Rejected' => 'bg-danger text-white',
        default    => 'bg-warning text-dark'
    };
}

// Resolve item image or fallback to SVG placeholder
function itemImageUrl($itemOrPath = null): string {
    $path = is_array($itemOrPath) ? ($itemOrPath['image_url'] ?? null) : $itemOrPath;
    return !empty($path) ? url($path) : url('public/assets/img/placeholder.svg');
}
