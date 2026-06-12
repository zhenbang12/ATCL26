<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;
use PDO;

class BackupController
{
    private function requireAuth(): void
    {
        Auth::requireRole(['advisor', 'committee']);
    }

    public function showPage(): void
    {
        $this->requireAuth();
        
        $title = 'Backup & Restore';
        $message = $_SESSION['backup_message'] ?? null;
        $messageType = $_SESSION['backup_message_type'] ?? 'info';
        if (isset($_SESSION['backup_message'])) {
            unset($_SESSION['backup_message'], $_SESSION['backup_message_type']);
        }

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/settings/backup.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function runBackup(): void
    {
        $this->requireAuth();

        try {
            $db = Container::get('db');
            $sql = $this->generateBackupSql($db);

            $filename = 'backup_' . date('Y-m-d_His') . '.sql';

            // Send headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($sql));
            header('Pragma: no-cache');
            header('Expires: 0');

            echo $sql;
            exit;
        } catch (\Exception $e) {
            $_SESSION['backup_message'] = 'Backup failed: ' . $e->getMessage();
            $_SESSION['backup_message_type'] = 'danger';
            header('Location: /settings/backup');
            exit;
        }
    }

    public function runRestore(): void
    {
        $this->requireAuth();

        if (empty($_FILES['backup_file']['tmp_name'])) {
            $_SESSION['backup_message'] = 'Please upload a backup SQL file.';
            $_SESSION['backup_message_type'] = 'danger';
            header('Location: /settings/backup');
            exit;
        }

        $filePath = $_FILES['backup_file']['tmp_name'];
        $sql = file_get_contents($filePath);

        if ($sql === false || trim($sql) === '') {
            $_SESSION['backup_message'] = 'The uploaded backup file is empty or invalid.';
            $_SESSION['backup_message_type'] = 'danger';
            header('Location: /settings/backup');
            exit;
        }

        try {
            $db = Container::get('db');
            
            // Execute the restore logic
            $this->executeRestoreSql($db, $sql);

            $_SESSION['backup_message'] = 'Database restored successfully!';
            $_SESSION['backup_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['backup_message'] = 'Restore failed: ' . $e->getMessage();
            $_SESSION['backup_message_type'] = 'danger';
        }

        header('Location: /settings/backup');
        exit;
    }

    private function generateBackupSql(PDO $db): string
    {
        $sqlDump = "-- Database Backup\n";
        $sqlDump .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        // Get list of tables
        $tablesStmt = $db->query("SHOW TABLES");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Drop table statement
            $sqlDump .= "DROP TABLE IF EXISTS `" . $table . "`;\n";

            // Create table statement
            $createStmt = $db->query("SHOW CREATE TABLE `" . $table . "`");
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            $sqlDump .= $createRow[1] . ";\n\n";

            // Insert data
            $dataStmt = $db->query("SELECT * FROM `" . $table . "`");
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $sqlDump .= "INSERT INTO `" . $table . "` VALUES \n";
                $inserts = [];
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = $db->quote((string)$val);
                        }
                    }
                    $inserts[] = "(" . implode(',', $values) . ")";
                }
                $sqlDump .= implode(",\n", $inserts) . ";\n\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $sqlDump;
    }

    private function executeRestoreSql(PDO $db, string $sql): void
    {
        try {
            // Disable foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Execute the entire SQL script
            $db->exec($sql);

            // Re-enable foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
