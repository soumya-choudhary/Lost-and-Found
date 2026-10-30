<?php
// app/repositories/ClaimRepository.php - Claims DB operations

require_once __DIR__ . '/../../config/database.php';

class ClaimRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    // Submit a claim (transitions item from Open to Claim Requested)
    public function create(array $data): ?int {
        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO claims (item_id, claimant_name, claimant_email, claimant_phone, proof_description, proof_image_url, status)
                    VALUES (:item_id, :name, :email, :phone, :proof, :image, 'Pending') RETURNING id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'item_id' => (int)$data['item_id'],
                'name'    => trim($data['claimant_name']),
                'email'   => trim($data['claimant_email']),
                'phone'   => !empty($data['claimant_phone']) ? trim($data['claimant_phone']) : null,
                'proof'   => trim($data['proof_description']),
                'image'   => $data['proof_image_url'] ?? null,
            ]);
            $claimId = (int)$stmt->fetchColumn();

            // Set item status to 'Claim Requested' if it was 'Open'
            $this->db->prepare("UPDATE items SET status = 'Claim Requested', updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'Open'")
                     ->execute(['id' => (int)$data['item_id']]);

            // Audit log
            $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:id, 'Claim Submitted', :det)")
                     ->execute(['id' => (int)$data['item_id'], 'det' => "Claim filed by " . trim($data['claimant_name'])]);

            $this->db->commit();
            return $claimId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return null;
        }
    }

    // Lookup claim with item owner details
    public function findById(int $id): ?array {
        $sql = "SELECT c.*, i.user_id AS item_user_id, i.contact_email AS item_contact_email, i.title AS item_title
                FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Claims filed on a specific item
    public function getByItemId(int $itemId): array {
        $stmt = $this->db->prepare("SELECT * FROM claims WHERE item_id = :id ORDER BY created_at DESC");
        $stmt->execute(['id' => $itemId]);
        return $stmt->fetchAll();
    }

    // Claims filed by a specific user email
    public function getByUserEmail(string $email): array {
        $sql = "SELECT c.*, i.title AS item_title, i.item_type, i.location, i.status AS item_status, i.image_url
                FROM claims c JOIN items i ON c.item_id = i.id WHERE LOWER(c.claimant_email) = LOWER(:email) ORDER BY c.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => trim($email)]);
        return $stmt->fetchAll();
    }

    // All claims for admin desk
    public function getAll(int $limit = 100): array {
        $sql = "SELECT c.*, i.title AS item_title, i.item_type, i.location, i.status AS item_status
                FROM claims c JOIN items i ON c.item_id = i.id ORDER BY c.created_at DESC LIMIT :l";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Count pending claims
    public function getPendingCount(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM claims WHERE status = 'Pending'")->fetchColumn();
    }

    // Update claim decision (Approve auto-resolves item)
    public function updateClaimStatus(int $claimId, string $status, string $notes = ''): bool {
        if (!in_array($status, ['Pending', 'Approved', 'Rejected'])) return false;
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE claims SET status = :st, admin_notes = :nt, resolved_at = CURRENT_TIMESTAMP WHERE id = :id RETURNING item_id");
            $stmt->execute(['st' => $status, 'nt' => trim($notes), 'id' => $claimId]);
            $itemId = (int)$stmt->fetchColumn();

            if ($status === 'Approved' && $itemId) {
                $this->db->prepare("UPDATE items SET status = 'Resolved', updated_at = CURRENT_TIMESTAMP WHERE id = :id")->execute(['id' => $itemId]);
                $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:id, 'Claim Approved', :det)")
                         ->execute(['id' => $itemId, 'det' => "Claim #{$claimId} approved. Marked Resolved. " . $notes]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
