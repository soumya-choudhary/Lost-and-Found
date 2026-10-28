# Campus Lost-and-Found Portal

A PHP web application served by Apache, with PostgreSQL for relational data storage. XAMPP's bundled MariaDB/MySQL is not used. These instructions walk through getting the source from GitHub and running it locally on Windows with XAMPP and PostgreSQL.

The default configured local URL is **http://localhost/Lost-and-Found/**. Apache serves the project directly from XAMPP's `htdocs` directory.

---

## Requirements

- Windows 10 or 11.
- Git, or a web browser to download the repository as a ZIP archive.
- XAMPP with Apache and PHP 8.1 or newer (tested on PHP 8.3).
- PostgreSQL 14 or newer (tested on PostgreSQL 18), installed separately from XAMPP. PostgreSQL's installer also provides pgAdmin 4.
- The XAMPP PHP `pdo_pgsql` and `pgsql` extensions enabled.

---

## 1. Download the project

Install XAMPP using its default directory, `C:\xampp`. If you choose a different directory, use that actual path in subsequent steps.

The recommended project location is:

```text
C:\xampp\htdocs\Lost-and-Found
```

### Option A: Clone with Git

Open PowerShell and run the following from the XAMPP `htdocs` directory. Replace the example repository URL with your GitHub repository URL:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/SouL29092005/Lost-and-Found.git
```

*Note: If you have already cloned the repository elsewhere (for example, on your Desktop at `C:\Users\Soumya\Desktop\Lost-and-Found`), you can create a directory junction link inside `htdocs` instead of duplicating files:*

```powershell
cmd /c "mklink /J C:\xampp\htdocs\Lost-and-Found C:\Users\Soumya\Desktop\Lost-and-Found"
```

### Option B: Download a ZIP

1. Open the repository on GitHub.
2. Select **Code**, then **Download ZIP**.
3. Extract the ZIP into `C:\xampp\htdocs`.
4. If the extracted folder has a generated name (e.g., `Lost-and-Found-main`), rename it to `Lost-and-Found`.

Check that this file exists:

```text
C:\xampp\htdocs\Lost-and-Found\README.md
```

Avoid an extra nested directory such as `C:\xampp\htdocs\Lost-and-Found\Lost-and-Found-main\README.md`; if the files are nested, move the repository contents up one directory level.

---

## 2. Install and start PostgreSQL

XAMPP does not provide PostgreSQL. Install PostgreSQL separately using the Windows installer from the official PostgreSQL website. During setup, remember the password you assign to the superuser (`postgres`) and keep the default port `5432` unless another service already uses it.

Start the PostgreSQL Windows service via Windows Services (`services.msc`), the installer, or pgAdmin 4. Do not start XAMPP's MySQL module for this application; it is not used.

---

## 3. Create the database and load the schema

Open **pgAdmin 4**, expand **Servers** in the Browser tree, and enter your PostgreSQL master password when prompted. Right-click **Databases** and select **Create** > **Database...**. Set **Database** to `lost_and_found_db` and **Owner** to `postgres`, then click **Save**.

### Load the database schema in pgAdmin 4:

1. In the Browser tree, expand **Databases** and right-click `lost_and_found_db`.
2. Select **Query Tool**. Make sure the query tool is connected to `lost_and_found_db`, not the default `postgres` database.
3. In the Query Tool, select **Open File** (or press `Ctrl + O`) and open:

   ```text
   C:\xampp\htdocs\Lost-and-Found\database\migrations\schema.sql
   ```

   *(Or `database\schema.sql`).*
4. Confirm the Query Tool is connected to `lost_and_found_db`, then click **Execute/Refresh** (the lightning icon) or press **F5**.

The schema creates the required relational tables, constraints, foreign keys, indexes, and initial seed data:
- `users`: Registered student and administrative accounts.
- `categories`: Campus belongings classifications (Electronics, IDs, Wallets, Keys, Books, Bags, Clothing, Others).
- `items`: Reported lost and found items with location, date, descriptions, photos, and live statuses (`Open`, `Claim Requested`, `Resolved`).
- `claims`: Ownership claims with claimant information, detailed proofs, supporting photos, and verification statuses (`Pending`, `Approved`, `Rejected`).
- `activity_logs`: Complete audit trail tracking item status transitions and administrator actions.

### Alternative: Load schema using the command line (`psql`):

Open PowerShell and run:

```powershell
$env:PGPASSWORD = "your_postgres_password"
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -h 127.0.0.1 -p 5432 -d lost_and_found_db -f "C:\xampp\htdocs\Lost-and-Found\database\migrations\schema.sql"
```

---

### Default Test Accounts & Creating Custom Accounts

The initial schema includes pre-seeded accounts:

| Role | Email | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Campus Admin** | `admin@campus.edu` | `admin123` | Full access to Admin Panel, claim reviews, item purging, and user roles |
| **Demo Student** | `student@campus.edu` | `admin123` | Student dashboard, item reporting, ownership claims, and report management |

To create a new custom administrator account with your own credentials:

In **pgAdmin 4**:
1. Open the **Query Tool** connected to `lost_and_found_db`.
2. Paste the SQL below, replace with your desired name, email, and password, and press **F5**:

```sql
CREATE EXTENSION IF NOT EXISTS pgcrypto;

