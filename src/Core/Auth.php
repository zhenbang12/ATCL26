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
        $db = Container::get('db');

        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Prevent Session Fixation attacks
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
            ];
            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    /**
     * Returns true only if the current user has the 'superuser' role.
     */
    public static function isSuperuser(): bool
    {
        return self::role() === 'superuser';
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

