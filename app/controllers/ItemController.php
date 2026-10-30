<?php
// app/controllers/ItemController.php - Item Reporting, Image Uploads & AJAX Controller
//
// WHAT THIS CONTROLLER DOES:
// 1. getCategories(): Returns all categories from ItemRepository.
// 2. handleReportSubmission():
//    - Validates required inputs (item_type, title, category, location, event_date, description, contact).
//    - Ensures event_date is not in the future.
//    - Validates uploaded image (checks extension, MIME type, 5MB file size limit).
//    - Saves report via ItemRepository::create() and redirects to item-details.php.
// 3. handleAjaxFilter():
//    - Serves the live catalog search & filter endpoint (called by public/assets/js/items.js).
//    - Returns JSON with count and pre-rendered HTML cards for instantaneous page update.
// 4. renderItemCard($item):
//    - Renders the HTML markup for an individual item card (used in both server render and AJAX).
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO CHANGE MAXIMUM UPLOAD FILE SIZE: In handleReportSubmission(), change `5 * 1024 * 1024` to 10MB or another value.
// - TO ADD ALLOWED IMAGE FORMATS (e.g. GIF):
//   In handleReportSubmission(), add 'gif' to $allowedExts and 'image/gif' to the MIME check.
// - TO MODIFY CARD DISPLAY: Edit renderItemCard() below to change what information appears on cards.

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../repositories/ItemRepository.php';

class ItemController {
    private ItemRepository $itemRepo;

    public function __construct() {
        $this->itemRepo = new ItemRepository();
    }

    // Get list of all categories for dropdown options
    public function getCategories(): array {
        return $this->itemRepo->getAllCategories();
    }

    // =========================================================================
    // ITEM REPORT SUBMISSION & FILE UPLOAD
    // =========================================================================

    // Process new lost or found report submission
    // Validates inputs and handles photo upload if present.
    // Returns ['errors' => array, 'data' => array]
    public function handleReportSubmission(): array {
        $errors = [];
        $type   = strtolower(trim($_POST['item_type'] ?? ''));
        $title  = trim($_POST['title'] ?? '');
        $catId  = (int)($_POST['category_id'] ?? 0);
        $loc    = trim($_POST['location'] ?? '');
        $date   = trim($_POST['event_date'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $name   = trim($_POST['contact_name'] ?? '');
        $email  = trim($_POST['contact_email'] ?? '');
        $phone  = trim($_POST['contact_phone'] ?? '');

        // Form Validation Rules
        if (!in_array($type, ['lost', 'found'])) {
            $errors[] = 'Please specify whether the item was Lost or Found.';
        }
        if (strlen($title) < 3) {
            $errors[] = 'Item title must be at least 3 characters.';
        }
        if ($catId <= 0) {
            $errors[] = 'Please select a valid item category.';
        }
        if (empty($loc)) {
            $errors[] = 'Campus location is required.';
        }
        // Ensure date is not in future
        if (empty($date) || strtotime($date) > time()) {
            $errors[] = 'Event date cannot be in the future.';
        }
        if (strlen($desc) < 5) {
            $errors[] = 'Please provide a description (minimum 5 characters).';
        }
        if (empty($name)) {
            $errors[] = 'Contact name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid contact email is required.';
        }

        // Image Upload Processing
        $imagePath = null;
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp']; // TO ALLOW GIF: add 'gif'

            // TO CHANGE MAX UPLOAD SIZE: Edit 5 * 1024 * 1024 (5MB) below
            if (in_array($ext, $allowedExts) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $dir = dirname(__DIR__, 2) . '/uploads/items';
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                // Cryptographically random unique filename to prevent collisions or overwrites
                $file = 'item_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $file)) {
                    $imagePath = 'uploads/items/' . $file;
                }
            } else {
                $errors[] = 'Invalid image. Please upload a JPG, PNG, or WebP photo under 5MB.';
            }
        }

        // Save report to database if validation passed
        if (empty($errors)) {
            $user = currentUser();
            $id = $this->itemRepo->create([
                'item_type'     => $type,
                'title'         => $title,
                'category_id'   => $catId,
                'location'      => $loc,
                'event_date'    => $date,
                'description'   => $desc,
                'image_url'     => $imagePath,
                'status'        => 'Open',
                'user_id'       => $user['id'] ?? null,
                'contact_name'  => $name,
                'contact_email' => $email,
                'contact_phone' => $phone
            ]);

            if ($id) {
                setFlash('success', "Item report #{$id} created successfully!");
                redirect('item-details.php?id=' . $id);
            }
            $errors[] = 'Database error: Could not save item report. Please try again.';
        }

        return [
            'errors' => $errors,
            'data'   => [
                'item_type'     => $type,
                'title'         => $title,
                'category_id'   => $catId,
                'location'      => $loc,
                'event_date'    => $date,
                'description'   => $desc,
                'contact_name'  => $name,
                'contact_email' => $email,
                'contact_phone' => $phone
            ]
        ];
    }

