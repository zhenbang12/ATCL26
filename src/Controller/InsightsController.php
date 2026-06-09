<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Container;
use App\Core\SessionHelper;

class InsightsController
{
    /** Return active session_id shortcut */
    private function sid(): int
    {
        return SessionHelper::currentSessionId();
    }

    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Insights & Graphs';
        $db = Container::get('db');
        $sid = $this->sid();

        // 1. Get Session Info
        $sessionStmt = $db->prepare('SELECT name FROM sessions WHERE id = ?');
        $sessionStmt->execute([$sid]);
        $sessionName = $sessionStmt->fetchColumn() ?: 'Default Session';

        // 2. Summary stats
        $summaryStmt = $db->prepare('
            SELECT 
                COUNT(*) as total_active,
                SUM(CASE WHEN checked_in_at IS NOT NULL THEN 1 ELSE 0 END) as checked_in,
                SUM(CASE WHEN registration_type = "pre_register" THEN 1 ELSE 0 END) as pre_register,
                SUM(CASE WHEN registration_type = "walk_in" THEN 1 ELSE 0 END) as walk_in
            FROM participants 
            WHERE duplicate_of IS NULL AND session_id = ?
        ');
        $summaryStmt->execute([$sid]);
        $summary = $summaryStmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_active' => 0,
            'checked_in' => 0,
            'pre_register' => 0,
            'walk_in' => 0
        ];

        // Ensure keys are integer
        $summary['total_active'] = (int)($summary['total_active'] ?? 0);
        $summary['checked_in'] = (int)($summary['checked_in'] ?? 0);
        $summary['pre_register'] = (int)($summary['pre_register'] ?? 0);
        $summary['walk_in'] = (int)($summary['walk_in'] ?? 0);

        // Get duplicate count
        $dupStmt = $db->prepare('
            SELECT COUNT(*) FROM participants WHERE duplicate_of IS NOT NULL AND session_id = ?
        ');
        $dupStmt->execute([$sid]);
        $summary['duplicates'] = (int)$dupStmt->fetchColumn();

        // 3. Registrations flow over time (by Date)
        $regOverTimeStmt = $db->prepare('
            SELECT DATE(created_at) as reg_date, COUNT(*) as count
            FROM participants
            WHERE duplicate_of IS NULL AND session_id = ?
            GROUP BY DATE(created_at)
            ORDER BY reg_date ASC
        ');
        $regOverTimeStmt->execute([$sid]);
        $regOverTime = $regOverTimeStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // 4. Hourly registrations grouped by Date + Hour
        $hourlyRegStmt = $db->prepare('
            SELECT DATE(created_at) as reg_date, HOUR(created_at) as reg_hour, COUNT(*) as count
            FROM participants
            WHERE duplicate_of IS NULL AND session_id = ?
            GROUP BY DATE(created_at), HOUR(created_at)
            ORDER BY reg_date ASC, reg_hour ASC
        ');
        $hourlyRegStmt->execute([$sid]);
        $hourlyRegData = $hourlyRegStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // 5. Hourly check-ins grouped by Date + Hour
        $hourlyCheckinStmt = $db->prepare('
            SELECT DATE(checked_in_at) as checkin_date, HOUR(checked_in_at) as checkin_hour, COUNT(*) as count
            FROM participants
            WHERE duplicate_of IS NULL AND checked_in_at IS NOT NULL AND session_id = ?
            GROUP BY DATE(checked_in_at), HOUR(checked_in_at)
            ORDER BY checkin_date ASC, checkin_hour ASC
        ');
        $hourlyCheckinStmt->execute([$sid]);
        $hourlyCheckinData = $hourlyCheckinStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // 6. Faculty Distribution
        $facultyStmt = $db->prepare('
            SELECT faculty, COUNT(*) as count
            FROM participants
            WHERE duplicate_of IS NULL AND session_id = ?
            GROUP BY faculty
            ORDER BY count DESC
        ');
        $facultyStmt->execute([$sid]);
        $facultyDistribution = $facultyStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // 7. Preferred Language Distribution
        $langStmt = $db->prepare('
            SELECT preferred_language, COUNT(*) as count
            FROM participants
            WHERE duplicate_of IS NULL AND session_id = ?
            GROUP BY preferred_language
            ORDER BY count DESC
        ');
        $langStmt->execute([$sid]);
        $languageDistribution = $langStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // 8. Gender Distribution
        $genderStmt = $db->prepare('
            SELECT gender, COUNT(*) as count
            FROM participants
            WHERE duplicate_of IS NULL AND session_id = ?
            GROUP BY gender
            ORDER BY count DESC
        ');
        $genderStmt->execute([$sid]);
        $genderDistribution = $genderStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // 9. Group Code sizes (for balancing overview)
        $groupSizeStmt = $db->prepare("
            SELECT group_code, COUNT(*) as count
            FROM participants
            WHERE duplicate_of IS NULL AND group_code IS NOT NULL AND group_code != '' AND session_id = ?
            GROUP BY group_code
            ORDER BY CAST(group_code AS UNSIGNED), group_code
        ");
        $groupSizeStmt->execute([$sid]);
        $groupSizes = $groupSizeStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/insights/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }
}
