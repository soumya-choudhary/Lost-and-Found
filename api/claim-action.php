<?php
// api/claim-action.php - Alias Wrapper for admin-claim-action.php
//
// WHAT THIS FILE DOES:
// Serves as a backward-compatible alias route pointing directly to admin-claim-action.php.
// Allows both /api/claim-action.php and /api/admin-claim-action.php to be called transparently.

require_once __DIR__ . '/admin-claim-action.php';
