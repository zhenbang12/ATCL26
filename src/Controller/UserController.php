<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Container;

class UserController
{
    /**
     * Show all users with a reset password form for each.
     */
    public function index(): void
    {
        Auth::requireRole(['superuser']);

        $db = Container::get('db');
        $title = 'User Management';

        try {
            $stmt = $db->query('SELECT id, username, role, created_at, updated_at FROM users ORDER BY id ASC');
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $users = [];
        }

        $message     = $_SESSION['user_mgmt_message'] ?? null;
        $messageType = $_SESSION['user_mgmt_message_type'] ?? 'success';
        unset($_SESSION['user_mgmt_message'], $_SESSION['user_mgmt_message_type']);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/users/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    /**
     * Reset a user's password (POST).
     */
    public function resetPassword(): void
    {
        Auth::requireRole(['superuser']);

        $db         = Container::get('db');
        $userId     = (int)($_POST['user_id'] ?? 0);
        $newPassword = trim((string)($_POST['new_password'] ?? ''));
        $confirm    = trim((string)($_POST['confirm_password'] ?? ''));

        if ($userId <= 0) {
            $_SESSION['user_mgmt_message'] = 'Invalid user.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['user_mgmt_message'] = 'Password must be at least 8 characters.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        if ($newPassword !== $confirm) {
            $_SESSION['user_mgmt_message'] = 'Passwords do not match.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        try {
            // Fetch username for display
            $stmt = $db->prepare('SELECT username FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['user_mgmt_message'] = 'User not found.';
                $_SESSION['user_mgmt_message_type'] = 'danger';
                header('Location: /users');
                exit;
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd  = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([$hash, $userId]);

            $_SESSION['user_mgmt_message'] = 'Password for "' . htmlspecialchars($user['username']) . '" has been reset successfully.';
            $_SESSION['user_mgmt_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['user_mgmt_message'] = 'Failed to reset password: ' . $e->getMessage();
            $_SESSION['user_mgmt_message_type'] = 'danger';
        }

        header('Location: /users');
        exit;
    }

    /**
     * Create a new user (POST).
     */
    public function create(): void
    {
        Auth::requireRole(['superuser']);

        $db = Container::get('db');
        $username = trim((string)($_POST['username'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'committee'));
        $password = trim((string)($_POST['password'] ?? ''));
        $confirm = trim((string)($_POST['confirm_password'] ?? ''));

        if ($username === '') {
            $_SESSION['user_mgmt_message'] = 'Username is required.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        // Validate username characters
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $_SESSION['user_mgmt_message'] = 'Username must be 3-30 characters long and contain only letters, numbers, and underscores.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        // Validate role
        if (!in_array($role, ['superuser', 'advisor', 'committee', 'treasurer'], true)) {
            $_SESSION['user_mgmt_message'] = 'Invalid role selected.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['user_mgmt_message'] = 'Password must be at least 8 characters.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        if ($password !== $confirm) {
            $_SESSION['user_mgmt_message'] = 'Passwords do not match.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        try {
            // Check if username already exists
            $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ((int)$stmt->fetchColumn() > 0) {
                $_SESSION['user_mgmt_message'] = 'Username already exists.';
                $_SESSION['user_mgmt_message_type'] = 'danger';
                header('Location: /users');
                exit;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $db->prepare('INSERT INTO users (username, role, password_hash) VALUES (?, ?, ?)');
            $ins->execute([$username, $role, $hash]);

            $_SESSION['user_mgmt_message'] = 'User "' . htmlspecialchars($username) . '" has been created successfully.';
            $_SESSION['user_mgmt_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['user_mgmt_message'] = 'Failed to create user: ' . $e->getMessage();
            $_SESSION['user_mgmt_message_type'] = 'danger';
        }

        header('Location: /users');
        exit;
    }

    /**
     * Delete a user (POST).
     */
    public function delete(): void
    {
        Auth::requireRole(['superuser']);

        $db = Container::get('db');
        $userId = (int)($_POST['user_id'] ?? 0);
        $currentUser = Auth::user();

        if ($userId <= 0) {
            $_SESSION['user_mgmt_message'] = 'Invalid user ID.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        // Prevent self-deletion
        if ($currentUser && (int)$currentUser['id'] === $userId) {
            $_SESSION['user_mgmt_message'] = 'You cannot delete your own account.';
            $_SESSION['user_mgmt_message_type'] = 'danger';
            header('Location: /users');
            exit;
        }

        try {
            // Fetch username for display
            $stmt = $db->prepare('SELECT username FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['user_mgmt_message'] = 'User not found.';
                $_SESSION['user_mgmt_message_type'] = 'danger';
                header('Location: /users');
                exit;
            }

            $del = $db->prepare('DELETE FROM users WHERE id = ?');
            $del->execute([$userId]);

            $_SESSION['user_mgmt_message'] = 'User "' . htmlspecialchars($user['username']) . '" has been deleted successfully.';
            $_SESSION['user_mgmt_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['user_mgmt_message'] = 'Failed to delete user: ' . $e->getMessage();
            $_SESSION['user_mgmt_message_type'] = 'danger';
        }

        header('Location: /users');
        exit;
    }
}
