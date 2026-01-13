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
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (Auth::attempt($username, $password)) {
            header('Location: /');
            exit;
        }

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

