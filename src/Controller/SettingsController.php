<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Container;

class SettingsController
{
    private const LANDING_SLOTS = [
        'hero' => 'Top banner — wide image below the main title',
        'feature_1' => 'Mid page — shown before “What to expect”',
        'feature_2' => 'Lower — shown before “Before you arrive”',
    ];

    private const MAX_BYTES = 5 * 1024 * 1024;

    /** @var array<string, string> */
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function landingPage(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Landing page images';
        $db = Container::get('db');
        $defaults = [];
        foreach (array_keys(self::LANDING_SLOTS) as $slot) {
            $defaults[$slot] = ['filename' => null, 'alt_text' => ''];
        }
        $rows = array_merge($defaults, self::loadLandingImages($db));
        $settings = self::loadLandingSettings($db);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/settings/landing.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function landingPageSave(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $uploadDir = $this->landingUploadDir();
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                $_SESSION['settings_message'] = 'Could not create upload folder.';
                $_SESSION['settings_message_type'] = 'danger';
                header('Location: /settings/landing');
                exit;
            }
        }

        try {
            foreach (array_keys(self::LANDING_SLOTS) as $slot) {
                $alt = trim((string)($_POST['alt_' . $slot] ?? ''));
                if (mb_strlen($alt) > 500) {
                    $alt = mb_substr($alt, 0, 500);
                }

                $stmt = $db->prepare('SELECT filename FROM landing_images WHERE slot = ?');
                $stmt->execute([$slot]);
                $current = $stmt->fetch(\PDO::FETCH_ASSOC);
                $oldFile = $current ? (string)($current['filename'] ?? '') : '';

                $remove = isset($_POST['remove_' . $slot]);
                $fileKey = 'file_' . $slot;

                if ($remove) {
                    $this->safeUnlink($uploadDir, $oldFile);
                    $upd = $db->prepare('UPDATE landing_images SET filename = NULL, alt_text = ? WHERE slot = ?');
                    $upd->execute([$alt, $slot]);
                    continue;
                }

                if (!empty($_FILES[$fileKey]['tmp_name']) && is_uploaded_file($_FILES[$fileKey]['tmp_name'])) {
                    $newName = $this->storeUploadedImage($_FILES[$fileKey], $uploadDir);
                    if ($newName === null) {
                        $_SESSION['settings_message'] = 'Invalid or unsupported image for slot: ' . $slot;
                        $_SESSION['settings_message_type'] = 'danger';
                        header('Location: /settings/landing');
                        exit;
                    }
                    $this->safeUnlink($uploadDir, $oldFile);
                    $upd = $db->prepare('UPDATE landing_images SET filename = ?, alt_text = ? WHERE slot = ?');
                    $upd->execute([$newName, $alt, $slot]);
                    continue;
                }

                $upd = $db->prepare('UPDATE landing_images SET alt_text = ? WHERE slot = ?');
                $upd->execute([$alt, $slot]);
            }

            // Handle logos and background color settings
            $logo1Alt = trim((string)($_POST['logo_1_alt'] ?? ''));
            if (mb_strlen($logo1Alt) > 500) {
                $logo1Alt = mb_substr($logo1Alt, 0, 500);
            }

            $logo2Alt = trim((string)($_POST['logo_2_alt'] ?? ''));
            if (mb_strlen($logo2Alt) > 500) {
                $logo2Alt = mb_substr($logo2Alt, 0, 500);
            }

            $logo3Alt = trim((string)($_POST['logo_3_alt'] ?? ''));
            if (mb_strlen($logo3Alt) > 500) {
                $logo3Alt = mb_substr($logo3Alt, 0, 500);
            }

            $backgroundColor = trim((string)($_POST['background_color'] ?? '#ffffff'));
            // Validate hex color
            if (!preg_match('/^#[a-fA-F0-9]{6}$/', $backgroundColor)) {
                $backgroundColor = '#ffffff';
            }

            $mainTitle = trim((string)($_POST['main_title'] ?? 'Welcome to Adjustment To Campus Life'));
            if (mb_strlen($mainTitle) > 255) {
                $mainTitle = mb_substr($mainTitle, 0, 255);
            }

            $mainCaption = trim((string)($_POST['main_caption'] ?? ''));
            $section1Title = trim((string)($_POST['section_1_title'] ?? 'What is it?'));
            if (mb_strlen($section1Title) > 255) {
                $section1Title = mb_substr($section1Title, 0, 255);
            }
            $section1Caption = trim((string)($_POST['section_1_caption'] ?? ''));
            $section2Title = trim((string)($_POST['section_2_title'] ?? 'What to expect'));
            if (mb_strlen($section2Title) > 255) {
                $section2Title = mb_substr($section2Title, 0, 255);
            }
            $section2Caption = trim((string)($_POST['section_2_caption'] ?? ''));
            $section3Title = trim((string)($_POST['section_3_title'] ?? 'Before you arrive'));
            if (mb_strlen($section3Title) > 255) {
                $section3Title = mb_substr($section3Title, 0, 255);
            }
            $section3Caption = trim((string)($_POST['section_3_caption'] ?? ''));

            $stmt = $db->prepare('SELECT logo_1_filename, logo_2_filename, logo_3_filename FROM landing_settings LIMIT 1');
            $stmt->execute();
            $current = $stmt->fetch(\PDO::FETCH_ASSOC);
            $oldLogo1File = $current ? (string)($current['logo_1_filename'] ?? '') : '';
            $oldLogo2File = $current ? (string)($current['logo_2_filename'] ?? '') : '';
            $oldLogo3File = $current ? (string)($current['logo_3_filename'] ?? '') : '';

            $removeLogo1 = isset($_POST['remove_logo_1']);
            $removeLogo2 = isset($_POST['remove_logo_2']);
            $removeLogo3 = isset($_POST['remove_logo_3']);

            $logo1Name = $oldLogo1File !== '' ? $oldLogo1File : null;
            $logo2Name = $oldLogo2File !== '' ? $oldLogo2File : null;
            $logo3Name = $oldLogo3File !== '' ? $oldLogo3File : null;

            // Handle logo 1
            if ($removeLogo1) {
                $this->safeUnlink($uploadDir, $oldLogo1File);
                $logo1Name = null;
            } elseif (!empty($_FILES['logo_1_file']['tmp_name']) && is_uploaded_file($_FILES['logo_1_file']['tmp_name'])) {
                $logo1Name = $this->storeUploadedImage($_FILES['logo_1_file'], $uploadDir);
                if ($logo1Name === null) {
                    $_SESSION['settings_message'] = 'Invalid or unsupported logo 1 image.';
                    $_SESSION['settings_message_type'] = 'danger';
                    header('Location: /settings/landing');
                    exit;
                }
                $this->safeUnlink($uploadDir, $oldLogo1File);
            }

            // Handle logo 2
            if ($removeLogo2) {
                $this->safeUnlink($uploadDir, $oldLogo2File);
                $logo2Name = null;
            } elseif (!empty($_FILES['logo_2_file']['tmp_name']) && is_uploaded_file($_FILES['logo_2_file']['tmp_name'])) {
                $logo2Name = $this->storeUploadedImage($_FILES['logo_2_file'], $uploadDir);
                if ($logo2Name === null) {
                    $_SESSION['settings_message'] = 'Invalid or unsupported logo 2 image.';
                    $_SESSION['settings_message_type'] = 'danger';
                    header('Location: /settings/landing');
                    exit;
                }
                $this->safeUnlink($uploadDir, $oldLogo2File);
            }

            // Handle logo 3
            if ($removeLogo3) {
                $this->safeUnlink($uploadDir, $oldLogo3File);
                $logo3Name = null;
            } elseif (!empty($_FILES['logo_3_file']['tmp_name']) && is_uploaded_file($_FILES['logo_3_file']['tmp_name'])) {
                $logo3Name = $this->storeUploadedImage($_FILES['logo_3_file'], $uploadDir);
                if ($logo3Name === null) {
                    $_SESSION['settings_message'] = 'Invalid or unsupported logo 3 image.';
                    $_SESSION['settings_message_type'] = 'danger';
                    header('Location: /settings/landing');
                    exit;
                }
                $this->safeUnlink($uploadDir, $oldLogo3File);
            }

            $upd = $db->prepare('UPDATE landing_settings SET logo_1_filename = ?, logo_1_alt_text = ?, logo_2_filename = ?, logo_2_alt_text = ?, logo_3_filename = ?, logo_3_alt_text = ?, background_color = ?, main_title = ?, main_caption = ?, section_1_title = ?, section_1_caption = ?, section_2_title = ?, section_2_caption = ?, section_3_title = ?, section_3_caption = ?');
            $upd->execute([$logo1Name, $logo1Alt, $logo2Name, $logo2Alt, $logo3Name, $logo3Alt, $backgroundColor, $mainTitle, $mainCaption, $section1Title, $section1Caption, $section2Title, $section2Caption, $section3Title, $section3Caption]);

            $_SESSION['settings_message'] = 'Landing page settings saved.';
            $_SESSION['settings_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['settings_message'] = 'Could not save. Run migrations if needed: ' . $e->getMessage();
            $_SESSION['settings_message_type'] = 'danger';
        }

        header('Location: /settings/landing');
        exit;
    }

