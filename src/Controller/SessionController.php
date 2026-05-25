<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Container;
use App\Core\SessionHelper;

class SessionController
{
    /**
     * List all sessions and show create form.
     */
    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Event Sessions';
        $sessions = SessionHelper::all();
        $activeSessionId = SessionHelper::currentSessionId();

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/sessions/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    /**
     * Create form (redirects back to index with inline form).
     */
    public function create(): void
    {
        Auth::requireRole(['advisor', 'committee']);
        header('Location: /sessions');
        exit;
    }

    /**
     * Store a new session.
     */
    public function store(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        if ($name === '') {
            $_SESSION['session_message'] = 'Session name is required.';
            $_SESSION['session_message_type'] = 'danger';
            header('Location: /sessions');
            exit;
        }

        if (mb_strlen($name) > 255) {
            $name = mb_substr($name, 0, 255);
        }

        try {
            $stmt = $db->prepare('INSERT INTO sessions (name, description, is_active) VALUES (?, ?, 0)');
            $stmt->execute([$name, $description]);

            $newId = (int)$db->lastInsertId();
            $_SESSION['session_message'] = 'Session "' . htmlspecialchars($name) . '" created.';
            $_SESSION['session_message_type'] = 'success';

            // Auto-activate the new session
            SessionHelper::setActiveSession($newId);
        } catch (\Exception $e) {
            $_SESSION['session_message'] = 'Could not create session: ' . $e->getMessage();
            $_SESSION['session_message_type'] = 'danger';
        }

        header('Location: /sessions');
        exit;
    }

    /**
     * Switch to a session (per-user only, does not affect other users).
     */
    public function activate(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $sessionId = (int)($_POST['session_id'] ?? 0);

        if ($sessionId <= 0) {
            $_SESSION['session_message'] = 'Invalid session.';
            $_SESSION['session_message_type'] = 'danger';
            header('Location: /sessions');
            exit;
        }

        $db = Container::get('db');
        try {
            $stmt = $db->prepare('SELECT id, name FROM sessions WHERE id = ?');
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$session) {
                $_SESSION['session_message'] = 'Session not found.';
                $_SESSION['session_message_type'] = 'danger';
                header('Location: /sessions');
                exit;
            }

            // Only update your own PHP session (per-user switching)
            SessionHelper::setActiveSession($sessionId);

            $_SESSION['session_message'] = 'Switched to session: ' . htmlspecialchars($session['name']);
            $_SESSION['session_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['session_message'] = 'Could not switch session: ' . $e->getMessage();
            $_SESSION['session_message_type'] = 'danger';
        }

        header('Location: /sessions');
        exit;
    }

    /**
     * Set a session as the global default (superuser only).
     * This determines which session public pages and new logins use.
     */
    public function setDefault(): void
    {
        Auth::requireRole(['superuser']);

        $sessionId = (int)($_POST['session_id'] ?? 0);

        if ($sessionId <= 0) {
            $_SESSION['session_message'] = 'Invalid session.';
            $_SESSION['session_message_type'] = 'danger';
            header('Location: /sessions');
            exit;
        }

        $db = Container::get('db');
        try {
            $stmt = $db->prepare('SELECT id, name FROM sessions WHERE id = ?');
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$session) {
                $_SESSION['session_message'] = 'Session not found.';
                $_SESSION['session_message_type'] = 'danger';
                header('Location: /sessions');
                exit;
            }

            // Update global default: deactivate all, activate selected
            $db->exec('UPDATE sessions SET is_active = 0');
            $upd = $db->prepare('UPDATE sessions SET is_active = 1 WHERE id = ?');
            $upd->execute([$sessionId]);

            // Also set this user's active session
            SessionHelper::setActiveSession($sessionId);

            $_SESSION['session_message'] = '"' . htmlspecialchars($session['name']) . '" is now the default session for public pages and new logins.';
            $_SESSION['session_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['session_message'] = 'Could not set default: ' . $e->getMessage();
            $_SESSION['session_message_type'] = 'danger';
        }

        header('Location: /sessions');
        exit;
    }
}