INSERT INTO users (name, email, password_hash, phone, role)
VALUES (
    'Your Name',
    'your-admin-email@campus.edu',
    crypt('YourStrongPasswordHere', gen_salt('bf')),
    '+1-555-0100',
    'admin'
);
```

---

## 4. Configure the database credentials

Create a file named `.env` in the repository root directory:

```text
C:\xampp\htdocs\Lost-and-Found\.env
```

*(You can copy `.env.example` to `.env`).*

Add your database connection settings:

```dotenv
# Database Configuration
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=lost_and_found_db
DB_USER=postgres
DB_PASS="your_postgres_password"

# Application Settings
APP_NAME="Campus Lost and Found Portal"
APP_ENV=development
APP_URL="http://localhost/Lost-and-Found"
```

Save the file as `.env`, not `.env.txt`. In Windows Notepad, choose **Save as type: All files (*.*)**. This file contains private credentials and is excluded by `.gitignore`; never commit or publish it.

---

## 5. Enable PostgreSQL in XAMPP PHP

Apache's PHP must have the PostgreSQL PDO driver enabled. Edit:

```text
C:\xampp\php\php.ini
```

The easiest way to open the correct file is from the **XAMPP Control Panel** > **Apache** > **Config** > **PHP (php.ini)**.

Find the following two extension lines (around lines 957 and 959) and remove the leading semicolon `;`:

```ini
extension=pdo_pgsql
extension=pgsql
```

Save `php.ini`. Confirm the matching DLL files (`php_pdo_pgsql.dll` and `php_pgsql.dll`) exist in `C:\xampp\php\ext`.

In the XAMPP Control Panel, stop and restart Apache after saving changes.

To verify that the extensions are enabled via PHP CLI, run:

```powershell
C:\xampp\php\php.exe -m | Select-String "pgsql"
```

The output should list:
```text
pdo_pgsql
pgsql
```

---

## 6. Configure Apache and the Local URL

### Standard XAMPP Setup (Recommended)

When placed in `C:\xampp\htdocs\Lost-and-Found`, the portal is accessible directly without altering virtual host files:

**http://localhost/Lost-and-Found/**

### Optional: Virtual Host Setup (`http://lostfound.local:8080/`)

If you prefer a custom local hostname and port:

