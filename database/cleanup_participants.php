<?php
/**
 * Script to remove all participants from the database
 * WARNING: This will delete ALL participants!
 * Run: php database/cleanup_participants.php
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Container;

$db = Container::get('db');

echo "WARNING: This will delete ALL participants from the database!\n";
echo "Are you sure you want to continue? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$confirmation = trim(strtolower($line));
fclose($handle);

if ($confirmation !== 'yes') {
    echo "Cancelled. No data was deleted.\n";
    exit(0);
}

try {
    $db->beginTransaction();
    
    // Count participants before deletion
    $countStmt = $db->query('SELECT COUNT(*) as count FROM participants');
    $count = $countStmt->fetch(\PDO::FETCH_ASSOC)['count'];
    
    // Delete all participants
    $db->exec('DELETE FROM participants');
    
    $db->commit();
    
    echo "Successfully deleted $count participant(s) from the database.\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
