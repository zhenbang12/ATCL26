<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Helper to manage the active event session.
 * Stores the selected session_id in the PHP session so every page
 * operates on the same session context.
 */
class SessionHelper
{
    /**
     * Return the active session_id (int).
     * If none selected yet, pick the session marked is_active=1,
     * or fall back to session id 1.
     */
    public static function currentSessionId(): int
    {
        if (isset($_SESSION['active_session_id']) && (int)$_SESSION['active_session_id'] > 0) {
            return (int)$_SESSION['active_session_id'];
        }

        // Auto-select: prefer is_active=1, else id=1
        $db = Container::get('db');
        try {
            $row = $db->query('SELECT id FROM sessions WHERE is_active = 1 ORDER BY id LIMIT 1')
                       ->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $_SESSION['active_session_id'] = (int)$row['id'];
                return (int)$row['id'];
            }
        } catch (\Exception $e) {
            // sessions table may not exist yet
        }

        $_SESSION['active_session_id'] = 1;
        return 1;
    }

    /**
     * Return full session record for the active session.
     */
    public static function currentSession(): ?array
    {
        $id = self::currentSessionId();
        $db = Container::get('db');
        try {
            $stmt = $db->prepare('SELECT * FROM sessions WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set the active session by id.
     */
    public static function setActiveSession(int $sessionId): void
    {
        $_SESSION['active_session_id'] = $sessionId;
    }

    /**
     * Return all sessions ordered by newest first.
     */
    public static function all(): array
    {
        $db = Container::get('db');
        try {
            return $db->query('SELECT * FROM sessions ORDER BY id DESC')
                      ->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }
}