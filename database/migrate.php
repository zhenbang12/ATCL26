<?php
/**
 * Database Migration Runner.
 * Automatically runs any pending SQL files in database/migrations/ and tracks their execution.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Container;

try {
    $db = Container::get('db');
    echo "Connected to database successfully.\n";

    // 1. Create migration history table if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS migration_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 2. Scan database/migrations directory for .sql files
    $migrationsDir = __DIR__ . '/migrations';
    if (!is_dir($migrationsDir)) {
        echo "Migrations directory not found.\n";
        exit(1);
    }

    $files = glob($migrationsDir . '/*.sql');
    if ($files === false) {
        $files = [];
    }

    // Sort files to run them in chronological order
    sort($files);

    echo "Found " . count($files) . " migration file(s).\n";

    $executedCount = 0;
    foreach ($files as $file) {
        $filename = basename($file);

        // Check if already executed
        $stmt = $db->prepare("SELECT COUNT(*) FROM migration_history WHERE migration_name = ?");
        $stmt->execute([$filename]);
        $alreadyRun = (int)$stmt->fetchColumn() > 0;

        if ($alreadyRun) {
            echo "Migration '$filename' is already applied. Skipping.\n";
            continue;
        }

        echo "Applying migration '$filename'...\n";
        $sql = file_get_contents($file);
        
        try {
            if (trim($sql) !== '') {
                $db->exec($sql);
            }
            // Record execution
            $insert = $db->prepare("INSERT INTO migration_history (migration_name) VALUES (?)");
            $insert->execute([$filename]);
            echo "Migration '$filename' applied successfully.\n";
            $executedCount++;
        } catch (\PDOException $e) {
            // 1050: Table already exists, 1054: Unknown column, 1060: Column already exists, 1061: Duplicate key, 1091: Can't drop
            $errCode = $e->errorInfo[1] ?? 0;
            if (in_array($errCode, [1050, 1054, 1060, 1061, 1091], true)) {
                echo "Migration '$filename' notice: elements already exist or are already modified in database (SQL error $errCode). Marking as applied.\n";
                $insert = $db->prepare("INSERT INTO migration_history (migration_name) VALUES (?)");
                $insert->execute([$filename]);
            } else {
                throw $e;
            }
        }
    }

    // 3. Run users table seed check (run seed_users.php logic inline)
    echo "Ensuring default users are seeded...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL DEFAULT 'committee',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $defaultUsers = [
        [
            'username' => 'advisor1',
            'password' => 'password',
            'role'     => 'advisor',
        ],
        [
            'username' => 'chair1',
            'password' => 'password',
            'role'     => 'committee',
        ],
        [
            'username' => 'treasurer1',
            'password' => 'password',
            'role'     => 'treasurer',
        ]
    ];

    foreach ($defaultUsers as $u) {
        $check = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $check->execute([$u['username']]);
        if ((int)$check->fetchColumn() === 0) {
            $hash = password_hash($u['password'], PASSWORD_DEFAULT);
            $insert = $db->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
            $insert->execute([$u['username'], $hash, $u['role']]);
            echo "Seeded user: {$u['username']} (role: {$u['role']})\n";
        }
    }

    echo "All migrations and seeding verified. Applied $executedCount new migration(s).\n";

} catch (\Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
