<?php
// app/repositories/ClaimRepository.php - Ownership Claims Database Operations
//
// WHAT THIS FILE DOES:
// Manages the complete ownership claim workflow:
// 1. Transactional claim creation (inserts claim + updates item status to 'Claim Requested' + logs activity).
// 2. Transactional claim decisions (Approving sets item to 'Resolved'; Rejecting reverts to 'Open' if no pending claims remain).
// 3. Queries for admin claims desk, item detail received claims, and student's submitted claims.
//
// HOW TO EXPLAIN TO PROFESSOR:
// "Database Transactions (beginTransaction, commit, rollBack):
// Claim operations modify both the `claims` table AND the `items` table simultaneously.
// We wrap these operations in a PDO database transaction so that if any query fails, all changes
// are rolled back, preventing any orphaned or inconsistent database states."

require_once __DIR__ . '/../../config/database.php';

class ClaimRepository {
    private PDO $db;

    /**
     * Constructor: injects PDO database connection.
     */
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Submit a new ownership claim.
     * Wrapped in a PDO Transaction:
     * 1. Inserts the claim row with status 'Pending'.
     * 2. Automatically updates the item's status from 'Open' to 'Claim Requested'.
     * 3. Appends a 'Claim Filed' entry into activity_logs.
     *
     * @param array $d Claim submission data (item_id, claimant_name, claimant_email, claimant_phone, proof_description, proof_image_url)
     * @return int|null Generated claim ID on success, or null on error
     */
    public function create(array $d): ?int {
        try {
            // Begin atomic transaction
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO claims (
                        item_id, claimant_name, claimant_email, claimant_phone, 
                        proof_description, proof_image_url, status
                    ) VALUES (
                        :iid, :name, :email, :phone, 
                        :proof, :img, 'Pending'
                    ) RETURNING id");

            $stmt->execute([
                'iid'   => (int)$d['item_id'],
                'name'  => trim($d['claimant_name']),
                'email' => trim($d['claimant_email']),
                'phone' => !empty($d['claimant_phone']) ? trim($d['claimant_phone']) : null,
                'proof' => trim($d['proof_description']),
                'img'   => $d['proof_image_url'] ?? null
            ]);
            $cid = (int)$stmt->fetchColumn();

            // Automatically update parent item status to 'Claim Requested' (only if currently 'Open')
            $this->db->prepare("UPDATE items SET status = 'Claim Requested' WHERE id = :iid AND status = 'Open'")
                     ->execute(['iid' => (int)$d['item_id']]);

            // Add activity log entry to document when the claim was filed
            $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:iid, 'Claim Filed', :dt)")
                     ->execute(['iid' => (int)$d['item_id'], 'dt' => 'Claim filed by ' . trim($d['claimant_name'])]);

            // Commit all changes atomically
            $this->db->commit();
            return $cid ?: null;
        } catch (Exception $e) {
            // Roll back all changes if an exception occurs
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return null;
        }
    }

    /**
     * Find a claim by ID joined with its parent item details.
     * Crucial for authorization checks: retrieves item_user_id and item_contact_email
     * so api/admin-claim-action.php can verify if the user deciding the claim is the original reporter.
     *
     * @param int $id Primary key claim ID
     * @return array|null Claim record or null if not found
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT c.*, i.user_id AS item_user_id, i.contact_email AS item_contact_email, i.title AS item_title
                                     FROM claims c 
                                     JOIN items i ON c.item_id = i.id 
                                     WHERE c.id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get the count of pending claims currently awaiting review.
     * Used for dashboard notification badges.
     *
     * @return int Number of pending claims
     */
    public function getPendingCount(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM claims WHERE status = 'Pending'")->fetchColumn();
    }

    /**
     * Get all claims filed for a specific item.
     * Used in item-details.php so the reporter/admin can review all claimant submissions.
     *
     * @param int $iid Item ID
     * @return array List of claims ordered by created_at DESC
     */
    public function getByItemId(int $iid): array {
        $stmt = $this->db->prepare("SELECT * FROM claims WHERE item_id = :iid ORDER BY created_at DESC");
        $stmt->execute(['iid' => $iid]);
        return $stmt->fetchAll();
    }

    /**
     * Get all claims submitted by a specific user email.
     * Used in student/my-claims.php so students can track the status of their claims.
     *
     * @param string $email Student's email
     * @return array List of submitted claims with parent item titles
     */
    public function getByUserEmail(string $email): array {
        $stmt = $this->db->prepare("SELECT c.*, i.title AS item_title 
                                     FROM claims c 
                                     JOIN items i ON c.item_id = i.id 
                                     WHERE LOWER(c.claimant_email) = LOWER(:em) 
                                     ORDER BY c.created_at DESC");
        $stmt->execute(['em' => trim($email)]);
        return $stmt->fetchAll();
    }

    /**
     * Fetch all claims in the system for the Admin Claims Desk (admin/claims.php).
     *
     * @param int $limit Max rows to return (default 100)
     * @return array List of all claims
     */
    public function getAll(int $limit = 100): array {
        $stmt = $this->db->prepare("SELECT c.*, i.title AS item_title 
                                     FROM claims c 
                                     JOIN items i ON c.item_id = i.id 
                                     ORDER BY c.created_at DESC 
                                     LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update claim status (Approve or Reject) with administrative remarks.
     * Wrapped in a PDO Transaction to maintain data consistency:
     * - If 'Approved': Automatically sets parent item status to 'Resolved' and logs 'Claim Approved'.
     * - If 'Rejected': Checks if there are any remaining pending claims on the item.
     *   If NO pending claims remain, automatically reverts item status back to 'Open' and logs 'Claim Rejected'.
     *
     * @param int $cid Claim ID
     * @param string $status 'Approved' or 'Rejected'
     * @param string $notes Optional remarks from the reviewer
     * @return bool True on success, false on failure
     */
    public function updateClaimStatus(int $cid, string $status, string $notes = ''): bool {
        // Validation check
        if (!in_array($status, ['Approved', 'Rejected'])) return false;

        try {
            $this->db->beginTransaction();

            // 1. Update claim status, notes, and timestamp
            $stmt = $this->db->prepare("UPDATE claims 
                                         SET status = :st, admin_notes = :nt, resolved_at = CURRENT_TIMESTAMP 
                                         WHERE id = :cid 
                                         RETURNING item_id");
            $stmt->execute(['st' => $status, 'nt' => trim($notes), 'cid' => $cid]);
            $itemId = (int)$stmt->fetchColumn();

            // 2. Cascade logic depending on approval or rejection
            if ($itemId && $status === 'Approved') {
                // When approved, mark the item as 'Resolved'
                $this->db->prepare("UPDATE items SET status = 'Resolved' WHERE id = :iid")->execute(['iid' => $itemId]);
                $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:iid, 'Claim Approved', :dt)")
                         ->execute(['iid' => $itemId, 'dt' => 'Approved: ' . $notes]);
            } elseif ($itemId && $status === 'Rejected') {
                // When rejected, check if other pending claims exist for this item
                $checkPending = $this->db->prepare("SELECT COUNT(*) FROM claims WHERE item_id = :iid AND status = 'Pending'");
                $checkPending->execute(['iid' => $itemId]);
                $remainingPending = (int)$checkPending->fetchColumn();

                // If no remaining pending claims, revert item back to 'Open'
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
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }
}
