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
     * In demo mode, HARD-LOCKS to the demo session ID to strictly isolate real event data.
     */
    public static function currentSessionId(): int
    {
        // AIRTIGHT SECURITY LOCK:
        // In demo mode or for any demo user, it is physically impossible to return
        // any session_id other than the isolated demonstration session.
        if (self::isDemoMode()) {
            return DemoHelper::getDemoSessionId();
        }

        // 1. If manually selected/overridden by the user, return it
        if (isset($_SESSION['active_session_id']) && (int)$_SESSION['active_session_id'] > 0) {
            return (int)$_SESSION['active_session_id'];
        }

        // 2. Otherwise, dynamically query the active default session from the database
        $db = Container::get('db');
        try {
            $row = $db->query('SELECT id FROM sessions WHERE is_active = 1 ORDER BY id LIMIT 1')
                       ->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                return (int)$row['id'];
            }
        } catch (\Exception $e) {
            // sessions table may not exist yet
        }

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
        if (self::isDemoMode()) {
            // Never allow session switching in demo mode
            $_SESSION['active_session_id'] = DemoHelper::getDemoSessionId();
            return;
        }
        $_SESSION['active_session_id'] = $sessionId;
    }

    /**
     * Return all sessions ordered by newest first.
     */
    public static function all(): array
    {
        $db = Container::get('db');
        try {
            if (self::isDemoMode()) {
                // In demo mode, NEVER leak other session names or IDs
                $demoId = DemoHelper::getDemoSessionId();
                $stmt = $db->prepare('SELECT * FROM sessions WHERE id = ?');
                $stmt->execute([$demoId]);
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            return $db->query('SELECT * FROM sessions ORDER BY id DESC')
                      ->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if currently running in interactive demonstration mode.
     * Evaluates session flag or whether authenticated as a demo account.
     */
    public static function isDemoMode(): bool
    {
        if (!empty($_SESSION['is_demo_mode'])) {
            return true;
        }
        $user = Auth::user();
        if ($user && in_array($user['username'] ?? '', ['demo_committee', 'demo_advisor'], true)) {
            return true;
        }
        return false;
    }

    /**
     * Set demo mode status in current session.
     */
    public static function setDemoMode(bool $active): void
    {
        if ($active) {
            $_SESSION['is_demo_mode'] = true;
            $_SESSION['active_session_id'] = DemoHelper::getDemoSessionId();
        } else {
            unset($_SESSION['is_demo_mode']);
            unset($_SESSION['active_session_id']);
        }
    }
}