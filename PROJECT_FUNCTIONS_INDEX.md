# Campus Lost & Found — Function & File Directory

> 💡 **Tip for your presentation:** 
> - Open **`PROJECT_FUNCTIONS_INDEX.html`** in your browser for a **dark-mode, color-coded, live searchable dashboard**.
> - Or browse this **Markdown file** right here in your editor with syntax-colored code blocks!

---

## 🎨 Color & Symbol Legend
- 🟡 **Files**: `config/`, `app/`, `api/`, `public/`
- 🟣 **Classes**: `Database`, `UserRepository`, `ItemRepository`, `ClaimRepository`, `AuthController`, `ItemController`
- 🔵 **Functions / Methods**: `create()`, `handleLogin()`, `updateStatus()`, `searchAndFilter()`
- 🟢 **Return Types**: `bool`, `?array`, `string`, `int`, `void`, `PDO`
- 🟠 **Parameters**: `$email`, `$status`, `$itemId`

---

## 1. Global Helpers & Configuration (`config/`)

### 🟡 `config/database.php`
```php
// Reads .env file and sets environment variables into $_ENV and putenv
function loadEnv(string $path): void

// Singleton pattern: creates and returns the single PostgreSQL PDO connection
Database::getConnection(): PDO
```
* **PostgreSQL DSN:** `"pgsql:host={$host};port={$port};dbname={$name};"`
* **PDO Options:** `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `ATTR_EMULATE_PREPARES => false`.

---

### 🟡 `config/app.php`
```php
// 1. URL & Redirection
function url(string $path = ''): string
function redirect(string $path): void

// 2. Flash Messages (One-time session notices)
function setFlash(string $type, string $message): void
function getFlash(): ?array

// 3. Authentication & Roles
function isLoggedIn(): bool
function isAdmin(): bool
function currentUser(): ?array // ['id', 'name', 'email', 'role']

// 4. Security (XSS Protection)
function sanitize($val): string // htmlspecialchars($val, ENT_QUOTES, 'UTF-8')

// 5. Ownership & Permissions
function isItemOwner(array $item, ?array $user = null): bool
function canManageItem(array $item, ?array $user = null): bool // isAdmin() || isItemOwner()

// 6. UI Badges & Images
function typeBadgeClass(string $type): string       // 'lost' => bg-danger, 'found' => bg-success
function statusBadgeClass(string $status): string   // 'Open', 'Claim Requested', 'Resolved'
function claimBadgeClass(string $status): string    // 'Approved' (success), 'Rejected' (danger), 'Pending' (warning)
function itemImageUrl($itemOrPath = null): string   // Uploaded image or fallback to placeholder.svg
```

---

## 2. Route Guards & Security (`app/middleware/`)

### 🟡 `app/middleware/AuthMiddleware.php`
```php
// Enforces user authentication; redirects guests to login.php
AuthMiddleware::check(): void

// Blocks authenticated users from re-visiting login/register; redirects to dashboard
AuthMiddleware::guestOnly(): void
```

### 🟡 `app/middleware/AdminMiddleware.php`
```php
// Enforces admin role; redirects non-admin users to login.php with error flash
AdminMiddleware::check(): void
```

---

## 3. Database Repositories (`app/repositories/`)

### 🟡 `app/repositories/UserRepository.php` — `class UserRepository`
```php
public function findByEmail(string $email): ?array
// SQL: SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1

public function emailExists(string $email): bool

public function findById(int $id): ?array
// SQL: SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id

public function create(string $name, string $email, string $password, ?string $phone = null): ?int
// SQL: INSERT INTO users (name, email, password_hash, phone, role) VALUES (...) RETURNING id
// Uses: password_hash($password, PASSWORD_BCRYPT)

public function updateRole(int $userId, string $newRole): bool
// SQL: UPDATE users SET role = :role WHERE id = :id (allowed: 'user', 'admin')

public function getAll(int $limit = 100): array
// SQL: SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT :lim
```

---

### 🟡 `app/repositories/ItemRepository.php` — `class ItemRepository`
```php
public function getAllCategories(): array
// SQL: SELECT * FROM categories ORDER BY name ASC

public function create(array $d): ?int
// SQL: INSERT INTO items (...) VALUES (...) RETURNING id
// Automatically writes 'Created' into activity_logs

public function syncClaimRequestedItems(): void
// SQL: Reverts 'Claim Requested' items without active 'Pending' claims back to 'Open'

public function searchAndFilter(array $f = []): array
// Dynamic SQL with ILIKE keyword search, type, category, status, and sorting

public function findById(int $id): ?array
// SQL: SELECT i.*, c.name AS category_name, u.name AS reporter_name FROM items JOIN ... WHERE i.id = :id

public function updateStatus(int $id, string $status, string $remarks = ''): bool
// SQL: UPDATE items SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id
// Automatically appends to activity_logs

public function getByUserId(int $uid): array
// SQL: Fetches items reported by user with count of claims

public function getStats(): array
// SQL: Aggregates total, total_lost, total_found, open, in_claim, total_resolved

public function delete(int $id): bool
// SQL: DELETE FROM items WHERE id = :id (Cascades to claims and activity_logs)

public function logActivity(int $id, string $action, string $details = ''): bool
// SQL: INSERT INTO activity_logs (item_id, action, details) VALUES (:id, :action, :details)

