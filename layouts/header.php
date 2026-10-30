<?php
// layouts/header.php - Global Header & Navigation

require_once __DIR__ . '/../config/app.php';

$currentUser = currentUser();
$flash = getFlash();
$pageTitle = $pageTitle ?? 'Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | <?= sanitize(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= url('public/assets/css/style.css') ?>">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= url('index.php') ?>">
                <i class="fa-solid fa-magnifying-glass-location me-1"></i> <?= sanitize(APP_NAME) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>"><i class="fa-solid fa-compass me-1"></i> Browse</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('report-item.php') ?>"><i class="fa-solid fa-circle-plus me-1"></i> Report Item</a></li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <?php if ($currentUser): ?>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="btn btn-warning btn-sm text-dark fw-bold" href="<?= url('admin/dashboard.php') ?>"><i class="fa-solid fa-shield-halved me-1"></i> Admin</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="<?= url('student/dashboard.php') ?>"><i class="fa-solid fa-gauge-high me-1"></i> Dashboard</a></li>
                        <?php endif; ?>
                        <li class="nav-item text-white small fw-semibold"><?= sanitize($currentUser['name']) ?></li>
                        <li class="nav-item"><a class="btn btn-outline-light btn-sm py-0 px-2" href="<?= url('logout.php') ?>">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>">Sign In</a></li>
                        <li class="nav-item"><a class="btn btn-outline-light btn-sm py-1 px-3" href="<?= url('register.php') ?>">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash message alert -->
    <?php if ($flash): ?>
        <div class="container mt-2">
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show py-2 small shadow-sm" role="alert">
                <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <main class="flex-grow-1">
