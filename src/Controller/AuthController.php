<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;

class AuthController
{
    public function showLogin(): void
    {
        $title = 'Login';
        $error = $_GET['error'] ?? null;

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/auth/login.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function login(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $attempts = (int)($_SESSION['login_attempts'] ?? 0);
        $lastAttempt = (int)($_SESSION['login_last_attempt'] ?? 0);

        if ($attempts >= 5 && (time() - $lastAttempt) < 300) {
            $remaining = 300 - (time() - $lastAttempt);
            $_SESSION['login_error'] = "Too many failed login attempts. Please wait {$remaining} seconds before trying again.";
            header('Location: /login?error=rate_limited');
            exit;
        }

        if (time() - $lastAttempt >= 300) {
            $_SESSION['login_attempts'] = 0;
            $attempts = 0;
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Prevent demo accounts from authenticating via the live login portal
        if (in_array(strtolower($username), ['demo_committee', 'demo_advisor'], true)) {
            $_SESSION['login_attempts'] = $attempts + 1;
            $_SESSION['login_last_attempt'] = time();
            usleep(500000); // 0.5s delay
            header('Location: /login?error=1');
            exit;
        }

        if (Auth::attempt($username, $password)) {
            unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt'], $_SESSION['login_error']);
            header('Location: /dashboard');
            exit;
        }

        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['login_last_attempt'] = time();

        // Delay to mitigate brute force credential stuffing
        usleep(500000); // 0.5s delay
        header('Location: /login?error=1');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /');
        exit;
    }
}
