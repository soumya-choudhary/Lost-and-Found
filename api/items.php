<?php
// api/items.php - AJAX Filter & Search API Endpoint

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/controllers/ItemController.php';

$controller = new ItemController();
$controller->handleAjaxFilter();

