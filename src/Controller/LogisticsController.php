<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;

class LogisticsController
{
    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Logistics & Resources';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/logistics/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function venues(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Venue Master Plan';
        $db = Container::get('db');
        $stmt = $db->query('SELECT id, name, location, capacity FROM venues ORDER BY name');
        $venues = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/logistics/venues.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function inventory(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Equipment & Inventory';
        $db = Container::get('db');
        $stmt = $db->query('SELECT id, item_name, category, quantity_available FROM inventory_items ORDER BY item_name');
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/logistics/inventory.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }
}

