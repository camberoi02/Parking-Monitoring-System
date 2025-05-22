<?php
// Include database configuration
require_once "config/db_config.php";

// Select database
mysqli_select_db($conn, DB_NAME);

// Create a function to log results to a file since terminal output might not work
function log_message($message) {
    echo $message . "\n";
    file_put_contents('user_fix_log.txt', $message . "\n", FILE_APPEND);
}

// Start with a fresh log file
file_put_contents('user_fix_log.txt', "USER FIX LOG - " . date('Y-m-d H:i:s') . "\n\n");

log_message("Starting user account fix process...");

// Check if the 'cash' user exists
$result = mysqli_query($conn, "SELECT * FROM users WHERE username = 'cash'");
if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    log_message("Found 'cash' user with ID: " . $user['id'] . ", Role: " . $user['role']);
    
    // Test the current password
    $test_password = 'cash123';
    if (password_verify($test_password, $user['password'])) {
        log_message("Current password 'cash123' is already working correctly");
    } else {
        log_message("Current password 'cash123' is NOT working - will reset it now");
        
        // Reset the password
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = ? WHERE username = 'cash'";
        
        if ($stmt = mysqli_prepare($conn, $update_sql)) {
            mysqli_stmt_bind_param($stmt, "s", $new_hash);
            
            if (mysqli_stmt_execute($stmt)) {
                log_message("SUCCESS: Password for 'cash' user has been reset");
                log_message("New password hash: " . $new_hash);
            } else {
                log_message("ERROR: Failed to reset password: " . mysqli_error($conn));
            }
            
            mysqli_stmt_close($stmt);
        } else {
            log_message("ERROR: Failed to prepare statement: " . mysqli_error($conn));
        }
    }
} else {
    log_message("'cash' user does not exist - creating it now");
    
    // Create the 'cash' user
    $username = 'cash';
    $password = 'cash123';
    $role = 'operator';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $insert_sql)) {
        mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            log_message("SUCCESS: 'cash' user created with ID: " . $user_id);
            log_message("Username: cash");
            log_message("Password: cash123");
            log_message("Role: operator");
            log_message("Hash: " . $hash);
        } else {
            log_message("ERROR: Failed to create 'cash' user: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
    } else {
        log_message("ERROR: Failed to prepare statement: " . mysqli_error($conn));
    }
}

// Also create a test 'cashier' user to verify login functionality
$result = mysqli_query($conn, "SELECT * FROM users WHERE username = 'cashier'");
if (mysqli_num_rows($result) > 0) {
    log_message("'cashier' test user already exists - will update password");
    
    $new_hash = password_hash('cashier123', PASSWORD_DEFAULT);
    $update_sql = "UPDATE users SET password = ? WHERE username = 'cashier'";
    
    if ($stmt = mysqli_prepare($conn, $update_sql)) {
        mysqli_stmt_bind_param($stmt, "s", $new_hash);
        
        if (mysqli_stmt_execute($stmt)) {
            log_message("SUCCESS: Password for 'cashier' user has been reset");
        } else {
            log_message("ERROR: Failed to reset 'cashier' password: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
    }
} else {
    log_message("Creating 'cashier' test user");
    
    $username = 'cashier';
    $password = 'cashier123';
    $role = 'operator';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $insert_sql)) {
        mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            log_message("SUCCESS: 'cashier' test user created with ID: " . $user_id);
        } else {
            log_message("ERROR: Failed to create 'cashier' test user: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
    }
}

// List all users for reference
$result = mysqli_query($conn, "SELECT id, username, role FROM users ORDER BY id");
log_message("\nAll users in the system:");
log_message("---------------------");
while ($row = mysqli_fetch_assoc($result)) {
    log_message("ID: " . $row['id'] . ", Username: " . $row['username'] . ", Role: " . $row['role']);
}

log_message("\nLOGIN INSTRUCTIONS:");
log_message("-------------------");
log_message("You can now log in with either of these accounts:");
log_message("1. Username: cash, Password: cash123, Role: operator");
log_message("2. Username: cashier, Password: cashier123, Role: operator");
log_message("\nProcess complete!");

// Close the database connection
mysqli_close($conn);

// Output file location
echo "\nCheck the log file for details: " . __DIR__ . "/user_fix_log.txt";
?> 