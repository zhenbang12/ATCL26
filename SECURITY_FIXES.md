# ATCL26 Security Fixes & Bug Remediation Guide

This document contains step-by-step code fixes, diffs, and architectural recommendations for all **16 issues and vulnerabilities** discovered in the ATCL26 codebase.

---

## Quick Reference Summary

| # | Issue | File(s) | Severity |
|---|---|---|---|
| 1 | Arbitrary File Upload to RCE | `src/Controller/FinanceController.php` | **Critical** |
| 2 | Sensitive Files Exposed in Web Root | `.htaccess`, `Dockerfile`, `.dockerignore` | **Critical** |
| 3 | Demo Portal Creates Backdoor Accounts | `src/Controller/DemoController.php`, `src/Controller/AuthController.php` | **Critical** |
| 4 | Complete Absence of CSRF Protection | Global forms, `src/Core/Router.php` | **High** |
| 5 | Raw SQL Execution via Backup Restore | `src/Controller/BackupController.php` | **High** |
| 6 | Verification Bypass & Profile Hijacking | `src/Controller/ParticipantController.php`, `views/participants/lookup_result.php` | **High** |
| 7 | Foreign Key Constraint Crash on Session Delete | `src/Controller/SessionController.php` | **High** |
| 8 | CSV Formula Injection on Export | `src/Controller/ParticipantController.php` | **Medium** |
| 9 | Source Code Disclosure via Built-in Server | `router.php` | **Medium** |
| 10 | Cross-Session Contamination in Group Moves | `src/Controller/ParticipantController.php` | **Medium** |
| 11 | Inline Event Handler XSS | `views/operations/crew.php` | **Medium** |
| 12 | Potential DivisionByZero Crash | `views/forms/summary.php` | **Low / Bug** |
| 13 | Insights Dashboard Inconsistency with Anomalies | `src/Controller/InsightsController.php` | **Low / Bug** |
| 14 | Inconsistent Image Validation in Lost & Found | `src/Controller/LostAndFoundController.php` | **Low / Bug** |
| 15 | Missing Rate Limiting on Auth & Lookups | `Caddyfile`, `src/Controller/AuthController.php` | **Low / Info** |
| 16 | DB Connection Error Leaks Server Details | `src/bootstrap.php` | **Low / Info** |

---

### Issue 1: Arbitrary File Upload Leading to RCE in `FinanceController`

**Files**:
- `src/Controller/FinanceController.php` (lines 63–94, 174–188, 328–359, 434–450)
- `uploads/.htaccess`

#### Root Cause
File upload verification checks only `$_FILES['...']['type']` (which is client-controlled) and uses `pathinfo($file['name'], PATHINFO_EXTENSION)` directly without validating extensions.

#### Fix in `src/Controller/FinanceController.php`
Add the following private method to `FinanceController`:

```php
private const ALLOWED_IMAGE_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

/**
 * Validates file magic bytes and saves upload securely with server-generated filename.
 */
private function storeFinanceUpload(array $file, string $subDir, string $prefix): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) <= 0) {
        return null;
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return null;
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string)$file['tmp_name']);
    if ($mime === false || !isset(self::ALLOWED_IMAGE_MIMES[$mime])) {
        return null;
    }

    $ext = self::ALLOWED_IMAGE_MIMES[$mime];
    $uploadDir = __DIR__ . '/../../uploads/' . trim($subDir, '/') . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target = $uploadDir . $filename;

    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        return null;
    }

    return '/uploads/' . trim($subDir, '/') . '/' . $filename;
}
```

Replace the upload blocks in `storeClaim()`, `updateClaim()`, `storeBuyingRequest()`, and `updateBuyingRequest()`:
```diff
- if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
-     $file = $_FILES['receipt_image'];
-     ...
-     $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $extension;
-     ...
- }
+ if (!empty($_FILES['receipt_image']['tmp_name'])) {
+     $receiptImage = $this->storeFinanceUpload($_FILES['receipt_image'], 'claims', 'receipt') ?? $receiptImage;
+ }
+ if (!empty($_FILES['items_image']['tmp_name'])) {
+     $itemsImage = $this->storeFinanceUpload($_FILES['items_image'], 'claims', 'items') ?? $itemsImage;
+ }
```

#### Create `uploads/.htaccess`
Create `uploads/.htaccess` to disallow executing PHP in uploads:
```apache
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar
SetHandler default-handler
Options -ExecCGI
```

---

### Issue 2: Sensitive Files Exposed in Apache Web Root

**Files**:
- `.htaccess`
- `.dockerignore`

