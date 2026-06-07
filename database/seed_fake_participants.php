<?php
/**
 * Script to generate 150 fake participants for testing
 * Run: php database/seed_fake_participants.php
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Container;

$db = Container::get('db');

// Malaysian first names (mix of Chinese, Malay, Indian)
$firstNames = [
    'Ahmad', 'Muhammad', 'Siti', 'Nur', 'Nurul', 'Fatimah', 'Hassan', 'Ibrahim',
    'Tan', 'Lee', 'Lim', 'Wong', 'Ng', 'Chong', 'Ooi', 'Teoh', 'Goh', 'Koh',
    'Kumar', 'Raj', 'Priya', 'Devi', 'Anand', 'Vikram', 'Saravanan',
    'Wei', 'Ming', 'Ying', 'Jun', 'Hui', 'Ling', 'Xin', 'Jia', 'Yan',
    'Amir', 'Zain', 'Aisyah', 'Aminah', 'Hakim', 'Rashid', 'Farid',
    'David', 'Sarah', 'John', 'Emily', 'Michael', 'Jessica'
];

// Malaysian last names
$lastNames = [
    'bin Abdullah', 'bin Ahmad', 'bin Hassan', 'binti Ali', 'binti Rahman',
    'Tan', 'Lee', 'Lim', 'Wong', 'Ng', 'Chong', 'Ooi', 'Teoh', 'Goh', 'Koh',
    'Kumar', 'Raj', 'Devi', 'Anand', 'Vikram', 'Saravanan',
    'Chen', 'Zhang', 'Liu', 'Wang', 'Yang', 'Huang',
    'Abdullah', 'Rahman', 'Ibrahim', 'Hassan', 'Ali', 'Mohamed',
    'Smith', 'Johnson', 'Williams', 'Brown', 'Jones'
];

// Programme names
$programmes = [
    'Diploma in Computer Science',
    'Diploma in Business Administration',
    'Diploma in Accounting',
    'Diploma in Engineering',
    'Diploma in Mass Communication',
    'Foundation in Science',
    'Foundation in Arts',
    'Foundation in Business',
    'Diploma in Information Technology',
    'Diploma in Finance',
    'Diploma in Marketing'
];

// Faculty codes
$faculties = ['FAFB', 'FOAS', 'FOCS', 'FOBE', 'FOET', 'FCCI', 'FSSH', 'CPUS'];

// Intake options
$intakes = ['Diploma new intake', 'Foundation new intake', 'Degree from other campus'];

// Genders
$genders = ['Male', 'Female'];

// Emergency relationships
$relationships = ['Parents', 'Spouse'];

// Preferred languages
$languages = ['Mandarin-speaking Group', 'English-speaking Group'];

function generateIC(): string {
    // Malaysian IC format: YYMMDD-PB-GGGG
    // For simplicity, generate 12 digits
    $year = rand(0, 5); // 00-05 (2000-2005)
    $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
    $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
    $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    return $year . $month . $day . $random;
}

function generateStudentID(): string {
    $year = rand(20, 26); // 20-26
    $letters = ['WMR', 'WMS', 'WMT', 'WMU', 'WMV', 'WMX', 'WMY', 'WMZ'];
    $number = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return $year . $letters[array_rand($letters)] . $number;
}

function generateEmail(string $name): string {
    $nameParts = explode(' ', strtolower($name));
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? substr($nameParts[1], 0, 2) : 'xx';
    $random = rand(10, 99);
    return $firstName . $lastName . '-wm' . $random . '@student.tarc.edu.my';
}

function generatePhone(): string {
    $prefixes = ['010', '011', '012', '013', '014', '015', '016', '017', '018', '019'];
    $prefix = $prefixes[array_rand($prefixes)];
    $number = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
    return $prefix . $number;
}

echo "Generating 150 fake participants...\n";

$db->beginTransaction();

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
        qr_code,
        group_code,
        checked_in_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    for ($i = 1; $i <= 150; $i++) {
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $fullName = $firstName . ' ' . $lastName;
        
        $ic = generateIC();
        $studentId = generateStudentID();
        $email = generateEmail($fullName);
        $intake = $intakes[array_rand($intakes)];
        $programme = $programmes[array_rand($programmes)];
        $faculty = $faculties[array_rand($faculties)];
        $gender = $genders[array_rand($genders)];
        $contactNo = generatePhone();
        $emergencyContact = generatePhone();
        $relationship = $relationships[array_rand($relationships)];
        $language = $languages[array_rand($languages)];
        $qrCode = bin2hex(random_bytes(8));
        
        // No grouping data
        $groupCode = null;
        
        // 30% chance of being checked in
        $checkedIn = (rand(1, 10) <= 3) ? date('Y-m-d H:i:s', time() - rand(0, 86400 * 7)) : null;
        
        $stmt->execute([
            $fullName,
            $ic,
            $studentId,
            $email,
            $intake,
            $programme,
            $faculty,
            $gender,
            $contactNo,
            $emergencyContact,
            $relationship,
            $language,
            $qrCode,
            $groupCode,
            $checkedIn
        ]);
        
        if ($i % 25 == 0) {
            echo "Generated $i participants...\n";
        }
    }
    
    $db->commit();
    echo "Successfully generated 150 fake participants!\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
