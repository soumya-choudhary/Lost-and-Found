# Campus Lost-and-Found Portal (P1)

A full-stack campus web portal built to report, search, claim, and track lost and found items across university premises.

## Tech Stack
- **Backend**: PHP 8.3 (PDO, Object-Oriented MVC Architecture)
- **Database**: PostgreSQL 18 with Relational Integrity & Indexes
- **Frontend**: HTML5, CSS3, JavaScript, jQuery 3.7.1, Bootstrap 5.3.3, FontAwesome 6
- **Server**: Apache Server (XAMPP)

## Project Structure
```text
Lost-and-Found/
├── .env                  # Local environment configuration (ignored by Git)
├── .env.example          # Template environment file
├── .gitignore            # Git exclusion rules
├── config/
│   ├── app.php           # App configuration, sessions, flash messages, & helper functions
│   └── database.php      # PostgreSQL PDO connection handler
├── database/
│   ├── migrations/
│   │   └── schema.sql    # Relational database schema & seed data
│   └── schema.sql
├── app/
│   ├── controllers/
│   │   └── AuthController.php
│   ├── middleware/
│   │   ├── AuthMiddleware.php
│   │   └── AdminMiddleware.php
│   ├── repositories/
│   │   └── UserRepository.php
│   └── views/
│       ├── admin/
│       │   └── dashboard.php
│       └── student/
│           └── dashboard.php
├── layouts/
│   ├── header.php        # Navigation header with role-based links & flash alerts
│   └── footer.php        # Footer scripts (jQuery & Bootstrap)
├── public/
│   └── assets/
│       ├── css/style.css
│       └── js/auth.js
├── uploads/              # Uploaded photos
├── index.php             # Public home page
├── home.php              # Alias
├── login.php             # Authentication: Login portal
├── register.php          # Authentication: Registration portal
└── logout.php            # Session termination
```

## Setup Instructions

1. **Clone repository**:
   ```bash
   git clone <repo-url>
   ```

2. **Configure Environment**:
   - Copy `.env.example` to `.env`:
     ```bash
     copy .env.example .env
     ```
   - Update `DB_PASS` with your PostgreSQL password.

3. **Import Database Schema**:
   - Run `database/migrations/schema.sql` in PostgreSQL (via `psql` or pgAdmin 4).

4. **Run Application**:
   - Start Apache and PostgreSQL in XAMPP & Windows Services.
   - Open browser: `http://localhost/Lost-and-Found/`

## Default Test Accounts
- **Admin**: `admin@campus.edu` / `admin123`
- **Student**: `student@campus.edu` / `admin123`

