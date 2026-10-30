<?php
// app/repositories/ItemRepository.php - Item, Category & Activity DB operations

require_once __DIR__ . '/../../config/database.php';

class ItemRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    // Get all categories for dropdown filters
    public function getAllCategories(): array {
        return $this->db->query("SELECT id, name, icon, description FROM categories ORDER BY name ASC")->fetchAll();
    }

    // Create a new lost/found report & log activity
    public function create(array $data): ?int {
        $sql = "INSERT INTO items (item_type, title, category_id, location, event_date, description, image_url, status, user_id, contact_name, contact_email, contact_phone)
                VALUES (:item_type, :title, :category_id, :location, :event_date, :description, :image_url, :status, :user_id, :contact_name, :contact_email, :contact_phone)
                RETURNING id";
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
        $id = $stmt->fetchColumn();
        if ($id) $this->logActivity((int)$id, 'Created', "Item reported as " . ucfirst($data['item_type']));
        return $id ? (int)$id : null;
    }

    // Search and filter items dynamically
    public function searchAndFilter(array $filters = []): array {
        $sql = "SELECT i.*, c.name AS category_name, c.icon AS category_icon, u.name AS reporter_name
                FROM items i
                JOIN categories c ON i.category_id = c.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE 1=1";
        $params = [];

        // 1. Keyword search
        if (!empty($filters['query'])) {
            $sql .= " AND (i.title ILIKE :q OR i.description ILIKE :q OR i.location ILIKE :q)";
            $params['q'] = '%' . trim($filters['query']) . '%';
        }
        // 2. Type filter ('lost' or 'found')
        if (!empty($filters['item_type']) && in_array(strtolower($filters['item_type']), ['lost', 'found'])) {
            $sql .= " AND i.item_type = :type";
            $params['type'] = strtolower($filters['item_type']);
        }
        // 3. Category filter
        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $sql .= " AND i.category_id = :cat";
            $params['cat'] = (int)$filters['category_id'];
        }
        // 4. Status filter ('Open', 'Claim Requested', 'Resolved')
        if (!empty($filters['status']) && in_array($filters['status'], ['Open', 'Claim Requested', 'Resolved'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $filters['status'];
        }

        // 5. Sorting
        $sort = $filters['sort'] ?? 'newest';
        $sql .= match($sort) {
            'oldest'    => " ORDER BY i.created_at ASC",
            'date_desc' => " ORDER BY i.event_date DESC, i.created_at DESC",
            default     => " ORDER BY i.created_at DESC"
        };

        $limit = max(1, min((int)($filters['limit'] ?? 100), 200));
        $sql .= " LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Find item by ID with category and user details
    public function findById(int $id): ?array {
        $sql = "SELECT i.*, c.name AS category_name, c.icon AS category_icon, u.name AS reporter_name, u.email AS reporter_email
                FROM items i
                JOIN categories c ON i.category_id = c.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE i.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Update item status ('Open', 'Claim Requested', 'Resolved')
    public function updateStatus(int $id, string $status, string $remarks = ''): bool {
        if (!in_array($status, ['Open', 'Claim Requested', 'Resolved'])) return false;
        $stmt = $this->db->prepare("UPDATE items SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $ok = $stmt->execute(['status' => $status, 'id' => $id]);
        if ($ok) $this->logActivity($id, 'Status Updated', "Status changed to {$status}. " . $remarks);
        return $ok;
    }

    // Items reported by specific student
    public function getByUserId(int $userId): array {
        $sql = "SELECT i.*, c.name AS category_name, c.icon AS category_icon,
                       (SELECT COUNT(*) FROM claims WHERE item_id = i.id) AS total_claims
                FROM items i JOIN categories c ON i.category_id = c.id
                WHERE i.user_id = :uid ORDER BY i.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    // Aggregate statistics
    public function getStats(): array {
        $sql = "SELECT COUNT(*) AS total_items,
                       COUNT(*) FILTER (WHERE item_type = 'lost') AS total_lost,
                       COUNT(*) FILTER (WHERE item_type = 'found') AS total_found,
                       COUNT(*) FILTER (WHERE status = 'Open') AS total_open,
                       COUNT(*) FILTER (WHERE status = 'Claim Requested') AS total_claim_requested,
                       COUNT(*) FILTER (WHERE status = 'Resolved') AS total_resolved
                FROM items";
        return $this->db->query($sql)->fetch() ?: ['total_items'=>0,'total_lost'=>0,'total_found'=>0,'total_open'=>0,'total_claim_requested'=>0,'total_resolved'=>0];
    }

    // Delete item
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM items WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // Audit trail logging
    public function logActivity(int $itemId, string $action, string $details = ''): void {
        try {
            $stmt = $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:id, :act, :det)");
            $stmt->execute(['id' => $itemId, 'act' => $action, 'det' => $details]);
        } catch (Exception $e) { error_log($e->getMessage()); }
    }

    // Retrieve activity logs for an item
    public function getActivityLogs(int $itemId, int $limit = 10): array {
        $stmt = $this->db->prepare("SELECT * FROM activity_logs WHERE item_id = :id ORDER BY created_at DESC LIMIT :l");
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