    // =========================================================================
    // AJAX CATALOG FILTERING & RENDERING
    // =========================================================================

    // Endpoint for AJAX requests from public/assets/js/items.js
    // Queries ItemRepository and returns rendered HTML cards in a JSON package.
    public function handleAjaxFilter(): void {
        header('Content-Type: application/json; charset=utf-8');

        $items = $this->itemRepo->searchAndFilter([
            'query'       => $_GET['q'] ?? '',
            'item_type'   => $_GET['type'] ?? '',
            'category_id' => $_GET['category'] ?? '',
            'status'      => $_GET['status'] ?? '',
            'sort'        => $_GET['sort'] ?? 'newest'
        ]);

        // Buffer rendered card HTML
        ob_start();
        if (empty($items)) {
            echo '<div class="col-12 text-center py-5 text-muted"><p class="mb-0">No matching items found. Try different filters.</p></div>';
        } else {
            foreach ($items as $item) {
                $this->renderItemCard($item);
            }
        }
        $html = ob_get_clean();

        echo json_encode(['success' => true, 'count' => count($items), 'html' => $html]);
        exit;
    }

    // Render HTML markup for a single item card
    // Used during initial server render (index.php) and dynamically during AJAX updates.
    // TO MODIFY CARD DESIGN: Edit the HTML snippet below.
    public function renderItemCard(array $item): void {
        $typeBadge   = typeBadgeClass($item['item_type']);
        $statusBadge = statusBadgeClass($item['status']);
        $img         = itemImageUrl($item['image_url'] ?? null);
        ?>
        <div class="col-sm-6 col-lg-4 col-xl-3 item-card-wrapper">
            <div class="card h-100 border shadow-sm rounded-3 overflow-hidden item-card bg-white" 
                 data-href="<?= url('item-details.php?id=' . $item['id']) ?>" role="button">
                <!-- Image Container with Type & Status Badges -->
                <div class="position-relative bg-light text-center" style="height: 180px;">
                    <img src="<?= $img ?>" class="w-100 h-100 object-fit-cover" alt="<?= sanitize($item['title']) ?>">
                    <span class="badge position-absolute top-0 start-0 m-2 <?= $typeBadge ?> text-uppercase"><?= sanitize($item['item_type']) ?></span>
                    <span class="badge position-absolute top-0 end-0 m-2 <?= $statusBadge ?>"><?= sanitize($item['status']) ?></span>
                </div>

                <!-- Card Content -->
                <div class="card-body d-flex flex-column p-3">
                    <small class="text-primary fw-semibold mb-1"><?= sanitize($item['category_name']) ?></small>
                    <h6 class="card-title fw-bold text-truncate mb-1"><?= sanitize($item['title']) ?></h6>
                    <p class="card-text text-muted small line-clamp-2 mb-3"><?= sanitize($item['description']) ?></p>
                    
                    <!-- Metadata Footer -->
                    <div class="mt-auto pt-2 border-top small text-muted d-flex justify-content-between">
                        <span><i class="fa-solid fa-location-dot me-1"></i><?= sanitize($item['location']) ?></span>
                        <span><?= date('M d', strtotime($item['event_date'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function getItem(int $id): ?array { 
        return $this->itemRepo->findById($id); 
    }
}
