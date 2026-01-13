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

        // Get filter parameter
        $filter = $_GET['filter'] ?? 'all'; // 'all', 'checked_in', 'not_checked_in'
        
        // Build query with optional filter
        $query = 'SELECT id, full_name, student_id, intake, programme_name, faculty, contact_no, preferred_language, group_code, checked_in_at FROM participants';
        
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
        $title = 'Register Participant';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/create.php';
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
                qr_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
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
                $_POST['preferred_language'] ?? '',
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
        $stmt = $db->query('SELECT group_code, COUNT(*) AS count FROM participants WHERE group_code IS NOT NULL GROUP BY group_code ORDER BY group_code');
        $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get ungrouped count
        $stmt = $db->query('SELECT COUNT(*) AS count FROM participants WHERE group_code IS NULL');
        $ungrouped = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

        // Get participants by group for detailed view
        $stmt = $db->query('SELECT id, full_name, student_id, group_code FROM participants WHERE group_code IS NOT NULL ORDER BY group_code, full_name');
        $participantsByGroup = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $p) {
            $group = $p['group_code'];
            if (!isset($participantsByGroup[$group])) {
                $participantsByGroup[$group] = [];
            }
            $participantsByGroup[$group][] = $p;
        }

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/participants/groups.php';
        include __DIR__ . '/../../views/layout/footer.php';
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
        if ($numGroups < 1 || $numGroups > 26) {
            $numGroups = 8; // Safety limit
        }

        // Generate group codes (A, B, C, ...)
        $groupCodes = [];
        for ($i = 0; $i < $numGroups; $i++) {
            $groupCodes[] = chr(65 + $i); // A=65, B=66, etc.
        }

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
        } catch (Exception $e) {
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
        if ($numGroups < 1 || $numGroups > 26) {
            $numGroups = 8;
        }

        // Generate group codes
        $groupCodes = [];
        for ($i = 0; $i < $numGroups; $i++) {
            $groupCodes[] = chr(65 + $i);
        }

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
        } catch (Exception $e) {
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
        if ($numGroups < 1 || $numGroups > 26) {
            $numGroups = 8;
        }

        // Generate group codes
        $groupCodes = [];
        for ($i = 0; $i < $numGroups; $i++) {
            $groupCodes[] = chr(65 + $i);
        }

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
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['grouping_message'] = 'Error grouping by language: ' . $e->getMessage();
            $_SESSION['grouping_message_type'] = 'danger';
        }

        header('Location: /participants/groups');
        exit;
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
        } catch (Exception $e) {
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
        $query = 'SELECT id, full_name, ic_passport_no, student_id, student_email, intake, programme_name, faculty, gender, contact_no, emergency_contact_no, emergency_contact_relationship, preferred_language, group_code, checked_in_at, created_at FROM participants';
        
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
}

