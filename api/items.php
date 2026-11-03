<?php
// api/items.php - Live Catalog Filter & Search JSON Endpoint
//
// WHAT THIS FILE DOES:
// Serves asynchronous GET requests from the live search filter on index.php (public/assets/js/items.js).
// Receives query filters (q, type, category, status, sort), calls ItemController::handleAjaxFilter(),
// and returns a JSON payload containing { success, count, html }.
//
// ACCESS: Public (No login required)
// METHOD: GET

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/controllers/ItemController.php';

// Delegate filtering and HTML card rendering to ItemController
$controller = new ItemController();
$controller->handleAjaxFilter();
