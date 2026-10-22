<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Container;
use App\Core\DemoHelper;
use App\Core\SessionHelper;

/**
 * Controller for the dedicated Demo & Presentation Portal.
 * Allows instant, 1-click role logins and sandboxed demonstration of all ATCL features.
 */
class DemoController
{
    /**
     * Show the Demo Portal landing hub.
     */
    public function index(): void
    {
        $title = 'Interactive Demo Portal';

        $demoSessionId = DemoHelper::getDemoSessionId();
        DemoHelper::ensureDemoUsers();
        DemoHelper::ensureDemoDataSeeded($demoSessionId);

        $stats = DemoHelper::getDemoStats($demoSessionId);

        // Fetch a few sample participants for the quick reference cheatsheet
        $db = Container::get('db');
        $sampleParticipants = [];
        try {
            $stmt = $db->prepare('SELECT full_name, student_id, faculty, preferred_language, group_code, checked_in_at 
                FROM participants WHERE session_id = ? ORDER BY id ASC LIMIT 8');
            $stmt->execute([$demoSessionId]);
            $sampleParticipants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // ignore
        }

        $isDemoModeActive = SessionHelper::isDemoMode();
        $currentUser = Auth::user();

        // Flash message
        $flash = $_SESSION['demo_flash'] ?? null;
        $flashType = $_SESSION['demo_flash_type'] ?? 'info';
        unset($_SESSION['demo_flash'], $_SESSION['demo_flash_type']);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/demo/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    /**
     * One-click demo login into sandboxed session.
     */
    public function quickLogin(): void
    {
        $role = trim((string)($_POST['role'] ?? 'committee'));
        $target = trim((string)($_POST['target'] ?? '/dashboard'));

        // Whitelist safe redirect paths
        $allowedTargets = [
            '/dashboard',
            '/participants',
            '/participants/groups',
            '/participants/checkin',
            '/participants/assign-buddy',
            '/operations/crew',
            '/insights',
            '/lost-and-found',
            '/settings/theme',
            '/participants/duplicates',
            '/participants/anomalies',
        ];

        if (!in_array($target, $allowedTargets, true)) {
            $target = '/dashboard';
        }

        $demoSessionId = DemoHelper::getDemoSessionId();
        DemoHelper::ensureDemoUsers();
        DemoHelper::ensureDemoDataSeeded($demoSessionId);

        $user = DemoHelper::getDemoUser($role === 'advisor' ? 'advisor' : 'committee');

        if ($user) {
            Auth::login($user);
            SessionHelper::setDemoMode(true);
            SessionHelper::setActiveSession($demoSessionId);

            $_SESSION['demo_flash'] = 'Welcome! You are now demonstrating as ' . ($role === 'advisor' ? 'Advisor' : 'Committee Member') . ' in an isolated sandbox.';
            $_SESSION['demo_flash_type'] = 'success';
        }

        header('Location: ' . $target);
        exit;
    }

    /**
     * Start public participant demo view.
     */
    public function startParticipantDemo(): void
    {
        $target = trim((string)($_GET['target'] ?? '/participants/create'));
        $allowedTargets = [
            '/',
            '/public',
            '/participants/create',
            '/participants/lookup',
            '/lost-and-found/public',
        ];

        if (!in_array($target, $allowedTargets, true)) {
            $target = '/participants/create';
        }

        $demoSessionId = DemoHelper::getDemoSessionId();
        DemoHelper::ensureDemoDataSeeded($demoSessionId);

        // If logged in as demo user, log out to give authentic participant view
        if (Auth::check()) {
            Auth::logout();
        }

        SessionHelper::setDemoMode(true);
        SessionHelper::setActiveSession($demoSessionId);

        header('Location: ' . $target);
        exit;
    }

    /**
     * Reset demo session data to fresh sample state.
     */
    public function resetDemoData(): void
    {
        $demoSessionId = DemoHelper::getDemoSessionId();

        try {
            DemoHelper::resetDemoData($demoSessionId);
            $_SESSION['demo_flash'] = 'Demo data successfully reset! All sample participants, groups, and senior buddies have been restored.';
            $_SESSION['demo_flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['demo_flash'] = 'Could not reset demo data: ' . $e->getMessage();
            $_SESSION['demo_flash_type'] = 'danger';
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/demo';
        header('Location: ' . $referer);
        exit;
    }

    /**
     * Exit demo mode and restore standard live site environment.
     */
    public function exitDemo(): void
    {
        $user = Auth::user();
        if ($user && in_array($user['username'] ?? '', ['demo_committee', 'demo_advisor'], true)) {
            Auth::logout();
        }

        SessionHelper::setDemoMode(false);
        unset($_SESSION['active_session_id']);

        header('Location: /');
        exit;
    }
}
