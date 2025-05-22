<?php
$title = "Backup Database";
include_once '../includes/header.php';

require_once '../config/db_config.php';

// Set maximum execution time to 5 minutes for large databases
set_time_limit(300);

// Increase memory limit
ini_set('memory_limit', '512M');

// Add Bootstrap container
echo '<div class="container mt-4">';
echo '<div class="card">';
echo '<div class="card-header bg-info text-white"><h3>Database Backup</h3></div>';
echo '<div class="card-body">';

// Check if database exists
$result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
if (mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-warning">Database does not exist. Nothing to backup.</div>';
} else {
    // Select the database
    mysqli_select_db($conn, DB_NAME);
    
    // Get all tables
    $tables = array();
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    if (empty($tables)) {
        echo '<div class="alert alert-warning">No tables found in the database. Nothing to backup.</div>';
    } else {
        // Generate backup file name
        $backupFileName = DB_NAME . '_backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Set appropriate headers for file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backupFileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Disable output buffering to handle large files
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start output
        echo "-- Parking Monitoring System Database Backup\n";
        echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "-- Server: " . DB_SERVER . "\n";
        echo "-- Database: " . DB_NAME . "\n\n";
        
        // Add drop database and create database statements
        echo "DROP DATABASE IF EXISTS `" . DB_NAME . "`;\n";
        echo "CREATE DATABASE `" . DB_NAME . "`;\n";
        echo "USE `" . DB_NAME . "`;\n\n";
        
        // Process each table
        foreach ($tables as $table) {
            // Get table structure
            $result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
            $row = mysqli_fetch_row($result);
            $createTableSql = $row[1];
            
            echo "-- Table structure for table `$table`\n";
            echo "$createTableSql;\n\n";
            
            // Get table data
            $result = mysqli_query($conn, "SELECT * FROM `$table`");
            $numFields = mysqli_num_fields($result);
            $numRows = mysqli_num_rows($result);
            
            if ($numRows > 0) {
                echo "-- Dumping data for table `$table`\n";
                
                // For larger tables, process in batches of 100 rows
                $batchSize = 100;
                $rowCount = 0;
                
                while ($rowCount < $numRows) {
                    echo "INSERT INTO `$table` VALUES\n";
                    
                    $batchCount = 0;
                    while ($row = mysqli_fetch_row($result)) {
                        $rowCount++;
                        $batchCount++;
                        
                        echo "(";
                        for ($i = 0; $i < $numFields; $i++) {
                            if (isset($row[$i])) {
                                // Handle special data types
                                if (is_numeric($row[$i])) {
                                    echo $row[$i];
                                } else {
                                    // Escape special characters
                                    $value = addslashes($row[$i]);
                                    $value = str_replace("\n", "\\n", $value);
                                    echo "'" . $value . "'";
                                }
                            } else {
                                echo "NULL";
                            }
                            
                            if ($i < ($numFields - 1)) {
                                echo ", ";
                            }
                        }
                        
                        if ($batchCount < $batchSize && $rowCount < $numRows) {
                            echo "),\n";
                        } else {
                            echo ");\n";
                            break;
                        }
                    }
                    
                    if ($rowCount < $numRows) {
                        echo "\n";
                    }
                }
            }
            
            echo "\n";
        }
        
        exit;
    }
}

// Navigation buttons (only shown if we didn't output a download)
echo '<div class="mt-4">';
echo '<a href="../system_settings.php?active_tab=database" class="btn btn-primary">Back to System Settings</a>';
echo '</div>';

echo '</div>'; // card-body
echo '</div>'; // card
echo '</div>'; // container

// Close connection
mysqli_close($conn);

include_once '../includes/footer.php';
?>
