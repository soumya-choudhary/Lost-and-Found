<?php
// app/repositories/ClaimRepository.php - Ownership Claims Database Operations
require_once __DIR__ . '/../../config/database.php';

class ClaimRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    // Insert claim and automatically set item status to 'Claim Requested'
    public function create(array $d): ?int {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("INSERT INTO claims (item_id, claimant_name, claimant_email, claimant_phone, proof_description, proof_image_url, status)
                                         VALUES (:iid, :name, :email, :phone, :proof, :img, 'Pending') RETURNING id");
            $stmt->execute([
                'iid'   => (int)$d['item_id'],
                'name'  => trim($d['claimant_name']),
                'email' => trim($d['claimant_email']),
                'phone' => !empty($d['claimant_phone']) ? trim($d['claimant_phone']) : null,
                'proof' => trim($d['proof_description']),
                'img'   => $d['proof_image_url'] ?? null
            ]);
            $cid = (int)$stmt->fetchColumn();

            // Auto-update item status to 'Claim Requested'
            $this->db->prepare("UPDATE items SET status = 'Claim Requested' WHERE id = :iid AND status = 'Open'")
                     ->execute(['iid' => (int)$d['item_id']]);

            // Add activity log
            $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:iid, 'Claim Filed', :dt)")
                     ->execute(['iid' => (int)$d['item_id'], 'dt' => 'Claim filed by ' . trim($d['claimant_name'])]);

            $this->db->commit();
            return $cid ?: null;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return null;
        }
    }

    // Find claim by ID with item owner info for authorization
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT c.*, i.user_id AS item_user_id, i.contact_email AS item_contact_email, i.title AS item_title
                                     FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Get pending claims count
    public function getPendingCount(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM claims WHERE status = 'Pending'")->fetchColumn();
    }

    // Get all claims for an item
    public function getByItemId(int $iid): array {
        $stmt = $this->db->prepare("SELECT * FROM claims WHERE item_id = :iid ORDER BY created_at DESC");
        $stmt->execute(['iid' => $iid]);
        return $stmt->fetchAll();
    }

    // Get claims submitted by a user email
    public function getByUserEmail(string $email): array {
        $stmt = $this->db->prepare("SELECT c.*, i.title AS item_title FROM claims c JOIN items i ON c.item_id = i.id WHERE LOWER(c.claimant_email) = LOWER(:em) ORDER BY c.created_at DESC");
        $stmt->execute(['em' => trim($email)]);
        return $stmt->fetchAll();
    }

    // Fetch all claims for admin review desk
    public function getAll(int $limit = 100): array {
        $stmt = $this->db->prepare("SELECT c.*, i.title AS item_title FROM claims c JOIN items i ON c.item_id = i.id ORDER BY c.created_at DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Update claim status (Approved/Rejected) and auto-resolve item if approved
    public function updateClaimStatus(int $cid, string $status, string $notes = ''): bool {
        if (!in_array($status, ['Approved', 'Rejected'])) return false;
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE claims SET status = :st, admin_notes = :nt, resolved_at = CURRENT_TIMESTAMP WHERE id = :cid RETURNING item_id");
            $stmt->execute(['st' => $status, 'nt' => trim($notes), 'cid' => $cid]);
            $itemId = (int)$stmt->fetchColumn();

            // If approved, mark the item as 'Resolved'
            if ($itemId && $status === 'Approved') {
                $this->db->prepare("UPDATE items SET status = 'Resolved' WHERE id = :iid")->execute(['iid' => $itemId]);
                $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:iid, 'Claim Approved', :dt)")
                         ->execute(['iid' => $itemId, 'dt' => 'Approved: ' . $notes]);
            } elseif ($itemId && $status === 'Rejected') {
                // If rejected, check if there are any remaining pending claims on this item
                $checkPending = $this->db->prepare("SELECT COUNT(*) FROM claims WHERE item_id = :iid AND status = 'Pending'");
                $checkPending->execute(['iid' => $itemId]);
                $remainingPending = (int)$checkPending->fetchColumn();

                // If no remaining pending claims, revert item status from 'Claim Requested' back to 'Open'
                if ($remainingPending === 0) {
                    $this->db->prepare("UPDATE items SET status = 'Open' WHERE id = :iid AND status = 'Claim Requested'")
                             ->execute(['iid' => $itemId]);
                }

                $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:iid, 'Claim Rejected', :dt)")
                         ->execute(['iid' => $itemId, 'dt' => 'Rejected: ' . $notes]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }
}
