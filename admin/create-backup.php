<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

// Configuration
$backup_dir = __DIR__ . '/../backups/';
$max_backups = 10; // Keep maximum 10 backups

// Create backups directory if it doesn't exist
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Generate backup filename
$timestamp = date('Y-m-d_H-i-s');
$filename = "backup_{$timestamp}.sql";
$filepath = $backup_dir . $filename;

try {
    // Get database configuration
    $host = '127.0.0.1'; // Update with your actual host
    $user = 'root';      // Update with your actual username
    $pass = 'root';      // Update with your actual password
    $dbname = 'resort_reservation_db';
    
    // Create backup using mysqldump (requires exec access)
    $command = "mysqldump --host={$host} --user={$user} --password={$pass} {$dbname} > {$filepath} 2>&1";
    exec($command, $output, $return_var);
    
    if ($return_var !== 0) {
        throw new Exception("Backup failed: " . implode("\n", $output));
    }
    
    // Verify backup was created
    if (!file_exists($filepath) || filesize($filepath) === 0) {
        throw new Exception("Backup file was not created or is empty");
    }
    
    // Clean up old backups
    $backup_files = glob($backup_dir . 'backup_*.sql');
    if (count($backup_files) > $max_backups) {
        // Sort by modification time (oldest first)
        usort($backup_files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Remove oldest backups
        $to_delete = count($backup_files) - $max_backups;
        for ($i = 0; $i < $to_delete; $i++) {
            unlink($backup_files[$i]);
        }
    }
    
    // Log the backup action
    logAction($_SESSION['user_id'], 'CREATE_BACKUP', 'system', null, json_encode([
        'filename' => $filename,
        'size' => filesize($filepath),
        'backup_count' => count(glob($backup_dir . 'backup_*.sql'))
    ]));
    
    $_SESSION['success'] = "Database backup created successfully: {$filename}";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Backup failed: " . $e->getMessage();
}

header("Location: audit-logs.php");
exit();

// Alternative: Manual backup using PHP (if mysqldump is not available)
function createManualBackup() {
    global $pdo, $backup_dir;
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "manual_backup_{$timestamp}.sql";
    $filepath = $backup_dir . $filename;
    
    $backup_content = "-- Resort Reservation System Database Backup\n";
    $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup_content .= "-- Database: resort_reservation_db\n\n";
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Add table structure
        $backup_content .= "\n-- Table structure for table `{$table}`\n";
        $backup_content .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        $create_table = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
        $backup_content .= $create_table['Create Table'] . ";\n\n";
        
        // Add table data
        $backup_content .= "-- Dumping data for table `{$table}`\n";
        
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $backup_content .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";
            
            $values = [];
            foreach ($rows as $row) {
                $escaped_values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    return $pdo->quote($value);
                }, $row);
                
                $values[] = "(" . implode(', ', $escaped_values) . ")";
            }
            
            $backup_content .= implode(",\n", $values) . ";\n";
        }
        
        $backup_content .= "\n";
    }
    
    // Write to file
    if (file_put_contents($filepath, $backup_content)) {
        return [
            'success' => true,
            'filename' => $filename,
            'size' => filesize($filepath)
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to write backup file'];
}
?>