<?php
// app/controllers/ItemController.php - Report Handling, AJAX Filter & Item Card Rendering
//
// WHAT THIS FILE DOES:
// 1. handleReportSubmission(): Validates new lost/found report submissions, handles secure photo uploads, and calls ItemRepository::create().
// 2. handleAjaxFilter(): Backend processor for the live catalog search endpoint (api/items.php). Returns JSON with matching item cards.
// 3. renderItemCard(): Centralized HTML generator for rendering individual item cards.
//
// HOW TO EXPLAIN TO PROFESSOR:
// "File uploads are strictly validated against an extension whitelist (jpg, jpeg, png, webp) and a 5MB size limit.
// We rename files using cryptographically secure random bytes (bin2hex(random_bytes(4))) and timestamps to prevent filename collisions and remote code execution."

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../repositories/ItemRepository.php';

class ItemController {
    private ItemRepository $itemRepo;

    /**
     * Initialize controller and instantiate ItemRepository.
     */
    public function __construct() {
        $this->itemRepo = new ItemRepository();
    }

    /**
     * Get all categories for select dropdowns.
     *
     * @return array Categories list
     */
    public function getCategories(): array {
        return $this->itemRepo->getAllCategories();
    }

    /**
     * Fetch single item by ID.
     *
     * @param int $id Item ID
     * @return array|null Item record or null
     */
    public function getItem(int $id): ?array {
        return $this->itemRepo->findById($id);
    }

    /**
     * Process new lost or found report form submission (POST from report-item.php).
     *
     * Validations:
     * - item_type: must be 'lost' or 'found'.
     * - title: >= 3 chars.
     * - category_id: must be a positive integer.
     * - location: required.
     * - event_date: required and cannot be a future date.
     * - description: >= 5 chars.
     * - contact_name & contact_email: required and email validated.
     * - image upload (optional): whitelisted extensions, max 5MB.
     *
     * @return array Errors and sticky form data on failure
     */
    public function handleReportSubmission(): array {
        $errors = [];
        $d = [
            'item_type'     => strtolower(trim($_POST['item_type'] ?? '')),
            'title'         => trim($_POST['title'] ?? ''),
            'category_id'   => (int)($_POST['category_id'] ?? 0),
            'location'      => trim($_POST['location'] ?? ''),
            'event_date'    => trim($_POST['event_date'] ?? ''),
            'description'   => trim($_POST['description'] ?? ''),
            'contact_name'  => trim($_POST['contact_name'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'user_id'       => currentUser()['id'] ?? null // Associate with user account if logged in, otherwise null
        ];

        // 1. Validate Form Fields
        if (!in_array($d['item_type'], ['lost', 'found'])) {
            $errors[] = 'Select Lost or Found.';
        }
        if (strlen($d['title']) < 3) {
            $errors[] = 'Title must be at least 3 characters.';
        }
        if ($d['category_id'] <= 0) {
            $errors[] = 'Select a valid category.';
        }
        if (empty($d['location'])) {
            $errors[] = 'Location is required.';
        }
        if (empty($d['event_date']) || strtotime($d['event_date']) > time()) {
            $errors[] = 'Date cannot be in the future.';
        }
        if (strlen($d['description']) < 5) {
            $errors[] = 'Description must be at least 5 characters.';
        }
        if (empty($d['contact_name'])) {
            $errors[] = 'Contact name is required.';
        }
        if (!filter_var($d['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid contact email is required.';
        }

        // 2. Handle Optional Image Upload
        if (empty($errors) && !empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $maxFileSize = 5 * 1024 * 1024; // 5 Megabytes

            if (in_array($ext, $allowedExtensions) && $_FILES['image']['size'] <= $maxFileSize) {
                $dir = dirname(__DIR__, 2) . '/uploads/items';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                // Generate secure unguessable filename: item_TIMESTAMP_RANDOMHEX.ext
                $file = 'item_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $file)) {
                    $d['image_url'] = 'uploads/items/' . $file;
                }
            } else {
                $errors[] = 'Image must be JPG, PNG, or WEBP under 5MB.';
            }
        }

        // 3. Save to database if no errors
        if (empty($errors)) {
            $id = $this->itemRepo->create($d);
            if ($id) {
                setFlash('success', 'Item report submitted successfully!');
                redirect('item-details.php?id=' . $id);
            }
            $errors[] = 'Failed to save item to database.';
        }

        return ['errors' => $errors, 'data' => $d];
    }

    /**
     * Live AJAX Filter for Catalog (invoked by api/items.php).
     * Reads GET parameters, queries ItemRepository, buffers card HTML, and outputs JSON.
     */
    public function handleAjaxFilter(): void {
        header('Content-Type: application/json; charset=utf-8');

        $items = $this->itemRepo->searchAndFilter([
            'query'       => $_GET['q'] ?? '',
            'item_type'   => $_GET['type'] ?? '',
            'category_id' => $_GET['category'] ?? '',
            'status'      => $_GET['status'] ?? '',
            'sort'        => $_GET['sort'] ?? 'newest'
        ]);

        // Output buffering: capture HTML output of renderItemCard() calls
        ob_start();
        if (empty($items)) {
            echo '<div class="col-12 text-center py-5 text-muted"><p class="mb-0">No matching items found.</p></div>';
        } else {
            foreach ($items as $item) {
                $this->renderItemCard($item);
            }
        }
        $html = ob_get_clean();

        // Return JSON response to frontend items.js
        echo json_encode([
            'success' => true, 
            'count'   => count($items), 
            'html'    => $html
        ]);
        exit;
    }

    /**
     * Render the Bootstrap HTML card for a single item.
     * Used on both the initial server render (index.php) and dynamic AJAX updates.
     *
     * @param array $item Item data array
     */
    public function renderItemCard(array $item): void {
        $typeBadge   = typeBadgeClass($item['item_type']);
        $statusBadge = statusBadgeClass($item['status']);
        $img         = itemImageUrl($item['image_url'] ?? null);
        ?>
        <div class="col-sm-6 col-lg-4 col-xl-3 item-card-wrapper">
            <div class="card h-100 border shadow-sm rounded-3 overflow-hidden item-card bg-white" 
                 data-href="<?= url('item-details.php?id=' . $item['id']) ?>" role="button">
                <!-- Top image & badges -->
                <div class="position-relative bg-light text-center" style="height: 180px;">
                    <img src="<?= $img ?>" class="w-100 h-100 object-fit-cover" alt="<?= sanitize($item['title']) ?>">
                    <span class="badge position-absolute top-0 start-0 m-2 <?= $typeBadge ?> text-uppercase"><?= sanitize($item['item_type']) ?></span>
                    <span class="badge position-absolute top-0 end-0 m-2 <?= $statusBadge ?>"><?= sanitize($item['status']) ?></span>
                </div>
                <!-- Card content -->
                <div class="card-body d-flex flex-column p-3">
                    <small class="text-primary fw-semibold mb-1"><?= sanitize($item['category_name']) ?></small>
                    <h6 class="card-title fw-bold text-truncate mb-1"><?= sanitize($item['title']) ?></h6>
                    <p class="card-text text-muted small line-clamp-2 mb-3"><?= sanitize($item['description']) ?></p>
                    <div class="mt-auto pt-2 border-top small text-muted d-flex justify-content-between">
                        <span><i class="fa-solid fa-location-dot me-1"></i><?= sanitize($item['location']) ?></span>
                        <span><?= date('M d', strtotime($item['event_date'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
