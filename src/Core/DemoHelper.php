<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Helper to manage the sandboxed interactive demonstration environment.
 * Ensures demo users, demo event session, and realistic presentation sample data.
 */
class DemoHelper
{
    public const DEMO_SESSION_NAME = 'Interactive Demo Session';

    private static ?int $cachedDemoSessionId = null;

    /**
     * Get or create the dedicated demo session ID.
     */
    public static function getDemoSessionId(): int
    {
        if (self::$cachedDemoSessionId !== null && self::$cachedDemoSessionId > 0) {
            return self::$cachedDemoSessionId;
        }

        $db = Container::get('db');
        try {
            $stmt = $db->prepare('SELECT id FROM sessions WHERE name = ? LIMIT 1');
            $stmt->execute([self::DEMO_SESSION_NAME]);
            $id = $stmt->fetchColumn();

            if ($id !== false && (int)$id > 0) {
                self::$cachedDemoSessionId = (int)$id;
                return self::$cachedDemoSessionId;
            }

            // Create new demo session
            $insert = $db->prepare('INSERT INTO sessions (name, description, is_active) VALUES (?, ?, 0)');
            $insert->execute([
                self::DEMO_SESSION_NAME,
                'Sandboxed demo session for presentation and testing. Production data is protected.'
            ]);
            $newId = (int)$db->lastInsertId();

            self::seedDemoData($newId);
            self::$cachedDemoSessionId = $newId;
            return $newId;
        } catch (\Exception $e) {
            // Under NO circumstance return a production session ID (such as 1, 2, 3, 4).
            try {
                $fallback = $db->query("SELECT id FROM sessions WHERE name LIKE '%Demo%' LIMIT 1")->fetchColumn();
                if ($fallback) {
                    self::$cachedDemoSessionId = (int)$fallback;
                    return self::$cachedDemoSessionId;
                }
            } catch (\Exception $ex) {
                // ignore
            }
            throw new \RuntimeException('Failed to locate or initialize isolated demo session: ' . $e->getMessage());
        }
    }

    /**
     * Check if a given session ID is the demo session.
     */
    public static function isDemoSession(int $sessionId): bool
    {
        return $sessionId > 0 && $sessionId === self::getDemoSessionId();
    }

    /**
     * Ensure default demo users exist in the database.
     */
    public static function ensureDemoUsers(): void
    {
        $db = Container::get('db');
        $demoUsers = [
            [
                'username' => 'demo_committee',
                'password' => 'demopass2026',
                'role'     => 'committee',
            ],
            [
                'username' => 'demo_advisor',
                'password' => 'demopass2026',
                'role'     => 'advisor',
            ],
        ];

        foreach ($demoUsers as $u) {
            $check = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $check->execute([$u['username']]);
            if (!$check->fetch()) {
                $hash = password_hash($u['password'], PASSWORD_DEFAULT);
                $ins = $db->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
                $ins->execute([$u['username'], $hash, $u['role']]);
            }
        }
    }

