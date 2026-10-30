<?php
// app/repositories/ClaimRepository.php - Ownership Claims Data Access Object
//
// WHAT THIS CLASS DOES:
// Manages ownership verification claims filed by students on lost/found items:
// 1. create($data): Submits a claim transactionally:
//    - Inserts new claim record with status 'Pending'.
//    - If the item's status is 'Open', automatically transitions it to 'Claim Requested'.
//    - Records an audit trail log in activity_logs.
// 2. findById($id): Returns claim record with joined item reporter details for authorization checks.
// 3. getByItemId($itemId): Returns all claims filed against a specific item (for reporter review).
// 4. getByUserEmail($email): Returns all claims filed by a specific user email.
// 5. getAll($limit): Returns all claims across the portal for the admin review desk.
// 6. getPendingCount(): Returns integer count of claims awaiting evaluation.
// 7. updateClaimStatus($claimId, $status, $notes):
//    - Sets claim to 'Approved' or 'Rejected'.
//    - If 'Approved', automatically marks the item as 'Resolved' and logs activity.
//
// HOW TO DEBUG & MODIFY:
// - TO CHANGE WHAT HAPPENS ON CLAIM SUBMISSION: Check create() below.
// - TO PREVENT AUTO-RESOLVING ON CLAIM APPROVAL: In updateClaimStatus(), remove the UPDATE items SET status='Resolved' block.
// - TO ADD A NEW CLAIM STATUS (e.g., 'More Info Needed'):
//   1. In updateClaimStatus(), add to `in_array($status, ['Pending', 'Approved', 'Rejected', 'More Info Needed'])`.
//   2. In database/schema.sql, update the claims_status_check constraint.

require_once __DIR__ . '/../../config/database.php';

class ClaimRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    // =========================================================================
    // CLAIM CREATION & WORKFLOW
    // =========================================================================

    // Submit an ownership claim for an item
    // Transactional workflow:
    // 1. Inserts claim with status 'Pending'.
    // 2. Automatically updates parent item status from 'Open' to 'Claim Requested'.
    // 3. Inserts an audit trail entry in activity_logs.
    // $data keys: item_id, claimant_name, claimant_email, claimant_phone, proof_description, proof_image_url
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
            // TO DISABLE AUTO-UPDATE: Comment out the line below
            $this->db->prepare("UPDATE items SET status = 'Claim Requested', updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'Open'")
                     ->execute(['id' => (int)$data['item_id']]);

            // Add audit log entry
            $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:id, 'Claim Submitted', :det)")
                     ->execute(['id' => (int)$data['item_id'], 'det' => "Claim filed by " . trim($data['claimant_name'])]);

            $this->db->commit();
            return $claimId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed to create claim: " . $e->getMessage());
            return null;
        }
    }

    // Lookup claim by ID with item owner details for permission checks
    // Aliases item's user_id as item_user_id and contact_email as item_contact_email.
    public function findById(int $id): ?array {
        $sql = "SELECT c.*, i.user_id AS item_user_id, i.contact_email AS item_contact_email, i.title AS item_title
                FROM claims c 
                JOIN items i ON c.item_id = i.id 
                WHERE c.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Get all claims filed on a specific item (for item-details.php owner review)
    public function getByItemId(int $itemId): array {
        $stmt = $this->db->prepare("SELECT * FROM claims WHERE item_id = :id ORDER BY created_at DESC");
        $stmt->execute(['id' => $itemId]);
        return $stmt->fetchAll();
    }

    // Get all claims filed by a specific user email (for student/my-claims.php)
    public function getByUserEmail(string $email): array {
        $sql = "SELECT c.*, i.title AS item_title, i.item_type, i.location, i.status AS item_status, i.image_url
                FROM claims c 
                JOIN items i ON c.item_id = i.id 
                WHERE LOWER(c.claimant_email) = LOWER(:email) 
                ORDER BY c.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => trim($email)]);
        return $stmt->fetchAll();
    }

    // Get all claims for the admin claims review desk
    public function getAll(int $limit = 100): array {
        $sql = "SELECT c.*, i.title AS item_title, i.item_type, i.location, i.status AS item_status
                FROM claims c 
                JOIN items i ON c.item_id = i.id 
                ORDER BY c.created_at DESC LIMIT :l";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Count pending claims (displayed on admin dashboard)
    public function getPendingCount(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM claims WHERE status = 'Pending'")->fetchColumn();
    }

    // Update claim decision (Approve or Reject)
    // When a claim is Approved:
    // 1. Sets claim status to 'Approved' with reviewer notes and resolved_at timestamp.
    // 2. Automatically updates the parent item's status to 'Resolved'.
    // 3. Records the approval in activity_logs.
    // TO CHANGE ALLOWED DECISIONS: Edit `in_array($status, ['Pending', 'Approved', 'Rejected'])` below.
    public function updateClaimStatus(int $claimId, string $status, string $notes = ''): bool {
        if (!in_array($status, ['Pending', 'Approved', 'Rejected'])) return false;
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE claims SET status = :st, admin_notes = :nt, resolved_at = CURRENT_TIMESTAMP WHERE id = :id RETURNING item_id");
            $stmt->execute(['st' => $status, 'nt' => trim($notes), 'id' => $claimId]);
            $itemId = (int)$stmt->fetchColumn();

            // When claim is Approved, automatically mark the item as 'Resolved'
            if ($status === 'Approved' && $itemId) {
                $this->db->prepare("UPDATE items SET status = 'Resolved', updated_at = CURRENT_TIMESTAMP WHERE id = :id")->execute(['id' => $itemId]);
                $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:id, 'Claim Approved', :det)")
                         ->execute(['id' => $itemId, 'det' => "Claim #{$claimId} approved. Marked Resolved. " . $notes]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed to update claim status: " . $e->getMessage());
            return false;
        }
    }
}
