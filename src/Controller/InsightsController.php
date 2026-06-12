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
            SELECT student_email, registration_type, checked_in_at, exclude_from_anomalies 
            FROM participants 
            WHERE duplicate_of IS NULL AND session_id = ?
        ');
        $summaryStmt->execute([$sid]);
        $participants = $summaryStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $totalActive = 0;
        $checkedIn = 0;
        $preRegister = 0;
        $walkIn = 0;
        $preRegisterCheckedIn = 0;
        $walkInCheckedIn = 0;

        foreach ($participants as $p) {
            $email = trim((string)($p['student_email'] ?? ''));

            // Check if anomaly (must not be whitelisted)
            $isAnomaly = false;
            if ((int)($p['exclude_from_anomalies'] ?? 0) === 0) {
                if ($email === '') {
                    $isAnomaly = true;
                } else {
                    $parts = explode('@', $email);
                    $username = $parts[0] ?? '';
                    $domain = isset($parts[1]) ? strtolower(trim($parts[1])) : '';

                    if ($domain !== 'student.tarc.edu.my') {
                        $isAnomaly = true;
                    } else {
                        $is26IntakeID = preg_match('/^26/i', $username);
                        $is26IntakeName = preg_match('/w[a-z0-9]26$/i', $username);
                        if (!$is26IntakeID && !$is26IntakeName) {
                            $isAnomaly = true;
                        }
                    }
                }
            }

            if ($isAnomaly) {
                continue;
            }

            $totalActive++;
            $isCheckedIn = !empty($p['checked_in_at']);
            if ($isCheckedIn) {
                $checkedIn++;
            }
            if (($p['registration_type'] ?? 'pre_register') === 'walk_in') {
                $walkIn++;
                if ($isCheckedIn) {
                    $walkInCheckedIn++;
                }
            } else {
                $preRegister++;
                if ($isCheckedIn) {
                    $preRegisterCheckedIn++;
                }
            }
        }

        $preRegisterDropout = max(0, $preRegister - $preRegisterCheckedIn);
        $walkInDropout = max(0, $walkIn - $walkInCheckedIn);
        $preRegisterDropoutRate = $preRegister > 0 ? round(($preRegisterDropout / $preRegister) * 100, 1) : 0;
        $walkInDropoutRate = $walkIn > 0 ? round(($walkInDropout / $walkIn) * 100, 1) : 0;

        $summary = [
            'total_active' => $totalActive,
            'checked_in' => $checkedIn,
            'pre_register' => $preRegister,
            'walk_in' => $walkIn,
            'pre_register_checked_in' => $preRegisterCheckedIn,
            'walk_in_checked_in' => $walkInCheckedIn,
            'pre_register_dropout' => $preRegisterDropout,
            'walk_in_dropout' => $walkInDropout,
            'pre_register_dropout_rate' => $preRegisterDropoutRate,
            'walk_in_dropout_rate' => $walkInDropoutRate
        ];

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