public function getActivityLogs(int $id): array
// SQL: SELECT * FROM activity_logs WHERE item_id = :id ORDER BY created_at DESC LIMIT 20
```

---

### 🟡 `app/repositories/ClaimRepository.php` — `class ClaimRepository`
```php
public function create(array $d): ?int
// Transactional:
// 1. INSERT INTO claims (...) VALUES (...) RETURNING id
// 2. UPDATE items SET status = 'Claim Requested' WHERE id = :iid AND status = 'Open'
// 3. INSERT INTO activity_logs ('Claim Filed')

public function findById(int $id): ?array
// Joins claims with items to get reporter info for permission checks

public function getPendingCount(): int
// SQL: SELECT COUNT(*) FROM claims WHERE status = 'Pending'

public function getByItemId(int $iid): array
// SQL: SELECT * FROM claims WHERE item_id = :iid ORDER BY created_at DESC

public function getByUserEmail(string $email): array
// SQL: SELECT c.*, i.title AS item_title FROM claims JOIN ... WHERE LOWER(c.claimant_email) = LOWER(:em)

public function getAll(int $limit = 100): array
// SQL: All claims with item titles for the Admin Claims Desk

public function updateClaimStatus(int $cid, string $status, string $notes = ''): bool
// Transactional:
// 1. UPDATE claims SET status = :st, admin_notes = :nt, resolved_at = CURRENT_TIMESTAMP WHERE id = :cid
// 2. If 'Approved' => UPDATE items SET status = 'Resolved'
// 3. If 'Rejected' and no pending claims left => UPDATE items SET status = 'Open'
```

---

## 4. Controllers (`app/controllers/`)

### 🟡 `app/controllers/AuthController.php` — `class AuthController`
```php
// Handles login POST: checks email, password_verify(), session_regenerate_id(true), sets $_SESSION
public function handleLogin(): array

// Handles registration POST: validates input, creates user via UserRepository, auto-logs in
public function handleRegister(): array

// Terminates session, clears $_SESSION, destroys cookie, redirects to login.php
public function logout(): void
```

---

### 🟡 `app/controllers/ItemController.php` — `class ItemController`
```php
public function getCategories(): array
public function getItem(int $id): ?array

// Validates form fields, uploads image to uploads/items/, inserts item, redirects to details
public function handleReportSubmission(): array

// AJAX handler for api/items.php: fetches items, buffers HTML cards, returns JSON
public function handleAjaxFilter(): void

// Generates the clickable Bootstrap card HTML for an item
public function renderItemCard(array $item): void
```

---

## 5. AJAX Endpoints (`api/`)

| Endpoint | Method | Access | Parameters | What it does |
| :--- | :---: | :---: | :--- | :--- |
| `api/items.php` | `GET` | Public | `q`, `type`, `category`, `status`, `sort` | Returns `{success, count, html}` for live search |
| `api/update-status.php` | `POST` | Reporter or Admin | `item_id`, `status`, `remarks` | Changes item status (`Open`, `Claim Requested`, `Resolved`) |
| `api/claims.php` | `POST` | Signed in | `item_id`, `claimant_name`, `email`, `phone`, `proof`, `proof_image` | Submits ownership claim (**Reporter cannot claim own item**) |
| `api/admin-claim-action.php` | `POST` | Reporter or Admin | `claim_id`, `decision` (`Approved`/`Rejected`), `admin_notes` | Decides claim; Approved sets item to Resolved |
| `api/admin-item-delete.php` | `POST` | Admin Only | `item_id` | Permanently deletes item (cascades) |
| `api/admin-user-role.php` | `POST` | Admin Only | `user_id`, `role` (`user`/`admin`) | Updates role (**Prevents admin self-demotion**) |

---

## 6. Client JavaScript (`public/assets/js/`)

### 🟡 `public/assets/js/items.js`
```javascript
filterItems()                               // Debounced (250ms) AJAX GET to /api/items.php
$(document).on('click', '.item-card')       // Card click navigation via data-href
$('.status-toggle-btn').on('click')         // AJAX POST to /api/update-status.php
$('#claimSubmitForm').on('submit')          // FormData AJAX POST to /api/claims.php
$('.user-claim-decision-btn').on('click')   // AJAX POST to /api/admin-claim-action.php
```

### 🟡 `public/assets/js/admin.js`
```javascript
$('#claimFilterButtons button').on('click') // Instant client-side tab filter by status
$('.open-decision-modal-btn').on('click')   // Opens claim review modal with prefilled data
$('.admin-submit-decision-btn').on('click') // Approves/Rejects claim via AJAX
$('.admin-status-select').on('change')      // In-place table status dropdown
$('.admin-delete-item-btn').on('click')     // In-place item deletion with confirmation
$('.admin-role-select').on('change')        // In-place user role dropdown
$('#adminItemSearchInput').on('input')      // Instant table row text filtering
```

### 🟡 `public/assets/js/auth.js`
```javascript
$('.toggle-password-btn').on('click')       // Swaps between 'password' and 'text' input types
$('#reg_password, #reg_confirm').on('input')// Live password match feedback styling
$('.fill-demo-btn').on('click')             // One-click demo credential autofill
```

