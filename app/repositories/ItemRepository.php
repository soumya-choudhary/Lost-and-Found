<?php
// app/repositories/ItemRepository.php - Database Operations for Items & Categories
require_once __DIR__ . '/../../config/database.php';

class ItemRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    // Fetch all categories for dropdown menus
    public function getAllCategories(): array {
        return $this->db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    }

    // Insert a new lost/found report and log initial activity
    public function create(array $d): ?int {
        $sql = "INSERT INTO items (item_type, title, category_id, location, event_date, description, image_url, status, user_id, contact_name, contact_email, contact_phone)
                VALUES (:type, :title, :cat, :loc, :date, :descr, :img, :status, :uid, :cname, :cemail, :cphone) RETURNING id";
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
        if ($id) $this->logActivity($id, 'Created', 'Reported as ' . ucfirst($d['item_type']));
        return $id ?: null;
    }

    // Search and filter items dynamically (keyword, category, type, status, sort)
    public function searchAndFilter(array $f = []): array {
        $sql = "SELECT i.*, c.name AS category_name, c.icon AS category_icon, u.name AS reporter_name
                FROM items i JOIN categories c ON i.category_id = c.id LEFT JOIN users u ON i.user_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($f['query'])) {
            $sql .= " AND (i.title ILIKE :q OR i.description ILIKE :q OR i.location ILIKE :q)";
            $params['q'] = '%' . trim($f['query']) . '%';
        }
        if (!empty($f['item_type']) && in_array(strtolower($f['item_type']), ['lost', 'found'])) {
            $sql .= " AND i.item_type = :type";
            $params['type'] = strtolower($f['item_type']);
        }
        if (!empty($f['category_id']) && is_numeric($f['category_id'])) {
            $sql .= " AND i.category_id = :cat";
            $params['cat'] = (int)$f['category_id'];
        }
        if (!empty($f['status']) && in_array($f['status'], ['Open', 'Claim Requested', 'Resolved'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $f['status'];
        }

        $sort = $f['sort'] ?? 'newest';
        $sql .= match($sort) {
            'oldest'    => " ORDER BY i.created_at ASC",
            'date_desc' => " ORDER BY i.event_date DESC",
            default     => " ORDER BY i.created_at DESC"
        };
        $sql .= " LIMIT " . max(1, min((int)($f['limit'] ?? 100), 200));

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Find single item by ID
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT i.*, c.name AS category_name, c.icon AS category_icon, u.name AS reporter_name, u.email AS reporter_email
                                     FROM items i JOIN categories c ON i.category_id = c.id LEFT JOIN users u ON i.user_id = u.id WHERE i.id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Update item status ('Open', 'Claim Requested', 'Resolved') and record activity log
    public function updateStatus(int $id, string $status, string $remarks = ''): bool {
        if (!in_array($status, ['Open', 'Claim Requested', 'Resolved'])) return false;
        $stmt = $this->db->prepare("UPDATE items SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $ok = $stmt->execute(['status' => $status, 'id' => $id]);
        if ($ok) $this->logActivity($id, 'Status Updated', "Changed to {$status}. " . $remarks);
        return $ok;
    }

    // Get items reported by a specific user
    public function getByUserId(int $uid): array {
        $stmt = $this->db->prepare("SELECT i.*, c.name AS category_name, (SELECT COUNT(*) FROM claims WHERE item_id = i.id) AS total_claims
                                     FROM items i JOIN categories c ON i.category_id = c.id WHERE i.user_id = :uid ORDER BY i.created_at DESC");
        $stmt->execute(['uid' => $uid]);
        return $stmt->fetchAll();
    }

    // Aggregate portal statistics for dashboard counters
    public function getStats(): array {
        $counts = $this->db->query("SELECT 
            COUNT(*) as total,
            COUNT(*) FILTER (WHERE item_type = 'lost') as total_lost,
            COUNT(*) FILTER (WHERE item_type = 'found') as total_found,
            COUNT(*) FILTER (WHERE status = 'Open') as open,
            COUNT(*) FILTER (WHERE status = 'Claim Requested') as in_claim,
            COUNT(*) FILTER (WHERE status = 'Resolved') as total_resolved,
            COUNT(*) FILTER (WHERE status = 'Resolved') as resolved
            FROM items")->fetch();
        return $counts ?: ['total' => 0, 'total_lost' => 0, 'total_found' => 0, 'open' => 0, 'in_claim' => 0, 'total_resolved' => 0, 'resolved' => 0];
    }

    // Delete item (cascades to related claims and logs in DB)
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM items WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // Append to audit trail
    public function logActivity(int $id, string $action, string $details = ''): bool {
        $stmt = $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:id, :action, :details)");
        return $stmt->execute(['id' => $id, 'action' => $action, 'details' => $details]);
    }

    // Fetch audit trail for an item
    public function getActivityLogs(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM activity_logs WHERE item_id = :id ORDER BY created_at DESC LIMIT 20");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }
}
