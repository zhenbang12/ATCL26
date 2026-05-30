<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;
use App\Core\SessionHelper;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class ParticipantController
{
    /** Return active session_id shortcut */
    private function sid(): int
    {
        return SessionHelper::currentSessionId();
    }

    public function index(): void
    {
        // Only advisor / committee can see full participant listing
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Participants & Admission';
        $db = Container::get('db');
        $sid = $this->sid();

        // Calculate statistics
        $stats = [];
        
        // Fetch total and checked-in counts in a single query (excluding duplicates)
        $statsStmt = $db->prepare('SELECT COUNT(*) as total, COUNT(checked_in_at) as checked_in FROM participants WHERE duplicate_of IS NULL AND session_id = ?');
        $statsStmt->execute([$sid]);
        $statsData = $statsStmt->fetch(\PDO::FETCH_ASSOC);
        $stats['total'] = (int)($statsData['total'] ?? 0);
        $stats['checked_in'] = (int)($statsData['checked_in'] ?? 0);
        $stats['not_checked_in'] = $stats['total'] - $stats['checked_in'];
        
        // Groups: configured shells if present, else distinct assignments
        try {
            $sidStmt = $db->prepare('SELECT COUNT(*) FROM event_groups WHERE session_id = ?');
            $sidStmt->execute([$sid]);
            $shellCount = (int)$sidStmt->fetchColumn();
            if ($shellCount > 0) {
                $stats['groups'] = $shellCount;
            } else {
                $stmt = $db->prepare("SELECT COUNT(DISTINCT group_code) as count FROM participants WHERE group_code IS NOT NULL AND group_code != '' AND duplicate_of IS NULL AND session_id = ?");
                $stmt->execute([$sid]);
                $stats['groups'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];
            }
        } catch (\Exception $e) {
            $stmt = $db->prepare("SELECT COUNT(DISTINCT group_code) as count FROM participants WHERE group_code IS NOT NULL AND group_code != '' AND duplicate_of IS NULL AND session_id = ?");
            $stmt->execute([$sid]);
            $stats['groups'] = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        }
        
        // Faculty distribution
        $stmt = $db->prepare("SELECT faculty, COUNT(*) as count FROM participants WHERE duplicate_of IS NULL AND session_id = ? GROUP BY faculty ORDER BY count DESC");
        $stmt->execute([$sid]);
        $facultyData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stats['faculty_distribution'] = [];
        foreach ($facultyData as $row) {
            $stats['faculty_distribution'][$row['faculty'] ?? 'Not Specified'] = (int)$row['count'];
        }
        
        // Language distribution
        $stmt = $db->prepare("SELECT preferred_language, COUNT(*) as count FROM participants WHERE duplicate_of IS NULL AND session_id = ? GROUP BY preferred_language ORDER BY count DESC");
        $stmt->execute([$sid]);
        $languageData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stats['language_distribution'] = [];
        foreach ($languageData as $row) {
            $stats['language_distribution'][$row['preferred_language'] ?? 'Not Specified'] = (int)$row['count'];
        }
        
        // Group distribution
        $stmt = $db->prepare("SELECT group_code, COUNT(*) as count FROM participants WHERE group_code IS NOT NULL AND group_code != '' AND duplicate_of IS NULL AND session_id = ? GROUP BY group_code ORDER BY group_code");
        $stmt->execute([$sid]);
        $groupData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stats['group_distribution'] = [];
        foreach ($groupData as $row) {
            $stats['group_distribution'][$row['group_code']] = (int)$row['count'];
        }
        
        // Recent registrations (last 10)
        $stmt = $db->prepare('SELECT id, full_name, student_id, intake, programme_name, faculty, group_code, registration_type, checked_in_at FROM participants WHERE duplicate_of IS NULL AND session_id = ? ORDER BY id DESC LIMIT 10');
        $stmt->execute([$sid]);
        $recentParticipants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $registrationSettings = SettingsController::loadRegistrationSettings($db);
 
        // NOTE: The main table list is loaded dynamically via DataTables AJAX endpoint (/participants/data).
        // Statically query-loading all participants here is redundant and has been removed to conserve database resources and memory.
        $currentFilter = $_GET['filter'] ?? 'all';

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function list(): void
    {
        // Redirect to unified index
        $filter = $_GET['filter'] ?? 'all';
        header('Location: /participants?filter=' . urlencode($filter));
        exit;
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

        if (!$registrationSettings['walk_in_enabled'] && !Auth::check()) {
            $closedTitle = 'Walk-in registration is currently closed';
            include __DIR__ . '/../../views/layout/header.php';
            include __DIR__ . '/../../views/participants/registration_closed.php';
            include __DIR__ . '/../../views/layout/footer.php';
            return;
        }

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

        if (!Auth::check() && $registrationType === 'walk_in' && !$registrationSettings['walk_in_enabled']) {
            $_SESSION['registration_error'] = 'Walk-in registration is currently closed.';
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
        $sid = $this->sid();
        if (!empty($studentId)) {
            $checkStmt = $db->prepare('SELECT id, full_name FROM participants WHERE student_id = ? AND session_id = ?');
            $checkStmt->execute([$studentId, $sid]);
            $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                // Student ID already exists - redirect back with edit details prompt
                $_SESSION['registration_error'] = 'This Student ID is already registered.';
                $_SESSION['registration_duplicate_id'] = $studentId;
                $_SESSION['registration_input'] = $_POST;
                header('Location: ' . $returnPath);
                exit;
            }
        }

        // Check for duplicate email
        if (!empty($studentEmail)) {
            $checkStmt = $db->prepare('SELECT id, full_name, student_id FROM participants WHERE student_email = ? AND duplicate_of IS NULL AND session_id = ? LIMIT 1');
            $checkStmt->execute([$studentEmail, $sid]);
            $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                $_SESSION['registration_error'] = 'This email is already registered.';
                $_SESSION['registration_duplicate_id'] = $existing['student_id'];
                $_SESSION['registration_input'] = $_POST;
                header('Location: ' . $returnPath);
                exit;
            }
        }

        // Check for duplicate phone number
        $contactNo = $this->formatPhoneNumber($_POST['contact_no'] ?? '');
        if (!empty($contactNo)) {
            $checkStmt = $db->prepare('SELECT id, full_name, student_id FROM participants WHERE contact_no = ? AND duplicate_of IS NULL AND session_id = ? LIMIT 1');
            $checkStmt->execute([$contactNo, $sid]);
            $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                $_SESSION['registration_error'] = 'This phone number is already registered.';
                $_SESSION['registration_duplicate_id'] = $existing['student_id'];
                $_SESSION['registration_input'] = $_POST;
                header('Location: ' . $returnPath);
                exit;
            }
        }

        // Generate a unique QR code value for this participant
        $qrCode = bin2hex(random_bytes(8));

        // Convert emergency phone number from 0XXXXXXXXX to 60XXXXXXXXX format
        $emergencyContactNo = $this->formatPhoneNumber($_POST['emergency_contact_no'] ?? '');
        $preferredLanguage = trim((string)($_POST['preferred_language'] ?? ''));

        try {
            $stmt = $db->prepare('INSERT INTO participants (
                session_id,
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
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $sid,
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

        $db = Container::get('db');
        $sid = $this->sid();
        $sidStmt = $db->prepare('
            SELECT id, full_name, student_id, checked_in_at, group_code, preferred_language 
            FROM participants 
            WHERE checked_in_at IS NOT NULL AND session_id = ?
            ORDER BY checked_in_at DESC 
            LIMIT 5
        ');
        $sidStmt->execute([$sid]);
        $recentCheckins = $sidStmt->fetchAll(\PDO::FETCH_ASSOC);

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

        $sid = $this->sid();
        $stmt = $db->prepare('SELECT * FROM participants WHERE (qr_code = ? OR student_id = ?) AND session_id = ?');
        $stmt->execute([$code, $code, $sid]);
        $participant = $stmt->fetch(\PDO::FETCH_ASSOC);

        $checkinAssignmentNotice = null;
        $checkinCriticalError = null;

        if ($participant) {
            if ((int)($participant['blacklisted'] ?? 0) === 1) {
                $checkinCriticalError = 'Participant is blacklisted and cannot be checked in.';
            } elseif (!empty($participant['checked_in_at'])) {
                $formattedTime = date('g:i A', strtotime($participant['checked_in_at']));
                $checkinCriticalError = 'Participant is already checked in (at ' . $formattedTime . ').';
            } else {
                if (!empty($participant['duplicate_of'])) {
                    $canonStmt = $db->prepare('SELECT full_name, student_id FROM participants WHERE id = ?');
                    $canonStmt->execute([(int)$participant['duplicate_of']]);
                    $canonical = $canonStmt->fetch(\PDO::FETCH_ASSOC);
                    if ($canonical) {
                        $checkinAssignmentNotice = 'This record is flagged as a duplicate of ' . htmlspecialchars($canonical['full_name']) . ' (' . htmlspecialchars($canonical['student_id']) . '). Consider checking in the original record instead.';
                    }
                }
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
                    $stmt->execute([$code, $code, $sid]);
                    $participant = $stmt->fetch(\PDO::FETCH_ASSOC);
                }
            }
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_GET['format']) && $_GET['format'] === 'json');

        if ($isAjax) {
            header('Content-Type: application/json');
            if ($participant) {
                if ($checkinCriticalError) {
                    echo json_encode([
                        'success' => false,
                        'message' => $checkinCriticalError
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Check-in successful',
                        'participant' => [
                            'id' => $participant['id'],
                            'full_name' => $participant['full_name'],
                            'student_id' => $participant['student_id'],
                            'student_email' => $participant['student_email'] ?? '',
                            'preferred_language' => $participant['preferred_language'] ?? '',
                            'group_code' => $participant['group_code'] ?: 'Not assigned',
                            'medical_notes' => $participant['medical_notes'] ?: '',
                            'dietary_notes' => $participant['dietary_notes'] ?: '',
                            'checked_in_at' => $participant['checked_in_at']
                        ],
                        'notice' => $checkinAssignmentNotice
                    ], JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $checkinCriticalError ?: 'QR code / Student ID not recognized.'
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
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
        $sid = $this->sid();
        $sidStmt = $db->prepare('
                SELECT group_code, language_pool, max_per_group
                FROM event_groups
                WHERE session_id = ?
                ORDER BY sort_order ASC, CAST(group_code AS UNSIGNED), group_code
            ');
            $sidStmt->execute([$sid]);
            $layoutRows = $sidStmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $layoutRows = [];
        }

        $groupMaxMap = [];

        if ($layoutRows !== []) {
            $countMap = [];
            $sidStmt2 = $db->prepare("SELECT group_code, COUNT(*) AS count FROM participants WHERE group_code IS NOT NULL AND group_code != '' AND session_id = ? GROUP BY group_code");
            $sidStmt2->execute([$sid]);
            foreach ($sidStmt2->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $countMap[(string)$row['group_code']] = (int)$row['count'];
            }

            $groups = [];
            $groupTypes = [];
            $participantsByGroup = [];
            foreach ($layoutRows as $row) {
                $gc = (string)$row['group_code'];
                $groups[] = ['group_code' => $gc, 'count' => $countMap[$gc] ?? 0];
                $groupTypes[$gc] = (($row['language_pool'] ?? '') === 'english') ? 'English' : 'Mandarin';
                $groupMaxMap[$gc] = (int)($row['max_per_group'] ?? 0);
                if (($row['language_pool'] ?? '') === 'english') {
                    $currentEnglishGroups++;
                }
                $participantsByGroup[$gc] = [];
            }

            $sidStmt3 = $db->prepare("SELECT id, full_name, student_id, preferred_language, registration_type, checked_in_at, group_code FROM participants WHERE group_code IS NOT NULL AND group_code != '' AND session_id = ? ORDER BY CAST(group_code AS UNSIGNED), group_code, full_name");
            $sidStmt3->execute([$sid]);
            foreach ($sidStmt3->fetchAll(\PDO::FETCH_ASSOC) as $p) {
                $group = (string)$p['group_code'];
                if (isset($participantsByGroup[$group])) {
                    $participantsByGroup[$group][] = $p;
                }
            }
        } else {
            // Legacy: infer groups from participant assignments only
            $sidStmt4 = $db->prepare("SELECT group_code, COUNT(*) AS count FROM participants WHERE group_code IS NOT NULL AND group_code != '' AND session_id = ? GROUP BY group_code ORDER BY CAST(group_code AS UNSIGNED), group_code");
            $sidStmt4->execute([$sid]);
            $groups = $sidStmt4->fetchAll(\PDO::FETCH_ASSOC);

            $sidStmt5 = $db->prepare("SELECT id, full_name, student_id, preferred_language, registration_type, checked_in_at, group_code FROM participants WHERE group_code IS NOT NULL AND group_code != '' AND session_id = ? ORDER BY CAST(group_code AS UNSIGNED), group_code, full_name");
            $sidStmt5->execute([$sid]);
            $participantsByGroup = [];
            foreach ($sidStmt5->fetchAll(\PDO::FETCH_ASSOC) as $p) {
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
        $sidStmt6 = $db->prepare("SELECT COUNT(*) AS count FROM participants WHERE (group_code IS NULL OR group_code = '') AND session_id = ?");
        $sidStmt6->execute([$sid]);
        $ungrouped = $sidStmt6->fetch(\PDO::FETCH_ASSOC)['count'];

        // Get ungrouped participants for drag and drop editor
        $sidStmt7 = $db->prepare("SELECT id, full_name, student_id, preferred_language, registration_type, checked_in_at FROM participants WHERE (group_code IS NULL OR group_code = '') AND session_id = ? ORDER BY full_name");
        $sidStmt7->execute([$sid]);
        $ungroupedParticipants = $sidStmt7->fetchAll(\PDO::FETCH_ASSOC);

        // Facilitator data for senior buddy assignment per group
        $facilitators = [];
        $facilitatorByGroup = [];
        try {
            $sidStmt8 = $db->prepare("
                SELECT id, full_name, assigned_group_code
                FROM crew
                WHERE is_facilitator = 1 AND session_id = ?
                ORDER BY full_name
            ");
            $sidStmt8->execute([$sid]);
            $facilitators = $sidStmt8->fetchAll(\PDO::FETCH_ASSOC);
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
            $sidStmt9 = $db->prepare("
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
                WHERE gml.session_id = ?
                ORDER BY gml.id DESC
                LIMIT 25
            ");
            $sidStmt9->execute([$sid]);
            $recentMoveLogs = $sidStmt9->fetchAll(\PDO::FETCH_ASSOC);
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
     * View for assigning senior buddies (facilitators) to group shells.
     */
    public function assignBuddy(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        // Get group shells
        $sid = $this->sid();
        $sidStmt = $db->prepare('SELECT id, group_code, language_pool FROM event_groups WHERE session_id = ? ORDER BY sort_order, group_code');
        $sidStmt->execute([$sid]);
        $groups = $sidStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get crew marked as facilitators
        $facilitators = [];
        $facilitatorByGroup = [];
        try {
            $sidStmt2 = $db->prepare("
                SELECT id, full_name, assigned_group_code
                FROM crew
                WHERE is_facilitator = 1 AND session_id = ?
                ORDER BY full_name
            ");
            $sidStmt2->execute([$sid]);
            $facilitators = $sidStmt2->fetchAll(\PDO::FETCH_ASSOC);
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

        $title = 'Assign Senior Buddies';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/assign_buddy.php';
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
            header('Location: /participants/assign-buddy');
            exit;
        }

        $sid = $this->sid();
        if ($this->eventGroupLayoutExists($db)) {
            $v = $db->prepare('SELECT 1 FROM event_groups WHERE group_code = ? AND session_id = ? LIMIT 1');
            $v->execute([$groupCode, $sid]);
            if (!$v->fetchColumn()) {
                $_SESSION['grouping_message'] = 'That group is not part of the saved layout.';
                $_SESSION['grouping_message_type'] = 'danger';
                header('Location: /participants/assign-buddy');
                exit;
            }
        }

        try {
            $db->beginTransaction();

            // Ensure one facilitator per group and one group per facilitator.
            $clearGroupStmt = $db->prepare('UPDATE crew SET assigned_group_code = NULL WHERE assigned_group_code = ? AND is_facilitator = 1 AND session_id = ?');
            $clearGroupStmt->execute([$groupCode, $sid]);

            foreach ($crewIds as $crewId) {
                $clearCrewStmt = $db->prepare('UPDATE crew SET assigned_group_code = NULL WHERE id = ? AND is_facilitator = 1 AND session_id = ?');
                $clearCrewStmt->execute([$crewId, $sid]);

                $assignStmt = $db->prepare('UPDATE crew SET assigned_group_code = ? WHERE id = ? AND is_facilitator = 1 AND session_id = ?');
                $assignStmt->execute([$groupCode, $crewId, $sid]);
            }

            $db->commit();
            $_SESSION['grouping_message'] = 'Senior buddy assignment updated.';
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['grouping_message'] = 'Failed to assign senior buddy: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/assign-buddy');
        exit;
    }

    /**
     * Bulk assign senior buddies to groups.
     */
    public function assignFacilitatorsBulk(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $assignments = $_POST['assignments'] ?? [];

        if (!is_array($assignments)) {
            $_SESSION['grouping_message'] = 'Invalid request parameters.';
            $_SESSION['grouping_message_type'] = 'danger';
            header('Location: /participants/assign-buddy');
            exit;
        }

        try {
            $db->beginTransaction();

            // Clear all current assignments for facilitators in current session
            $sid = $this->sid();
            $clearAll = $db->prepare('UPDATE crew SET assigned_group_code = NULL WHERE is_facilitator = 1 AND session_id = ?');
            $clearAll->execute([$sid]);

            $assignedCrewIds = [];
            $assignStmt = $db->prepare('UPDATE crew SET assigned_group_code = ? WHERE id = ? AND is_facilitator = 1');

            foreach ($assignments as $groupCode => $crewIds) {
                $groupCode = trim((string)$groupCode);
                if ($groupCode === '') {
                    continue;
                }

                if (!is_array($crewIds)) {
                    $crewIds = [];
                }

                // Filter and unique crew IDs, ignoring 0 (unassigned)
                $crewIds = array_values(array_unique(array_filter(array_map('intval', $crewIds), static function ($id) {
                    return $id > 0;
                })));

                // Limit to 2 facilitators per group max
                $crewIds = array_slice($crewIds, 0, 2);

                foreach ($crewIds as $crewId) {
                    // Check for duplicate assignments across different groups
                    if (isset($assignedCrewIds[$crewId])) {
                        $q = $db->prepare('SELECT full_name FROM crew WHERE id = ? LIMIT 1');
                        $q->execute([$crewId]);
                        $name = $q->fetchColumn() ?: 'Facilitator (ID ' . $crewId . ')';
                        throw new \Exception('Senior buddy "' . $name . '" cannot be assigned to multiple groups.');
                    }

                    $assignedCrewIds[$crewId] = $groupCode;

                    // Update assignment in database
                    $assignStmt->execute([$groupCode, $crewId]);
                }
            }

            $db->commit();
            $_SESSION['grouping_message'] = 'All senior buddy assignments updated successfully.';
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['grouping_message'] = 'Failed to save assignments: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/assign-buddy');
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

        $sid = $this->sid();
        try {
            $db->beginTransaction();

            $delStmt = $db->prepare('DELETE FROM event_groups WHERE session_id = ?');
            $delStmt->execute([$sid]);

            $insert = $db->prepare('INSERT INTO event_groups (session_id, group_code, language_pool, sort_order) VALUES (?, ?, ?, ?)');
            $sort = 0;
            foreach ($groupCodes as $code) {
                $sort++;
                $pool = $sort <= $englishGroups ? 'english' : 'mandarin';
                $insert->execute([$sid, (string)$code, $pool, $sort]);
            }

            $settingsStmt = $db->prepare('
                INSERT INTO event_group_settings (session_id, id, max_per_group) VALUES (?, 1, ?)
                ON DUPLICATE KEY UPDATE max_per_group = VALUES(max_per_group)
            ');
            $settingsStmt->execute([$sid, $maxPerGroup]);

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
            $sid = $this->sid();
            $sidStmt = $db->prepare('
                SELECT
                    COALESCE(MAX(CAST(group_code AS UNSIGNED)), 0) AS max_code,
                    COALESCE(MAX(sort_order), 0) AS max_sort,
                    COUNT(*) AS total_groups
                FROM event_groups
                WHERE session_id = ?
            ');
            $sidStmt->execute([$sid]);
            $row = $sidStmt->fetch(\PDO::FETCH_ASSOC);

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

            $stmt = $db->prepare('INSERT INTO event_groups (session_id, group_code, language_pool, sort_order) VALUES (?, ?, ?, ?)');
            $stmt->execute([$sid, (string)$nextCode, $pool, $nextSort]);

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

        $sid = $this->sid();
        try {
            $stmt = $db->prepare('
                INSERT INTO event_group_settings (session_id, id, max_per_group) VALUES (?, 1, ?)
                ON DUPLICATE KEY UPDATE max_per_group = VALUES(max_per_group)
            ');
            $stmt->execute([$sid, $nextMax]);

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
     * Adjust max slots for a specific group (per-group override).
     * delta: +1 or -1
     */
    public function adjustGroupSlot(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $groupCode = trim((string)($_POST['group_code'] ?? ''));
        $delta = (int)($_POST['delta'] ?? 0);

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($groupCode === '' || !in_array($delta, [-1, 1], true)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid request.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['grouping_message'] = 'Invalid request.';
            $_SESSION['grouping_message_type'] = 'danger';
            header('Location: /participants/groups');
            exit;
        }

        try {
            // Verify group exists and get current per-group max
            $sid = $this->sid();
            $v = $db->prepare('SELECT max_per_group FROM event_groups WHERE group_code = ? AND session_id = ? LIMIT 1');
            $v->execute([$groupCode, $sid]);
            $current = (int)$v->fetchColumn();
            if ($current === false) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Group not found.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $_SESSION['grouping_message'] = 'Group not found.';
                $_SESSION['grouping_message_type'] = 'danger';
                header('Location: /participants/groups');
                exit;
            }

            // Compute effective current max (if 0, use global)
            $globalMax = $this->getEventGroupMaxPerGroup($db);
            $effectiveMax = $current > 0 ? $current : $globalMax;

            // Use atomic increment/decrement to prevent race conditions
            // When current is 0 (uses global), set it explicitly to effectiveMax + delta
            // Otherwise use atomic SQL arithmetic
            if ($current > 0) {
                $stmt = $db->prepare('UPDATE event_groups SET max_per_group = GREATEST(0, max_per_group + ?) WHERE group_code = ? AND session_id = ?');
                $stmt->execute([$delta, $groupCode, $sid]);
            } else {
                // Group uses global max (0); set per-group override explicitly
                $newMax = max(0, $effectiveMax + $delta);
                $stmt = $db->prepare('UPDATE event_groups SET max_per_group = ? WHERE group_code = ? AND session_id = ?');
                $stmt->execute([$newMax, $groupCode, $sid]);
            }

            // Read back the actual value to report accurately
            $v->execute([$groupCode, $sid]);
            $newMax = (int)$v->fetchColumn();
            $label = $newMax > 0 ? (string)$newMax : 'No limit';

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Group ' . $groupCode . ' max set to ' . $label . '.',
                    'group_code' => $groupCode,
                    'max_per_group' => $newMax,
                    'max_label' => $label,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $_SESSION['grouping_message'] = 'Group ' . $groupCode . ' max per group set to ' . $label . '.';
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Could not adjust slot: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['grouping_message'] = 'Could not adjust slot: ' . $e->getMessage();
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
        $sid = $this->sid();
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
            $v = $db->prepare('SELECT 1 FROM event_groups WHERE group_code = ? AND session_id = ? LIMIT 1');
            $v->execute([$targetGroup, $sid]);
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
                        session_id,
                        participant_id,
                        participant_name,
                        from_group_code,
                        to_group_code,
                        moved_by,
                        action_type
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $logStmt->execute([
                    $sid,
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
            $db->beginTransaction();
            $sid = $this->sid();
            $clearStmt = $db->prepare('UPDATE participants SET group_code = NULL WHERE session_id = ?');
            $clearStmt->execute([$sid]);
            $db->commit();
            $_SESSION['grouping_message'] = 'All group assignments have been cleared.';
            $_SESSION['grouping_message_type'] = 'success';
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
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
            $sid = $this->sid();
            $delShells = $db->prepare('DELETE FROM event_groups WHERE session_id = ?');
            $delShells->execute([$sid]);
            $resetSettings = $db->prepare('INSERT INTO event_group_settings (session_id, id, max_per_group) VALUES (?, 1, 0) ON DUPLICATE KEY UPDATE max_per_group = 0');
            $resetSettings->execute([$sid]);
            $clearBuddies = $db->prepare('UPDATE crew SET assigned_group_code = NULL WHERE is_facilitator = 1 AND session_id = ?');
            $clearBuddies->execute([$sid]);
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
            $sid = $this->sid();
            $stmt = $db->prepare('SELECT * FROM participants WHERE student_id = ? AND session_id = ?');
            $stmt->execute([$studentId, $sid]);
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
     * Export group assignments with participant info to CSV.
     */
    public function exportGroups(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $sid = $this->sid();

        // Get all participants with group info, ordered by group then name
        $stmt = $db->prepare('
            SELECT 
                p.group_code,
                p.full_name,
                p.student_id,
                p.student_email,
                p.faculty,
                p.programme_name,
                p.preferred_language,
                p.registration_type,
                p.contact_no,
                p.checked_in_at
            FROM participants p
            WHERE p.duplicate_of IS NULL AND p.session_id = ?
            ORDER BY 
                CASE WHEN p.group_code IS NULL OR p.group_code = "" THEN 1 ELSE 0 END,
                CAST(p.group_code AS UNSIGNED),
                p.group_code,
                p.full_name
        ');
        $stmt->execute([$sid]);

        // Get session name for filename
        $sessionInfo = SessionHelper::currentSession();
        $sessionName = $sessionInfo ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $sessionInfo['name']) : 'session';

        // Set headers for CSV download
        $filename = 'grouping_' . $sessionName . '_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // CSV headers
        $headers = [
            'Group',
            'Name',
            'Student ID',
            'Email',
            'Faculty',
            'Programme',
            'Language',
            'Registration Type',
            'Contact No',
            'Checked In',
            'Checked In At',
        ];
        fputcsv($output, $headers);

        // Write data rows
        while ($p = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $row = [
                $p['group_code'] ?? 'Ungrouped',
                $p['full_name'] ?? '',
                $p['student_id'] ?? '',
                $p['student_email'] ?? '',
                $p['faculty'] ?? '',
                $p['programme_name'] ?? '',
                $p['preferred_language'] ?? '',
                ($p['registration_type'] ?? 'pre_register') === 'walk_in' ? 'Walk-in' : 'Pre-register',
                $p['contact_no'] ?? '',
                !empty($p['checked_in_at']) ? 'Yes' : 'No',
                $p['checked_in_at'] ?? '',
            ];
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
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
        $sid = $this->sid();
        $query = 'SELECT id, full_name, ic_passport_no, student_id, student_email, intake, programme_name, faculty, gender, contact_no, emergency_contact_no, emergency_contact_relationship, preferred_language, registration_type, group_code, checked_in_at, created_at FROM participants WHERE duplicate_of IS NULL AND session_id = ?';

        if ($filter === 'checked_in') {
            $query .= ' AND checked_in_at IS NOT NULL';
        } elseif ($filter === 'not_checked_in') {
            $query .= ' AND checked_in_at IS NULL';
        }
        
        $query .= ' ORDER BY full_name';
        
        $stmt = $db->prepare($query);
        $stmt->execute([$sid]);

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

        // Write data rows by streaming each database record to output
        while ($p = $stmt->fetch(\PDO::FETCH_ASSOC)) {
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
            $sid = $this->sid();
            $stmt = $db->prepare('SELECT COUNT(*) FROM event_groups WHERE session_id = ?');
            $stmt->execute([$sid]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getEventGroupMaxPerGroup(\PDO $db): int
    {
        try {
            $sid = $this->sid();
            $stmt = $db->prepare('SELECT max_per_group FROM event_group_settings WHERE session_id = ? AND id = 1');
            $stmt->execute([$sid]);
            return max(0, (int)$stmt->fetchColumn());
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

        // Use a transaction with row-level locking (FOR UPDATE) to prevent
        // concurrent check-in operators from assigning two participants to
        // the same group that was at capacity, causing a TOCTOU race.
        $db->beginTransaction();

        try {
            // Lock the event_group_settings row to serialize group assignment
            $sid = $this->sid();
            $lockStmt = $db->prepare('SELECT max_per_group FROM event_group_settings WHERE session_id = ? AND id = 1 FOR UPDATE');
            $lockStmt->execute([$sid]);
            $globalMax = max(0, (int)$lockStmt->fetchColumn());

            // Lock all group rows in this language pool
            $stmt = $db->prepare('
                SELECT group_code, max_per_group
                FROM event_groups
                WHERE language_pool = ? AND session_id = ?
                ORDER BY sort_order ASC, CAST(group_code AS UNSIGNED), group_code
                FOR UPDATE
            ');
            $stmt->execute([$pool, $sid]);
            $poolRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $poolCodes = [];
            $perGroupMax = [];
            foreach ($poolRows as $row) {
                $gc = (string)$row['group_code'];
                $poolCodes[] = $gc;
                $perGroupMax[$gc] = (int)($row['max_per_group'] ?? 0);
            }

            if ($poolCodes === []) {
                $db->rollBack();
                return 'The saved layout has no groups in this participant\'s language pool. Adjust total vs English group counts.';
            }

            // Count current members (read is safe within the locked transaction)
            $chosen = $this->pickGroupWithLightestLoad($db, $poolCodes, $globalMax, $perGroupMax);
            if ($chosen === null) {
                $db->rollBack();
                return 'All groups in this language pool are at capacity. Raise max per group or add groups, then check in again.';
            }

            $upd = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ? AND IFNULL(group_code, \'\') = \'\'');
            $upd->execute([$chosen, $participantId]);

            if ($upd->rowCount() === 0) {
                $db->rollBack();
                return null;
            }

            // Log the assignment
            try {
                $log = $db->prepare('
                    INSERT INTO group_move_logs (
                        session_id,
                        participant_id,
                        participant_name,
                        from_group_code,
                        to_group_code,
                        moved_by,
                        action_type
                    ) VALUES (?, ?, ?, NULL, ?, ?, ?)
                ');
                $log->execute([$sid, $participantId, $participantName, $chosen, 'System Check-in', 'move']);
            } catch (\Exception $e) {
                // Logging failure should not block check-in
            }

            $db->commit();
            return null;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return 'Group assignment failed: ' . $e->getMessage();
        }
    }

    /**
     * @param list<string> $poolCodes
     * @param array<string,int> $perGroupMax  Per-group max overrides (0 = use global)
     */
    private function pickGroupWithLightestLoad(\PDO $db, array $poolCodes, int $globalMax, array $perGroupMax = []): ?string
    {
        if ($poolCodes === []) {
            return null;
        }

        $counts = [];
        foreach ($poolCodes as $c) {
            $counts[$c] = 0;
        }

        $sid = $this->sid();
        $placeholders = implode(',', array_fill(0, count($poolCodes), '?'));
        $cstmt = $db->prepare("SELECT group_code, COUNT(*) AS c FROM participants WHERE group_code IN ($placeholders) AND session_id = ? GROUP BY group_code");
        $cstmt->execute(array_merge($poolCodes, [$sid]));
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
            // Per-group max takes precedence over global if non-zero
            $groupLimit = ($perGroupMax[$code] ?? 0) > 0 ? $perGroupMax[$code] : $globalMax;
            if ($groupLimit > 0 && $n >= $groupLimit) {
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
            $sid = $this->sid();
            $stmt = $db->prepare('SELECT id, moved_at FROM group_move_logs WHERE session_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$sid]);
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

    public function edit(): void
    {
        Auth::requireRole(['advisor', 'committee']);
        $db = Container::get('db');
        $id = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare('SELECT * FROM participants WHERE id = ?');
        $stmt->execute([$id]);
        $participant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$participant) {
            $_SESSION['participants_message'] = 'Participant not found.';
            $_SESSION['participants_message_type'] = 'danger';
            header('Location: /participants');
            exit;
        }

        $title = 'Edit Participant';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/edit.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function update(): void
    {
        Auth::requireRole(['advisor', 'committee']);
        $db = Container::get('db');
        $id = (int)($_POST['id'] ?? 0);

        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $icPassport = trim((string)($_POST['ic_passport_no'] ?? ''));
        $studentId = trim((string)($_POST['student_id'] ?? ''));
        $studentEmail = trim((string)($_POST['student_email'] ?? ''));
        $intake = trim((string)($_POST['intake'] ?? ''));
        $programmeName = trim((string)($_POST['programme_name'] ?? ''));
        $faculty = trim((string)($_POST['faculty'] ?? ''));
        $gender = trim((string)($_POST['gender'] ?? ''));
        $contactNo = $this->formatPhoneNumber($_POST['contact_no'] ?? '');
        $emergencyContactNo = $this->formatPhoneNumber($_POST['emergency_contact_no'] ?? '');
        $emergencyRelationship = trim((string)($_POST['emergency_contact_relationship'] ?? ''));
        $preferredLanguage = trim((string)($_POST['preferred_language'] ?? ''));
        $registrationType = trim((string)($_POST['registration_type'] ?? 'pre_register'));
        $groupCode = trim((string)($_POST['group_code'] ?? ''));
        if ($groupCode === '') {
            $groupCode = null;
        }
        $blacklisted = isset($_POST['blacklisted']) ? 1 : 0;

        if ($fullName === '' || $studentId === '' || $studentEmail === '') {
            $_SESSION['participants_message'] = 'Name, Student ID, and Email are required.';
            $_SESSION['participants_message_type'] = 'danger';
            header('Location: /participants/edit?id=' . $id);
            exit;
        }

        try {
            $stmt = $db->prepare('UPDATE participants SET 
                full_name = ?,
                ic_passport_no = ?,
                student_id = ?,
                student_email = ?,
                intake = ?,
                programme_name = ?,
                faculty = ?,
                gender = ?,
                contact_no = ?,
                emergency_contact_no = ?,
                emergency_contact_relationship = ?,
                preferred_language = ?,
                registration_type = ?,
                group_code = ?,
                blacklisted = ?
                WHERE id = ?');
            $stmt->execute([
                $fullName,
                $icPassport,
                $studentId,
                $studentEmail,
                $intake,
                $programmeName,
                $faculty,
                $gender,
                $contactNo,
                $emergencyContactNo,
                $emergencyRelationship,
                $preferredLanguage,
                $registrationType,
                $groupCode,
                $blacklisted,
                $id
            ]);

            $_SESSION['participants_message'] = 'Participant updated successfully.';
            $_SESSION['participants_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['participants_message'] = 'Error updating participant: ' . $e->getMessage();
            $_SESSION['participants_message_type'] = 'danger';
        }

        header('Location: /participants');
        exit;
    }

    public function delete(): void
    {
        Auth::requireRole(['advisor', 'committee']);
        $db = Container::get('db');
        $id = (int)($_POST['id'] ?? 0);

        try {
            $stmt = $db->prepare('DELETE FROM participants WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['participants_message'] = 'Participant deleted successfully.';
            $_SESSION['participants_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['participants_message'] = 'Error deleting participant: ' . $e->getMessage();
            $_SESSION['participants_message_type'] = 'danger';
        }

        header('Location: /participants');
        exit;
    }

    public function verifyEditForm(): void
    {
        $studentId = trim((string)($_GET['student_id'] ?? ''));
        $title = 'Verify Identity';
        
        $errorMessage = $_SESSION['public_verify_error'] ?? null;
        if (isset($_SESSION['public_verify_error'])) {
            unset($_SESSION['public_verify_error']);
        }

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/verify_edit.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function processVerifyEdit(): void
    {
        $db = Container::get('db');
        $studentId = trim((string)($_POST['student_id'] ?? ''));
        $email = trim((string)($_POST['student_email'] ?? ''));

        if ($studentId === '' || $email === '') {
            $_SESSION['public_verify_error'] = 'Both Student ID and Email are required.';
            header('Location: /participants/verify-edit?student_id=' . urlencode($studentId));
            exit;
        }

        $sid = $this->sid();
        $stmt = $db->prepare('SELECT id, student_email FROM participants WHERE student_id = ? AND session_id = ?');
        $stmt->execute([$studentId, $sid]);
        $participant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($participant && strtolower(trim((string)$participant['student_email'])) === strtolower($email)) {
            $_SESSION['public_edit_participant_id'] = (int)$participant['id'];
            header('Location: /participants/edit-public');
            exit;
        } else {
            $_SESSION['public_verify_error'] = 'Verification failed. The email entered does not match our records for this Student ID.';
            header('Location: /participants/verify-edit?student_id=' . urlencode($studentId));
            exit;
        }
    }

    public function editPublicForm(): void
    {
        $db = Container::get('db');
        $id = (int)($_SESSION['public_edit_participant_id'] ?? 0);

        if ($id === 0) {
            $_SESSION['registration_error'] = 'Session expired or invalid access. Please try again.';
            header('Location: /participants/create');
            exit;
        }

        $stmt = $db->prepare('SELECT * FROM participants WHERE id = ?');
        $stmt->execute([$id]);
        $participant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$participant) {
            $_SESSION['registration_error'] = 'Participant not found.';
            header('Location: /participants/create');
            exit;
        }

        $errorMessage = $_SESSION['registration_error'] ?? null;
        if (isset($_SESSION['registration_error'])) {
            unset($_SESSION['registration_error']);
        }

        $title = 'Edit Registration Info';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/edit_public.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function updatePublic(): void
    {
        $db = Container::get('db');
        $id = (int)($_SESSION['public_edit_participant_id'] ?? 0);

        if ($id === 0) {
            $_SESSION['registration_error'] = 'Session expired or invalid access.';
            header('Location: /participants/create');
            exit;
        }

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
            || $studentEmail === ''
            || $programmeName === ''
            || $faculty === ''
            || $contactRaw === ''
            || $preferredLanguage === ''
        ) {
            $_SESSION['registration_error'] = 'Please complete every field on the form.';
            header('Location: /participants/edit-public');
            exit;
        }

        if (!$this->isValidTarcStudentEmail($studentEmail)) {
            $_SESSION['registration_error'] = 'Student email must be a valid address ending with @student.tarc.edu.my.';
            header('Location: /participants/edit-public');
            exit;
        }

        // Convert phone numbers
        $contactNo = $this->formatPhoneNumber($contactRaw);
        $emergencyContactNo = $this->formatPhoneNumber($_POST['emergency_contact_no'] ?? '');

        try {
            $stmt = $db->prepare('UPDATE participants SET 
                full_name = ?,
                gender = ?,
                student_email = ?,
                programme_name = ?,
                faculty = ?,
                contact_no = ?,
                emergency_contact_no = ?,
                emergency_contact_relationship = ?,
                preferred_language = ?
                WHERE id = ?');
            $stmt->execute([
                $fullName,
                $gender,
                $studentEmail,
                $programmeName,
                $faculty,
                $contactNo,
                $emergencyContactNo,
                $_POST['emergency_contact_relationship'] ?? '',
                $preferredLanguage,
                $id
            ]);

            // Retrieve updated student_id for redirect
            $stmtId = $db->prepare('SELECT student_id FROM participants WHERE id = ?');
            $stmtId->execute([$id]);
            $studentId = $stmtId->fetchColumn();

            // Clear session edit ID
            unset($_SESSION['public_edit_participant_id']);

            $_SESSION['registration_error'] = 'Your registration details have been updated successfully.';
            header('Location: /participants/lookup?student_id=' . urlencode($studentId));
            exit;
        } catch (\Exception $e) {
            $_SESSION['registration_error'] = 'Error updating registration: ' . $e->getMessage();
            header('Location: /participants/edit-public');
            exit;
        }
    }

    /**
     * AJAX endpoint for server-side paginated DataTables.
     */
    public function tableData(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        // Parameters from DataTables
        $draw = (int)($_GET['draw'] ?? 1);
        $start = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 10);
        $search = trim((string)($_GET['search']['value'] ?? ''));
        $currentFilter = $_GET['filter'] ?? 'all';

        // Sorting
        $orderColumnIndex = (int)($_GET['order'][0]['column'] ?? 1);
        $orderDir = strtolower(trim((string)($_GET['order'][0]['dir'] ?? 'asc')));
        if ($orderDir !== 'desc') {
            $orderDir = 'asc';
        }

        // Map column index to database column name
        $columns = [
            0 => 'id',
            1 => 'full_name',
            2 => 'student_id',
            3 => 'student_email',
            4 => 'programme_name',
            5 => 'faculty',
            6 => 'contact_no',
            7 => 'preferred_language',
            8 => 'registration_type',
            9 => 'group_code',
            10 => 'checked_in_at'
        ];
        $orderBy = $columns[$orderColumnIndex] ?? 'full_name';
        
        $sid = $this->sid();
        // Build base query
        $whereClauses = ['duplicate_of IS NULL', 'session_id = ?'];
        $params = [$sid];

        // Apply tab filter (all, checked_in, not_checked_in)
        if ($currentFilter === 'checked_in') {
            $whereClauses[] = 'checked_in_at IS NOT NULL';
        } elseif ($currentFilter === 'not_checked_in') {
            $whereClauses[] = 'checked_in_at IS NULL';
        }

        // Apply search keyword
        if ($search !== '') {
            $searchWildcard = '%' . $search . '%';
            $whereClauses[] = '(full_name LIKE ? OR student_id LIKE ? OR student_email LIKE ? OR programme_name LIKE ? OR faculty LIKE ? OR group_code LIKE ?)';
            array_push($params, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
        }

        $whereSql = '';
        if ($whereClauses !== []) {
            $whereSql = ' WHERE ' . implode(' AND ', $whereClauses);
        }

        // Get total records in the database (without search keyword, but with custom tab filter if active)
        $totalWhereClauses = ['duplicate_of IS NULL', 'session_id = ?'];
        $totalParams = [$sid];
        if ($currentFilter === 'checked_in') {
            $totalWhereClauses[] = 'checked_in_at IS NOT NULL';
        } elseif ($currentFilter === 'not_checked_in') {
            $totalWhereClauses[] = 'checked_in_at IS NULL';
        }
        $totalWhereSql = ' WHERE ' . implode(' AND ', $totalWhereClauses);
        $countTotalStmt = $db->prepare('SELECT COUNT(*) FROM participants' . $totalWhereSql);
        $countTotalStmt->execute($totalParams);
        $recordsTotal = (int)$countTotalStmt->fetchColumn();

        // Get filtered records count
        $countFilteredStmt = $db->prepare('SELECT COUNT(*) FROM participants' . $whereSql);
        $countFilteredStmt->execute($params);
        $recordsFiltered = (int)$countFilteredStmt->fetchColumn();

        // Fetch records
        $orderBySafe = $orderBy; 
        $orderDirSafe = $orderDir; 

        $querySql = 'SELECT id, full_name, student_id, student_email, intake, programme_name, faculty, contact_no, preferred_language, group_code, registration_type, checked_in_at FROM participants' . $whereSql;
        
        // Add ordering
        if ($orderBySafe === 'group_code') {
            $querySql .= ' ORDER BY CAST(group_code AS UNSIGNED) ' . $orderDirSafe . ', group_code ' . $orderDirSafe;
        } else {
            $querySql .= ' ORDER BY ' . $orderBySafe . ' ' . $orderDirSafe;
        }
        
        // Add limit and offset
        $querySql .= ' LIMIT ' . (int)$length . ' OFFSET ' . (int)$start;

        $stmt = $db->prepare($querySql);
        $stmt->execute($params);
        $participants = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $isLoggedIn = Auth::check();
        $data = [];
        $counter = $start + 1;

        foreach ($participants as $p) {
            $actionsHtml = '';
            if ($isLoggedIn) {
                $actionsHtml = '<div class="d-flex align-items-center">' .
                    '<a href="/participants/edit?id=' . (int)$p['id'] . '" class="btn btn-sm btn-outline-primary py-1 px-3 me-2" style="border-radius: 8px !important; font-size: 0.75rem !important;">Edit</a>' .
                    '<form method="post" action="/participants/delete" class="d-inline m-0" onsubmit="return confirm(\'Are you sure you want to delete this participant?\');">' .
                    '<input type="hidden" name="id" value="' . (int)$p['id'] . '">' .
                    '<button type="submit" class="btn btn-sm btn-outline-danger py-1 px-3" style="border-radius: 8px !important; font-size: 0.75rem !important;">Delete</button>' .
                    '</form>' .
                    '</div>';
            }

            $langBadge = '<span class="badge bg-secondary" style="font-size: 10px;">' . htmlspecialchars($p['preferred_language'] ?? '') . '</span>';
            
            $regBadge = (($p['registration_type'] ?? 'pre_register') === 'walk_in')
                ? '<span class="badge bg-dark" style="font-size: 10px;">Walk-in</span>'
                : '<span class="badge bg-secondary" style="font-size: 10px;">Pre-register</span>';

            $checkedInBadge = !empty($p['checked_in_at'])
                ? '<span class="badge bg-success" style="font-size: 10px;">Yes</span>'
                : '<span class="badge bg-warning text-dark" style="font-size: 10px;">No</span>';

            $row = [
                $counter++,
                htmlspecialchars($p['full_name'] ?? ''),
                htmlspecialchars($p['student_id'] ?? ''),
                htmlspecialchars($p['student_email'] ?? ''),
                htmlspecialchars($p['programme_name'] ?? ''),
                htmlspecialchars($p['faculty'] ?? ''),
                htmlspecialchars($p['contact_no'] ?? ''),
                $langBadge,
                $regBadge,
                '<span class="fw-bold text-primary">' . htmlspecialchars($p['group_code'] ?: '-') . '</span>',
                $checkedInBadge,
            ];

            if ($isLoggedIn) {
                $row[] = $actionsHtml;
            }

            $data[] = $row;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Move multiple participants to another group at once (POST).
     */
    public function bulkMoveParticipantGroup(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $sid = $this->sid();
        $participantIds = $_POST['participant_ids'] ?? [];
        if (!is_array($participantIds)) {
            $participantIds = [];
        }
        $participantIds = array_map('intval', $participantIds);
        $participantIds = array_values(array_filter($participantIds, function($id) { return $id > 0; }));

        $targetGroup = trim((string)($_POST['target_group'] ?? ''));
        if ($targetGroup !== '') {
            if (!preg_match('/^\d{1,2}$/', $targetGroup)) {
                $this->respondGroupMove(false, 'Invalid target group.');
                return;
            }
            $targetGroup = (string)((int)$targetGroup);
        } else {
            $targetGroup = null;
        }

        if ($participantIds === []) {
            $this->respondGroupMove(false, 'No participants selected.');
            return;
        }

        if ($targetGroup !== null && $this->eventGroupLayoutExists($db)) {
            $v = $db->prepare('SELECT 1 FROM event_groups WHERE group_code = ? AND session_id = ? LIMIT 1');
            $v->execute([$targetGroup, $sid]);
            if (!$v->fetchColumn()) {
                $this->respondGroupMove(false, 'Target group is not in the saved layout.');
                return;
            }
        }

        try {
            $db->beginTransaction();

            $movedBy = (string)(Auth::user()['username'] ?? 'Unknown');
            $logInsert = $db->prepare('
                INSERT INTO group_move_logs (
                    session_id,
                    participant_id,
                    participant_name,
                    from_group_code,
                    to_group_code,
                    moved_by,
                    action_type
                ) VALUES (?, ?, ?, ?, ?, ?, "move")
            ');

            $update = $db->prepare('UPDATE participants SET group_code = ? WHERE id = ?');
            
            $movedCount = 0;
            foreach ($participantIds as $id) {
                // Get current group and name
                $stmt = $db->prepare('SELECT full_name, group_code FROM participants WHERE id = ?');
                $stmt->execute([$id]);
                $p = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$p) {
                    continue;
                }

                $fromGroup = $p['group_code'] ?? null;
                $fromGroup = $fromGroup === '' ? null : $fromGroup;

                // Update group code
                $update->execute([$targetGroup, $id]);

                // Log entry
                $logInsert->execute([
                    $sid,
                    $id,
                    $p['full_name'],
                    $fromGroup,
                    $targetGroup,
                    $movedBy
                ]);
                $movedCount++;
            }

            $db->commit();
            
            // Fetch the latest move log ID
            $sid = $this->sid();
            $latestIdStmt = $db->prepare('SELECT MAX(id) FROM group_move_logs WHERE session_id = ?');
            $latestIdStmt->execute([$sid]);
            $latestLogId = (int)$latestIdStmt->fetchColumn();

            $toLabel = $targetGroup === null ? 'Ungrouped' : 'Group ' . $targetGroup;
            $this->respondGroupMove(true, "Successfully moved {$movedCount} participants to {$toLabel}.", [
                'latest_move_log_id' => $latestLogId
            ]);
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->respondGroupMove(false, 'Failed to perform bulk move: ' . $e->getMessage());
        }
    }

    public function duplicates(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $title = 'Duplicate Detection';
        $sid = $this->sid();

        // Find duplicate emails
        $emailDupes = [];
        $stmt = $db->prepare("SELECT student_email, COUNT(*) as cnt, GROUP_CONCAT(id) as ids FROM participants WHERE duplicate_of IS NULL AND student_email != '' AND session_id = ? GROUP BY student_email HAVING cnt > 1 ORDER BY cnt DESC");
        $stmt->execute([$sid]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $ids = array_map('intval', explode(',', $row['ids']));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pStmt = $db->prepare("SELECT * FROM participants WHERE id IN ($placeholders) ORDER BY created_at ASC");
            $pStmt->execute($ids);
            $emailDupes[] = [
                'match_value' => $row['student_email'],
                'count' => (int)$row['cnt'],
                'participants' => $pStmt->fetchAll(\PDO::FETCH_ASSOC)
            ];
        }

        // Find duplicate phones
        $phoneDupes = [];
        $stmt = $db->prepare("SELECT contact_no, COUNT(*) as cnt, GROUP_CONCAT(id) as ids FROM participants WHERE duplicate_of IS NULL AND contact_no != '' AND session_id = ? GROUP BY contact_no HAVING cnt > 1 ORDER BY cnt DESC");
        $stmt->execute([$sid]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $ids = array_map('intval', explode(',', $row['ids']));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pStmt = $db->prepare("SELECT * FROM participants WHERE id IN ($placeholders) ORDER BY created_at ASC");
            $pStmt->execute($ids);
            $phoneDupes[] = [
                'match_value' => $row['contact_no'],
                'count' => (int)$row['cnt'],
                'participants' => $pStmt->fetchAll(\PDO::FETCH_ASSOC)
            ];
        }

        // Find duplicate names
        $nameDupes = [];
        $stmt = $db->prepare("SELECT full_name, COUNT(*) as cnt, GROUP_CONCAT(id) as ids FROM participants WHERE duplicate_of IS NULL AND session_id = ? GROUP BY full_name HAVING cnt > 1 ORDER BY cnt DESC");
        $stmt->execute([$sid]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $ids = array_map('intval', explode(',', $row['ids']));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pStmt = $db->prepare("SELECT * FROM participants WHERE id IN ($placeholders) ORDER BY created_at ASC");
            $pStmt->execute($ids);
            $nameDupes[] = [
                'match_value' => $row['full_name'],
                'count' => (int)$row['cnt'],
                'participants' => $pStmt->fetchAll(\PDO::FETCH_ASSOC)
            ];
        }

        // Already flagged duplicates
        $flaggedStmt = $db->prepare("SELECT p.*, c.full_name as canonical_name, c.student_id as canonical_student_id FROM participants p LEFT JOIN participants c ON p.duplicate_of = c.id WHERE p.duplicate_of IS NOT NULL AND p.session_id = ? ORDER BY p.created_at DESC");
        $flaggedStmt->execute([$sid]);
        $flagged = $flaggedStmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalGroups = count($emailDupes) + count($phoneDupes) + count($nameDupes);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/duplicates.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function resolveDuplicate(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $duplicateIdsRaw = trim((string)($_POST['duplicate_id'] ?? ''));
        $canonicalId = (int)($_POST['canonical_id'] ?? 0);

        if ($duplicateIdsRaw === '' || $canonicalId <= 0) {
            $_SESSION['participants_message'] = 'Invalid selection. Please select one record as the original.';
            $_SESSION['participants_message_type'] = 'danger';
            header('Location: /participants/duplicates');
            exit;
        }

        $duplicateIds = array_filter(array_map('intval', explode(',', $duplicateIdsRaw)), fn($id) => $id > 0 && $id !== $canonicalId);

        if (empty($duplicateIds)) {
            $_SESSION['participants_message'] = 'No valid duplicate records to flag.';
            $_SESSION['participants_message_type'] = 'danger';
            header('Location: /participants/duplicates');
            exit;
        }

        try {
            // Validate canonical record exists
            $checkStmt = $db->prepare('SELECT id FROM participants WHERE id = ? LIMIT 1');
            $checkStmt->execute([$canonicalId]);
            if (!$checkStmt->fetch()) {
                $_SESSION['participants_message'] = 'The selected original record no longer exists.';
                $_SESSION['participants_message_type'] = 'danger';
                header('Location: /participants/duplicates');
                exit;
            }

            $stmt = $db->prepare('UPDATE participants SET duplicate_of = ? WHERE id = ? AND id != ?');
            $flagged = 0;
            foreach ($duplicateIds as $dupId) {
                $stmt->execute([$canonicalId, $dupId, $canonicalId]);
                $flagged += $stmt->rowCount();
            }
            $_SESSION['participants_message'] = "Flagged {$flagged} record(s) as duplicate of #{$canonicalId}.";
            $_SESSION['participants_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['participants_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['participants_message_type'] = 'danger';
        }

        header('Location: /participants/duplicates');
        exit;
    }

    public function unresolveDuplicate(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['participants_message'] = 'Invalid participant ID.';
            $_SESSION['participants_message_type'] = 'danger';
            header('Location: /participants/duplicates');
            exit;
        }

        try {
            $stmt = $db->prepare('UPDATE participants SET duplicate_of = NULL WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['participants_message'] = 'Record #' . $id . ' has been unflagged.';
            $_SESSION['participants_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['participants_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['participants_message_type'] = 'danger';
        }

        header('Location: /participants/duplicates');
        exit;
    }
}
