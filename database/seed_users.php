<?php
/**
 * Database Seeder and Migration Runner for users table.
 * Sets up the users table and seeds default accounts with hashed passwords.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Container;

try {
    $db = Container::get('db');
    echo "Connected to database successfully.\n";

    // 1. Create the users table if it does not exist
    echo "Ensuring 'users' table exists...\n";
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
    echo "Table 'users' verified/created.\n";

    // 2. Check and seed default users
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
        ],
        [
            'username' => 'superuser',
            'password' => 'changeme',   // <-- CHANGE THIS before running on server
            'role'     => 'superuser',
        ],
    ];


    foreach ($defaultUsers as $u) {
        $check = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $check->execute([$u['username']]);
        if ((int)$check->fetchColumn() === 0) {
            $hash = password_hash($u['password'], PASSWORD_DEFAULT);
            $insert = $db->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
            $insert->execute([$u['username'], $hash, $u['role']]);
            echo "Seeded user: {$u['username']} (role: {$u['role']})\n";
        } else {
            echo "User '{$u['username']}' already exists. Skipping.\n";
        }
    }

    echo "Seeding completed successfully.\n";
} catch (\Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
