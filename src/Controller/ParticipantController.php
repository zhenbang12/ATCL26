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
        
        // Groups: configured shells if present, else distinct assignments
        try {
            $shellCount = (int)$db->query('SELECT COUNT(*) FROM event_groups')->fetchColumn();
            if ($shellCount > 0) {
                $stats['groups'] = $shellCount;
            } else {
                $stmt = $db->query("SELECT COUNT(DISTINCT group_code) as count FROM participants WHERE group_code IS NOT NULL AND group_code != ''");
                $stats['groups'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];
            }
        } catch (\Exception $e) {
            $stmt = $db->query("SELECT COUNT(DISTINCT group_code) as count FROM participants WHERE group_code IS NOT NULL AND group_code != ''");
            $stats['groups'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        }
        
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
        $registrationSettings = SettingsController::loadRegistrationSettings($db);

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
        $query = 'SELECT id, full_name, student_id, student_email, intake, programme_name, faculty, contact_no, preferred_language, group_code, registration_type, checked_in_at FROM participants';
        
        if ($filter === 'checked_in') {
            $query .= ' WHERE checked_in_at IS NOT NULL';
        } elseif ($filter === 'not_checked_in') {
            $query .= ' WHERE checked_in_at IS NULL';
        }
        
        $query .= ' ORDER BY full_name';
        
        $stmt = $db->query($query);
        $participants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $registrationSettings = SettingsController::loadRegistrationSettings($db);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function create(): void
    {
        $title = 'Pre-register Participant';
        $registrationType = 'pre_register';
        $registrationSettings = SettingsController::loadRegistrationSettings(Container::get('db'));
        if (!$registrationSettings['pre_register_enabled'] && !Auth::check()) {
            $closedTitle = 'Pre-registration is currently closed';
            include __DIR__ . '/../../views/layout/header.php';
            include __DIR__ . '/../../views/participants/registration_closed.php';
            include __DIR__ . '/../../views/layout/footer.php';
            return;
        }

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/create.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function createWalkIn(): void
    {
        $title = 'Walk-in Registration';
        $registrationType = 'walk_in';
        $registrationSettings = SettingsController::loadRegistrationSettings(Container::get('db'));

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/walkin.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function store(): void
    {
        $db = Container::get('db');

        $studentId = trim($_POST['student_id'] ?? '');
        $registrationType = strtolower(trim((string)($_POST['registration_type'] ?? 'pre_register')));
        if (!in_array($registrationType, ['pre_register', 'walk_in'], true)) {
            $registrationType = 'pre_register';
        }

        $registrationSettings = SettingsController::loadRegistrationSettings($db);
        if (!Auth::check() && $registrationType === 'pre_register' && !$registrationSettings['pre_register_enabled']) {
            $_SESSION['registration_error'] = 'Pre-registration is currently closed. You can still use Find My QR if you already registered.';
            header('Location: /participants/lookup');
            exit;
        }

        $returnPath = $registrationType === 'walk_in' ? '/participants/create-walkin' : '/participants/create';
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $gender = trim((string)($_POST['gender'] ?? ''));
        $studentEmail = trim((string)($_POST['student_email'] ?? ''));
        $programmeName = trim((string)($_POST['programme_name'] ?? ''));
        $faculty = trim((string)($_POST['faculty'] ?? ''));
        $contactRaw = trim((string)($_POST['contact_no'] ?? ''));
        $preferredLanguage = trim((string)($_POST['preferred_language'] ?? ''));

        if (
            $fullName === ''
            || $gender === ''
            || $studentId === ''
            || $studentEmail === ''
            || $programmeName === ''
            || $faculty === ''
            || $contactRaw === ''
            || $preferredLanguage === ''
        ) {
            $_SESSION['registration_error'] = 'Please complete every field on the form.';
            $_SESSION['registration_input'] = $_POST;
            header('Location: ' . $returnPath);
            exit;
        }

        if (!$this->isValidTarcStudentEmail($studentEmail)) {
            $_SESSION['registration_error'] = 'Student email must be a valid address ending with @student.tarc.edu.my.';
            $_SESSION['registration_input'] = $_POST;
            header('Location: ' . $returnPath);
            exit;
        }

        // Check for duplicate student ID
        if (!empty($studentId)) {
            $checkStmt = $db->prepare('SELECT id, full_name FROM participants WHERE student_id = ?');
            $checkStmt->execute([$studentId]);
            $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Student ID already exists - redirect to lookup page with error message
                $_SESSION['registration_error'] = 'This Student ID is already registered. Please use the "Find My QR" page to retrieve your QR code.';
                $_SESSION['registration_input'] = $_POST;
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
                null,
                $qrCode,
            ]);
        } catch (\PDOException $e) {
            // Handle duplicate student_id constraint violation
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'student_id') !== false) {
                $_SESSION['registration_error'] = 'This Student ID is already registered. Please use the "Find My QR" page to retrieve your QR code.';
                $_SESSION['registration_input'] = $_POST;
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

        // Generate QR image as base64 PNG data URI (in-memory, no file saved)
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64' => true,
        ]);
        $qrImage = (new QRCode($options))->render($participant['qr_code'] ?? '');

        $title = 'Registration Successful';

        if (isset($_SESSION['registration_input'])) {
            unset($_SESSION['registration_input']);
        }

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

        $checkinAssignmentNotice = null;
        $checkinCriticalError = null;

        if ($participant) {
            $participantId = (int)$participant['id'];
            $hadGroup = trim((string)($participant['group_code'] ?? '')) !== '';

            $checkinSaved = false;
            try {
                $update = $db->prepare('UPDATE participants SET checked_in_at = NOW() WHERE id = ?');
                $update->execute([$participantId]);
                $checkinSaved = true;
            } catch (\Exception $e) {
                $checkinCriticalError = 'Could not save check-in: ' . $e->getMessage();
            }

            if ($checkinSaved && !$hadGroup) {
                try {
                    $notice = $this->assignGroupAtCheckIn(
                        $db,
                        $participantId,
                        (string)($participant['preferred_language'] ?? ''),
                        (string)($participant['full_name'] ?? 'Participant')
                    );
                    if ($notice !== null) {
                        $checkinAssignmentNotice = $notice;
                    }
                } catch (\Exception $e) {
                    $checkinAssignmentNotice = 'Checked in, but group assignment failed: ' . $e->getMessage();
                }
            }

            if ($checkinSaved) {
                $stmt->execute([$code]);
                $participant = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
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
        $currentMaxPerGroup = $this->getEventGroupMaxPerGroup($db);
        $currentEnglishGroups = 0;

        // Groups dashboard: prefer configured empty shells (event_groups)
        $layoutRows = [];
        try {
            $layoutRows = $db->query('
                SELECT group_code, language_pool
                FROM event_groups
                ORDER BY sort_order ASC, CAST(group_code AS UNSIGNED), group_code
            ')->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $layoutRows = [];
        }

        if ($layoutRows !== []) {
            $countMap = [];
            $stmt = $db->query("SELECT group_code, COUNT(*) AS count FROM participants WHERE group_code IS NOT NULL AND group_code != '' GROUP BY group_code");
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $countMap[(string)$row['group_code']] = (int)$row['count'];
            }

            $groups = [];
            $groupTypes = [];
            $participantsByGroup = [];
            foreach ($layoutRows as $row) {
                $gc = (string)$row['group_code'];
                $groups[] = ['group_code' => $gc, 'count' => $countMap[$gc] ?? 0];
                $groupTypes[$gc] = (($row['language_pool'] ?? '') === 'english') ? 'English' : 'Mandarin';
                if (($row['language_pool'] ?? '') === 'english') {
                    $currentEnglishGroups++;
                }
                $participantsByGroup[$gc] = [];
            }

            $stmt = $db->query("SELECT id, full_name, student_id, preferred_language, registration_type, checked_in_at, group_code FROM participants WHERE group_code IS NOT NULL AND group_code != '' ORDER BY CAST(group_code AS UNSIGNED), group_code, full_name");
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $p) {
                $group = (string)$p['group_code'];
                if (isset($participantsByGroup[$group])) {
                    $participantsByGroup[$group][] = $p;
                }
            }
        } else {
            // Legacy: infer groups from participant assignments only
            $stmt = $db->query("SELECT group_code, COUNT(*) AS count FROM participants WHERE group_code IS NOT NULL AND group_code != '' GROUP BY group_code ORDER BY CAST(group_code AS UNSIGNED), group_code");
            $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stmt = $db->query("SELECT id, full_name, student_id, preferred_language, registration_type, checked_in_at, group_code FROM participants WHERE group_code IS NOT NULL AND group_code != '' ORDER BY CAST(group_code AS UNSIGNED), group_code, full_name");
            $participantsByGroup = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $p) {
                $group = $p['group_code'];
                if (!isset($participantsByGroup[$group])) {
                    $participantsByGroup[$group] = [];
                }
                $participantsByGroup[$group][] = $p;
            }

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
        }

        // Get ungrouped count
        $stmt = $db->query("SELECT COUNT(*) AS count FROM participants WHERE group_code IS NULL OR group_code = ''");
        $ungrouped = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

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

        if ($this->eventGroupLayoutExists($db)) {
            $v = $db->prepare('SELECT 1 FROM event_groups WHERE group_code = ? LIMIT 1');
            $v->execute([$groupCode]);
            if (!$v->fetchColumn()) {
                $_SESSION['grouping_message'] = 'That group is not part of the saved layout.';
                $_SESSION['grouping_message_type'] = 'danger';
                header('Location: /participants/groups');
                exit;
            }
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
        $_SESSION['grouping_message'] = 'Bulk auto-grouping is disabled. Configure group shells on this page; participants are placed into groups when they check in.';
        $_SESSION['grouping_message_type'] = 'info';
        header('Location: /participants/groups');
        exit;
    }

    /**
     * Group participants by Faculty, then round-robin within each faculty
     */
    public function groupByFaculty(): void
    {
        Auth::requireRole(['advisor', 'committee']);
        $_SESSION['grouping_message'] = 'Bulk grouping by faculty is disabled. Participants are assigned to groups at check-in based on the saved group layout and preferred language.';
        $_SESSION['grouping_message_type'] = 'info';
        header('Location: /participants/groups');
        exit;
    }

    /**
     * Group participants by Language, then round-robin within each language
     */
    public function groupByLanguage(): void
    {
        Auth::requireRole(['advisor', 'committee']);
        $_SESSION['grouping_message'] = 'Bulk grouping by language is disabled. Participants are assigned to groups at check-in based on the saved group layout and preferred language.';
        $_SESSION['grouping_message_type'] = 'info';
        header('Location: /participants/groups');
        exit;
    }

    /**
     * Save empty group shells (codes + English vs Mandarin pools). Assignment happens at check-in only.
     */
    public function saveGroupLayout(): void
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

        try {
            $db->beginTransaction();

            $db->exec('DELETE FROM event_groups');

            $insert = $db->prepare('INSERT INTO event_groups (group_code, language_pool, sort_order) VALUES (?, ?, ?)');
            $sort = 0;
            foreach ($groupCodes as $code) {
                $sort++;
                $pool = $sort <= $englishGroups ? 'english' : 'mandarin';
                $insert->execute([(string)$code, $pool, $sort]);
            }

            $settingsStmt = $db->prepare('
                INSERT INTO event_group_settings (id, max_per_group) VALUES (1, ?)
                ON DUPLICATE KEY UPDATE max_per_group = VALUES(max_per_group)
            ');
            $settingsStmt->execute([$maxPerGroup]);

            $db->commit();
            $capText = $maxPerGroup > 0 ? " Max {$maxPerGroup} participants per group when assigning at check-in." : '';
            $_SESSION['grouping_message'] = "Saved {$numGroups} empty group shells ({$englishGroups} English pool, " . ($numGroups - $englishGroups) . " Mandarin pool). Participants are assigned round-robin when they check in.{$capText}";
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['grouping_message'] = 'Could not save group layout. Run database migrations if this is a new install: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/groups');
        exit;
    }

    public function addGroupShell(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        try {
            $row = $db->query('
                SELECT
                    COALESCE(MAX(CAST(group_code AS UNSIGNED)), 0) AS max_code,
                    COALESCE(MAX(sort_order), 0) AS max_sort,
                    COUNT(*) AS total_groups
                FROM event_groups
            ')->fetch(\PDO::FETCH_ASSOC);

            $totalGroups = (int)($row['total_groups'] ?? 0);
            if ($totalGroups >= 99) {
                $_SESSION['grouping_message'] = 'You already have the maximum of 99 groups.';
                $_SESSION['grouping_message_type'] = 'warning';
                header('Location: /participants/groups');
                exit;
            }

            $nextCode = (int)($row['max_code'] ?? 0) + 1;
            $nextSort = (int)($row['max_sort'] ?? 0) + 1;
            $pool = $totalGroups === 0 ? 'english' : 'mandarin';

            $stmt = $db->prepare('INSERT INTO event_groups (group_code, language_pool, sort_order) VALUES (?, ?, ?)');
            $stmt->execute([(string)$nextCode, $pool, $nextSort]);

            $_SESSION['grouping_message'] = 'Added Group ' . $nextCode . ' to the saved layout.';
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['grouping_message'] = 'Could not add group. Run migrations if needed: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/groups');
        exit;
    }

    public function addGroupSlot(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $currentMax = $this->getEventGroupMaxPerGroup($db);
        $nextMax = min(300, $currentMax + 1);

        try {
            $stmt = $db->prepare('
                INSERT INTO event_group_settings (id, max_per_group) VALUES (1, ?)
                ON DUPLICATE KEY UPDATE max_per_group = VALUES(max_per_group)
            ');
            $stmt->execute([$nextMax]);

            $_SESSION['grouping_message'] = 'Max per group increased to ' . $nextMax . '.';
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['grouping_message'] = 'Could not add slot. Run migrations if needed: ' . $e->getMessage();
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

        if ($targetGroup !== null && $this->eventGroupLayoutExists($db)) {
            $v = $db->prepare('SELECT 1 FROM event_groups WHERE group_code = ? LIMIT 1');
            $v->execute([$targetGroup]);
            if (!$v->fetchColumn()) {
                $this->respondGroupMove(false, 'Target group is not in the saved layout. Refresh the page after updating the layout.');
                return;
            }
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
     * Remove saved group layout (event_groups), reset per-group cap, and clear senior buddy → group links.
     * Does not change participant group_code; use clearGroups for that.
     */
    public function clearGroupShells(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        try {
            $db->beginTransaction();
            $db->exec('DELETE FROM event_groups');
            $db->exec('INSERT INTO event_group_settings (id, max_per_group) VALUES (1, 0) ON DUPLICATE KEY UPDATE max_per_group = 0');
            $db->exec('UPDATE crew SET assigned_group_code = NULL WHERE is_facilitator = 1');
            $db->commit();
            $_SESSION['grouping_message'] = 'Group shells and senior buddy group links were removed. Participant group numbers were not changed—use “Clear participant group assignments” if you need those cleared too. Save a new layout before check-in can assign groups again.';
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['grouping_message'] = 'Could not clear group shells. If this is a new database, run migrations: ' . $e->getMessage();
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
        $lookupFrom = $this->normalizeLookupFrom((string)($_GET['from'] ?? 'pre-reg'));
        $registrationSettings = SettingsController::loadRegistrationSettings(Container::get('db'));
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
        $lookupFrom = $this->normalizeLookupFrom((string)($_POST['from'] ?? 'pre-reg'));

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

    private function normalizeLookupFrom(string $from): string
    {
        return $from === 'walk-in' ? 'walk-in' : 'pre-reg';
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
     * Student email must be syntactically valid and use hostname student.tarc.edu.my (case-insensitive domain).
     */
    private function isValidTarcStudentEmail(string $email): bool
    {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $at = strrpos($email, '@');
        if ($at === false) {
            return false;
        }

        $domain = strtolower(substr($email, $at + 1));

        return $domain === 'student.tarc.edu.my';
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

    private function eventGroupLayoutExists(\PDO $db): bool
    {
        try {
            return (int)$db->query('SELECT COUNT(*) FROM event_groups')->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getEventGroupMaxPerGroup(\PDO $db): int
    {
        try {
            $v = $db->query('SELECT max_per_group FROM event_group_settings WHERE id = 1')->fetchColumn();

            return max(0, (int)$v);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Assign group at check-in (round-robin = lightest load within language pool). Returns a notice if not assigned.
     */
    private function assignGroupAtCheckIn(\PDO $db, int $participantId, string $preferredLanguage, string $participantName): ?string
    {
        if (!$this->eventGroupLayoutExists($db)) {
            return 'Group shells are not set up yet. On Grouping Overview, save a group layout; then check in again to assign a group.';
        }

        $lang = strtolower(trim($preferredLanguage));
        $pool = ($lang === 'english') ? 'english' : 'mandarin';

        $stmt = $db->prepare('
            SELECT group_code
            FROM event_groups
            WHERE language_pool = ?
            ORDER BY sort_order ASC, CAST(group_code AS UNSIGNED), group_code
        ');
        $stmt->execute([$pool]);
        $poolCodes = array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        if ($poolCodes === []) {
            return 'The saved layout has no groups in this participant\'s language pool. Adjust total vs English group counts.';
        }

        $max = $this->getEventGroupMaxPerGroup($db);
        $chosen = $this->pickGroupWithLightestLoad($db, $poolCodes, $max);
        if ($chosen === null) {
            return 'All groups in this language pool are at capacity. Raise max per group or add groups, then check in again.';
        }

        $upd = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ? AND IFNULL(group_code, \'\') = \'\'');
        $upd->execute([$chosen, $participantId]);
        if ($upd->rowCount() === 0) {
            return null;
        }

        try {
            $log = $db->prepare('
                INSERT INTO group_move_logs (
                    participant_id,
                    participant_name,
                    from_group_code,
                    to_group_code,
                    moved_by,
                    action_type
                ) VALUES (?, ?, NULL, ?, ?, ?)
            ');
            $log->execute([$participantId, $participantName, $chosen, 'System Check-in', 'move']);
        } catch (\Exception $e) {
            // Check-in assignment should still succeed if logging fails.
        }

        return null;
    }

    /**
     * @param list<string> $poolCodes
     */
    private function pickGroupWithLightestLoad(\PDO $db, array $poolCodes, int $maxPerGroup): ?string
    {
        if ($poolCodes === []) {
            return null;
        }

        $counts = [];
        foreach ($poolCodes as $c) {
            $counts[$c] = 0;
        }

        $placeholders = implode(',', array_fill(0, count($poolCodes), '?'));
        $cstmt = $db->prepare("SELECT group_code, COUNT(*) AS c FROM participants WHERE group_code IN ($placeholders) GROUP BY group_code");
        $cstmt->execute($poolCodes);
        foreach ($cstmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $gc = (string)$row['group_code'];
            if (array_key_exists($gc, $counts)) {
                $counts[$gc] = (int)$row['c'];
            }
        }

        $best = null;
        $bestCount = PHP_INT_MAX;
        foreach ($poolCodes as $code) {
            $n = $counts[$code] ?? 0;
            if ($maxPerGroup > 0 && $n >= $maxPerGroup) {
                continue;
            }
            if ($n < $bestCount) {
                $bestCount = $n;
                $best = $code;
            }
        }

        return $best;
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
