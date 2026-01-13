<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;

class OperationsController
{
    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Event Operations & Crew';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/operations/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function crew(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Crew Management';
        $db = Container::get('db');
        $stmt = $db->query('SELECT id, full_name, role, assigned_group_code FROM crew ORDER BY full_name');
        $crew = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/operations/crew.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function games(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Games & Scores';
        $db = Container::get('db');
        $stmt = $db->query('SELECT g.id, g.name, s.group_code, s.score FROM games g LEFT JOIN scores s ON g.id = s.game_id ORDER BY g.name, s.group_code');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/operations/games.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }
}

