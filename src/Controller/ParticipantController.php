<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class ParticipantController
{
    public function index(): void
    {
        // Only advisor / committee can see full participant listing
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Participants & Admission';
        $db = Container::get('db');

        // Calculate statistics
        $stats = [];
        
        // Total participants
        $stmt = $db->query('SELECT COUNT(*) as total FROM participants');
        $stats['total'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['total'];
        
        // Checked in count
        $stmt = $db->query('SELECT COUNT(*) as count FROM participants WHERE checked_in_at IS NOT NULL');
        $stats['checked_in'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        
        // Not checked in count
        $stats['not_checked_in'] = $stats['total'] - $stats['checked_in'];
        
        // Groups count
        $stmt = $db->query("SELECT COUNT(DISTINCT group_code) as count FROM participants WHERE group_code IS NOT NULL AND group_code != ''");
        $stats['groups'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        
        // Faculty distribution
        $stmt = $db->query("SELECT faculty, COUNT(*) as count FROM participants GROUP BY faculty ORDER BY count DESC");
        $facultyData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stats['faculty_distribution'] = [];
        foreach ($facultyData as $row) {
            $stats['faculty_distribution'][$row['faculty'] ?? 'Not Specified'] = (int)$row['count'];
        }
        
        // Language distribution
        $stmt = $db->query("SELECT preferred_language, COUNT(*) as count FROM participants GROUP BY preferred_language ORDER BY count DESC");
        $languageData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stats['language_distribution'] = [];
        foreach ($languageData as $row) {
            $stats['language_distribution'][$row['preferred_language'] ?? 'Not Specified'] = (int)$row['count'];
        }
        
        // Group distribution
        $stmt = $db->query("SELECT group_code, COUNT(*) as count FROM participants WHERE group_code IS NOT NULL AND group_code != '' GROUP BY group_code ORDER BY group_code");
        $groupData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stats['group_distribution'] = [];
        foreach ($groupData as $row) {
            $stats['group_distribution'][$row['group_code']] = (int)$row['count'];
        }
        
        // Recent registrations (last 10)
        $stmt = $db->query('SELECT id, full_name, student_id, intake, programme_name, faculty, group_code, registration_type, checked_in_at FROM participants ORDER BY id DESC LIMIT 10');
        $recentParticipants = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/dashboard.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function list(): void
    {
        // Only advisor / committee can see full participant listing
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Participants List';
        $db = Container::get('db');

        // Get filter parameter
        $filter = $_GET['filter'] ?? 'all'; // 'all', 'checked_in', 'not_checked_in'
        
        // Build query with optional filter
        $query = 'SELECT id, full_name, student_id, intake, programme_name, faculty, contact_no, preferred_language, group_code, registration_type, checked_in_at FROM participants';
        
        if ($filter === 'checked_in') {
            $query .= ' WHERE checked_in_at IS NOT NULL';
        } elseif ($filter === 'not_checked_in') {
            $query .= ' WHERE checked_in_at IS NULL';
        }
        
        $query .= ' ORDER BY full_name';
        
        $stmt = $db->query($query);
        $participants = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function create(): void
    {
        $title = 'Pre-register Participant';
        $registrationType = 'pre_register';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/create.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function createWalkIn(): void
    {
        $title = 'Walk-in Registration';
        $registrationType = 'walk_in';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/walkin.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function store(): void
    {
        $db = Container::get('db');

        $studentId = trim($_POST['student_id'] ?? '');
        
        // Check for duplicate student ID
        if (!empty($studentId)) {
            $checkStmt = $db->prepare('SELECT id, full_name FROM participants WHERE student_id = ?');
            $checkStmt->execute([$studentId]);
            $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Student ID already exists - redirect to lookup page with error message
                $_SESSION['registration_error'] = 'This Student ID is already registered. Please use the "Find My QR" page to retrieve your QR code.';
                header('Location: /participants/lookup?student_id=' . urlencode($studentId));
                exit;
            }
        }

        // Generate a unique QR code value for this participant
        $qrCode = bin2hex(random_bytes(8));

        // Convert phone numbers from 0XXXXXXXXX to 60XXXXXXXXX format
        $contactNo = $this->formatPhoneNumber($_POST['contact_no'] ?? '');
        $emergencyContactNo = $this->formatPhoneNumber($_POST['emergency_contact_no'] ?? '');
        $preferredLanguage = trim((string)($_POST['preferred_language'] ?? ''));
        $registrationType = strtolower(trim((string)($_POST['registration_type'] ?? 'pre_register')));
        if (!in_array($registrationType, ['pre_register', 'walk_in'], true)) {
            $registrationType = 'pre_register';
        }
        $autoAssignedGroupCode = null;
        if ($registrationType === 'walk_in') {
            $autoAssignedGroupCode = $this->findAutoAssignGroupCode($db, $preferredLanguage);
        }

        try {
            $stmt = $db->prepare('INSERT INTO participants (
                full_name,
                ic_passport_no,
                student_id,
                student_email,
                intake,
                programme_name,
                faculty,
                gender,
                contact_no,
                emergency_contact_no,
                emergency_contact_relationship,
                preferred_language,
                registration_type,
                group_code,
                qr_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $_POST['full_name'] ?? '',
                $_POST['ic_passport_no'] ?? '',
                $studentId,
                $_POST['student_email'] ?? '',
                $_POST['intake'] ?? '',
                $_POST['programme_name'] ?? '',
                $_POST['faculty'] ?? '',
                $_POST['gender'] ?? '',
                $contactNo,
                $emergencyContactNo,
                $_POST['emergency_contact_relationship'] ?? '',
                $preferredLanguage,
                $registrationType,
                $autoAssignedGroupCode,
                $qrCode,
            ]);
        } catch (\PDOException $e) {
            // Handle duplicate student_id constraint violation
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'student_id') !== false) {
                $_SESSION['registration_error'] = 'This Student ID is already registered. Please use the "Find My QR" page to retrieve your QR code.';
                header('Location: /participants/lookup?student_id=' . urlencode($studentId));
                exit;
            }
            // Re-throw if it's a different error
            throw $e;
        }

        $id = (int)$db->lastInsertId();

        $stmt = $db->prepare('SELECT * FROM participants WHERE id = ?');
        $stmt->execute([$id]);
        $participant = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Persist auto-assignment in move logs for traceability.
        if ($registrationType === 'walk_in' && !empty($autoAssignedGroupCode) && !empty($participant)) {
            try {
                $logStmt = $db->prepare("
                    INSERT INTO group_move_logs (
                        participant_id,
                        participant_name,
                        from_group_code,
                        to_group_code,
                        moved_by,
                        action_type
                    ) VALUES (?, ?, NULL, ?, 'System Auto-Assign', 'move')
                ");
                $logStmt->execute([
                    (int)($participant['id'] ?? 0),
                    (string)($participant['full_name'] ?? 'Participant'),
                    (string)$autoAssignedGroupCode,
                ]);
            } catch (\Exception $e) {
                // Keep registration successful even if logging is unavailable.
            }
        }

        // Generate QR image as base64 PNG data URI (in-memory, no file saved)
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64' => true,
        ]);
        $qrImage = (new QRCode($options))->render($participant['qr_code'] ?? '');

        $title = 'Registration Successful';

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/registered.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function checkinForm(): void
    {
        // Event crew / committee may operate check-in;
        // adjust roles as needed. For now, restrict to advisor / committee.
        Auth::requireRole(['advisor', 'committee']);

        $title = 'QR Check-In';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/checkin.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function processCheckin(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $code = $_POST['qr_code'] ?? '';

        $stmt = $db->prepare('SELECT * FROM participants WHERE qr_code = ?');
        $stmt->execute([$code]);
        $participant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($participant) {
            $update = $db->prepare('UPDATE participants SET checked_in_at = NOW() WHERE id = ?');
            $update->execute([$participant['id']]);
        }

        $title = 'QR Check-In Result';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/checkin_result.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function groups(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Grouping Overview';
        $db = Container::get('db');

        // Get group summary
        $stmt = $db->query("SELECT group_code, COUNT(*) AS count FROM participants WHERE group_code IS NOT NULL AND group_code != '' GROUP BY group_code ORDER BY CAST(group_code AS UNSIGNED), group_code");
        $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get ungrouped count
        $stmt = $db->query("SELECT COUNT(*) AS count FROM participants WHERE group_code IS NULL OR group_code = ''");
        $ungrouped = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

        // Get participants by group for detailed view
        $stmt = $db->query("SELECT id, full_name, student_id, preferred_language, registration_type, checked_in_at, group_code FROM participants WHERE group_code IS NOT NULL AND group_code != '' ORDER BY CAST(group_code AS UNSIGNED), group_code, full_name");
        $participantsByGroup = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $p) {
            $group = $p['group_code'];
            if (!isset($participantsByGroup[$group])) {
                $participantsByGroup[$group] = [];
            }
            $participantsByGroup[$group][] = $p;
        }

        // Build group type labels using only: English, Mandarin, Mixed
        $groupTypes = [];
        foreach ($participantsByGroup as $groupCode => $participants) {
            $englishCount = 0;
            $mandarinCount = 0;
            $otherCount = 0;
            foreach ($participants as $participant) {
                $language = strtolower(trim((string)($participant['preferred_language'] ?? '')));
                if ($language === 'english') {
                    $englishCount++;
                } elseif ($language === 'mandarin' || $language === 'chinese') {
                    $mandarinCount++;
                } else {
                    $otherCount++;
                }
            }

            if ($englishCount > 0 && $mandarinCount === 0 && $otherCount === 0) {
                $groupTypes[$groupCode] = 'English';
            } elseif ($mandarinCount > 0 && $englishCount === 0 && $otherCount === 0) {
                $groupTypes[$groupCode] = 'Mandarin';
            } else {
                $groupTypes[$groupCode] = 'Mixed';
            }
        }

        // Get ungrouped participants for drag and drop editor
        $stmt = $db->query("SELECT id, full_name, student_id, preferred_language, registration_type, checked_in_at FROM participants WHERE group_code IS NULL OR group_code = '' ORDER BY full_name");
        $ungroupedParticipants = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Facilitator data for senior buddy assignment per group
        $facilitators = [];
        $facilitatorByGroup = [];
        try {
            $stmt = $db->query("
                SELECT id, full_name, assigned_group_code
                FROM crew
                WHERE is_facilitator = 1
                ORDER BY full_name
            ");
            $facilitators = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($facilitators as $facilitator) {
                $assignedGroup = trim((string)($facilitator['assigned_group_code'] ?? ''));
                if ($assignedGroup !== '') {
                    if (!isset($facilitatorByGroup[$assignedGroup])) {
                        $facilitatorByGroup[$assignedGroup] = [];
                    }
                    $facilitatorByGroup[$assignedGroup][] = $facilitator;
                }
            }
        } catch (\Exception $e) {
            $facilitators = [];
            $facilitatorByGroup = [];
        }

        // Load recent persisted move logs (if migration already applied)
        $recentMoveLogs = [];
        try {
            $stmt = $db->query("
                SELECT
                    gml.id,
                    gml.participant_id,
                    gml.participant_name,
                    gml.from_group_code,
                    gml.to_group_code,
                    gml.moved_by,
                    gml.action_type,
                    gml.moved_at
                FROM group_move_logs gml
                ORDER BY gml.id DESC
                LIMIT 25
            ");
            $recentMoveLogs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Migration might not be applied yet; keep page functional.
            $recentMoveLogs = [];
        }
        $latestMoveLogId = !empty($recentMoveLogs) ? (int)($recentMoveLogs[0]['id'] ?? 0) : 0;

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/groups.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    /**
     * Assign one facilitator (senior buddy) to a specific group.
     */
    public function assignFacilitatorToGroup(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $groupCode = trim((string)($_POST['group_code'] ?? ''));
        $crewIds = $_POST['crew_ids'] ?? [];
        if (!is_array($crewIds)) {
            $crewIds = [];
        }
        $crewIds = array_values(array_unique(array_filter(array_map('intval', $crewIds), static function ($id) {
            return $id > 0;
        })));
        $crewIds = array_slice($crewIds, 0, 2);

        if (!preg_match('/^\d{1,2}$/', $groupCode)) {
            $_SESSION['grouping_message'] = 'Invalid group code for facilitator assignment.';
            $_SESSION['grouping_message_type'] = 'danger';
            header('Location: /participants/groups');
            exit;
        }

        try {
            $db->beginTransaction();

            // Ensure one facilitator per group and one group per facilitator.
            $clearGroupStmt = $db->prepare('UPDATE crew SET assigned_group_code = NULL WHERE assigned_group_code = ? AND is_facilitator = 1');
            $clearGroupStmt->execute([$groupCode]);

            foreach ($crewIds as $crewId) {
                $clearCrewStmt = $db->prepare('UPDATE crew SET assigned_group_code = NULL WHERE id = ? AND is_facilitator = 1');
                $clearCrewStmt->execute([$crewId]);

                $assignStmt = $db->prepare('UPDATE crew SET assigned_group_code = ? WHERE id = ? AND is_facilitator = 1');
                $assignStmt->execute([$groupCode, $crewId]);
            }

            $db->commit();
            $_SESSION['grouping_message'] = 'Senior buddy assignment updated.';
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['grouping_message'] = 'Failed to assign senior buddy: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/groups');
        exit;
    }

    /**
     * Auto-assign groups using round-robin algorithm
     */
    public function autoGroup(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        // Get number of groups from POST (default to 8 if not provided)
        $numGroups = (int)($_POST['num_groups'] ?? 8);
        if ($numGroups < 1 || $numGroups > 99) {
            $numGroups = 8; // Safety limit
        }

        // Generate numeric group codes (1, 2, 3, ...)
        $groupCodes = $this->buildNumericGroupCodes($numGroups);

        // Get all participants without groups, ordered by ID for consistent round-robin
        $stmt = $db->query('SELECT id FROM participants WHERE group_code IS NULL ORDER BY id');
        $participants = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($participants)) {
            $_SESSION['grouping_message'] = 'No ungrouped participants found.';
            $_SESSION['grouping_message_type'] = 'warning';
            header('Location: /participants/groups');
            exit;
        }

        // Round-robin assignment
        $db->beginTransaction();
        try {
            $updateStmt = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ?');
            
            $groupIndex = 0;
            foreach ($participants as $participantId) {
                $groupCode = $groupCodes[$groupIndex % count($groupCodes)];
                $updateStmt->execute([$groupCode, $participantId]);
                $groupIndex++;
            }

            $db->commit();
            $_SESSION['grouping_message'] = "Successfully assigned " . count($participants) . " participants to " . $numGroups . " groups using round-robin algorithm.";
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['grouping_message'] = 'Error assigning groups: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/groups');
        exit;
    }

    /**
     * Group participants by Faculty, then round-robin within each faculty
     */
    public function groupByFaculty(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        $numGroups = (int)($_POST['num_groups'] ?? 8);
        if ($numGroups < 1 || $numGroups > 99) {
            $numGroups = 8;
        }

        // Generate numeric group codes
        $groupCodes = $this->buildNumericGroupCodes($numGroups);

        // Get all ungrouped participants grouped by faculty
        $stmt = $db->query('SELECT id, faculty FROM participants WHERE group_code IS NULL AND faculty IS NOT NULL AND faculty != "" ORDER BY faculty, id');
        $participants = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($participants)) {
            $_SESSION['grouping_message'] = 'No ungrouped participants with faculty information found.';
            $_SESSION['grouping_message_type'] = 'warning';
            header('Location: /participants/groups');
            exit;
        }

        // Group by faculty
        $byFaculty = [];
        foreach ($participants as $p) {
            $faculty = $p['faculty'];
            if (!isset($byFaculty[$faculty])) {
                $byFaculty[$faculty] = [];
            }
            $byFaculty[$faculty][] = $p['id'];
        }

        $db->beginTransaction();
        try {
            $updateStmt = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ?');
            
            $totalAssigned = 0;
            foreach ($byFaculty as $faculty => $participantIds) {
                // Round-robin within each faculty
                $groupIndex = 0;
                foreach ($participantIds as $participantId) {
                    $groupCode = $groupCodes[$groupIndex % count($groupCodes)];
                    $updateStmt->execute([$groupCode, $participantId]);
                    $groupIndex++;
                    $totalAssigned++;
                }
            }

            $db->commit();
            $_SESSION['grouping_message'] = "Successfully assigned $totalAssigned participants to $numGroups groups by Faculty (round-robin within each faculty).";
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['grouping_message'] = 'Error grouping by faculty: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/groups');
        exit;
    }

    /**
     * Group participants by Language, then round-robin within each language
     */
    public function groupByLanguage(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        $numGroups = (int)($_POST['num_groups'] ?? 8);
        if ($numGroups < 1 || $numGroups > 99) {
            $numGroups = 8;
        }

        // Generate numeric group codes
        $groupCodes = $this->buildNumericGroupCodes($numGroups);

        // Get all ungrouped participants grouped by language
        $stmt = $db->query('SELECT id, preferred_language FROM participants WHERE group_code IS NULL AND preferred_language IS NOT NULL AND preferred_language != "" ORDER BY preferred_language, id');
        $participants = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($participants)) {
            $_SESSION['grouping_message'] = 'No ungrouped participants with language information found.';
            $_SESSION['grouping_message_type'] = 'warning';
            header('Location: /participants/groups');
            exit;
        }

        // Group by language
        $byLanguage = [];
        foreach ($participants as $p) {
            $language = $p['preferred_language'];
            if (!isset($byLanguage[$language])) {
                $byLanguage[$language] = [];
            }
            $byLanguage[$language][] = $p['id'];
        }

        $db->beginTransaction();
        try {
            $updateStmt = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ?');
            
            $totalAssigned = 0;
            foreach ($byLanguage as $language => $participantIds) {
                // Round-robin within each language
                $groupIndex = 0;
                foreach ($participantIds as $participantId) {
                    $groupCode = $groupCodes[$groupIndex % count($groupCodes)];
                    $updateStmt->execute([$groupCode, $participantId]);
                    $groupIndex++;
                    $totalAssigned++;
                }
            }

            $db->commit();
            $_SESSION['grouping_message'] = "Successfully assigned $totalAssigned participants to $numGroups groups by Language (round-robin within each language).";
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['grouping_message'] = 'Error grouping by language: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/groups');
        exit;
    }

    /**
     * Group ungrouped participants by language with dedicated English groups.
     */
    public function groupByLanguageCustom(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        $numGroups = (int)($_POST['num_groups'] ?? 8);
        $englishGroups = (int)($_POST['english_groups'] ?? 2);
        $maxPerGroup = (int)($_POST['max_per_group'] ?? 0);

        if ($numGroups < 1 || $numGroups > 99) {
            $numGroups = 8;
        }
        if ($englishGroups < 1) {
            $englishGroups = 1;
        }
        if ($englishGroups > $numGroups) {
            $englishGroups = $numGroups;
        }
        if ($maxPerGroup < 0) {
            $maxPerGroup = 0;
        }

        $groupCodes = $this->buildNumericGroupCodes($numGroups);

        $englishGroupCodes = array_slice($groupCodes, 0, $englishGroups);
        $nonEnglishGroupCodes = array_slice($groupCodes, $englishGroups);
        if (empty($nonEnglishGroupCodes)) {
            $nonEnglishGroupCodes = $groupCodes;
        }

        $stmt = $db->query("SELECT id, preferred_language FROM participants WHERE group_code IS NULL OR group_code = '' ORDER BY id");
        $participants = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($participants)) {
            $_SESSION['grouping_message'] = 'No ungrouped participants found.';
            $_SESSION['grouping_message_type'] = 'warning';
            header('Location: /participants/groups');
            exit;
        }

        $groupCounts = [];
        foreach ($groupCodes as $code) {
            $groupCounts[$code] = 0;
        }
        $countStmt = $db->query("SELECT group_code, COUNT(*) AS count FROM participants WHERE group_code IS NOT NULL AND group_code != '' GROUP BY group_code");
        foreach ($countStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $code = (string)($row['group_code'] ?? '');
            if (isset($groupCounts[$code])) {
                $groupCounts[$code] = (int)($row['count'] ?? 0);
            }
        }

        $englishParticipants = [];
        $nonEnglishParticipants = [];
        foreach ($participants as $participant) {
            $language = strtolower(trim((string)($participant['preferred_language'] ?? '')));
            if ($language === 'english') {
                $englishParticipants[] = (int)$participant['id'];
            } else {
                $nonEnglishParticipants[] = (int)$participant['id'];
            }
        }

        $db->beginTransaction();
        try {
            $updateStmt = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ?');

            $assigned = 0;
            foreach ($englishParticipants as $participantId) {
                $groupCode = $this->findAvailableGroupCode($englishGroupCodes, $groupCounts, $maxPerGroup);
                if ($groupCode === null) {
                    throw new \RuntimeException('Not enough capacity for English participants. Increase max per group or number of groups.');
                }
                $updateStmt->execute([$groupCode, $participantId]);
                $groupCounts[$groupCode]++;
                $assigned++;
            }

            foreach ($nonEnglishParticipants as $participantId) {
                $groupCode = $this->findAvailableGroupCode($nonEnglishGroupCodes, $groupCounts, $maxPerGroup);
                if ($groupCode === null) {
                    throw new \RuntimeException('Not enough capacity for non-English participants. Increase max per group or number of groups.');
                }
                $updateStmt->execute([$groupCode, $participantId]);
                $groupCounts[$groupCode]++;
                $assigned++;
            }

            $db->commit();
            $limitText = $maxPerGroup > 0 ? " (max {$maxPerGroup} per group)." : '.';
            $_SESSION['grouping_message'] = "Assigned {$assigned} participants into {$numGroups} groups with {$englishGroups} dedicated English group(s){$limitText}";
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['grouping_message'] = 'Error in custom language grouping: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/groups');
        exit;
    }

    /**
     * Move a participant to another group (used by drag-and-drop).
     */
    public function moveParticipantGroup(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $participantId = (int)($_POST['participant_id'] ?? 0);
        $targetGroup = trim((string)($_POST['target_group'] ?? ''));
        $expectedFromGroup = trim((string)($_POST['expected_from_group'] ?? ''));
        $expectedFromGroup = $expectedFromGroup === '' ? null : (string)((int)$expectedFromGroup);
        $actionType = trim((string)($_POST['move_action'] ?? 'move'));
        if (!in_array($actionType, ['move', 'undo'], true)) {
            $actionType = 'move';
        }

        if ($participantId <= 0) {
            $this->respondGroupMove(false, 'Invalid participant.');
            return;
        }

        if ($targetGroup !== '') {
            if (!preg_match('/^\d{1,2}$/', $targetGroup)) {
                $this->respondGroupMove(false, 'Invalid target group.');
                return;
            }
            $targetGroup = (string)((int)$targetGroup);
        } else {
            $targetGroup = null;
        }

        try {
            $participantStmt = $db->prepare('SELECT full_name, group_code FROM participants WHERE id = ?');
            $participantStmt->execute([$participantId]);
            $participant = $participantStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$participant) {
                $this->respondGroupMove(false, 'Participant not found.');
                return;
            }

            $fromGroup = $participant['group_code'] ?? null;
            $participantName = (string)($participant['full_name'] ?? 'Participant');
            $fromGroup = ($fromGroup === '' ? null : $fromGroup);
            $toGroup = $targetGroup;

            // Optimistic concurrency guard: fail if stale source group.
            if ($expectedFromGroup !== $fromGroup) {
                $currentLabel = $fromGroup === null ? 'Ungrouped' : ('Group ' . $fromGroup);
                $this->respondGroupMove(false, "Conflict: participant is now in {$currentLabel}. Please refresh and try again.", [
                    'current_group' => $fromGroup,
                    'status_code' => 409,
                ]);
                return;
            }

            $stmt = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ?');
            $stmt->execute([$targetGroup, $participantId]);

            $movedBy = (string)(Auth::user()['username'] ?? 'Unknown');
            $logDescription = null;
            $latestMoveLogId = 0;

            try {
                $logStmt = $db->prepare("
                    INSERT INTO group_move_logs (
                        participant_id,
                        participant_name,
                        from_group_code,
                        to_group_code,
                        moved_by,
                        action_type
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $logStmt->execute([
                    $participantId,
                    $participantName,
                    $fromGroup,
                    $toGroup,
                    $movedBy,
                    $actionType,
                ]);
                $latestMoveLogId = (int)$db->lastInsertId();

                $fromLabel = ($fromGroup === null ? 'Ungrouped' : 'Group ' . $fromGroup);
                $toLabel = ($toGroup === null ? 'Ungrouped' : 'Group ' . $toGroup);
                $verb = $actionType === 'undo' ? 'restored' : 'moved';
                $logDescription = sprintf(
                    '%s %s from %s to %s by %s at %s',
                    $participantName,
                    $verb,
                    $fromLabel,
                    $toLabel,
                    $movedBy,
                    date('H:i:s')
                );
            } catch (\Exception $e) {
                // Logging failure should not block the actual move.
            }

            $this->respondGroupMove(true, 'Participant group updated.', [
                'log_entry' => $logDescription,
                'latest_move_log_id' => $latestMoveLogId,
            ]);
        } catch (\Exception $e) {
            $this->respondGroupMove(false, 'Failed to update group: ' . $e->getMessage());
        }
    }

    /**
     * Clear all group assignments
     */
    public function clearGroups(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        try {
            $db->exec('UPDATE participants SET group_code = NULL');
            $_SESSION['grouping_message'] = 'All group assignments have been cleared.';
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['grouping_message'] = 'Error clearing groups: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/groups');
        exit;
    }

    /**
     * Public form where participants can look up their QR code.
     */
    public function lookupForm(): void
    {
        $title = 'Find My QR Code';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/lookup.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    /**
     * Handle lookup and show QR if participant is found.
     */
    public function lookup(): void
    {
        $db = Container::get('db');

        $studentId = trim($_POST['student_id'] ?? '');

        $participant = null;
        $qrImage = null;

        if ($studentId !== '') {
            $stmt = $db->prepare('SELECT * FROM participants WHERE student_id = ?');
            $stmt->execute([$studentId]);
            $participant = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($participant && !empty($participant['qr_code'])) {
                $options = new QROptions([
                    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                    'imageBase64' => true,
                ]);
                $qrImage = (new QRCode($options))->render($participant['qr_code']);
            }
        }

        $title = 'Find My QR Code';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/lookup_result.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    /**
     * Export participants to CSV file
     */
    public function export(): void
    {
        // Only advisor / committee can export
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        // Get filter parameter
        $filter = $_GET['filter'] ?? 'all';
        
        // Build query with optional filter (same as index method)
        $query = 'SELECT id, full_name, ic_passport_no, student_id, student_email, intake, programme_name, faculty, gender, contact_no, emergency_contact_no, emergency_contact_relationship, preferred_language, registration_type, group_code, checked_in_at, created_at FROM participants';
        
        if ($filter === 'checked_in') {
            $query .= ' WHERE checked_in_at IS NOT NULL';
        } elseif ($filter === 'not_checked_in') {
            $query .= ' WHERE checked_in_at IS NULL';
        }
        
        $query .= ' ORDER BY full_name';
        
        $stmt = $db->query($query);
        $participants = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Set headers for CSV download
        $filename = 'participants_' . date('Y-m-d_His') . ($filter !== 'all' ? '_' . $filter : '') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV headers
        $headers = [
            'ID',
            'Name',
            'IC/Passport No',
            'Student ID',
            'Student Email',
            'Intake',
            'Programme',
            'Faculty',
            'Gender',
            'Contact No',
            'Emergency Contact No',
            'Emergency Contact Relationship',
            'Preferred Language',
            'Registration Type',
            'Group Code',
            'Checked In',
            'Checked In At',
            'Created At'
        ];
        fputcsv($output, $headers);

        // Write data rows
        foreach ($participants as $p) {
            $row = [
                $p['id'] ?? '',
                $p['full_name'] ?? '',
                $p['ic_passport_no'] ?? '',
                $p['student_id'] ?? '',
                $p['student_email'] ?? '',
                $p['intake'] ?? '',
                $p['programme_name'] ?? '',
                $p['faculty'] ?? '',
                $p['gender'] ?? '',
                $p['contact_no'] ?? '',
                $p['emergency_contact_no'] ?? '',
                $p['emergency_contact_relationship'] ?? '',
                $p['preferred_language'] ?? '',
                $p['registration_type'] ?? 'pre_register',
                $p['group_code'] ?? '',
                !empty($p['checked_in_at']) ? 'Yes' : 'No',
                $p['checked_in_at'] ?? '',
                $p['created_at'] ?? ''
            ];
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Convert phone number from 0XXXXXXXXX to 60XXXXXXXXX format
     */
    private function formatPhoneNumber(string $phone): string
    {
        $phone = trim($phone);
        
        // If empty, return as is
        if (empty($phone)) {
            return $phone;
        }
        
        // If starts with 0, replace with 60
        if (preg_match('/^0(.+)$/', $phone, $matches)) {
            return '60' . $matches[1];
        }
        
        // If already starts with 60, return as is
        if (strpos($phone, '60') === 0) {
            return $phone;
        }
        
        // Otherwise, return as is (might be invalid, but let it through)
        return $phone;
    }

    /**
     * Build numeric group codes like 1, 2, 3...
     */
    private function buildNumericGroupCodes(int $numGroups): array
    {
        $codes = [];
        for ($i = 1; $i <= $numGroups; $i++) {
            $codes[] = (string)$i;
        }

        return $codes;
    }

    private function findAutoAssignGroupCode(\PDO $db, string $preferredLanguage = ''): string
    {
        $stmt = $db->query("
            SELECT
                group_code,
                COUNT(*) AS count,
                SUM(CASE WHEN LOWER(preferred_language) = 'english' THEN 1 ELSE 0 END) AS english_count,
                SUM(CASE WHEN LOWER(preferred_language) IN ('mandarin', 'chinese') THEN 1 ELSE 0 END) AS mandarin_count
            FROM participants
            WHERE group_code IS NOT NULL AND group_code != ''
            GROUP BY group_code
            ORDER BY CAST(group_code AS UNSIGNED), group_code
        ");

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $normalizedLanguage = strtolower(trim($preferredLanguage));
        $matchingCodes = [];

        foreach ($rows as $row) {
            $groupCode = (string)($row['group_code'] ?? '');
            if ($groupCode === '') {
                continue;
            }

            $total = (int)($row['count'] ?? 0);
            $englishCount = (int)($row['english_count'] ?? 0);
            $mandarinCount = (int)($row['mandarin_count'] ?? 0);

            if ($normalizedLanguage === 'english' && $total > 0 && $englishCount === $total) {
                $matchingCodes[] = ['group_code' => $groupCode, 'count' => $total];
            }
            if (($normalizedLanguage === 'mandarin' || $normalizedLanguage === 'chinese') && $total > 0 && $mandarinCount === $total) {
                $matchingCodes[] = ['group_code' => $groupCode, 'count' => $total];
            }
        }

        if (!empty($matchingCodes)) {
            usort($matchingCodes, static function (array $a, array $b): int {
                $countCompare = ((int)$a['count']) <=> ((int)$b['count']);
                if ($countCompare !== 0) {
                    return $countCompare;
                }
                return ((int)$a['group_code']) <=> ((int)$b['group_code']);
            });
            return (string)$matchingCodes[0]['group_code'];
        }

        if (!empty($rows)) {
            usort($rows, static function (array $a, array $b): int {
                $countCompare = ((int)$a['count']) <=> ((int)$b['count']);
                if ($countCompare !== 0) {
                    return $countCompare;
                }
                return ((int)$a['group_code']) <=> ((int)$b['group_code']);
            });
            return (string)$rows[0]['group_code'];
        }

        // If no groups exist yet, start with Group 1.
        return '1';
    }

    private function findAvailableGroupCode(array $candidateCodes, array &$groupCounts, int $maxPerGroup): ?string
    {
        if (empty($candidateCodes)) {
            return null;
        }

        foreach ($candidateCodes as $code) {
            $count = (int)($groupCounts[$code] ?? 0);
            if ($maxPerGroup <= 0 || $count < $maxPerGroup) {
                return $code;
            }
        }

        return null;
    }

    private function respondGroupMove(bool $success, string $message, array $extra = []): void
    {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            header('Content-Type: application/json');
            $statusCode = isset($extra['status_code']) ? (int)$extra['status_code'] : ($success ? 200 : 400);
            unset($extra['status_code']);
            http_response_code($statusCode);
            echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['grouping_message'] = $message;
        $_SESSION['grouping_message_type'] = $success ? 'success' : 'danger';
        header('Location: /participants/groups');
        exit;
    }

    public function groupsState(): void
    {
        Auth::requireRole(['advisor', 'committee']);
        $db = Container::get('db');

        $latestMoveLogId = 0;
        $latestMovedAt = null;
        try {
            $stmt = $db->query('SELECT id, moved_at FROM group_move_logs ORDER BY id DESC LIMIT 1');
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $latestMoveLogId = (int)($row['id'] ?? 0);
                $latestMovedAt = $row['moved_at'] ?? null;
            }
        } catch (\Exception $e) {
            $latestMoveLogId = 0;
            $latestMovedAt = null;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'latest_move_log_id' => $latestMoveLogId,
            'latest_moved_at' => $latestMovedAt,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

