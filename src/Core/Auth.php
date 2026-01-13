<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $username, string $password): bool
    {
        /** @var array $config */
        $config = Container::get('config');
        $users = $config['users'] ?? [];

        foreach ($users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                $_SESSION['user'] = [
                    'username' => $user['username'],
                    'role' => $user['role'],
                ];
                return true;
            }
        }

        return false;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    /**
     * Require that the current user has at least one of the given roles.
     * Redirects to /login if not authorised.
     */
    public static function requireRole(array $roles): void
    {
        $role = self::role();
        if ($role === null || !in_array($role, $roles, true)) {
            header('Location: /login');
            exit;
        }
    }
}

