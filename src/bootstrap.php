<?php
declare(strict_types=1);

// Load Composer autoloader if present (for local libraries like QR code)
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
}

// Start session for simple authentication
if (session_status() === PHP_SESSION_NONE) {
    // Configure session for ngrok compatibility
    // ngrok provides HTTPS but we're proxying, so don't require secure cookies
    ini_set('session.cookie_secure', '0');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
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
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Make shared services globally accessible via container-style helper
App\Core\Container::set('db', $pdo);
App\Core\Container::set('config', $config);


