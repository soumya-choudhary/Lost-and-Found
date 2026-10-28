<?php
// report-item.php - Report Lost or Found Belonging

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/controllers/ItemController.php';

$itemController = new ItemController();
$categories = $itemController->getCategories();

$currentUser = currentUser();

// Pre-fill contact details if authenticated
$defaultType = isset($_GET['type']) && in_array(strtolower($_GET['type']), ['lost', 'found']) ? strtolower($_GET['type']) : 'lost';

$formData = [
    'item_type'     => $defaultType,
    'title'         => '',
    'category_id'   => '',
    'location'      => '',
    'event_date'    => date('Y-m-d'),
    'description'   => '',
    'contact_name'  => $currentUser ? $currentUser['name'] : '',
    'contact_email' => $currentUser ? $currentUser['email'] : '',
    'contact_phone' => ''
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $itemController->handleReportSubmission();
    $errors = $result['errors'];
    $formData = $result['data'];
}

$pageTitle = 'Report an Item';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Report an Item</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <!-- Header -->
                <div class="p-4 bg-primary text-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="auth-icon-circle bg-white text-primary mb-0">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">Campus Item Report</h4>
                            <p class="mb-0 small opacity-75">Provide accurate details to assist in quick identification and recovery</p>
                        </div>
                    </div>
                </div>

                <!-- Form Body -->
                <div class="p-4 p-md-5">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2 small mb-4">
                            <div class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Please correct the following:</div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= sanitize($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('report-item.php') ?>" method="POST" enctype="multipart/form-data" id="reportItemForm" novalidate>
                        
                        <!-- 1. Report Type Toggle -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary text-uppercase mb-2">Report Type *</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="item_type" id="type_lost" value="lost" <?= ($formData['item_type'] === 'lost') ? 'checked' : '' ?> required>
                                    <label class="btn btn-outline-danger w-100 py-3 rounded-3 d-flex flex-column align-items-center gap-1" for="type_lost">
                                        <i class="fa-solid fa-circle-question fs-3"></i>
                                        <span class="fw-bold">I Lost Something</span>
                                        <small class="text-muted">Report an item you misplaced</small>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="item_type" id="type_found" value="found" <?= ($formData['item_type'] === 'found') ? 'checked' : '' ?> required>
                                    <label class="btn btn-outline-success w-100 py-3 rounded-3 d-flex flex-column align-items-center gap-1" for="type_found">
                                        <i class="fa-solid fa-hand-holding-heart fs-3"></i>
                                        <span class="fw-bold">I Found Something</span>
                                        <small class="text-muted">Report an item you picked up</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Basic Item Information -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label for="title" class="form-label fw-semibold small text-secondary">Item Title / Name *</label>
                                <div class="input-icon-group">
                                    <i class="fa-solid fa-tag form-icon"></i>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           placeholder="e.g. Blue Hydro Flask Water Bottle, HP Laptop Charger" 
                                           value="<?= sanitize($formData['title']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <label for="category_id" class="form-label fw-semibold small text-secondary">Category *</label>
                                <select class="form-select form-control" id="category_id" name="category_id" required style="height: 48px; border-radius: 10px;">
                                    <option value="">-- Choose Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($formData['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= sanitize($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- 3. Campus Location & Date -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label for="location" class="form-label fw-semibold small text-secondary">Campus Location *</label>
                                <div class="input-icon-group">
                                    <i class="fa-solid fa-location-dot form-icon"></i>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="e.g. Science Library 2nd Floor, Room 204, Cafeteria" 
                                           value="<?= sanitize($formData['location']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <label for="event_date" class="form-label fw-semibold small text-secondary">Date of Incident *</label>
                                <div class="input-icon-group">
                                    <i class="fa-regular fa-calendar form-icon"></i>
                                    <input type="date" class="form-control" id="event_date" name="event_date" 
                                           max="<?= date('Y-m-d') ?>" value="<?= sanitize($formData['event_date']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Detailed Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold small text-secondary">Detailed Description *</label>
                            <textarea class="form-control rounded-3" id="description" name="description" rows="4" 
                                      placeholder="Include distinct identifiers, brand, color, scratches, stickers, contents, or serial hints..." required><?= sanitize($formData['description']) ?></textarea>
                            <div class="form-text small">Detailed features help verify the legitimate owner during claim inspection.</div>
                        </div>

                        <!-- 5. Photo Upload with Live jQuery Preview -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-secondary">Photo of Item (Optional, Recommended)</label>
                            <div class="border rounded-4 p-4 text-center bg-light image-dropzone position-relative" id="dropzone">
                                <input type="file" name="image" id="item_image_input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" accept="image/png, image/jpeg, image/webp">
                                <div id="upload_prompt">
                                    <div class="text-primary fs-2 mb-2"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                    <div class="fw-semibold text-dark">Click or Drag &amp; Drop to upload item photo</div>
                                    <small class="text-muted">PNG, JPG, or WebP up to 5MB</small>
                                </div>
                                <div id="preview_container" class="d-none mt-2">
                                    <img id="image_preview" src="" alt="Preview" class="img-thumbnail rounded-3 shadow-sm" style="max-height: 180px;">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="remove_image_btn">
                                            <i class="fa-solid fa-trash me-1"></i> Remove Image
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-muted">

                        <!-- 6. Reporter Contact Information -->
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-address-book me-2 text-primary"></i>Contact Information</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="contact_name" class="form-label fw-semibold small text-secondary">Your Name *</label>
                                <input type="text" class="form-control rounded-3" id="contact_name" name="contact_name" 
                                       value="<?= sanitize($formData['contact_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="contact_email" class="form-label fw-semibold small text-secondary">Campus Email *</label>
                                <input type="email" class="form-control rounded-3" id="contact_email" name="contact_email" 
                                       value="<?= sanitize($formData['contact_email']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="contact_phone" class="form-label fw-semibold small text-secondary">Phone Number (Optional)</label>
                                <input type="tel" class="form-control rounded-3" id="contact_phone" name="contact_phone" 
                                       value="<?= sanitize($formData['contact_phone']) ?>">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?= url('index.php') ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm fw-semibold">
                                <i class="fa-solid fa-paper-plane me-2"></i> Submit Item Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php 
$extraScripts = '<script src="' . url('public/assets/js/items.js') . '"></script>';
require_once __DIR__ . '/layouts/footer.php'; 
?>

