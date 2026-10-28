<?php
// app/repositories/UserRepository.php

require_once __DIR__ . '/../../config/database.php';

class UserRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Find a user by their email address
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute(['email' => trim($email)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Find a user by their primary key ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Check if an email is already registered
     */
    public function emailExists(string $email, ?int $excludeId = null): bool {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email) AND id != :id LIMIT 1");
            $stmt->execute(['email' => trim($email), 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
            $stmt->execute(['email' => trim($email)]);
        }
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Create a new user record
     */
    public function create(array $data): ?array {
        $sql = "INSERT INTO users (name, email, password_hash, phone, role) 
                VALUES (:name, :email, :password_hash, :phone, :role) 
                RETURNING id, name, email, phone, role, created_at";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name'          => trim($data['name']),
            'email'         => strtolower(trim($data['email'])),
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'phone'         => !empty($data['phone']) ? trim($data['phone']) : null,
            'role'          => $data['role'] ?? 'user'
        ]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Get all users (for admin panel)
     */
    public function getAll(int $limit = 50): array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