#### Root Cause
Because `COPY . /var/www/html/` puts `.env`, `ATCL26.zip`, and database SQL files into Apache DocumentRoot, and `.htaccess` only rewrites missing files, Apache serves existing files directly.

#### Fix in `.htaccess`
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Block access to dotfiles (.env, .git, etc.)
    RewriteRule (^|/)\. - [F,L]

    # Block sensitive file extensions
    RewriteRule \.(env|sql|zip|tar|gz|sh|bat|md|json|lock)$ - [F,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} !^/uploads/
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

<FilesMatch "(^\.|\.(env|sql|zip|tar|gz|sh|bat|md|json|lock)$)">
    Require all denied
</FilesMatch>
```

#### Fix in `.dockerignore`
```dockerignore
.env
.env.*
*.zip
*.sql
deploy.sh
dev.bat
start-ngrok.*
.git
```
*(Also delete `ATCL26.zip` from the workspace root).*

---

### Issue 3: Demo Portal Creates Production Backdoor Accounts

**Files**:
- `src/Controller/AuthController.php`
- `index.php`

#### Root Cause
`DemoHelper::ensureDemoUsers()` inserts `demo_advisor` and `demo_committee` into the live `users` table with password `demopass2026`. Because `/login` does not set `isDemoMode`, an attacker can log into the live site as an advisor.

#### Fix in `src/Controller/AuthController.php`
```diff
     public function login(): void
     {
         $username = trim($_POST['username'] ?? '');
         $password = trim($_POST['password'] ?? '');

+        // Prevent demo accounts from logging in through the live login form
+        if (in_array(strtolower($username), ['demo_committee', 'demo_advisor'], true)) {
+            header('Location: /login?error=1');
+            exit;
+        }

         if (Auth::attempt($username, $password)) {
             header('Location: /dashboard');
             exit;
         }
```

#### Fix in `index.php`
Wrap demo routes in an environment flag:
```php
if (getenv('APP_ENV') === 'development' || getenv('DEMO_MODE_ENABLED') === 'true') {
    $router->get('/demo', 'DemoController@index');
    $router->post('/demo/login', 'DemoController@quickLogin');
    $router->get('/demo/participant', 'DemoController@startParticipantDemo');
    $router->post('/demo/reset', 'DemoController@resetDemoData');
    $router->get('/demo/exit', 'DemoController@exitDemo');
}
```

---

### Issue 4: Total Absence of CSRF Protection

**Files**:
- Create `src/Core/Csrf.php`
- `src/Core/Router.php`

#### Fix: Create `src/Core/Csrf.php`
```php
<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!is_string($submitted) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $submitted);
    }

    public static function require(): void
    {
        if (!self::validate()) {
            http_response_code(403);
            die('Invalid or missing security token (CSRF). Please refresh the page and try again.');
        }
    }
}
```

#### Fix in `src/Core/Router.php`
Validate CSRF in `dispatch()`:
```diff
     public function dispatch(string $method, string $uri): void
     {
         $path = parse_url($uri, PHP_URL_PATH) ?? '/';
         $path = $this->normalize($path);

+        if ($method === 'POST') {
+            $exemptRoutes = ['/participants/store', '/forms/submit', '/lost-and-found/submit-claim'];
+            if (!in_array($path, $exemptRoutes, true) && \App\Core\Auth::check()) {
+                \App\Core\Csrf::require();
+            }
+        }
```
Add `<?= \App\Core\Csrf::field() ?>` inside all forms in `views/`.

---

### Issue 5: Arbitrary SQL Execution via `/settings/backup/restore`

**Files**:
- `src/Controller/BackupController.php`

#### Fix in `src/Controller/BackupController.php`
Restrict restoration strictly to superusers with password verification:
```diff
     public function runRestore(): void
     {
-        $this->requireAuth();
+        \App\Core\Auth::requireRole(['superuser']);
+        \App\Core\Csrf::require();
+
+        $password = trim((string)($_POST['password'] ?? ''));
+        $user = \App\Core\Auth::user();
+        $db = Container::get('db');
+
+        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
+        $stmt->execute([(int)$user['id']]);
+        $hash = $stmt->fetchColumn();
+        if (!$hash || !password_verify($password, $hash)) {
+            $_SESSION['backup_message'] = 'Password confirmation failed. Restore aborted.';
+            $_SESSION['backup_message_type'] = 'danger';
+            header('Location: /settings/backup');
+            exit;
+        }
```

---

### Issue 6: Participant Verification Bypass & Profile Hijacking

**Files**:
- `views/participants/lookup_result.php`
- `src/Controller/ParticipantController.php`

#### 1. Mask Email in `views/participants/lookup_result.php`
```diff
- <strong>Email:</strong> <?= htmlspecialchars($participant['student_email'] ?? 'N/A') ?>
+ <strong>Email:</strong> <?php
+     $email = (string)($participant['student_email'] ?? '');
+     if ($email !== '' && strpos($email, '@') !== false) {
+         [$name, $domain] = explode('@', $email, 2);
+         $masked = substr($name, 0, 1) . str_repeat('*', max(1, strlen($name) - 2)) . substr($name, -1);
+         echo htmlspecialchars($masked . '@' . $domain);
+     } else {
+         echo 'N/A';
+     }
+ ?>
```

#### 2. Require Additional Verification in `ParticipantController::processVerifyEdit()`
Require matching the last 4 digits of the registered contact number:
```diff
     public function processVerifyEdit(): void
     {
         $db = Container::get('db');
         $studentId = trim((string)($_POST['student_id'] ?? ''));
         $email = trim((string)($_POST['student_email'] ?? ''));
+        $phoneLast4 = trim((string)($_POST['phone_last4'] ?? ''));

-        if ($studentId === '' || $email === '') {
+        if ($studentId === '' || $email === '' || $phoneLast4 === '') {
             $_SESSION['public_verify_error'] = 'All fields are required for identity verification.';
             header('Location: /participants/verify-edit?student_id=' . urlencode($studentId));
             exit;
         }

         $sid = $this->sid();
-        $stmt = $db->prepare('SELECT id, student_email FROM participants WHERE student_id = ? AND session_id = ?');
+        $stmt = $db->prepare('SELECT id, student_email, contact_no FROM participants WHERE student_id = ? AND session_id = ?');
         $stmt->execute([$studentId, $sid]);
         $participant = $stmt->fetch(\PDO::FETCH_ASSOC);

-        if ($participant && strtolower(trim((string)$participant['student_email'])) === strtolower($email)) {
+        $phoneMatches = $participant && str_ends_with((string)$participant['contact_no'], $phoneLast4);
+        if ($participant && strtolower(trim((string)$participant['student_email'])) === strtolower($email) && $phoneMatches) {
             $_SESSION['public_edit_participant_id'] = (int)$participant['id'];
             header('Location: /participants/edit-public');
             exit;
```

#### 3. Stop Leaking Student ID on Duplicate Registration in `ParticipantController::store()`
```diff
             if ($existing) {
                 $_SESSION['registration_error'] = 'This phone number is already registered.';
-                $_SESSION['registration_duplicate_id'] = $existing['student_id'];
                 $_SESSION['registration_input'] = $_POST;
                 header('Location: ' . $returnPath);
                 exit;
             }
```

---

### Issue 7: Foreign Key Constraint Crash on Session Deletion

**Files**:
- `src/Controller/SessionController.php`

#### Fix in `src/Controller/SessionController.php`
Add the missing child tables to the deletion transaction:
```diff
             // Delete all data associated with this session (in FK order)
             $tables = [
+                'participant_audit_logs',
+                'anomaly_constraints',
+                'lost_and_found_items',
                 'group_move_logs',
                 'crew_attendance',
                 'crew',
                 'event_group_settings',
                 'event_groups',
                 'participants',
             ];
             foreach ($tables as $table) {
                 $delStmt = $db->prepare("DELETE FROM {$table} WHERE session_id = ?");
                 $delStmt->execute([$sessionId]);
             }
```

---

### Issue 8: CSV / Spreadsheet Formula Injection in Export Endpoints

**Files**:
- `src/Controller/ParticipantController.php` (lines 1468–1485, 1525–1575)

#### Fix in `src/Controller/ParticipantController.php`
Add a sanitizing closure before writing rows:
```php
$sanitizeCell = static function ($val): string {
    $str = (string)($val ?? '');
    if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $str;
    }
    return $str;
};
```
Wrap all data rows with `array_map($sanitizeCell, $row)` prior to `fputcsv($output, $row)`.

---

### Issue 9: Source Code Disclosure via Built-in Server Router

**Files**:
- `router.php`

#### Fix in `router.php`
Remove `/vendor/` from publicly served static directories:
```diff
-// List of directories that should be served as static files
-$staticDirs = ['/uploads/', '/vendor/', '/css/', '/js/', '/images/'];
+// List of directories that should be served as static files
+$staticDirs = ['/uploads/', '/css/', '/js/', '/images/'];
```

---

### Issue 10: Cross-Session Contamination in Group Moves

**Files**:
- `src/Controller/ParticipantController.php` (lines 1210–1235, 2438–2450)

#### Fix in `src/Controller/ParticipantController.php`
Always bind `AND session_id = ?`:
```diff
- $participantStmt = $db->prepare('SELECT full_name, group_code FROM participants WHERE id = ?');
- $participantStmt->execute([$participantId]);
+ $participantStmt = $db->prepare('SELECT full_name, group_code FROM participants WHERE id = ? AND session_id = ?');
+ $participantStmt->execute([$participantId, $sid]);
...
- $stmt = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ?');
- $stmt->execute([$targetGroup, $participantId]);
+ $stmt = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ? AND session_id = ?');
+ $stmt->execute([$targetGroup, $participantId, $sid]);
```

---

### Issue 11: Inline Event Handler XSS in Crew Table

**Files**:
- `views/operations/crew.php`

#### Fix in `views/operations/crew.php`
Remove the inline string concatenation and use a data attribute with standard JS event listener:
```diff
- <form
-     method="post"
-     action="/operations/crew/delete"
-     class="m-0"
-     onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($c['full_name'])) ?> from the roster? This cannot be undone.');"
- >
+ <form
+     method="post"
+     action="/operations/crew/delete"
+     class="m-0 delete-crew-form"
+     data-crew-name="<?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?>"
+ >
      <input type="hidden" name="crew_id" value="<?= (int)$c['id'] ?>">
+     <?= \App\Core\Csrf::field() ?>
      <button type="submit" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
          <span class="material-symbols-outlined" style="font-size: 15px;">delete</span>
          Delete
      </button>
  </form>
```

Add at the end of `views/operations/crew.php`:
```javascript
<script>
document.querySelectorAll('.delete-crew-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var name = this.getAttribute('data-crew-name') || 'this crew member';
        if (!confirm('Remove ' + name + ' from the roster? This cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>
```

---

### Issue 12: Potential DivisionByZeroError in Forms Summary

**Files**:
- `views/forms/summary.php` (line 31)

#### Fix in `views/forms/summary.php`
```diff
- $avgRate = $totalSubmissions > 0 ? round(array_sum(array_column($summary, 'response_rate')) / count($summary), 1) : 0;
+ $avgRate = ($totalSubmissions > 0 && !empty($summary))
+     ? round(array_sum(array_column($summary, 'response_rate')) / count($summary), 1)
+     : 0;
```

---

### Issue 13: Insights Dashboard Inconsistency with Dynamic Anomalies

**Files**:
- `src/Controller/InsightsController.php` (lines 51–70)

#### Fix in `src/Controller/InsightsController.php`
Query `anomaly_constraints` table instead of hardcoding outdated regex checks:
```php
$cStmt = $db->prepare('SELECT * FROM anomaly_constraints WHERE session_id = ? AND is_enabled = 1');
$cStmt->execute([$sid]);
$constraints = $cStmt->fetchAll(\PDO::FETCH_ASSOC);

// Use identical constraint evaluation as ParticipantController::evaluateConstraint
```

---

### Issue 14: Inconsistent Image Validation in Lost & Found Store

**Files**:
- `src/Controller/LostAndFoundController.php` (lines 145–155)

#### Fix in `src/Controller/LostAndFoundController.php`
Add binary image verification matching `update()`:
```diff
             if (!in_array($ext, $allowedExts, true)) {
                 $_SESSION['lf_message'] = 'Invalid image type "' . $ext . '". Allowed: JPG, PNG, GIF, WEBP.';
                 $_SESSION['lf_message_type'] = 'danger';
                 header('Location: /lost-and-found/create');
                 exit;
             }

+            // Verify binary content is an image
+            $imageInfo = @getimagesize($_FILES['photo']['tmp_name']);
+            if ($imageInfo === false) {
+                $_SESSION['lf_message'] = 'Uploaded file is not a valid image.';
+                $_SESSION['lf_message_type'] = 'danger';
+                header('Location: /lost-and-found/create');
+                exit;
+            }
```

---

### Issue 15: Missing Rate Limiting on Auth and Public Endpoints

**Files**:
- `Caddyfile`

#### Fix in `Caddyfile`
Add blocked file paths and request throttling rules:
```caddy
atcl.site, www.atcl.site {
    request_body {
        max_size 50MB
    }

    # Block sensitive paths
    @blocked {
        path /.git* /.env* /*.sql /*.zip /*.sh /*.bat /database/*
    }
    respond @blocked 403

    reverse_proxy web:80
}
```

---

### Issue 16: Database Connection Error Leaks Internal Details

**Files**:
- `src/bootstrap.php` (lines 64–66)

#### Fix in `src/bootstrap.php`
```diff
 } catch (PDOException $e) {
-    die('Database connection failed: ' . $e->getMessage());
+    error_log('Database connection error: ' . $e->getMessage());
+    http_response_code(500);
+    die('Service temporarily unavailable. Please try again shortly.');
 }
```
