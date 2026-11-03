<?php
// app/repositories/UserRepository.php - Users Table Database Operations
//
// WHAT THIS FILE DOES:
// Encapsulates all SQL queries and operations on the `users` PostgreSQL table.
// Follows the Repository Pattern to separate raw SQL from controllers and presentation views.
// All queries use PDO prepared statements to guarantee SQL injection immunity.
//
// HOW TO EXPLAIN TO PROFESSOR:
// "The UserRepository handles authentication queries, user registration with BCRYPT password hashing,
// role promotion/demotion, and user listings for the administrator directory."

require_once __DIR__ . '/../../config/database.php';

class UserRepository {
    private PDO $db;

    /**
     * Constructor: injects PDO database connection.
     * Defaults to the singleton Database::getConnection() if none is passed.
     */
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Find a user by their email address (case-insensitive via LOWER()).
     * Used by AuthController during login and registration duplicate checks.
     *
     * @param string $email User's email to search
     * @return array|null Associative array of user row, or null if not found
     */
    public function findByEmail(string $email): ?array {
        // Prepare statement with named placeholder :email
        $stmt = $this->db->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute(['email' => trim($email)]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Check if an email is already registered in the system.
     *
     * @param string $email Email address to test
     * @return bool True if email is in use, false otherwise
     */
    public function emailExists(string $email): bool {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Find a user record by primary key ID.
     * Excludes password_hash from the SELECT statement for security.
     *
     * @param int $id Primary key user ID
     * @return array|null User details array, or null if not found
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Register a new student/user.
     * Automatically hashes the plain-text password using strong BCRYPT before saving.
     * Uses PostgreSQL RETURNING id to get the generated primary key.
     *
     * @param string $name Full name
     * @param string $email Valid email address
     * @param string $password Plain text password
     * @param string|null $phone Optional phone number
     * @return int|null Generated user ID on success, or null on failure
     */
    public function create(string $name, string $email, string $password, ?string $phone = null): ?int {
        // Securely hash password with BCRYPT algorithm (cost 10)
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // SQL prepared statement with RETURNING clause for instant ID retrieval
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, phone, role) 
                                     VALUES (:name, :email, :hash, :phone, 'user') 
                                     RETURNING id");
        $stmt->execute([
            'name'  => trim($name), 
            'email' => strtolower(trim($email)), 
            'hash'  => $hash, 
            'phone' => $phone ? trim($phone) : null
        ]);

        $id = (int)$stmt->fetchColumn();
        return $id ?: null;
    }

    /**
     * Update a user's role (promote to 'admin' or demote to 'user').
     * Used by the administrator user directory in admin/users.php and api/admin-user-role.php.
     *
     * @param int $userId Target user ID
     * @param string $newRole Desired role ('user' or 'admin')
     * @return bool True on success, false on invalid role or failure
     */
    public function updateRole(int $userId, string $newRole): bool {
        // Whitelist check to prevent invalid roles
        if (!in_array($newRole, ['user', 'admin'])) return false;

        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
        return $stmt->execute(['role' => $newRole, 'id' => $userId]);
    }

    /**
     * Retrieve all registered users for the admin user console.
     * Ordered by most recently created accounts first.
     *
     * @param int $limit Maximum number of records to return (default 100)
     * @return array List of user associative arrays
     */
    public function getAll(int $limit = 100): array {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at 
                                     FROM users 
                                     ORDER BY created_at DESC 
                                     LIMIT :lim");
        // Bind integer parameter explicitly for PostgreSQL LIMIT
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
