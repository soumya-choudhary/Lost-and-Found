<?php
// app/repositories/ItemRepository.php

require_once __DIR__ . '/../../config/database.php';

class ItemRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Get all categories for filter dropdowns
     */
    public function getAllCategories(): array {
        $stmt = $this->db->query("SELECT id, name, icon, description FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Create a new item report
     */
    public function create(array $data): ?int {
        $sql = "INSERT INTO items (
                    item_type, title, category_id, location, event_date,
                    description, image_url, status, user_id, contact_name,
                    contact_email, contact_phone
                ) VALUES (
                    :item_type, :title, :category_id, :location, :event_date,
                    :description, :image_url, :status, :user_id, :contact_name,
                    :contact_email, :contact_phone
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'item_type'     => $data['item_type'],
            'title'         => trim($data['title']),
            'category_id'   => (int)$data['category_id'],
            'location'      => trim($data['location']),
            'event_date'    => $data['event_date'],
            'description'   => trim($data['description']),
            'image_url'     => $data['image_url'] ?? null,
            'status'        => $data['status'] ?? 'Open',
            'user_id'       => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            'contact_name'  => trim($data['contact_name']),
            'contact_email' => trim($data['contact_email']),
            'contact_phone' => !empty($data['contact_phone']) ? trim($data['contact_phone']) : null,
        ]);

        $itemId = $stmt->fetchColumn();

        if ($itemId) {
            // Log creation activity
            $this->logActivity((int)$itemId, 'Created', "Item reported as {$data['item_type']}.");
        }

        return $itemId ? (int)$itemId : null;
    }

    /**
     * Search and filter items with dynamic conditions
     */
    public function searchAndFilter(array $filters = []): array {
        $sql = "SELECT i.*, 
                       c.name AS category_name, 
                       c.icon AS category_icon,
                       u.name AS reporter_name
                FROM items i
                JOIN categories c ON i.category_id = c.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE 1=1";

        $params = [];

        // Search Keyword (in title, description, or location)
        if (!empty($filters['query'])) {
            $sql .= " AND (i.title ILIKE :q OR i.description ILIKE :q OR i.location ILIKE :q)";
            $params['q'] = '%' . trim($filters['query']) . '%';
        }

        // Item Type ('lost' or 'found')
        if (!empty($filters['item_type']) && in_array(strtolower($filters['item_type']), ['lost', 'found'])) {
            $sql .= " AND i.item_type = :item_type";
            $params['item_type'] = strtolower($filters['item_type']);
        }

        // Category Filter
        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $sql .= " AND i.category_id = :category_id";
            $params['category_id'] = (int)$filters['category_id'];
        }

        // Status Filter ('Open', 'Claim Requested', 'Resolved')
        if (!empty($filters['status']) && in_array($filters['status'], ['Open', 'Claim Requested', 'Resolved'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $filters['status'];
        }

        // Date Range
        if (!empty($filters['date_from'])) {
            $sql .= " AND i.event_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND i.event_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'oldest':
                $sql .= " ORDER BY i.created_at ASC";
                break;
            case 'date_desc':
                $sql .= " ORDER BY i.event_date DESC, i.created_at DESC";
                break;
            case 'date_asc':
                $sql .= " ORDER BY i.event_date ASC, i.created_at ASC";
                break;
            case 'title_asc':
                $sql .= " ORDER BY i.title ASC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY i.created_at DESC";
                break;
        }

        // Limit results
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 100;
        $sql .= " LIMIT " . max(1, min($limit, 200));

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find item by primary ID
     */
    public function findById(int $id): ?array {
        $sql = "SELECT i.*, 
                       c.name AS category_name, 
                       c.icon AS category_icon,
                       c.description AS category_desc,
                       u.name AS reporter_name,
                       u.email AS reporter_email
                FROM items i
                JOIN categories c ON i.category_id = c.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE i.id = :id 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    /**
     * Update Item Status (Open, Claim Requested, Resolved)
     */
    public function updateStatus(int $id, string $newStatus, string $remarks = ''): bool {
        if (!in_array($newStatus, ['Open', 'Claim Requested', 'Resolved'])) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE items SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $success = $stmt->execute([
            'status' => $newStatus,
            'id'     => $id
        ]);

        if ($success) {
            $this->logActivity($id, 'Status Updated', "Status changed to {$newStatus}. " . $remarks);
        }

        return $success;
    }

    /**
     * Get items reported by a specific user
     */
    public function getByUserId(int $userId): array {
        $sql = "SELECT i.*, c.name AS category_name, c.icon AS category_icon,
                       (SELECT COUNT(*) FROM claims WHERE item_id = i.id) AS total_claims
                FROM items i
                JOIN categories c ON i.category_id = c.id
                WHERE i.user_id = :user_id
                ORDER BY i.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Overall portal statistics
     */
    public function getStats(): array {
        $sql = "SELECT 
                    COUNT(*) AS total_items,
                    COUNT(*) FILTER (WHERE item_type = 'lost') AS total_lost,
                    COUNT(*) FILTER (WHERE item_type = 'found') AS total_found,
                    COUNT(*) FILTER (WHERE status = 'Open') AS total_open,
                    COUNT(*) FILTER (WHERE status = 'Claim Requested') AS total_claim_requested,
                    COUNT(*) FILTER (WHERE status = 'Resolved') AS total_resolved
                FROM items";

        $stmt = $this->db->query($sql);
        return $stmt->fetch() ?: [
            'total_items' => 0,
            'total_lost' => 0,
            'total_found' => 0,
            'total_open' => 0,
            'total_claim_requested' => 0,
            'total_resolved' => 0
        ];
    }

    /**
     * Audit log activity for an item
     */
    public function logActivity(int $itemId, string $action, string $details = ''): void {
        try {
            $stmt = $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:item_id, :action, :details)");
            $stmt->execute([
                'item_id' => $itemId,
                'action'  => $action,
                'details' => $details
            ]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}

