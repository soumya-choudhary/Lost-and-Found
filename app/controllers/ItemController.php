<?php
// app/controllers/ItemController.php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../repositories/ItemRepository.php';

class ItemController {
    private ItemRepository $itemRepo;

    public function __construct() {
        $this->itemRepo = new ItemRepository();
    }

    /**
     * Get categories list
     */
    public function getCategories(): array {
        return $this->itemRepo->getAllCategories();
    }

    /**
     * Handle item reporting submission
     */
    public function handleReportSubmission(): array {
        $errors = [];
        $itemType    = strtolower(trim($_POST['item_type'] ?? ''));
        $title       = trim($_POST['title'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $location    = trim($_POST['location'] ?? '');
        $eventDate   = trim($_POST['event_date'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contactName = trim($_POST['contact_name'] ?? '');
        $contactEmail= trim($_POST['contact_email'] ?? '');
        $contactPhone= trim($_POST['contact_phone'] ?? '');

        // Validation
        if (!in_array($itemType, ['lost', 'found'])) {
            $errors[] = 'Please specify whether the item was Lost or Found.';
        }

        if (empty($title) || strlen($title) < 3) {
            $errors[] = 'Item title is required (at least 3 characters).';
        }

        if ($categoryId <= 0) {
            $errors[] = 'Please select a valid item category.';
        }

        if (empty($location)) {
            $errors[] = 'Campus location is required (e.g. Science Library 2nd Floor).';
        }

        if (empty($eventDate)) {
            $errors[] = 'Date is required.';
        } elseif (strtotime($eventDate) > time()) {
            $errors[] = 'Event date cannot be in the future.';
        }

        if (empty($description) || strlen($description) < 10) {
            $errors[] = 'Please provide a detailed description (at least 10 characters).';
        }

        if (empty($contactName)) {
            $errors[] = 'Contact name is required.';
        }

        if (empty($contactEmail) || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid contact email is required.';
        }

        // Image Upload Processing
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'An error occurred during image upload.';
            } else {
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                if (!in_array($ext, $allowedExts) || !in_array($mimeType, $allowedMimes)) {
                    $errors[] = 'Invalid image type. Only JPG, PNG, and WebP are supported.';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $errors[] = 'Image size exceeds maximum limit of 5MB.';
                } else {
                    $uploadDir = dirname(__DIR__, 2) . '/uploads/items';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = 'item_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $targetPath = $uploadDir . '/' . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $imagePath = 'uploads/items/' . $fileName;
                    } else {
                        $errors[] = 'Failed to save uploaded image. Check folder permissions.';
                    }
                }
            }
        }

        // Save if no validation errors
        if (empty($errors)) {
            $user = currentUser();
            $userId = $user ? $user['id'] : null;

            $newItemId = $this->itemRepo->create([
                'item_type'     => $itemType,
                'title'         => $title,
                'category_id'   => $categoryId,
                'location'      => $location,
                'event_date'    => $eventDate,
                'description'   => $description,
                'image_url'     => $imagePath,
                'status'        => 'Open',
                'user_id'       => $userId,
                'contact_name'  => $contactName,
                'contact_email' => $contactEmail,
                'contact_phone' => $contactPhone
            ]);

            if ($newItemId) {
                setFlash('success', "Item report created successfully! Report Reference ID: #{$newItemId}");
                redirect('item-details.php?id=' . $newItemId);
            } else {
                $errors[] = 'Database error: Could not save item report. Please try again.';
            }
        }

        return [
            'errors' => $errors,
            'data'   => [
                'item_type'     => $itemType,
                'title'         => $title,
                'category_id'   => $categoryId,
                'location'      => $location,
                'event_date'    => $eventDate,
                'description'   => $description,
                'contact_name'  => $contactName,
                'contact_email' => $contactEmail,
                'contact_phone' => $contactPhone
            ]
        ];
    }

    /**
     * AJAX Live Filter & Search Endpoint
     */
    public function handleAjaxFilter(): void {
        header('Content-Type: application/json; charset=utf-8');

        $filters = [
            'query'       => $_GET['q'] ?? '',
            'item_type'   => $_GET['type'] ?? '',
            'category_id' => $_GET['category'] ?? '',
            'status'      => $_GET['status'] ?? '',
            'sort'        => $_GET['sort'] ?? 'newest'
        ];

        $items = $this->itemRepo->searchAndFilter($filters);

        // Generate rendered HTML cards for dynamic jQuery insertion
        ob_start();
        if (empty($items)) {
            ?>
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 border shadow-sm">
                    <div class="text-secondary display-4 mb-3">
                        <i class="fa-solid fa-magnifying-glass-chart"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Matching Items Found</h5>
                    <p class="text-muted small mb-3">Try adjusting your keyword, resetting filters, or changing categories.</p>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="reset-filters-btn">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset All Filters
                    </button>
                </div>
            </div>
            <?php
        } else {
            foreach ($items as $item) {
                $this->renderItemCard($item);
            }
        }
        $html = ob_get_clean();

        echo json_encode([
            'success' => true,
            'count'   => count($items),
            'items'   => $items,
            'html'    => $html
        ]);
        exit;
    }

    /**
     * Render a single item card
     */
    public function renderItemCard(array $item): void {
        $typeBadgeClass = ($item['item_type'] === 'lost') ? 'bg-danger text-white' : 'bg-success text-white';
        $typeIcon = ($item['item_type'] === 'lost') ? 'fa-circle-question' : 'fa-hand-holding-heart';

        $statusBadgeClass = 'badge-status-open';
        if ($item['status'] === 'Claim Requested') {
            $statusBadgeClass = 'badge-status-claim-requested';
        } elseif ($item['status'] === 'Resolved') {
            $statusBadgeClass = 'badge-status-resolved';
        }

        $imageSrc = !empty($item['image_url']) ? url($item['image_url']) : url('public/assets/img/placeholder.svg');
        ?>
        <div class="col-sm-6 col-lg-4 col-xl-3 item-card-wrapper" data-item-id="<?= $item['id'] ?>">
            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden item-card bg-white position-relative" data-href="<?= url('item-details.php?id=' . $item['id']) ?>" role="button">
                <!-- Image Header with badges -->
                <div class="item-img-container position-relative bg-light">
                    <img src="<?= $imageSrc ?>" class="card-img-top item-thumbnail" alt="<?= sanitize($item['title']) ?>"
                         onerror="this.src='<?= url('public/assets/img/placeholder.svg') ?>'">
                    <span class="badge position-absolute top-0 start-0 m-2 px-2 py-1 rounded-pill <?= $typeBadgeClass ?> shadow-sm">
                        <i class="fa-solid <?= $typeIcon ?> me-1"></i> <?= strtoupper($item['item_type']) ?>
                    </span>
                    <span class="badge position-absolute top-0 end-0 m-2 px-2 py-1 rounded-pill <?= $statusBadgeClass ?> shadow-sm">
                        <?= sanitize($item['status']) ?>
                    </span>
                </div>

                <!-- Card Body -->
                <div class="card-body d-flex flex-column p-3">
                    <div class="d-flex align-items-center gap-1 text-primary small fw-semibold mb-1">
                        <i class="fa-solid <?= sanitize($item['category_icon'] ?? 'fa-box') ?> me-1"></i>
                        <span><?= sanitize($item['category_name']) ?></span>
                    </div>

                    <h6 class="card-title fw-bold text-dark text-truncate mb-2" title="<?= sanitize($item['title']) ?>">
                        <?= sanitize($item['title']) ?>
                    </h6>

                    <p class="card-text text-muted small line-clamp-2 mb-3">
                        <?= sanitize($item['description']) ?>
                    </p>

                    <!-- Meta Details -->
                    <div class="mt-auto border-top pt-2">
                        <div class="d-flex align-items-center text-muted small mb-1">
                            <i class="fa-solid fa-location-dot text-danger me-2"></i>
                            <span class="text-truncate"><?= sanitize($item['location']) ?></span>
                        </div>
                        <div class="d-flex align-items-center text-muted small mb-3">
                            <i class="fa-regular fa-calendar text-secondary me-2"></i>
                            <span><?= date('M d, Y', strtotime($item['event_date'])) ?></span>
                        </div>

                        <!-- Action Button -->
                        <div class="d-grid">
                            <a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold">
                                View Details &amp; Claims <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get item details
     */
    public function getItem(int $id): ?array {
        return $this->itemRepo->findById($id);
    }
}

