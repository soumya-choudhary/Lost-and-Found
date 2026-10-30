<?php
// app/repositories/UserRepository.php - User DB operations

require_once __DIR__ . '/../../config/database.php';

class UserRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    // Lookup user by email (case-insensitive)
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute(['email' => trim($email)]);
        return $stmt->fetch() ?: null;
    }

    // Lookup user by ID
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Check if email is already taken
    public function emailExists(string $email, ?int $excludeId = null): bool {
        $sql = "SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email)" . ($excludeId ? " AND id != :id" : "") . " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $params = ['email' => trim($email)];
        if ($excludeId) $params['id'] = $excludeId;
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    // Create a new user with bcrypt password hash
    public function create(array $data): ?array {
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, phone, role) 
                                    VALUES (:name, :email, :password_hash, :phone, :role) 
                                    RETURNING id, name, email, phone, role, created_at");
        $stmt->execute([
            'name'          => trim($data['name']),
            'email'         => strtolower(trim($data['email'])),
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'phone'         => !empty($data['phone']) ? trim($data['phone']) : null,
            'role'          => $data['role'] ?? 'user'
        ]);
        return $stmt->fetch() ?: null;
    }

    // List all users for admin directory
    public function getAll(int $limit = 100): array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT :l");
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Change user role (admin / user)
    public function updateRole(int $id, string $role): bool {
        if (!in_array($role, ['user', 'admin'])) return false;
        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
        return $stmt->execute(['role' => $role, 'id' => $id]);
    }
}