    public function registrationSettingsSave(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $preRegisterEnabled = isset($_POST['pre_register_enabled']) ? 1 : 0;
        $walkInEnabled = isset($_POST['walk_in_enabled']) ? 1 : 0;

        try {
            $db->exec('CREATE TABLE IF NOT EXISTS registration_settings (
                id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
                pre_register_enabled TINYINT(1) NOT NULL DEFAULT 1,
                walk_in_enabled TINYINT(1) NOT NULL DEFAULT 1,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )');

            $stmt = $db->prepare('
                INSERT INTO registration_settings (id, pre_register_enabled, walk_in_enabled)
                VALUES (1, ?, ?)
                ON DUPLICATE KEY UPDATE
                    pre_register_enabled = VALUES(pre_register_enabled),
                    walk_in_enabled = VALUES(walk_in_enabled)
            ');
            $stmt->execute([$preRegisterEnabled, $walkInEnabled]);

            $_SESSION['registration_settings_message'] = 'Registration settings saved.';
            $_SESSION['registration_settings_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['registration_settings_message'] = 'Could not save registration settings: ' . $e->getMessage();
            $_SESSION['registration_settings_message_type'] = 'danger';
        }

        header('Location: /dashboard');
        exit;
    }

    /**
     * @return array<string, array{filename: ?string, alt_text: string}>
     */
    public static function loadLandingImages(\PDO $db): array
    {
        $out = [];
        try {
            $stmt = $db->query('
                SELECT slot, filename, alt_text
                FROM landing_images
                ORDER BY FIELD(slot, \'hero\', \'feature_1\', \'feature_2\')
            ');
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $slot = (string)$row['slot'];
                $out[$slot] = [
                    'filename' => $row['filename'] !== null && $row['filename'] !== '' ? (string)$row['filename'] : null,
                    'alt_text' => (string)($row['alt_text'] ?? ''),
                ];
            }
        } catch (\Exception $e) {
            return [];
        }

        return $out;
    }

    private function landingUploadDir(): string
    {
        return dirname(__DIR__, 2) . '/uploads/landing';
    }

    private function safeUnlink(string $dir, string $filename): void
    {
        if ($filename === '' || strpbrk($filename, '/\\') !== false) {
            return;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @param array<string, mixed> $file $_FILES entry
     */
    private function storeUploadedImage(array $file, string $uploadDir): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            return null;
        }

        $tmp = (string)$file['tmp_name'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if ($mime === false || !isset(self::ALLOWED_MIME[$mime])) {
            return null;
        }

        $ext = self::ALLOWED_MIME[$mime];
        $basename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $basename;
        if (!move_uploaded_file($tmp, $dest)) {
            return null;
        }

        return $basename;
    }

    /**
     * @return array<string, string>
     */
    public static function landingSlotLabels(): array
    {
        return self::LANDING_SLOTS;
    }

    /**
     * @return array{pre_register_enabled: bool, walk_in_enabled: bool}
     */
    public static function loadRegistrationSettings(\PDO $db): array
    {
        try {
            $stmt = $db->query('SELECT pre_register_enabled, walk_in_enabled FROM registration_settings WHERE id = 1');
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                return [
                    'pre_register_enabled' => (bool)$row['pre_register_enabled'],
                    'walk_in_enabled' => (bool)$row['walk_in_enabled'],
                ];
            }
        } catch (\Exception $e) {
            // Table might not exist yet; keep both registration paths open.
        }

        return [
            'pre_register_enabled' => true,
            'walk_in_enabled' => true,
        ];
    }

    /**
     * @return array{logo_1_filename: ?string, logo_1_alt_text: string, logo_2_filename: ?string, logo_2_alt_text: string, logo_3_filename: ?string, logo_3_alt_text: string, background_color: string, main_title: string, main_caption: string, section_1_title: string, section_1_caption: string, section_2_title: string, section_2_caption: string, section_3_title: string, section_3_caption: string}
     */
    public static function loadLandingSettings(\PDO $db): array
    {
        try {
            $stmt = $db->query('SELECT logo_1_filename, logo_1_alt_text, logo_2_filename, logo_2_alt_text, logo_3_filename, logo_3_alt_text, background_color, main_title, main_caption, section_1_title, section_1_caption, section_2_title, section_2_caption, section_3_title, section_3_caption FROM landing_settings LIMIT 1');
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                return [
                    'logo_1_filename' => $row['logo_1_filename'] !== null && $row['logo_1_filename'] !== '' ? (string)$row['logo_1_filename'] : null,
                    'logo_1_alt_text' => (string)($row['logo_1_alt_text'] ?? ''),
                    'logo_2_filename' => $row['logo_2_filename'] !== null && $row['logo_2_filename'] !== '' ? (string)$row['logo_2_filename'] : null,
                    'logo_2_alt_text' => (string)($row['logo_2_alt_text'] ?? ''),
                    'logo_3_filename' => $row['logo_3_filename'] !== null && $row['logo_3_filename'] !== '' ? (string)$row['logo_3_filename'] : null,
                    'logo_3_alt_text' => (string)($row['logo_3_alt_text'] ?? ''),
                    'background_color' => (string)($row['background_color'] ?? '#ffffff'),
                    'main_title' => (string)($row['main_title'] ?? 'Welcome to Adjustment To Campus Life'),
                    'main_caption' => (string)($row['main_caption'] ?? 'A few days of games, teamwork, and community—built for TAR UMT students to connect, learn, and make memories together.'),
                    'section_1_title' => (string)($row['section_1_title'] ?? 'What is it?'),
                    'section_1_caption' => (string)($row['section_1_caption'] ?? 'ATCL is our annual camp-style programme. You will join a small group, take part in station games and activities, and get to know facilitators and participants from across programmes. It is run by student leaders and advisors with safety and inclusion in mind.'),
                    'section_2_title' => (string)($row['section_2_title'] ?? 'What to expect'),
                    'section_2_caption' => (string)($row['section_2_caption'] ?? 'Icebreakers and group challenges across the event. Meals, briefings, and evening segments with your group. Check-in on arrival using the QR code you receive after registering. Language-friendly grouping so you can participate comfortably.'),
                    'section_3_title' => (string)($row['section_3_title'] ?? 'Before you arrive'),
                    'section_3_caption' => (string)($row['section_3_caption'] ?? 'Pre-register with your student details so we can prepare your QR for check-in and place you in a group when you arrive. If you already registered, you can retrieve your QR any time.'),
                ];
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }

        return [
            'logo_1_filename' => null,
            'logo_1_alt_text' => '',
            'logo_2_filename' => null,
            'logo_2_alt_text' => '',
            'logo_3_filename' => null,
            'logo_3_alt_text' => '',
            'background_color' => '#ffffff',
            'main_title' => 'Welcome to Adjustment To Campus Life',
            'main_caption' => 'A few days of games, teamwork, and community—built for TAR UMT students to connect, learn, and make memories together.',
            'section_1_title' => 'What is it?',
            'section_1_caption' => 'ATCL is our annual camp-style programme. You will join a small group, take part in station games and activities, and get to know facilitators and participants from across programmes. It is run by student leaders and advisors with safety and inclusion in mind.',
            'section_2_title' => 'What to expect',
            'section_2_caption' => 'Icebreakers and group challenges across the event. Meals, briefings, and evening segments with your group. Check-in on arrival using the QR code you receive after registering. Language-friendly grouping so you can participate comfortably.',
            'section_3_title' => 'Before you arrive',
            'section_3_caption' => 'Pre-register with your student details so we can prepare your QR for check-in and place you in a group when you arrive. If you already registered, you can retrieve your QR any time.',
        ];
    }
}
