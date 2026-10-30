<?php
// app/repositories/UserRepository.php - User Data Access Object (DAO)
//
// WHAT THIS CLASS DOES:
// Handles all database interaction with the PostgreSQL `users` table:
// 1. findByEmail($email): Case-insensitive user lookup by email.
// 2. findById($id): User lookup by primary key ID (omits password hash).
// 3. emailExists($email, $excludeId): Duplicate email validation check.
// 4. create($data): Creates a new user record with bcrypt password hashing.
// 5. getAll($limit): Lists registered campus members for admin user desk.
// 6. updateRole($id, $role): Promotes student to 'admin' or demotes back to 'user'.
//
// HOW TO DEBUG & MODIFY:
// - TO ADD A NEW USER FIELD (e.g., student_id, department):
//   1. Run SQL: `ALTER TABLE users ADD COLUMN department VARCHAR(100);`
//   2. In create() below: add :department to the SQL query and bind $data['department'].
// - TO CHANGE PASSWORD HASHING ALGORITHM: In create() below, edit `password_hash(..., PASSWORD_BCRYPT)`.
// - TO DEBUG FAILED LOGINS: Run `SELECT * FROM users WHERE email = '...';` in pgAdmin to check stored password_hash.

require_once __DIR__ . '/../../config/database.php';

class UserRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    // =========================================================================
    // USER LOOKUP FUNCTIONS
    // =========================================================================

    // Lookup user by email (case-insensitive via LOWER)
    // Used by AuthController during sign-in verification.
    // Returns user array with id, name, email, password_hash, role, or null if not found.
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute(['email' => trim($email)]);
        return $stmt->fetch() ?: null;
    }

    // Lookup user by primary key ID (omits password_hash for security)
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Check if an email address is already registered
    // Optional $excludeId allows excluding a user's own ID when editing their profile.
    public function emailExists(string $email, ?int $excludeId = null): bool {
        $sql = "SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email)" . ($excludeId ? " AND id != :id" : "") . " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $params = ['email' => trim($email)];
        if ($excludeId) $params['id'] = $excludeId;
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    // =========================================================================
    // USER CREATION & MANAGEMENT
    // =========================================================================

    // Create a new user with secure bcrypt password hash
    // $data keys: 'name', 'email', 'password', 'phone', 'role'
    // TO CHANGE DEFAULT ROLE: Edit `'role' => $data['role'] ?? 'user'` below.
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

    // List all users ordered by registration date (for Admin User Directory)
    // TO CHANGE DEFAULT USER LIMIT: Edit $limit parameter below.
    public function getAll(int $limit = 100): array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT :l");
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Update user role ('admin' or 'user')
    // Used by api/admin-user-role.php to promote/demote members.
    // TO ADD NEW ROLE (e.g. 'moderator'): Add 'moderator' to the array below and update DB constraint.
    public function updateRole(int $id, string $role): bool {
        if (!in_array($role, ['user', 'admin'])) return false;
        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
        return $stmt->execute(['role' => $role, 'id' => $id]);
    }
}
