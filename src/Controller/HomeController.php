<?php
declare(strict_types=1);

namespace App\Controller;

class HomeController
{
    public function index(): void
    {
        $title = 'Camp Management System';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/home.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }
}

