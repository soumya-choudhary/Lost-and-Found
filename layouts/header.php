<?php
// layouts/header.php

require_once __DIR__ . '/../config/app.php';

$currentUser = currentUser();
$flash = getFlash();
$pageTitle = $pageTitle ?? 'Campus Lost & Found Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | <?= sanitize(APP_NAME) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('public/assets/css/style.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= url('index.php') ?>">
                <span class="brand-icon"><i class="fa-solid fa-magnifying-glass-location"></i></span>
                <span>Campus Lost &amp; Found</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="<?= url('index.php') ?>">
                            <i class="fa-solid fa-compass me-1"></i> Browse Listings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'report-item.php') ? 'active' : '' ?>" href="<?= url('report-item.php') ?>">
                            <i class="fa-solid fa-circle-plus me-1"></i> Report Item
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                    <?php if ($currentUser): ?>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link badge text-bg-warning text-dark text-decoration-none px-3 py-2" href="<?= url('admin/dashboard.php') ?>">
                                    <i class="fa-solid fa-shield-halved me-1"></i> Admin Panel
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '' ?>" href="<?= url('student/dashboard.php') ?>">
                                    <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userMenu" role="button" data-bs-target="#userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar-circle">
                                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                                </div>
                                <span class="d-none d-lg-inline text-white fw-medium"><?= sanitize($currentUser['name']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" id="userDropdown" aria-labelledby="userMenu">
                                <li class="dropdown-header">
                                    <div class="fw-bold"><?= sanitize($currentUser['name']) ?></div>
                                    <small class="text-muted"><?= sanitize($currentUser['email']) ?></small>
                                    <div><span class="badge bg-secondary text-uppercase mt-1"><?= sanitize($currentUser['role']) ?></span></div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($currentUser['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?= url('admin/dashboard.php') ?>"><i class="fa-solid fa-sliders me-2 text-primary"></i>Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?= url('admin/claims.php') ?>"><i class="fa-solid fa-clipboard-check me-2 text-warning"></i>Manage Claims</a></li>
                                    <li><a class="dropdown-item" href="<?= url('admin/items.php') ?>"><i class="fa-solid fa-boxes-stacked me-2 text-info"></i>Manage Items</a></li>
                                    <li><a class="dropdown-item" href="<?= url('admin/users.php') ?>"><i class="fa-solid fa-users me-2 text-success"></i>Users</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?= url('student/dashboard.php') ?>"><i class="fa-solid fa-table-columns me-2 text-primary"></i>My Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?= url('student/my-reports.php') ?>"><i class="fa-solid fa-box-open me-2 text-info"></i>My Reported Items</a></li>
                                    <li><a class="dropdown-item" href="<?= url('student/my-claims.php') ?>"><i class="fa-solid fa-hand-holding-hand me-2 text-warning"></i>My Claims</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign Out</a></li>
                            </ul>
                        </li>

                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : '' ?>" href="<?= url('login.php') ?>">
                                <i class="fa-solid fa-right-to-bracket me-1"></i> Sign In
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light btn-sm px-3 rounded-pill" href="<?= url('register.php') ?>">
                                <i class="fa-solid fa-user-plus me-1"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Notification Messages -->
    <?php if ($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show shadow-sm border-0 d-flex align-items-center" role="alert">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-success' : ($flash['type'] === 'danger' ? 'fa-circle-exclamation text-danger' : 'fa-circle-info text-info') ?> fa-lg me-2"></i>
                <div class="flex-grow-1">
                    <?= sanitize($flash['message']) ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Page Content Starts -->
    <main class="flex-grow-1 py-4">

