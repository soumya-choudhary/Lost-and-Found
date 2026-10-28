<?php
// index.php - Public Homepage & Item Directory

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Home - Campus Lost & Found';

// Fetch initial statistics
try {
    $db = Database::getConnection();
    $stats = [
        'total_lost'     => (int)$db->query("SELECT COUNT(*) FROM items WHERE item_type = 'lost'")->fetchColumn(),
        'total_found'    => (int)$db->query("SELECT COUNT(*) FROM items WHERE item_type = 'found'")->fetchColumn(),
        'total_resolved' => (int)$db->query("SELECT COUNT(*) FROM items WHERE status = 'Resolved'")->fetchColumn(),
    ];
} catch (Exception $e) {
    $stats = ['total_lost' => 0, 'total_found' => 0, 'total_resolved' => 0];
}

require_once __DIR__ . '/layouts/header.php';
?>

<!-- Hero Banner -->
<div class="py-5 bg-white border-bottom shadow-sm">
    <div class="container text-center">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-3">
            <i class="fa-solid fa-building-columns me-1"></i> Official Campus Portal
        </span>
        <h1 class="display-5 fw-bold text-dark mb-3">Lost Something on Campus?</h1>
        <p class="lead text-muted mx-auto mb-4" style="max-width: 650px;">
            Connecting students, faculty, and campus staff to report, identify, and recover lost or misplaced items quickly and securely.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= url('report-item.php?type=lost') ?>" class="btn btn-danger btn-lg px-4 py-2 shadow-sm rounded-pill fs-6">
                <i class="fa-solid fa-circle-question me-2"></i> Report Lost Item
            </a>
            <a href="<?= url('report-item.php?type=found') ?>" class="btn btn-success btn-lg px-4 py-2 shadow-sm rounded-pill fs-6">
                <i class="fa-solid fa-hand-holding-heart me-2"></i> Report Found Item
            </a>
            <?php if (!isLoggedIn()): ?>
                <a href="<?= url('login.php') ?>" class="btn btn-outline-primary btn-lg px-4 py-2 rounded-pill fs-6">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In
                </a>
            <?php endif; ?>
        </div>

        <!-- Live Statistics Badges -->
        <div class="row g-3 justify-content-center mt-4">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-4 border">
                    <div class="fs-4 fw-bold text-danger"><?= $stats['total_lost'] ?></div>
                    <small class="text-muted fw-semibold">Lost Items Reported</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-4 border">
                    <div class="fs-4 fw-bold text-success"><?= $stats['total_found'] ?></div>
                    <small class="text-muted fw-semibold">Found Items Handed In</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-4 border">
                    <div class="fs-4 fw-bold text-primary"><?= $stats['total_resolved'] ?></div>
                    <small class="text-muted fw-semibold">Successfully Reunited</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>

