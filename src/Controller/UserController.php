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
}
