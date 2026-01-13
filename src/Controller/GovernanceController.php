<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;

class GovernanceController
{
    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Project Governance & Administration';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/governance/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function tasks(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Task Timeline';
        $db = Container::get('db');
        $stmt = $db->query('SELECT id, title, status, due_date, depends_on_task_id FROM tasks ORDER BY due_date');
        $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/governance/tasks.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function proposals(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Proposals & Approvals';
        $db = Container::get('db');
        $stmt = $db->query('SELECT id, title, owner_name, status FROM proposals ORDER BY created_at DESC');
        $proposals = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/governance/proposals.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }
}

