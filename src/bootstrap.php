<?php
declare(strict_types=1);

// Force PHP timezone to UTC+8 (Malaysia/Singapore time)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Load Composer autoloader if present (for local libraries like QR code)
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
}

// Start session for simple authentication
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.cookie_secure', $https ? '1' : '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Send HTTP Security Headers to prevent Clickjacking, XSS, and MIME-sniffing
if (!headers_sent()) {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
$config = require __DIR__ . '/config.php';

// Create a simple PDO connection (adjust DSN as needed)
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $config['db']['host'],
        $config['db']['name']
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Force MySQL connection to UTC+8
    $pdo->exec("SET time_zone = '+08:00';");
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Make shared services globally accessible via container-style helper
App\Core\Container::set('db', $pdo);
App\Core\Container::set('config', $config);


