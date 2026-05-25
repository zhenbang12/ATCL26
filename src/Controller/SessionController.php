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
     * Delete a session and all its associated data (superuser only, requires password).
     */
    public function delete(): void
    {
        Auth::requireRole(['superuser']);

        $sessionId = (int)($_POST['session_id'] ?? 0);
        $password = trim((string)($_POST['password'] ?? ''));

        if ($sessionId <= 0) {
            $_SESSION['session_message'] = 'Invalid session.';
            $_SESSION['session_message_type'] = 'danger';
            header('Location: /sessions');
            exit;
        }

        if ($password === '') {
            $_SESSION['session_message'] = 'Password is required to delete a session.';
            $_SESSION['session_message_type'] = 'danger';
            header('Location: /sessions');
            exit;
        }

        // Verify password
        $user = Auth::user();
        $db = Container::get('db');
        $userStmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $userStmt->execute([(int)$user['id']]);
        $userRow = $userStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$userRow || !password_verify($password, $userRow['password_hash'])) {
            $_SESSION['session_message'] = 'Incorrect password. Session was not deleted.';
            $_SESSION['session_message_type'] = 'danger';
            header('Location: /sessions');
            exit;
        }

        // Prevent deleting the global default session
        $sessStmt = $db->prepare('SELECT id, name, is_active FROM sessions WHERE id = ?');
        $sessStmt->execute([$sessionId]);
        $session = $sessStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$session) {
            $_SESSION['session_message'] = 'Session not found.';
            $_SESSION['session_message_type'] = 'danger';
            header('Location: /sessions');
            exit;
        }

        if ((int)$session['id'] === 1) {
            $_SESSION['session_message'] = 'The default session cannot be deleted.';
            $_SESSION['session_message_type'] = 'danger';
            header('Location: /sessions');
            exit;
        }

        // Check if this is the current user's active session
        $currentActive = SessionHelper::currentSessionId();
        if ($currentActive === $sessionId) {
            $_SESSION['session_message'] = 'Cannot delete the session you are currently viewing. Switch to another session first.';
            $_SESSION['session_message_type'] = 'danger';
            header('Location: /sessions');
            exit;
        }

        try {
            $db->beginTransaction();

            // Delete all data associated with this session (in FK order)
            $tables = [
                'group_move_logs',
                'crew_attendance',
                'crew',
                'event_group_settings',
                'event_groups',
                'participants',
            ];
            foreach ($tables as $table) {
                $delStmt = $db->prepare("DELETE FROM {$table} WHERE session_id = ?");
                $delStmt->execute([$sessionId]);
            }

            // Delete the session itself
            $delSession = $db->prepare('DELETE FROM sessions WHERE id = ?');
            $delSession->execute([$sessionId]);

            $db->commit();

            $_SESSION['session_message'] = 'Session "' . htmlspecialchars($session['name']) . '" and all its data have been permanently deleted.';
            $_SESSION['session_message_type'] = 'success';
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['session_message'] = 'Could not delete session: ' . $e->getMessage();
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
