<?php
// report-item.php - Lost or Found Item Report Form
//
// WHAT THIS PAGE DOES:
// 1. Renders the report form allowing campus students/staff to report a lost or found item.
// 2. Pre-fills contact name and email if the user is currently signed in.
// 3. Defaults report type to 'lost' or 'found' based on URL query string (e.g. ?type=found).
// 4. On form submission (POST):
//    - Delegates to ItemController::handleReportSubmission().
//    - On validation success: saves item to database and redirects to item-details.php.
//    - On validation error: repopulates form fields and displays error list.
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO ADD A NEW FORM FIELD (e.g. 'Reward Amount' or 'Serial Number'):
//   1. Add database column: `ALTER TABLE items ADD COLUMN reward_amount NUMERIC(8,2);`
//   2. Add `<input type="number" name="reward_amount" class="form-control form-control-sm">` in the form below.
//   3. In app/controllers/ItemController.php, read $_POST['reward_amount'] and pass to ItemRepository::create().
// - TO CHANGE MAXIMUM UPLOAD SIZE: Check app/controllers/ItemController.php handleReportSubmission().

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/controllers/ItemController.php';

$itemController = new ItemController();
$categories = $itemController->getCategories();
$currentUser = currentUser();

// Pre-fill form state
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

// Handle POST report submission
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

        <!-- Validation Error List -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Form: multipart/form-data required for image file upload -->
        <form action="<?= url('report-item.php') ?>" method="POST" enctype="multipart/form-data" novalidate>
            <!-- 1. Report Type: Lost vs Found Toggle -->
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">REPORT TYPE</label>
                <div class="d-flex gap-2">
                    <input type="radio" class="btn-check" name="item_type" id="type_lost" value="lost" <?= $formData['item_type']==='lost'?'checked':'' ?>>
                    <label class="btn btn-outline-danger flex-fill py-2" for="type_lost">
                        <i class="fa-solid fa-circle-question me-1"></i> I Lost Something
                    </label>
                    <input type="radio" class="btn-check" name="item_type" id="type_found" value="found" <?= $formData['item_type']==='found'?'checked':'' ?>>
                    <label class="btn btn-outline-success flex-fill py-2" for="type_found">
                        <i class="fa-solid fa-hand-holding-heart me-1"></i> I Found Something
                    </label>
                </div>
            </div>

            <!-- 2. Item Title & Category Selection -->
            <div class="row g-2 mb-2">
                <div class="col-sm-7">
                    <label for="title" class="form-label small">Item Title / Name *</label>
                    <input type="text" class="form-control form-control-sm" id="title" name="title" 
                           value="<?= sanitize($formData['title']) ?>" placeholder="e.g. Blue Hydro Flask, HP Charger" required>
                </div>
                <div class="col-sm-5">
                    <label for="category_id" class="form-label small">Category *</label>
                    <select class="form-select form-select-sm" id="category_id" name="category_id" required>
                        <option value="">-- Choose Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $formData['category_id']==$cat['id']?'selected':'' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 3. Campus Location & Incident Date -->
            <div class="row g-2 mb-2">
                <div class="col-sm-7">
                    <label for="location" class="form-label small">Campus Location *</label>
                    <input type="text" class="form-control form-control-sm" id="location" name="location" 
                           value="<?= sanitize($formData['location']) ?>" placeholder="e.g. Science Library 2nd Floor" required>
                </div>
                <div class="col-sm-5">
                    <label for="event_date" class="form-label small">Date of Incident *</label>
                    <input type="date" class="form-control form-control-sm" id="event_date" name="event_date" 
                           max="<?= date('Y-m-d') ?>" value="<?= sanitize($formData['event_date']) ?>" required>
                </div>
            </div>

            <!-- 4. Description & Distinct Marks -->
            <div class="mb-2">
                <label for="description" class="form-label small">Description &amp; Identifying Details *</label>
                <textarea class="form-control form-control-sm" id="description" name="description" rows="3" 
                          placeholder="Describe color, brand, unique marks, scratches, stickers, or contents..." required><?= sanitize($formData['description']) ?></textarea>
            </div>

            <!-- 5. Photo Attachment (Optional) -->
            <div class="mb-3">
                <label for="itemImageInput" class="form-label small">Photo Attachment (Optional, JPG/PNG/WebP under 5MB)</label>
                <input type="file" class="form-control form-control-sm" id="itemImageInput" name="image" accept="image/png, image/jpeg, image/webp">
            </div>

            <!-- 6. Reporter Contact Information -->
            <h6 class="fw-bold small text-secondary border-top pt-3 mb-2">CONTACT INFORMATION</h6>
            <div class="row g-2 mb-3">
                <div class="col-sm-4">
                    <label class="form-label small">Your Name *</label>
                    <input type="text" class="form-control form-control-sm" name="contact_name" 
                           value="<?= sanitize($formData['contact_name']) ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label small">Campus Email *</label>
                    <input type="email" class="form-control form-control-sm" name="contact_email" 
                           value="<?= sanitize($formData['contact_email']) ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label small">Phone (Optional)</label>
                    <input type="tel" class="form-control form-control-sm" name="contact_phone" 
                           value="<?= sanitize($formData['contact_phone']) ?>" placeholder="+1 (555) 012-3456">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold">
                <i class="fa-solid fa-paper-plane me-1"></i> Submit Item Report
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