    /**
     * Retrieve user record for a specific demo role.
     */
    public static function getDemoUser(string $role): ?array
    {
        self::ensureDemoUsers();
        $db = Container::get('db');
        $username = $role === 'advisor' ? 'demo_advisor' : 'demo_committee';

        $stmt = $db->prepare('SELECT id, username, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Ensure sample data is present in the demo session.
     */
    public static function ensureDemoDataSeeded(int $sessionId): void
    {
        $db = Container::get('db');
        try {
            $stmt = $db->prepare('SELECT COUNT(*) FROM participants WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            if ((int)$stmt->fetchColumn() === 0) {
                self::seedDemoData($sessionId);
            }
        } catch (\Exception $e) {
            // Ignore if tables not ready
        }
    }

    /**
     * Seed realistic sample data into the demo session.
     */
    public static function seedDemoData(int $sessionId): void
    {
        $db = Container::get('db');

        try {
            $db->beginTransaction();

            // 1. Group settings
            $settingsStmt = $db->prepare('REPLACE INTO event_group_settings (session_id, id, max_per_group) VALUES (?, 1, 8)');
            $settingsStmt->execute([$sessionId]);

            // 2. Event Groups (3 English, 3 Mandarin)
            $groups = [
                ['group_code' => 'A1', 'language_pool' => 'english', 'sort_order' => 1, 'max_per_group' => 8],
                ['group_code' => 'A2', 'language_pool' => 'english', 'sort_order' => 2, 'max_per_group' => 8],
                ['group_code' => 'A3', 'language_pool' => 'english', 'sort_order' => 3, 'max_per_group' => 8],
                ['group_code' => 'B1', 'language_pool' => 'mandarin', 'sort_order' => 4, 'max_per_group' => 8],
                ['group_code' => 'B2', 'language_pool' => 'mandarin', 'sort_order' => 5, 'max_per_group' => 8],
                ['group_code' => 'B3', 'language_pool' => 'mandarin', 'sort_order' => 6, 'max_per_group' => 8],
            ];

            $grpStmt = $db->prepare('INSERT INTO event_groups (session_id, group_code, language_pool, sort_order, max_per_group) VALUES (?, ?, ?, ?, ?)');
            foreach ($groups as $g) {
                $grpStmt->execute([$sessionId, $g['group_code'], $g['language_pool'], $g['sort_order'], $g['max_per_group']]);
            }

            // 3. Crew / Senior Buddies
            $crewMembers = [
                ['full_name' => 'Alex Tan Wei Ming', 'email' => 'alextan@demo.atcl', 'role' => 'Senior Buddy Lead', 'assigned_group_code' => 'A1', 'is_medic' => 0, 'is_facilitator' => 1],
                ['full_name' => 'Beatrice Wong Shi Ying', 'email' => 'beatrice@demo.atcl', 'role' => 'Senior Buddy', 'assigned_group_code' => 'A2', 'is_medic' => 0, 'is_facilitator' => 1],
                ['full_name' => 'Chloe Lim Mei Xuan', 'email' => 'chloe@demo.atcl', 'role' => 'Senior Buddy', 'assigned_group_code' => 'A3', 'is_medic' => 0, 'is_facilitator' => 1],
                ['full_name' => 'Daniel Lee Jun Wei', 'email' => 'daniel@demo.atcl', 'role' => 'Senior Buddy Lead', 'assigned_group_code' => 'B1', 'is_medic' => 0, 'is_facilitator' => 1],
                ['full_name' => 'Emily Chen Yu Ting', 'email' => 'emily@demo.atcl', 'role' => 'Senior Buddy', 'assigned_group_code' => 'B2', 'is_medic' => 0, 'is_facilitator' => 1],
                ['full_name' => 'Fiona Ng Xin Yi', 'email' => 'fiona@demo.atcl', 'role' => 'Senior Buddy', 'assigned_group_code' => 'B3', 'is_medic' => 0, 'is_facilitator' => 1],
                ['full_name' => 'Jason Khoo Zhen Yang', 'email' => 'jasonkhoo@demo.atcl', 'role' => 'First Aider / Medic', 'assigned_group_code' => null, 'is_medic' => 1, 'is_facilitator' => 0],
                ['full_name' => 'Kevin Low Kai Seng', 'email' => 'kevinlow@demo.atcl', 'role' => 'Logistics Coordinator', 'assigned_group_code' => null, 'is_medic' => 0, 'is_facilitator' => 0],
            ];

            $crewStmt = $db->prepare('INSERT INTO crew (session_id, full_name, email, role, assigned_group_code, is_medic, is_facilitator) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($crewMembers as $c) {
                $crewStmt->execute([$sessionId, $c['full_name'], $c['email'], $c['role'], $c['assigned_group_code'], $c['is_medic'], $c['is_facilitator']]);
            }

            // 4. Sample Participants (Realistic TAR UMT programmes)
            $participants = [
                // Group A1 (English) - Checked In
                ['full_name' => 'Marcus Tan Wei Lun', 'ic' => '040512-14-1123', 'sid' => '24WMR01234', 'email' => 'marcustan-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Diploma', 'period' => 'May', 'prog' => 'Diploma in Computer Science', 'fac' => 'FOCS', 'gender' => 'Male', 'phone' => '012-3456701', 'lang' => 'English', 'type' => 'pre_register', 'group' => 'A1', 'checked_in' => '2026-10-22 08:15:00'],
                ['full_name' => 'Chong Kah Wei', 'ic' => '040822-10-5541', 'sid' => '24WMR01236', 'email' => 'chongkw-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Mechanical Engineering', 'fac' => 'FOET', 'gender' => 'Male', 'phone' => '012-3456702', 'lang' => 'English', 'type' => 'pre_register', 'group' => 'A1', 'checked_in' => '2026-10-22 08:20:00'],
                ['full_name' => 'Muhammad Danish bin Azlan', 'ic' => '050110-14-8833', 'sid' => '24WMR01244', 'email' => 'danish-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Electrical Engineering', 'fac' => 'FOET', 'gender' => 'Male', 'phone' => '012-3456703', 'lang' => 'English', 'type' => 'pre_register', 'group' => 'A1', 'checked_in' => '2026-10-22 08:30:00'],

                // Group A2 (English) - Checked In
                ['full_name' => 'Nurul Aisyah binti Rahman', 'ic' => '050315-10-6622', 'sid' => '24WMR01237', 'email' => 'aisyah-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Diploma', 'period' => 'May', 'prog' => 'Diploma in Communication', 'fac' => 'FCCI', 'gender' => 'Female', 'phone' => '012-3456704', 'lang' => 'English', 'type' => 'pre_register', 'group' => 'A2', 'checked_in' => '2026-10-22 08:25:00'],
                ['full_name' => 'Darren Lim Ming Xuan', 'ic' => '041103-14-3311', 'sid' => '24WMR01240', 'email' => 'darrenlim-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Diploma', 'period' => 'May', 'prog' => 'Diploma in Mechatronics', 'fac' => 'FOET', 'gender' => 'Male', 'phone' => '012-3456705', 'lang' => 'English', 'type' => 'walk_in', 'group' => 'A2', 'checked_in' => '2026-10-22 08:45:00'],
                ['full_name' => 'Amanda Ong Li Min', 'ic' => '040918-14-9988', 'sid' => '24WMR01247', 'email' => 'amandaong-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Diploma', 'period' => 'May', 'prog' => 'Diploma in Architecture', 'fac' => 'FOBE', 'gender' => 'Female', 'phone' => '012-3456706', 'lang' => 'English', 'type' => 'pre_register', 'group' => 'A2', 'checked_in' => '2026-10-22 08:35:00'],

                // Group A3 (English) - Checked In
                ['full_name' => 'Priya a/p Subramaniam', 'ic' => '050220-10-7712', 'sid' => '24WMR01241', 'email' => 'priya-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Business Administration', 'fac' => 'FAFB', 'gender' => 'Female', 'phone' => '012-3456707', 'lang' => 'English', 'type' => 'pre_register', 'group' => 'A3', 'checked_in' => '2026-10-22 08:40:00'],
                ['full_name' => 'Bryan Teh Shen Yao', 'ic' => '060105-14-1199', 'sid' => '24WMR01242', 'email' => 'bryanteh-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Foundation', 'period' => 'May', 'prog' => 'Foundation in Computing', 'fac' => 'FOCS', 'gender' => 'Male', 'phone' => '012-3456708', 'lang' => 'English', 'type' => 'pre_register', 'group' => 'A3', 'checked_in' => '2026-10-22 08:50:00'],

                // Group B1 (Mandarin) - Checked In
                ['full_name' => 'Sarah Lee Zhi Xin', 'ic' => '040714-14-2210', 'sid' => '24WMR01235', 'email' => 'sarahlee-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Accounting', 'fac' => 'FAFB', 'gender' => 'Female', 'phone' => '012-3456709', 'lang' => 'Mandarin', 'type' => 'pre_register', 'group' => 'B1', 'checked_in' => '2026-10-22 08:18:00'],
                ['full_name' => 'Wong Jun Jie', 'ic' => '041201-14-6655', 'sid' => '24WMR01238', 'email' => 'wongjj-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Software Engineering', 'fac' => 'FOCS', 'gender' => 'Male', 'phone' => '012-3456710', 'lang' => 'Mandarin', 'type' => 'pre_register', 'group' => 'B1', 'checked_in' => '2026-10-22 08:22:00'],

                // Group B2 (Mandarin) - Checked In
                ['full_name' => 'Chloe Chew Xin Yi', 'ic' => '040329-10-3344', 'sid' => '24WMR01239', 'email' => 'chloechew-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Interior Architecture', 'fac' => 'FOBE', 'gender' => 'Female', 'phone' => '012-3456711', 'lang' => 'Mandarin', 'type' => 'pre_register', 'group' => 'B2', 'checked_in' => '2026-10-22 08:32:00'],
                ['full_name' => 'Kelly Lim Zhi Qi', 'ic' => '050619-14-4412', 'sid' => '24WMR01243', 'email' => 'kellylim-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Creative Media', 'fac' => 'FCCI', 'gender' => 'Female', 'phone' => '012-3456712', 'lang' => 'Mandarin', 'type' => 'pre_register', 'group' => 'B2', 'checked_in' => '2026-10-22 08:48:00'],

                // Group B3 (Mandarin) - Checked In
                ['full_name' => 'Evelyn Ng Siew Ling', 'ic' => '050408-10-5590', 'sid' => '24WMR01245', 'email' => 'evelynng-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Diploma', 'period' => 'May', 'prog' => 'Diploma in Finance', 'fac' => 'FAFB', 'gender' => 'Female', 'phone' => '012-3456713', 'lang' => 'Mandarin', 'type' => 'pre_register', 'group' => 'B3', 'checked_in' => '2026-10-22 08:52:00'],
                ['full_name' => 'Goh Jing Hang', 'ic' => '040625-14-8877', 'sid' => '24WMR01246', 'email' => 'gohjh-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Data Science', 'fac' => 'FOCS', 'gender' => 'Male', 'phone' => '012-3456714', 'lang' => 'Mandarin', 'type' => 'pre_register', 'group' => 'B3', 'checked_in' => '2026-10-22 08:55:00'],

                // Unassigned Participants (Ready to demonstrate Auto-Grouping & QR Check-in)
                ['full_name' => 'Nicholas Yeoh Wen Kang', 'ic' => '041011-14-7722', 'sid' => '24WMR01248', 'email' => 'nicholasyeoh-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Diploma', 'period' => 'May', 'prog' => 'Diploma in Civil Engineering', 'fac' => 'FOET', 'gender' => 'Male', 'phone' => '012-3456715', 'lang' => 'English', 'type' => 'pre_register', 'group' => null, 'checked_in' => null],
                ['full_name' => 'Tan Xin Ru', 'ic' => '060214-10-2233', 'sid' => '24WMR01249', 'email' => 'tanxr-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Foundation', 'period' => 'May', 'prog' => 'Foundation in Arts', 'fac' => 'FAFB', 'gender' => 'Female', 'phone' => '012-3456716', 'lang' => 'Mandarin', 'type' => 'pre_register', 'group' => null, 'checked_in' => null],
                ['full_name' => 'Lucas Fong Chee Seng', 'ic' => '040819-14-5511', 'sid' => '24WMR01250', 'email' => 'lucasfong-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Artificial Intelligence', 'fac' => 'FOCS', 'gender' => 'Male', 'phone' => '012-3456717', 'lang' => 'English', 'type' => 'pre_register', 'group' => null, 'checked_in' => null],
                ['full_name' => 'Hannah Tay Yu Xuan', 'ic' => '050503-14-6677', 'sid' => '24WMR01251', 'email' => 'hannahtay-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Advertising', 'fac' => 'FCCI', 'gender' => 'Female', 'phone' => '012-3456718', 'lang' => 'Mandarin', 'type' => 'pre_register', 'group' => null, 'checked_in' => null],
                ['full_name' => 'Ivan Chan Kian Hong', 'ic' => '040212-14-4499', 'sid' => '24WMR01252', 'email' => 'ivanchan-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Electronics', 'fac' => 'FOET', 'gender' => 'Male', 'phone' => '012-3456719', 'lang' => 'English', 'type' => 'walk_in', 'group' => null, 'checked_in' => '2026-10-22 09:10:00'],
                ['full_name' => 'Rachel Wong Huey Min', 'ic' => '040927-10-1188', 'sid' => '24WMR01253', 'email' => 'rachelwong-wm24@student.tarc.edu.my', 'intake' => '202605', 'level' => 'Degree', 'period' => 'May', 'prog' => 'Bachelor of Marketing', 'fac' => 'FAFB', 'gender' => 'Female', 'phone' => '012-3456720', 'lang' => 'Mandarin', 'type' => 'walk_in', 'group' => null, 'checked_in' => '2026-10-22 09:15:00'],
            ];

            $partStmt = $db->prepare('INSERT INTO participants (
                session_id, full_name, ic_passport_no, student_id, student_email,
                intake, study_level, intake_period, programme_name, faculty,
                gender, contact_no, emergency_contact_no, emergency_contact_relationship,
                preferred_language, registration_type, qr_code, group_code, checked_in_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

            foreach ($participants as $p) {
                $qrCode = hash('sha256', ($p['sid'] ?? 'demo') . '_' . $sessionId . '_demo_qr');
                $partStmt->execute([
                    $sessionId,
                    $p['full_name'],
                    $p['ic'],
                    $p['sid'],
                    $p['email'],
                    $p['intake'],
                    $p['level'],
                    $p['period'],
                    $p['prog'],
                    $p['fac'],
                    $p['gender'],
                    $p['phone'],
                    '019-8765432',
                    'Parent',
                    $p['lang'],
                    $p['type'],
                    $qrCode,
                    $p['group'],
                    $p['checked_in'],
                ]);
            }

            // 5. Lost & Found sample items
            $lostItems = [
                ['caption' => 'Hydro Flask Water Bottle (Black)', 'description' => 'Found near DTAR Hall main entrance during morning icebreaker.', 'status' => 'unclaimed'],
                ['caption' => 'TAR UMT Student ID Card (Marcus Tan)', 'description' => 'Left on Counter 2 during registration.', 'status' => 'claimed', 'claimed_by_name' => 'Marcus Tan', 'claimed_by_phone' => '012-3456701'],
            ];
            $lostStmt = $db->prepare('INSERT INTO lost_and_found_items (session_id, caption, description, status, claimed_by_name, claimed_by_phone) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($lostItems as $item) {
                $lostStmt->execute([
                    $sessionId,
                    $item['caption'],
                    $item['description'],
                    $item['status'],
                    $item['claimed_by_name'] ?? null,
                    $item['claimed_by_phone'] ?? null,
                ]);
            }

            $db->commit();
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Safely reset all demo data back to default records.
     * Guaranteed to ONLY affect the demo session.
     */
    public static function resetDemoData(int $sessionId): void
    {
        $demoSessionId = self::getDemoSessionId();
        if ($sessionId !== $demoSessionId) {
            throw new \RuntimeException('Security violation: Attempted to reset non-demo session.');
        }

        $db = Container::get('db');
        try {
            $db->beginTransaction();

            $tables = [
                'group_move_logs',
                'crew_attendance',
                'crew',
                'event_groups',
                'event_group_settings',
                'lost_and_found_items',
                'participants',
            ];

            foreach ($tables as $t) {
                $del = $db->prepare("DELETE FROM {$t} WHERE session_id = ?");
                $del->execute([$sessionId]);
            }

            $db->commit();

            // Re-seed with fresh sample data
            self::seedDemoData($sessionId);
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Retrieve summary statistics for the demo session.
     */
    public static function getDemoStats(int $sessionId): array
    {
        $db = Container::get('db');
        $stats = [
            'total_participants' => 0,
            'checked_in' => 0,
            'unassigned' => 0,
            'groups_count' => 0,
            'crew_count' => 0,
        ];

        try {
            $stmt = $db->prepare('SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN checked_in_at IS NOT NULL THEN 1 ELSE 0 END) as checked_in,
                SUM(CASE WHEN group_code IS NULL OR group_code = "" THEN 1 ELSE 0 END) as unassigned
                FROM participants WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $stats['total_participants'] = (int)($row['total'] ?? 0);
                $stats['checked_in'] = (int)($row['checked_in'] ?? 0);
                $stats['unassigned'] = (int)($row['unassigned'] ?? 0);
            }

            $grpStmt = $db->prepare('SELECT COUNT(*) FROM event_groups WHERE session_id = ?');
            $grpStmt->execute([$sessionId]);
            $stats['groups_count'] = (int)$grpStmt->fetchColumn();

            $crewStmt = $db->prepare('SELECT COUNT(*) FROM crew WHERE session_id = ?');
            $crewStmt->execute([$sessionId]);
            $stats['crew_count'] = (int)$crewStmt->fetchColumn();
        } catch (\Exception $e) {
            // fallback
        }

        return $stats;
    }
}
