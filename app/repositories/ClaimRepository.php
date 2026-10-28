<?php
// app/repositories/ClaimRepository.php

require_once __DIR__ . '/../../config/database.php';

class ClaimRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Submit an ownership claim for an item
     */
    public function create(array $data): ?int {
        $this->db->beginTransaction();

        try {
            $sql = "INSERT INTO claims (
                        item_id, claimant_name, claimant_email, claimant_phone,
                        proof_description, proof_image_url, status
                    ) VALUES (
                        :item_id, :claimant_name, :claimant_email, :claimant_phone,
                        :proof_description, :proof_image_url, 'Pending'
                    ) RETURNING id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'item_id'           => (int)$data['item_id'],
                'claimant_name'     => trim($data['claimant_name']),
                'claimant_email'    => trim($data['claimant_email']),
                'claimant_phone'    => !empty($data['claimant_phone']) ? trim($data['claimant_phone']) : null,
                'proof_description' => trim($data['proof_description']),
                'proof_image_url'   => $data['proof_image_url'] ?? null,
            ]);

            $claimId = (int)$stmt->fetchColumn();

            // Update item status to 'Claim Requested' if it was 'Open'
            $updateItemStmt = $this->db->prepare("UPDATE items SET status = 'Claim Requested', updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'Open'");
            $updateItemStmt->execute(['id' => (int)$data['item_id']]);

            // Log activity
            $logStmt = $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:item_id, 'Claim Submitted', :details)");
            $logStmt->execute([
                'item_id' => (int)$data['item_id'],
                'details' => "Claim filed by " . trim($data['claimant_name']) . " (" . trim($data['claimant_email']) . ")."
            ]);

            $this->db->commit();
            return $claimId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating claim: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get claims for a specific item
     */
    public function getByItemId(int $itemId): array {
        $stmt = $this->db->prepare("SELECT * FROM claims WHERE item_id = :item_id ORDER BY created_at DESC");
        $stmt->execute(['item_id' => $itemId]);
        return $stmt->fetchAll();
    }

    /**
     * Get claims filed by a specific user by email
     */
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

    /**
     * Get all claims for admin review
     */
    public function getAll(int $limit = 100): array {
        $sql = "SELECT c.*, i.title AS item_title, i.item_type, i.location, i.status AS item_status
                FROM claims c
                JOIN items i ON c.item_id = i.id
                ORDER BY c.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update claim status (Pending -> Approved or Rejected)
     */
    public function updateClaimStatus(int $claimId, string $status, string $adminNotes = ''): bool {
        if (!in_array($status, ['Pending', 'Approved', 'Rejected'])) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("UPDATE claims SET status = :status, admin_notes = :notes, resolved_at = CURRENT_TIMESTAMP WHERE id = :id RETURNING item_id");
            $stmt->execute([
                'status' => $status,
                'notes'  => trim($adminNotes),
                'id'     => $claimId
            ]);

            $itemId = (int)$stmt->fetchColumn();

            if ($status === 'Approved' && $itemId) {
                // If claim is approved, set item to Resolved
                $itemStmt = $this->db->prepare("UPDATE items SET status = 'Resolved', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                $itemStmt->execute(['id' => $itemId]);

                $logStmt = $this->db->prepare("INSERT INTO activity_logs (item_id, action, details) VALUES (:item_id, 'Claim Approved', :details)");
                $logStmt->execute([
                    'item_id' => $itemId,
                    'details' => "Claim #{$claimId} was approved. Item marked as Resolved. " . $adminNotes
                ]);
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

