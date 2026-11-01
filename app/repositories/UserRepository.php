<?php
// app/repositories/UserRepository.php - Users Table Operations
require_once __DIR__ . '/../../config/database.php';

class UserRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute(['email' => trim($email)]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $password, ?string $phone = null): ?int {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, phone, role) VALUES (:name, :email, :hash, :phone, 'user') RETURNING id");
        $stmt->execute(['name' => trim($name), 'email' => strtolower(trim($email)), 'hash' => $hash, 'phone' => $phone ? trim($phone) : null]);
        $id = (int)$stmt->fetchColumn();
        return $id ?: null;
    }

    public function updateRole(int $userId, string $newRole): bool {
        if (!in_array($newRole, ['user', 'admin'])) return false;
        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
        return $stmt->execute(['role' => $newRole, 'id' => $userId]);
    }

    public function getAll(int $limit = 100): array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
