<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    /**
     * Get or create the session CSRF token.
     */
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

    /**
     * Render an HTML hidden input containing the CSRF token.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate the submitted CSRF token against the session token.
     */
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

    /**
     * Enforce CSRF token validation; terminate with 403 on failure.
     */
    public static function require(): void
    {
        if (!self::validate()) {
            http_response_code(403);
            die('Invalid or missing security token (CSRF). Please refresh the page and try again.');
        }
    }
}
