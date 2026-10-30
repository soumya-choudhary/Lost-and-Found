<?php
// report-item.php - Report Lost or Found Item

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/controllers/ItemController.php';

$itemController = new ItemController();
$categories = $itemController->getCategories();
$currentUser = currentUser();

$formData = [
    'item_type'     => strtolower($_GET['type'] ?? 'lost'),
    'title'         => '',
    'category_id'   => '',
    'location'      => '',
    'event_date'    => date('Y-m-d'),
    'description'   => '',
    'contact_name'  => $currentUser['name'] ?? '',
    'contact_email' => $currentUser['email'] ?? '',
    'contact_phone' => ''
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = $itemController->handleReportSubmission();
    $errors = $res['errors'];
    $formData = $res['data'];
}

$pageTitle = 'Report Item';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container my-3" style="max-width: 680px;">
    <div class="card border shadow-sm rounded-3 bg-white p-4">
        <h4 class="fw-bold mb-3"><i class="fa-solid fa-bullhorn text-primary me-2"></i>Report Item</h4>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= url('report-item.php') ?>" method="POST" enctype="multipart/form-data" novalidate>
            <!-- 1. Type: Lost or Found -->
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">REPORT TYPE</label>
                <div class="d-flex gap-2">
                    <input type="radio" class="btn-check" name="item_type" id="type_lost" value="lost" <?= $formData['item_type']==='lost'?'checked':'' ?>>
                    <label class="btn btn-outline-danger flex-fill py-2" for="type_lost"><i class="fa-solid fa-circle-question me-1"></i> I Lost Something</label>
                    <input type="radio" class="btn-check" name="item_type" id="type_found" value="found" <?= $formData['item_type']==='found'?'checked':'' ?>>
                    <label class="btn btn-outline-success flex-fill py-2" for="type_found"><i class="fa-solid fa-hand-holding-heart me-1"></i> I Found Something</label>
                </div>
            </div>

            <!-- 2. Title & Category -->
            <div class="row g-2 mb-2">
                <div class="col-sm-7">
                    <label for="title" class="form-label small">Item Title *</label>
                    <input type="text" class="form-control form-control-sm" id="title" name="title" value="<?= sanitize($formData['title']) ?>" placeholder="e.g. Blue Hydro Flask" required>
                </div>
                <div class="col-sm-5">
                    <label for="category_id" class="form-label small">Category *</label>
                    <select class="form-select form-select-sm" id="category_id" name="category_id" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $formData['category_id']==$cat['id']?'selected':'' ?>><?= sanitize($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 3. Location & Date -->
            <div class="row g-2 mb-2">
                <div class="col-sm-7">
                    <label for="location" class="form-label small">Campus Location *</label>
                    <input type="text" class="form-control form-control-sm" id="location" name="location" value="<?= sanitize($formData['location']) ?>" placeholder="e.g. Science Library 2nd Floor" required>
                </div>
                <div class="col-sm-5">
                    <label for="event_date" class="form-label small">Date *</label>
                    <input type="date" class="form-control form-control-sm" id="event_date" name="event_date" max="<?= date('Y-m-d') ?>" value="<?= sanitize($formData['event_date']) ?>" required>
                </div>
            </div>

            <!-- 4. Description -->
            <div class="mb-2">
                <label for="description" class="form-label small">Description &amp; Identifying Details *</label>
                <textarea class="form-control form-control-sm" id="description" name="description" rows="3" placeholder="Color, brand, unique marks, contents..." required><?= sanitize($formData['description']) ?></textarea>
            </div>

            <!-- 5. Photo Upload -->
            <div class="mb-3">
                <label for="itemImageInput" class="form-label small">Photo (Optional, JPG/PNG under 5MB)</label>
                <input type="file" class="form-control form-control-sm" id="itemImageInput" name="image" accept="image/*">
            </div>

            <!-- 6. Contact Details -->
            <h6 class="fw-bold small text-secondary border-top pt-3 mb-2">CONTACT INFORMATION</h6>
            <div class="row g-2 mb-3">
                <div class="col-sm-4">
                    <label class="form-label small">Your Name *</label>
                    <input type="text" class="form-control form-control-sm" name="contact_name" value="<?= sanitize($formData['contact_name']) ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label small">Campus Email *</label>
                    <input type="email" class="form-control form-control-sm" name="contact_email" value="<?= sanitize($formData['contact_email']) ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label small">Phone (Optional)</label>
                    <input type="tel" class="form-control form-control-sm" name="contact_phone" value="<?= sanitize($formData['contact_phone']) ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold">Submit Report</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