1. In XAMPP Control Panel, open Apache **Config** > **Apache (httpd.conf)** and verify these lines are active:
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   Include conf/extra/httpd-vhosts.conf
   ```
2. In `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, append:
   ```apache
   <VirtualHost *:80>
       ServerName lostfound.local
       DocumentRoot "C:/xampp/htdocs/Lost-and-Found"

       <Directory "C:/xampp/htdocs/Lost-and-Found">
           Options FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. Open `C:\Windows\System32\drivers\etc\hosts` as Administrator and add:
   ```text
   127.0.0.1 lostfound.local
   ```
4. Update `APP_URL="http://lostfound.local"` in your `.env` file and restart Apache.

---

## 7. Start the website

1. Ensure the **PostgreSQL** service is running (port `5432`).
2. Start **Apache** in the XAMPP Control Panel. The MySQL module can remain stopped.
3. Open your browser and navigate to:

   👉 **http://localhost/Lost-and-Found/**

4. You can browse listings immediately. To test student workflows (reporting items, filing ownership claims, reviewing claims on your reported items), sign in with `student@campus.edu` / `admin123` or register a new student account. To access administrative controls, sign in with `admin@campus.edu` / `admin123`.

---

## Main pages & Endpoints

### Public & Student Pages
- `/` or `/index.php` - Live Catalog with real-time AJAX search and multi-criteria filters (Category, Status, Type) without full page reload. Item cards are fully clickable.
- `/report-item.php` - Report a Lost or Found item with image upload and instant client-side preview.
- `/item-details.php?id=X` - Item specification, ownership claim modal, owner/admin claim review desk, and live status toggle.
- `/register.php` - Student and staff registration with client-side password matching and validation.
- `/login.php` - Sign in portal with quick-fill demo buttons.
- `/logout.php` - Session destruction and redirect.
- `/student/dashboard.php` - Student control center with personal report/claim statistics.
- `/student/my-reports.php` - View and manage reported items and review incoming claims.
- `/student/my-claims.php` - Track status of submitted ownership verification claims.

### Administrator Control Center
- `/admin/dashboard.php` - Administrator overview with system counters and pending claim feeds.
- `/admin/claims.php` - Claim verification desk with proof inspections and single-click AJAX approve/reject actions.
- `/admin/items.php` - Inventory management table with single-click AJAX status updates and item deletion.
- `/admin/users.php` - Registered user directory with dynamic role management (promote/demote).

### AJAX API Endpoints
- `/api/items.php` - Live catalog filtering and search endpoint.
- `/api/update-status.php` - Instant item status updates (`Open`, `Claim Requested`, `Resolved`) with owner/admin authorization.
- `/api/claims.php` - Ownership claim submission endpoint (prevents owners from claiming their own items).
- `/api/claim-action.php` - AJAX decision endpoint for approving or rejecting claims.
- `/api/admin-item-delete.php` - Item deletion endpoint.
- `/api/admin-user-role.php` - User role promotion/demotion endpoint.

---

## Troubleshooting

### The browser says the site cannot be reached
- Verify that **Apache** is running (green indicator in XAMPP Control Panel).
- Confirm the project folder exists at `C:\xampp\htdocs\Lost-and-Found`.
- Verify the URL in the browser address bar is `http://localhost/Lost-and-Found/`.

### Apache will not start
- Check the Apache error log in XAMPP Control Panel.
- Check if another application (such as Skype, IIS, or VMware) is using port `80` or `443`.
- Verify there are no typos in `php.ini`.

### The application reports a database connection error
- Ensure the PostgreSQL service is active in Windows Services (`services.msc`).
- Confirm PostgreSQL is listening on port `5432`.
- Verify that the database `lost_and_found_db` exists in PostgreSQL.
- Check that `.env` is present in `C:\xampp\htdocs\Lost-and-Found\.env` and that `DB_PASS` matches your PostgreSQL master password.

### PHP reports that the PostgreSQL driver is missing
- Open `C:\xampp\php\php.ini`, verify that `extension=pdo_pgsql` and `extension=pgsql` are uncommented (no leading `;`).
- Confirm `php_pdo_pgsql.dll` exists in `C:\xampp\php\ext`.
- Restart Apache in XAMPP Control Panel.

### Photos fail to upload
- Verify that the `uploads/items` and `uploads/claims` directories exist.
- Ensure Windows user permissions allow the Apache web server process to write to the `uploads/` directory.

### "Permission denied" when updating item status
- Only a **Campus Admin** or the **Student who originally reported the item** can change its status or review claimant proof details. Random visitors and other users cannot alter someone else's item status.

---

## Security notes

- Keep `.env` private and never commit database credentials to version control.
- All user passwords are encrypted using strong `BCRYPT` hashing (`password_hash` / `password_verify`).
- All database operations use PHP PDO with parameterized prepared statements, preventing SQL injection.
- Ownership authorization ensures that private claimant proof details and receipt attachments are only accessible to the person who reported the item and campus administrators.
- The configuration described here is intended for local development. For production deployment, enforce HTTPS, configure proper file permissions, and restrict environment files.
