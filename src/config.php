<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'atcl26',
        'user' => 'root',
        'pass' => '',
    ],
    'app' => [
        'base_url' => '/', // adjust if not at web root
    ],
    // Very simple in-memory users for demo purposes.
    // In a real system, move this to a database with hashed passwords.
    'users' => [
        [
            'username' => 'advisor1',
            'password' => 'password', // demo only
            'role'     => 'advisor',
        ],
        [
            'username' => 'chair1',
            'password' => 'password', // demo only
            'role'     => 'committee',
        ],
    ],
];

