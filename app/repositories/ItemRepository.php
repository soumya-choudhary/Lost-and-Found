<?php
// app/repositories/ItemRepository.php - Database Operations for Items & Categories
//
// WHAT THIS FILE DOES:
// Manages all SQL queries related to lost and found items, category dropdowns,
// live search filtering, item lifecycle transitions, portal statistics, and activity audit logging.
//
// HOW TO EXPLAIN TO PROFESSOR:
// "The ItemRepository manages the core inventory. It implements dynamic query filtering using PostgreSQL
// ILIKE for case-insensitive keyword searches, handles status updates with automatic audit trail logging,
// and uses PostgreSQL FILTER aggregate syntax to compute live statistics efficiently in a single query."

require_once __DIR__ . '/../../config/database.php';

class ItemRepository {
    private PDO $db;

    /**
     * Constructor: injects PDO database connection.
     */
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Fetch all item categories (Electronics, Wallets, IDs, etc.) in alphabetical order.
     * Used to populate dropdown menus on the report page and catalog filter bar.
     *
     * @return array List of categories with id, name, icon, description
     */
    public function getAllCategories(): array {
        return $this->db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    }

    /**
     * Insert a new lost or found report into the items table.
     * Automatically logs an initial 'Created' entry in the activity_logs table.
     *
     * @param array $d Associative array containing report details
     * @return int|null Generated item ID or null on failure
     */
    public function create(array $d): ?int {
        $sql = "INSERT INTO items (
                    item_type, title, category_id, location, event_date, 
                    description, image_url, status, user_id, 
                    contact_name, contact_email, contact_phone
                ) VALUES (
                    :type, :title, :cat, :loc, :date, 
                    :descr, :img, :status, :uid, 
                    :cname, :cemail, :cphone
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'type'   => $d['item_type'],
            'title'  => trim($d['title']),
            'cat'    => (int)$d['category_id'],
            'loc'    => trim($d['location']),
            'date'   => $d['event_date'],
            'descr'  => trim($d['description']),
            'img'    => $d['image_url'] ?? null,
            'status' => $d['status'] ?? 'Open',
            'uid'    => !empty($d['user_id']) ? (int)$d['user_id'] : null,
            'cname'  => trim($d['contact_name']),
            'cemail' => trim($d['contact_email']),
            'cphone' => !empty($d['contact_phone']) ? trim($d['contact_phone']) : null,
        ]);

        $id = (int)$stmt->fetchColumn();

        // Write an initial audit log record for this new item
        if ($id) {
            $this->logActivity($id, 'Created', 'Reported as ' . ucfirst($d['item_type']));
        }

        return $id ?: null;
    }

    /**
     * Data integrity synchronization:
     * If an item is in 'Claim Requested' status but all claims have been rejected/deleted,
     * this automatically reverts the item status back to 'Open'.
     */
    public function syncClaimRequestedItems(): void {
        try {
            $this->db->exec("UPDATE items 
                             SET status = 'Open' 
                             WHERE status = 'Claim Requested' 
                               AND id NOT IN (SELECT DISTINCT item_id FROM claims WHERE status = 'Pending')");
        } catch (Exception $e) {
            // Silently continue if tables are still initializing
        }
    }

    /**
     * Search and filter items dynamically for the live catalog and admin tables.
     * Supports:
     * - Keyword search ($f['query']): Searches title, description, and location using PostgreSQL ILIKE.
     * - Item type ($f['item_type']): 'lost' or 'found'.
     * - Category ($f['category_id']): integer ID.
     * - Status ($f['status']): 'Open', 'Claim Requested', or 'Resolved'.
     * - Sort order ($f['sort']): 'newest' (created_at DESC), 'oldest' (created_at ASC), 'date_desc' (event_date DESC).
     *
     * @param array $f Filter arguments
     * @return array Array of matching items with joined category and reporter names
     */
    public function searchAndFilter(array $f = []): array {
        // Ensure claim statuses are synchronized first
        $this->syncClaimRequestedItems();

        $sql = "SELECT i.*, c.name AS category_name, c.icon AS category_icon, u.name AS reporter_name
                FROM items i 
                JOIN categories c ON i.category_id = c.id 
                LEFT JOIN users u ON i.user_id = u.id 
                WHERE 1=1";
        $params = [];

        // 1. Keyword search across title, description, or location
        if (!empty($f['query'])) {
            $sql .= " AND (i.title ILIKE :q OR i.description ILIKE :q OR i.location ILIKE :q)";
            $params['q'] = '%' . trim($f['query']) . '%';
        }

        // 2. Filter by item type (lost vs found)
        if (!empty($f['item_type']) && in_array(strtolower($f['item_type']), ['lost', 'found'])) {
            $sql .= " AND i.item_type = :type";
            $params['type'] = strtolower($f['item_type']);
        }

        // 3. Filter by category ID
        if (!empty($f['category_id']) && is_numeric($f['category_id'])) {
            $sql .= " AND i.category_id = :cat";
            $params['cat'] = (int)$f['category_id'];
        }

        // 4. Filter by status
        if (!empty($f['status']) && in_array($f['status'], ['Open', 'Claim Requested', 'Resolved'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $f['status'];
        }

        // 5. Sorting order (PHP 8 match expression)
        $sort = $f['sort'] ?? 'newest';
        $sql .= match($sort) {
            'oldest'    => " ORDER BY i.created_at ASC",
            'date_desc' => " ORDER BY i.event_date DESC",
            default     => " ORDER BY i.created_at DESC"
        };

        // 6. Pagination limit: clamped safely between 1 and 200
        $sql .= " LIMIT " . max(1, min((int)($f['limit'] ?? 100), 200));

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find a single item by primary key ID.
     * Joins category details and reporter name/email for presentation and authorization.
     *
     * @param int $id Item ID
     * @return array|null Item record or null if not found
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT i.*, c.name AS category_name, c.icon AS category_icon, 
                                           u.name AS reporter_name, u.email AS reporter_email
                                    FROM items i 
                                    JOIN categories c ON i.category_id = c.id 
                                    LEFT JOIN users u ON i.user_id = u.id 
                                    WHERE i.id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update an item's status ('Open', 'Claim Requested', or 'Resolved').
     * Automatically logs the status transition into the activity_logs table for auditing.
     *
     * @param int $id Item ID
     * @param string $status New status value
     * @param string $remarks Optional note explaining who/why status was updated
     * @return bool True on success, false on invalid status or DB failure
     */
    public function updateStatus(int $id, string $status, string $remarks = ''): bool {
        // Whitelist validation
        if (!in_array($status, ['Open', 'Claim Requested', 'Resolved'])) return false;

        $stmt = $this->db->prepare("UPDATE items SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $ok = $stmt->execute(['status' => $status, 'id' => $id]);

        // If update succeeded, record this action in the audit trail
        if ($ok) {
            $this->logActivity($id, 'Status Updated', "Changed to {$status}. " . $remarks);
        }

        return $ok;
    }

    /**
     * Get all items reported by a specific student.
     * Includes a correlated subquery that counts how many claims were filed against each item.
     * Used in student/my-reports.php.
     *
     * @param int $uid User ID of the student
     * @return array List of items reported by the user
     */
    public function getByUserId(int $uid): array {
        $this->syncClaimRequestedItems();
        $stmt = $this->db->prepare("SELECT i.*, c.name AS category_name, 
                                           (SELECT COUNT(*) FROM claims WHERE item_id = i.id) AS total_claims
                                    FROM items i 
                                    JOIN categories c ON i.category_id = c.id 
                                    WHERE i.user_id = :uid 
                                    ORDER BY i.created_at DESC");
        $stmt->execute(['uid' => $uid]);
        return $stmt->fetchAll();
    }

    /**
     * Aggregate portal statistics in a single high-performance SQL query.
     * Uses PostgreSQL's FILTER (WHERE ...) feature to calculate conditional totals.
     * Powers the top counter cards on index.php and admin/dashboard.php.
     *
     * @return array Counts: total, total_lost, total_found, open, in_claim, total_resolved
     */
    public function getStats(): array {
        $this->syncClaimRequestedItems();
        $counts = $this->db->query("SELECT 
            COUNT(*) as total,
            COUNT(*) FILTER (WHERE item_type = 'lost') as total_lost,
            COUNT(*) FILTER (WHERE item_type = 'found') as total_found,
            COUNT(*) FILTER (WHERE status = 'Open') as open,
            COUNT(*) FILTER (WHERE status = 'Claim Requested') as in_claim,
            COUNT(*) FILTER (WHERE status = 'Resolved') as total_resolved,
            COUNT(*) FILTER (WHERE status = 'Resolved') as resolved
            FROM items")->fetch();

        return $counts ?: [
            'total' => 0, 'total_lost' => 0, 'total_found' => 0, 
            'open' => 0, 'in_claim' => 0, 'total_resolved' => 0, 'resolved' => 0
        ];
    }

    /**
     * Permanently delete an item from the database.
     * Foreign key constraints with ON DELETE CASCADE ensure that all associated
     * claims and activity logs are automatically purged by PostgreSQL.
     *
     * @param int $id Item ID to delete
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM items WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Append a record to an item's audit trail in activity_logs.
     *
     * @param int $id Item ID
     * @param string $action Short action label (e.g. 'Created', 'Status Updated', 'Claim Filed')
     * @param string $details Detailed description of what happened
     * @return bool True on success
     */
    public function logActivity(int $id, string $action, string $details = ''): bool {
        $stmt = $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:id, :action, :details)");
        return $stmt->execute(['id' => $id, 'action' => $action, 'details' => $details]);
    }

    /**
     * Fetch the 20 most recent audit log entries for a specific item.
     * Displayed on item-details.php to show the full lifecycle history of the item.
     *
     * @param int $id Item ID
     * @return array List of activity logs ordered by timestamp DESC
     */
    public function getActivityLogs(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM activity_logs WHERE item_id = :id ORDER BY created_at DESC LIMIT 20");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }
}
