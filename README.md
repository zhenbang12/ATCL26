## Camp Management System – Base Scaffold

This project is a **PHP + MySQL** base for an online camp/event system that includes:

- Participant & Admission Management
- Financial Control & Procurement
- Event Operations & Crew Management
- Project Governance & Administration
- Logistics & Resource Management

### 1. Requirements

- PHP 8.0+ with PDO MySQL extension
- MySQL / MariaDB
- A web server pointing the document root to the `public/` folder (e.g. Apache, Nginx)

### 2. Setup

1. Create a database:

```sql
CREATE DATABASE camp_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the base schema:

```bash
mysql -u root -p camp_management < database/schema.sql
```

3. Configure database credentials in `src/config.php`:

```php
'db' => [
    'host' => 'localhost',
    'name' => 'camp_management',
    'user' => 'root',
    'pass' => '',
],
```

4. Point your web server’s document root to the `public/` directory and browse to `/`.

Alternatively, for quick testing:

```bash
php -S localhost:8000 -t public
```

then open `http://localhost:8000` in your browser.

### 3. Module Overview

- **Participants & Admission**: registration form, participant list, QR-style code check-in, grouping overview, safety/medical pop‑ups.
- **Finance & Procurement**: simple claims submission/listing and a budget dashboard backed by `claims` and `budgets` tables.
- **Operations & Crew**: crew listing, games & scores view using `crew`, `games`, and `scores` tables.
- **Governance & Administration**: task timeline with dependencies and proposals listing.
- **Logistics & Resources**: venue master plan and equipment inventory views.

This is an intentionally minimal **starting point** – you can now extend controllers, views, and database tables to implement your detailed business rules (QR generation, round‑robin grouping, approval workflows, notifications, etc.).

