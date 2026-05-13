<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Container;

class HomeController
{
    public function index(): void
    {
        if (Auth::check()) {
            $this->dashboard();
            return;
        }

        $title = 'ATCL - Register';
        $db = Container::get('db');
        $defaults = [
            'hero' => ['filename' => null, 'alt_text' => ''],
            'feature_1' => ['filename' => null, 'alt_text' => ''],
            'feature_2' => ['filename' => null, 'alt_text' => ''],
        ];
        $landingImages = array_merge($defaults, SettingsController::loadLandingImages($db));
        $landingSettings = SettingsController::loadLandingSettings($db);
        $registrationSettings = SettingsController::loadRegistrationSettings($db);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/home.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function dashboard(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Advisor / Committee Home';
        $stats = $this->loadDashboardStats();
        $registrationSettings = SettingsController::loadRegistrationSettings(Container::get('db'));

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/dashboard.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function public(): void
    {
        $title = 'ATCL - Register';

        $db = Container::get('db');
        $defaults = [
            'hero' => ['filename' => null, 'alt_text' => ''],
            'feature_1' => ['filename' => null, 'alt_text' => ''],
            'feature_2' => ['filename' => null, 'alt_text' => ''],
        ];
        $landingImages = array_merge($defaults, SettingsController::loadLandingImages($db));
        $landingSettings = SettingsController::loadLandingSettings($db);
        $registrationSettings = SettingsController::loadRegistrationSettings($db);
        $forcePublic = true;

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/home.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDashboardStats(): array
    {
        $db = Container::get('db');
        $stats = [];

        $stmt = $db->query('SELECT COUNT(*) as total,
            SUM(CASE WHEN checked_in_at IS NOT NULL THEN 1 ELSE 0 END) as checked_in
            FROM participants');
        $participantStats = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['participants'] = [
            'total' => (int)($participantStats['total'] ?? 0),
            'checked_in' => (int)($participantStats['checked_in'] ?? 0),
        ];

        $stmt = $db->query('SELECT COUNT(*) as total FROM claims');
        $stats['claims'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['total'];

        $stmt = $db->query('SELECT COUNT(*) as total FROM buying_requests');
        $stats['requests'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['total'];

        $stmt = $db->query('SELECT COUNT(*) as total FROM forms');
        $stats['forms'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['total'];

        return $stats;
    }
}
