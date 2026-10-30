<?php
// ====================================================================================
// student/my-reports.php - Items Reported by the Current Student
// ====================================================================================
// Displays a full tabular breakdown of all items reported as Lost or Found by the user.
// Features:
// - Shows item type (LOST / FOUND), category, incident location, and date.
// - Status badge (Open, Claim Requested, Resolved).
// - Interactive claims counter badge: If claims have been filed on the item, clicking
//   takes the user directly to the received claims management section on item-details.php.
//
// ------------------------------------------------------------------------------------
// HOW TO MODIFY / PROFESSOR TASKS:
// - TO ADD A COLUMN (e.g. Item ID): Add <th>#</th> in the <thead> and <td><?= $item['id'] ?></td> in <tbody>.
// - TO FILTER BY ITEM TYPE: Add array_filter($items, fn($i) => $i['item_type'] === 'lost') before rendering.
// - TO ALLOW QUICK REPORT DELETION: Add a delete button pointing to an API endpoint with ownership verification.
// ====================================================================================

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

// Route Guard: Ensures only authenticated users can view their personal reports
AuthMiddleware::check();

$currentUser = currentUser();
// Fetch all items submitted by this user's primary key ID
$items = (new ItemRepository())->getByUserId($currentUser['id']);

$pageTitle = 'My Reports';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-3">
    <!-- Header with Quick Action -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">My Reported Items</h4>
        <a href="<?= url('report-item.php') ?>" class="btn btn-primary btn-sm rounded-pill px-3">+ Report Item</a>
    </div>

    <div class="card border shadow-sm rounded-3 overflow-hidden bg-white">
        <?php if (empty($items)): ?>
            <div class="p-4 text-center text-muted">You have not reported any items yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Item</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Claims</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <!-- Item Title & Direct Link to Details -->
                                <td class="ps-3 fw-bold">
                                    <a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="text-dark text-decoration-none">
                                        <?= sanitize($item['title']) ?>
                                    </a>
                                </td>

                                <!-- Item Type (Lost vs Found) -->
                                <td>
                                    <span class="badge <?= typeBadgeClass($item['item_type']) ?>">
                                        <?= strtoupper($item['item_type']) ?>
                                    </span>
                                </td>

                                <!-- Category & Location -->
                                <td><?= sanitize($item['category_name']) ?></td>
                                <td><?= sanitize($item['location']) ?></td>

                                <!-- Incident Date (Formatted as 'M d, Y') -->
                                <td><?= date('M d, Y', strtotime($item['event_date'])) ?></td>

                                <!-- Current Workflow Status -->
                                <td>
                                    <span class="badge <?= statusBadgeClass($item['status']) ?>">
                                        <?= sanitize($item['status']) ?>
                                    </span>
                                </td>

                                <!-- Total Claims Filed by Other Users: Links directly to claim review anchor on details page -->
                                <td>
                                    <?php if ($item['total_claims'] > 0): ?>
                                        <a href="<?= url('item-details.php?id=' . $item['id']) ?>#claims-received-section" class="badge bg-warning text-dark text-decoration-none">
                                            <?= $item['total_claims'] ?> claim(s)
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Details View Button -->
                                <td class="text-end pe-3">
                                    <a href="<?= url('item-details.php?id=' . $item['id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
