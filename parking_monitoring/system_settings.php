<?php
// Include authentication check
include_once 'includes/auth_session.php';

$title = "System Settings";
include_once 'includes/header.php';
include_once 'includes/navigation.php';
require_once 'config/db_config.php';

// Check if user is admin
$isAdmin = false;
if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin') {
    $isAdmin = true;
}

if (!$isAdmin) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Access denied. Admin privileges required.</div></div>";
    include_once 'includes/footer.php';
    exit;
}

// Check if database exists first
$database_exists = false;
$result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
if (mysqli_num_rows($result) > 0) {
    $database_exists = true;
}

// Get current fee structure from database
$hourly_rate = 50.00; // Default value
$base_fee = 50.00; // Default value - for first 3 hours
$base_hours = 3; // Fixed value - how many hours the base fee covers

if (function_exists('getHourlyRate')) {
    $hourly_rate = getHourlyRate($conn);
}

if (function_exists('getBaseFee')) {
    $base_fee = getBaseFee($conn);
}

// Get the base hours configuration
$base_hours = getBaseHours($conn);

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_settings':
                if ($database_exists) {
                    $hourly_rate = mysqli_real_escape_string($conn, $_POST['hourly_rate']);
                    $system_name = mysqli_real_escape_string($conn, $_POST['system_name']);
                    
                    // Get old values for audit log
                    $old_hourly_rate = '';
                    $old_system_name = '';
                    
                    $result = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'hourly_rate'");
                    if ($result && $row = mysqli_fetch_assoc($result)) {
                        $old_hourly_rate = $row['setting_value'];
                    }
                    
                    $result = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'system_name'");
                    if ($result && $row = mysqli_fetch_assoc($result)) {
                        $old_system_name = $row['setting_value'];
                    }
                    
                    // Update hourly rate
                    $sql = "INSERT INTO settings (setting_key, setting_value) 
                            VALUES ('hourly_rate', '$hourly_rate') 
                            ON DUPLICATE KEY UPDATE setting_value = '$hourly_rate'";
                    
                    if (mysqli_query($conn, $sql)) {
                        // Log to audit trail - be more specific about what changed
                        if ($old_hourly_rate != $hourly_rate) {
                            logAudit($conn, 'update', 'settings', null, 'hourly_rate', $old_hourly_rate, $hourly_rate);
                            // Remove the IP address collection
                            logAudit($conn, 'update', 'settings', null, 'system_configuration', 
                                    "Hourly rate: $old_hourly_rate", 
                                    "Hourly rate: $hourly_rate (Changed by: Admin)");
                        }
                        
                        $message = "Settings updated successfully.";
                    } else {
                        $error = "Error updating settings: " . mysqli_error($conn);
                    }
                    
                    // Update system name
                    $sql = "INSERT INTO settings (setting_key, setting_value) 
                            VALUES ('system_name', '$system_name') 
                            ON DUPLICATE KEY UPDATE setting_value = '$system_name'";
                            
                    if (mysqli_query($conn, $sql)) {
                        // Log to audit trail with more details
                        if ($old_system_name != $system_name) {
                            logAudit($conn, 'update', 'settings', null, 'system_name', $old_system_name, $system_name);
                            // Remove the IP address collection
                            logAudit($conn, 'update', 'settings', null, 'system_configuration', 
                                    "System name: $old_system_name", 
                                    "System name: $system_name (Changed by: Admin)");
                        }
                    } else {
                        $error = "Error updating settings: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Database does not exist. Cannot update settings.";
                }
                break;
                
            case 'update_system_name':
                $system_name = mysqli_real_escape_string($conn, $_POST['system_name']);
                
                if ($database_exists) {
                    // Check if settings table exists
                    mysqli_select_db($conn, DB_NAME);
                    $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
                    if (mysqli_num_rows($result) == 0) {
                        // Create settings table if it doesn't exist
                        $sql = "CREATE TABLE settings (
                            setting_key VARCHAR(50) PRIMARY KEY,
                            setting_value VARCHAR(255) NOT NULL,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )";
                        mysqli_query($conn, $sql);
                    }
                    
                    // Save system name to settings
                    $sql = "INSERT INTO settings (setting_key, setting_value) 
                            VALUES ('system_name', '$system_name') 
                            ON DUPLICATE KEY UPDATE setting_value = '$system_name'";
                    if (mysqli_query($conn, $sql)) {
                        // Log to audit trail with more details
                        if ($old_system_name != $system_name) {
                            $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                            logAudit($conn, 'update', 'settings', null, 'system_configuration', 
                                    "System name: $old_system_name", 
                                    "System name: $system_name (Changed by: Admin)");
                        }
                        
                        $message = "System name updated successfully to: $system_name";
                    } else {
                        $error = "Error updating system name: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Database does not exist. Cannot update system name.";
                }
                break;
                
            case 'update_parking_rate':
                $base_fee = floatval($_POST['base_fee']);
                $hourly_rate = floatval($_POST['hourly_rate']);
                $base_hours = intval($_POST['base_hours']);
                
                // Get old values for audit log
                $old_base_fee = '';
                $old_hourly_rate = '';
                $old_base_hours = '';
                
                $result = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'base_fee'");
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $old_base_fee = $row['setting_value'];
                }
                
                $result = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'hourly_rate'");
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $old_hourly_rate = $row['setting_value'];
                }
                
                $result = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'base_hours'");
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $old_base_hours = $row['setting_value'];
                }
                
                // Update base hours
                updateBaseHours($conn, $base_hours);
                
                // Save to database using the function from db_config.php
                if (function_exists('updateParkingRates') && updateParkingRates($conn, $base_fee, $hourly_rate)) {
                    // Log to audit trail with improved details
                    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $changes = [];
                    
                    if ($old_base_fee != $base_fee) {
                        logAudit($conn, 'update', 'settings', null, 'base_fee', $old_base_fee, $base_fee);
                        $changes[] = "Base fee: ₱$old_base_fee → ₱$base_fee";
                    }
                    
                    if ($old_hourly_rate != $hourly_rate) {
                        logAudit($conn, 'update', 'settings', null, 'hourly_rate', $old_hourly_rate, $hourly_rate);
                        $changes[] = "Hourly rate: ₱$old_hourly_rate → ₱$hourly_rate";
                    }
                    
                    if ($old_base_hours != $base_hours) {
                        logAudit($conn, 'update', 'settings', null, 'base_hours', $old_base_hours, $base_hours);
                        $changes[] = "Base hours: $old_base_hours → $base_hours";
                    }
                    
                    // Consolidated audit log entry for all parking rate changes
                    if (!empty($changes)) {
                        logAudit($conn, 'update', 'settings', null, 'parking_rates', 
                                "Previous rates configuration", 
                                "Updated rates: " . implode(", ", $changes) . " (Changed by: Admin)");
                    }
                    
                    $message = "Parking rates updated successfully. Base fee: ₱" . number_format($base_fee, 2) . 
                              " for first $base_hours hours, Additional hours: ₱" . number_format($hourly_rate, 2) . " per hour";
                } else {
                    $error = "Error updating parking rates";
                }
                break;
                
            case 'add_user':
                $username = mysqli_real_escape_string($conn, $_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = mysqli_real_escape_string($conn, $_POST['role']);
                
                if ($database_exists) {
                    mysqli_select_db($conn, DB_NAME);
                    
                    // Check if username already exists
                    $check_sql = "SELECT id FROM users WHERE username = '$username'";
                    $result = mysqli_query($conn, $check_sql);
                    
                    if (mysqli_num_rows($result) > 0) {
                        $error = "Username '$username' already exists. Please choose a different username.";
                    } else {
                    $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
                    if (mysqli_query($conn, $sql)) {
                        $user_id = mysqli_insert_id($conn);
                        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        logAudit($conn, 'insert', 'users', $user_id, null, null, "User $username added");
                        logAudit($conn, 'insert', 'users', $user_id, 'user_management', null, 
                                "New user created: $username with role '$role' (Created by: Admin)");
                        $message = "User added successfully";
                    } else {
                        $error = "Error adding user: " . mysqli_error($conn);
                        }
                    }
                } else {
                    $error = "Database does not exist. Cannot add user.";
                }
                break;

            case 'edit_user':
                if ($database_exists) {
                    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
                    $username = mysqli_real_escape_string($conn, $_POST['username']);
                    $role = mysqli_real_escape_string($conn, $_POST['role']);
                    
                    mysqli_select_db($conn, DB_NAME);
                    
                    // Check if password is provided, only update it if it is
                    $password_sql = "";
                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $password_sql = ", password = '$password'";
                    }
                    
                    $sql = "UPDATE users SET username = '$username', role = '$role'$password_sql WHERE id = $user_id";
                    if (mysqli_query($conn, $sql)) {
                        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        logAudit($conn, 'update', 'users', $user_id, 'username', null, $username);
                        
                        // More detailed audit log
                        $changes = [];
                        $changes[] = "Username changed to: $username";
                        if (!empty($_POST['password'])) {
                            $changes[] = "Password changed";
                        }
                        if (isset($_POST['role'])) {
                            $changes[] = "Role changed to: $role";
                        }
                        
                        logAudit($conn, 'update', 'users', $user_id, 'user_management', null, 
                                "User updated: " . implode(", ", $changes) . " (Changed by: Admin)");
                        
                        $message = "User updated successfully";
                    } else {
                        $error = "Error updating user: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Database does not exist. Cannot edit user.";
                }
                break;
                
            case 'delete_user':
                if ($database_exists) {
                    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
                    
                    mysqli_select_db($conn, DB_NAME);
                    
                    // Check if this is the last admin user
                    $sql = "SELECT role FROM users WHERE id = $user_id";
                    $result = mysqli_query($conn, $sql);
                    $user_role = mysqli_fetch_assoc($result)['role'];
                    
                    if ($user_role == 'admin') {
                        // Count how many admins we have
                        $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
                        $result = mysqli_query($conn, $sql);
                        $admin_count = mysqli_fetch_assoc($result)['count'];
                        
                        if ($admin_count <= 1) {
                            $error = "Cannot delete the last admin user. The system requires at least one admin.";
                            break;
                        }
                    }
                    
                    $sql = "DELETE FROM users WHERE id = $user_id";
                    if (mysqli_query($conn, $sql)) {
                        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        logAudit($conn, 'delete', 'users', $user_id, null, null, "User deleted");
                        
                        // Get username for more detailed log
                        $username = "unknown";
                        $result = mysqli_query($conn, "SELECT username FROM users WHERE id = $user_id");
                        if ($result && $row = mysqli_fetch_assoc($result)) {
                            $username = $row['username'];
                        }
                        
                        logAudit($conn, 'delete', 'users', $user_id, 'user_management', 
                                "User $username existed", 
                                "User $username deleted (Deleted by: Admin)");
                        
                        $message = "User deleted successfully";
                    } else {
                        $error = "Error deleting user: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Database does not exist. Cannot delete user.";
                }
                break;

            case 'add_sector':
                if ($database_exists) {
                    $sector_name = trim(mysqli_real_escape_string($conn, $_POST['sector_name']));
                    $sector_description = trim(mysqli_real_escape_string($conn, $_POST['sector_description']));
                    
                    // Validate that name is not empty
                    if (empty($sector_name)) {
                        $error = "Parking Area name is required.";
                        break;
                    }
                    
                    mysqli_select_db($conn, DB_NAME);
                    
                    // Check if sectors table exists
                    $result = mysqli_query($conn, "SHOW TABLES LIKE 'sectors'");
                    if (mysqli_num_rows($result) == 0) {
                        // Create sectors table if it doesn't exist
                        $sql = "CREATE TABLE sectors (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(50) NOT NULL,
                            description VARCHAR(255),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )";
                        mysqli_query($conn, $sql);
                        
                        // Add sector_id column to parking_spots table
                        $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'sector_id'");
                        if (mysqli_num_rows($result) == 0) {
                            mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN sector_id INT NULL");
                        }
                    }
                    
                    // Insert new sector
                    $sql = "INSERT INTO sectors (name, description) VALUES ('$sector_name', '$sector_description')";
                    if (mysqli_query($conn, $sql)) {
                        $sector_id = mysqli_insert_id($conn);
                        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        logAudit($conn, 'insert', 'sectors', $sector_id, null, null, "Parking Area $sector_name added");
                        logAudit($conn, 'insert', 'sectors', $sector_id, 'sector_management', null, 
                                "New parking area created: $sector_name with description: '$sector_description' (Created by: Admin)");
                        $message = "Parking Area added successfully.";
                    } else {
                        $error = "Error adding parking area: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Database does not exist. Cannot add parking area.";
                }
                break;
                
            case 'edit_sector':
                if ($database_exists) {
                    $sector_id = mysqli_real_escape_string($conn, $_POST['sector_id']);
                    $sector_name = mysqli_real_escape_string($conn, $_POST['sector_name']);
                    $sector_description = mysqli_real_escape_string($conn, $_POST['sector_description']);
                    
                    mysqli_select_db($conn, DB_NAME);
                    
                    $sql = "UPDATE sectors SET name = '$sector_name', description = '$sector_description' WHERE id = $sector_id";
                    if (mysqli_query($conn, $sql)) {
                        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        logAudit($conn, 'update', 'sectors', $sector_id, 'name', null, $sector_name);
                        logAudit($conn, 'update', 'sectors', $sector_id, 'sector_management', null, 
                                "Sector updated: Name: $sector_name, Description: $sector_description (Changed by: Admin)");
                        $message = "Sector updated successfully";
                    } else {
                        $error = "Error updating sector: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Database does not exist. Cannot update sector.";
                }
                break;
                
            case 'delete_sector':
                if ($database_exists) {
                    $sector_id = mysqli_real_escape_string($conn, $_POST['sector_id']);
                    
                    mysqli_select_db($conn, DB_NAME);
                    
                    // Get sector name before deletion for audit log
                    $sector_name = "Unknown";
                    $result = mysqli_query($conn, "SELECT name FROM sectors WHERE id = $sector_id");
                    if ($result && $row = mysqli_fetch_assoc($result)) {
                        $sector_name = $row['name'];
                    }
                    
                    // Check if any spots are using this sector
                    $sql = "SELECT COUNT(*) as count FROM parking_spots WHERE sector_id = $sector_id";
                    $result = mysqli_query($conn, $sql);
                    $spots_count = 0;
                    if ($result && $row = mysqli_fetch_assoc($result)) {
                        $spots_count = $row['count'];
                    }
                    
                    if ($spots_count > 0) {
                        $error = "Cannot delete parking area because it contains $spots_count parking spot(s). Please reassign or delete those spots first.";
                    } else {
                        $sql = "DELETE FROM sectors WHERE id = $sector_id";
                        if (mysqli_query($conn, $sql)) {
                            $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                            logAudit($conn, 'delete', 'sectors', $sector_id, null, null, "Parking Area deleted");
                            logAudit($conn, 'delete', 'sectors', $sector_id, 'sector_management', 
                                    "Parking Area existed", 
                                    "Parking Area $sector_name deleted (Deleted by: Admin)");
                            $message = "Parking Area deleted successfully";
                        } else {
                            $error = "Error deleting parking area: " . mysqli_error($conn);
                        }
                    }
                } else {
                    $error = "Database does not exist. Cannot delete parking area.";
                }
                break;
                
            case 'add_spot':
                if ($database_exists) {
                    // Get selected sector - ensure it's not NULL
                    if (!isset($_POST['sector_id']) || $_POST['sector_id'] === 'NULL') {
                        $error = "Please select a specific sector for the new parking spot.";
                        break;
                    }
                    
                    $sector_id = mysqli_real_escape_string($conn, $_POST['sector_id']);
                    
                    // Auto-generate spot number
                    $spot_number = getNextSpotNumber($conn, $sector_id);
                    
                    // Check if sector_id column exists
                    mysqli_select_db($conn, DB_NAME);
                    $sector_column_exists = false;
                    $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'sector_id'");
                    if ($result && mysqli_num_rows($result) > 0) {
                        $sector_column_exists = true;
                    }
                    
                    // Build appropriate SQL query based on schema
                    if ($sector_column_exists) {
                        // Insert with sector_id
                        $sql = "INSERT INTO parking_spots (spot_number, is_occupied, sector_id) VALUES ('$spot_number', 0, $sector_id)";
                    } else {
                        // Insert without sector_id - only if we somehow don't have the column
                        $sql = "INSERT INTO parking_spots (spot_number, is_occupied) VALUES ('$spot_number', 0)";
                    }
                    
                    if (mysqli_query($conn, $sql)) {
                        $spot_id = mysqli_insert_id($conn);
                        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        logAudit($conn, 'insert', 'parking_spots', $spot_id, null, null, "Parking spot $spot_number added");
                        
                        // Get sector name
                        $sector_name = "Default";
                        if (isset($_POST['sector_id'])) {
                            $sector_id = mysqli_real_escape_string($conn, $_POST['sector_id']);
                            $result = mysqli_query($conn, "SELECT name FROM sectors WHERE id = $sector_id");
                            if ($result && $row = mysqli_fetch_assoc($result)) {
                                $sector_name = $row['name'];
                            }
                        }
                        
                        logAudit($conn, 'insert', 'parking_spots', $spot_id, 'spots_management', null, 
                                "New parking spot created: $spot_number in sector: $sector_name (Created by: Admin)");
                        $message = "Parking spot added successfully.";
                    } else {
                        $error = "Error adding parking spot: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Database does not exist. Cannot add parking spot.";
                }
                break;
                
            case 'delete_spot':
                if ($database_exists) {
                    $spot_id = mysqli_real_escape_string($conn, $_POST['spot_id']);
                    
                    // Check if spot is occupied first
                    mysqli_select_db($conn, DB_NAME);
                    $check_sql = "SELECT is_occupied FROM parking_spots WHERE id = $spot_id";
                    $result = mysqli_query($conn, $check_sql);
                    
                    if ($result && $row = mysqli_fetch_assoc($result)) {
                        if ($row['is_occupied']) {
                            $error = "Cannot delete an occupied parking spot. Please check out the vehicle first.";
                        } else {
                            // Check if there are any transactions associated with this spot
                            $check_transactions = "SELECT COUNT(*) as count FROM transactions WHERE spot_id = $spot_id";
                            $result = mysqli_query($conn, $check_transactions);
                            $has_transactions = false;
                            
                            if ($result && $row = mysqli_fetch_assoc($result)) {
                                $has_transactions = $row['count'] > 0;
                            }
                            
                            // If it has transactions and user hasn't confirmed, show warning
                            if ($has_transactions && !isset($_POST['confirm_delete_with_history'])) {
                                $error = "This parking spot has transaction history. Please confirm deletion by checking the confirmation box.";
                            } else {
                                // Safe to delete - either no transactions or user confirmed
                                // If it has transactions and user confirmed, also delete related transactions
                                if ($has_transactions && isset($_POST['confirm_delete_with_history'])) {
                                    // Delete transactions first (foreign key constraint)
                                    $sql = "DELETE FROM transactions WHERE spot_id = $spot_id";
                                    mysqli_query($conn, $sql);
                                    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                                    logAudit($conn, 'delete', 'transactions', null, null, null, "All transactions deleted for spot $spot_id");
                                    logAudit($conn, 'delete', 'transactions', null, 'transaction_management', 
                                            "Transactions for spot $spot_number existed", 
                                            "All transactions for spot $spot_number deleted (Deleted by: Admin)");
                                }
                        
                                // Now delete the spot
                                $sql = "DELETE FROM parking_spots WHERE id = $spot_id AND is_occupied = 0";
                                if (mysqli_query($conn, $sql)) {
                                    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                                    logAudit($conn, 'delete', 'parking_spots', $spot_id, null, null, "Parking spot deleted");
                                    logAudit($conn, 'delete', 'parking_spots', $spot_id, 'spots_management', 
                                            "Parking spot $spot_number existed", 
                                            "Parking spot $spot_number deleted" . ($has_transactions ? " including transaction history" : "") . 
                                            " (Deleted by: Admin)");
                                    $message = "Parking spot deleted successfully" . ($has_transactions ? " (including its transaction history)" : "");
                                } else {
                                    $error = "Error deleting parking spot: " . mysqli_error($conn);
                                }
                            }
                        }
                    } else {
                        $error = "Error: Unable to check parking spot status.";
                    }
                } else {
                    $error = "Database does not exist. Cannot delete parking spot.";
                }
                break;
                
            case 'update_logo':
                if ($database_exists) {
                    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
                        $file = $_FILES['logo'];
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        
                        if (in_array($file['type'], $allowedTypes) && $file['size'] <= 1048576) { // 1MB limit
                            $imageData = file_get_contents($file['tmp_name']);
                            
                            // Prepare and execute the SQL statement
                            $stmt = mysqli_prepare($conn, "INSERT INTO logo (image_data, file_name, mime_type) VALUES (?, ?, ?)");
                            mysqli_stmt_bind_param($stmt, "sss", $imageData, $file['name'], $file['type']);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                $message = "Logo updated successfully.";
                                // Log to audit trail
                                logAudit($conn, 'update', 'logo', null, 'logo_image', 'previous_logo', 'new_logo_uploaded');
                            } else {
                                $error = "Error saving logo to database: " . mysqli_error($conn);
                            }
                            mysqli_stmt_close($stmt);
                        } else {
                            $error = "Invalid file type or size. Please upload a JPG, PNG, or GIF file under 1MB.";
                        }
                    } else {
                        $error = "Error uploading file. Please try again.";
                    }
                } else {
                    $error = "Database does not exist. Cannot update logo.";
                }
                break;
                
            // ...existing code for other actions...
        }
    }
}

// Get the list of tables in the database
$tables = [];
if ($database_exists) {
    mysqli_select_db($conn, DB_NAME);
    $result = mysqli_query($conn, "SHOW TABLES");
    if ($result) {
        while($row = mysqli_fetch_array($result)) {
            $tables[] = $row[0];
        }
    }
}

// Get users if table exists
$users = [];
if ($database_exists && in_array('users', $tables)) {
    $result = mysqli_query($conn, "SELECT id, username, role, created_at FROM users ORDER BY username");
    if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
}

// Get transactions if table exists
$recent_transactions = [];
if ($database_exists && in_array('transactions', $tables)) {
    // Set up pagination
    $records_per_page = 20;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page); // Ensure page is at least 1
    $offset = ($page - 1) * $records_per_page;
    
    // Handle search terms
    $search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $search_condition = '';
    if (!empty($search_term)) {
        $search_condition = " AND (t.vehicle_id LIKE '%$search_term%' 
                              OR t.customer_name LIKE '%$search_term%' 
                              OR p.spot_number LIKE '%$search_term%')";
    }
    
    // Additional filters
    // Date range filter
    if (!empty($_GET['date_from'])) {
        $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
        $search_condition .= " AND t.entry_time >= '$date_from 00:00:00'";
    }
    
    if (!empty($_GET['date_to'])) {
        $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
        $search_condition .= " AND t.entry_time <= '$date_to 23:59:59'";
    }
    
    // Transaction type filter
    if (!empty($_GET['transaction_type'])) {
        $transaction_type = mysqli_real_escape_string($conn, $_GET['transaction_type']);
        $search_condition .= " AND t.transaction_type = '$transaction_type'";
    }
    
    // Status filter
    if (!empty($_GET['status'])) {
        $status = mysqli_real_escape_string($conn, $_GET['status']);
        if ($status == 'active') {
            $search_condition .= " AND t.exit_time IS NULL";
        } else if ($status == 'completed') {
            $search_condition .= " AND t.exit_time IS NOT NULL";
        }
    }
    
    // Sector filter
    if (!empty($_GET['sector_id'])) {
        $sector_id = mysqli_real_escape_string($conn, $_GET['sector_id']);
        $search_condition .= " AND p.sector_id = $sector_id";
    }
    
    // Fee range filter
    if (!empty($_GET['fee_min'])) {
        $fee_min = mysqli_real_escape_string($conn, $_GET['fee_min']);
        $search_condition .= " AND (t.fee >= $fee_min OR t.rental_rate >= $fee_min)";
    }
    
    if (!empty($_GET['fee_max'])) {
        $fee_max = mysqli_real_escape_string($conn, $_GET['fee_max']);
        $search_condition .= " AND (t.fee <= $fee_max OR t.rental_rate <= $fee_max)";
    }

    // Check if sectors table exists before joining with it
    $sectors_table_exists = in_array('sectors', $tables);
    
    // Get total record count for pagination
    if ($sectors_table_exists) {
        $count_sql = "SELECT COUNT(*) as total FROM transactions t 
                     JOIN parking_spots p ON t.spot_id = p.id 
                     WHERE 1=1 $search_condition";
    } else {
        $count_sql = "SELECT COUNT(*) as total FROM transactions t 
                     JOIN parking_spots p ON t.spot_id = p.id 
                     WHERE 1=1 $search_condition";
    }
    
    $count_result = mysqli_query($conn, $count_sql);
    $total_records = 0;
    if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
        $total_records = $row['total'];
    }
    $total_pages = ceil($total_records / $records_per_page);
    
    // Get transaction data with pagination
    if ($sectors_table_exists) {
        $sql = "SELECT t.*, p.spot_number, s.name as sector_name,
                CASE 
                    WHEN t.transaction_type = 'rental' THEN 'Rental' 
                    WHEN t.transaction_type = 'reservation' THEN 'Reservation'
                    ELSE 'Parking' 
                END as transaction_type_display   
                FROM transactions t
                JOIN parking_spots p ON t.spot_id = p.id
                LEFT JOIN sectors s ON p.sector_id = s.id
                WHERE 1=1 $search_condition
                ORDER BY entry_time DESC 
                LIMIT $offset, $records_per_page";
    } else {
        $sql = "SELECT t.*, p.spot_number, NULL as sector_name,
                CASE 
                    WHEN t.transaction_type = 'rental' THEN 'Rental' 
                    WHEN t.transaction_type = 'reservation' THEN 'Reservation'
                    ELSE 'Parking' 
                END as transaction_type_display   
                FROM transactions t
                JOIN parking_spots p ON t.spot_id = p.id
                WHERE 1=1 $search_condition
                ORDER BY entry_time DESC 
                LIMIT $offset, $records_per_page";
    }
    
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $recent_transactions[] = $row;
        }
    }
}

// Get sectors if table exists
$sectors = [];
if ($database_exists) {
    // Check if sectors table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'sectors'");
    if ($result && mysqli_num_rows($result) > 0) {
        $sql = "SELECT * FROM sectors ORDER BY name";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $sectors[] = $row;
            }
        }
    }
}

// Get parking spots with sector information
$parking_spots = [];
if ($database_exists && in_array('parking_spots', $tables)) {
    // Check if sectors table exists before attempting join
    $sectors_table_exists = false;
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'sectors'");
    if ($result && mysqli_num_rows($result) > 0) {
        $sectors_table_exists = true;
    }
    
    // Use appropriate query based on whether sectors table exists
    if ($sectors_table_exists) {
        // Check if sector_id column exists in parking_spots
        $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'sector_id'");
        if (mysqli_num_rows($result) > 0) {
            $sql = "SELECT p.*, s.name as sector_name 
                    FROM parking_spots p 
                    LEFT JOIN sectors s ON p.sector_id = s.id 
                    ORDER BY 
                    CASE WHEN p.sector_id IS NULL THEN 1 ELSE 0 END,
                    s.name,
                    CASE WHEN p.spot_number REGEXP '^[A-Z][0-9]+$' THEN 1 ELSE 2 END,
                    SUBSTRING(p.spot_number, 1, 1),
                    CAST(SUBSTRING(p.spot_number, 2) AS UNSIGNED)";
        } else {
            // sector_id column doesn't exist yet
            $sql = "SELECT *, NULL as sector_name FROM parking_spots 
                    ORDER BY 
                    CASE WHEN spot_number REGEXP '^[A-Z][0-9]+$' THEN 1 ELSE 2 END,
                    SUBSTRING(spot_number, 1, 1),
                    CAST(SUBSTRING(spot_number, 2) AS UNSIGNED)";
        }
    } else {
        // No sectors table - use simpler query
        $sql = "SELECT *, NULL as sector_name FROM parking_spots 
                ORDER BY 
                CASE WHEN spot_number REGEXP '^[A-Z][0-9]+$' THEN 1 ELSE 2 END,
                SUBSTRING(spot_number, 1, 1),
                CAST(SUBSTRING(spot_number, 2) AS UNSIGNED)";
    }
    
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $parking_spots[] = $row;
        }
    }
}

// Get system name from settings (only if database exists)
$system_name = "Parking Monitoring System"; // Default value
if ($database_exists && in_array('settings', $tables)) {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'system_name'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $system_name = $row['setting_value'];
    }
}

// Get next spot number for the form placeholder
$nextSpotNumber = "A1"; // Default value
if ($database_exists && function_exists('getNextSpotNumber')) {
    $nextSpotNumber = getNextSpotNumber($conn);
}
?>
<div class="container mt-4">
    <h1>System Settings</h1>
    <?php if (isset($message)): ?>
        <div class="alert alert-success d-none"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger d-none"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!$database_exists): ?>
        <div class="alert alert-warning no-toast">
            Database does not exist. Please initialize the database from the Database Management tab.
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                General Settings
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">
                User Management
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab" aria-controls="reports" aria-selected="false">
                Reports
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="database-tab" data-bs-toggle="tab" data-bs-target="#database" type="button" role="tab" aria-controls="database" aria-selected="false">
                Database Management
            </button>
        </li>
    </ul>

    <div class="tab-content" id="settingsTabsContent">
        <!-- General Settings -->
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Parking Rate Configuration</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="update_parking_rate">
                        <input type="hidden" name="active_tab" value="general">
                        
                        <div class="mb-3">
                            <label for="base_fee" class="form-label">Base Fee for First <?php echo $base_hours; ?> Hours (₱)</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="base_fee" name="base_fee" value="<?php echo number_format($base_fee, 2, '.', ''); ?>" required>
                            </div>
                            <div class="form-text text-body">This fee applies for the first <?php echo $base_hours; ?> hours of parking</div>
                        </div>
                        <div class="mb-3">
                            <label for="hourly_rate" class="form-label">Additional Hourly Rate (₱)</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="hourly_rate" name="hourly_rate" value="<?php echo number_format($hourly_rate, 2, '.', ''); ?>" required>
                            </div>
                            <div class="form-text text-body">This rate applies for each additional hour after the first <?php echo $base_hours; ?> hours</div>
                        </div>
                        <div class="mb-3">
                            <label for="base_hours" class="form-label">Base Hours</label>
                            <div class="input-group">
                                <input type="number" min="1" max="24" class="form-control" id="base_hours" name="base_hours" value="<?php echo $base_hours; ?>" required>
                                <span class="input-group-text">hours</span>
                            </div>
                            <div class="form-text text-body">Number of hours covered by the base fee</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Rates</button>
                    </form>
                </div>
            </div>
            
            <!-- Logo Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Logo Settings</h3>
                </div>
                <div class="card-body">
                    <form id="logoForm" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="logo" class="form-label">Upload New Logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*" required>
                            <div class="form-text text-body">Recommended size: 32x32 pixels. Supported formats: PNG, JPG, GIF</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Logo</button>
                    </form>
                </div>
            </div>
            
            <!-- Sector Management (Moved above Parking Spots) -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Area Management</h3>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSectorModal">
                        <i class="fas fa-plus-circle me-2"></i>Add New Parking Area
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($sectors)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> You need to create parking areas before adding parking spots.
                            Use the "Add New Parking Area" button above to create your first area.
                        </div>
                        <p class="text-center">No parking areas defined yet. Add one using the button above.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover border">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">Parking Area</th>
                                        <th class="fw-semibold">Description</th>
                                        <th class="fw-semibold">Spots Count</th>
                                        <th class="fw-semibold text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sectors as $sector): 
                                        // Count spots in this sector
                                        $count = 0;
                                        foreach ($parking_spots as $spot) {
                                            if (isset($spot['sector_id']) && $spot['sector_id'] == $sector['id']) {
                                                $count++;
                                            }
                                        }
                                    ?>
                                        <tr class="align-middle">
                                            <td class="fw-medium"><?php echo htmlspecialchars($sector['name']); ?></td>
                                            <td><?php echo htmlspecialchars($sector['description']); ?></td>
                                            <td><?php echo $count; ?> spots</td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-warning edit-sector-btn"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editSectorModal"
                                                    data-sector-id="<?php echo $sector['id']; ?>"
                                                    data-sector-name="<?php echo htmlspecialchars($sector['name']); ?>"
                                                    data-sector-description="<?php echo htmlspecialchars($sector['description']); ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-sector-btn"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteSectorModal"
                                                    data-sector-id="<?php echo $sector['id']; ?>"
                                                    data-sector-name="<?php echo htmlspecialchars($sector['name']); ?>"
                                                    data-spots-count="<?php echo $count; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Parking Spots Management (Only shown if sectors exist) -->
            <?php if (!empty($sectors)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Parking Spots Management</h3>
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" id="addSpotDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-plus-circle me-2"></i>Add Parking Spot
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="addSpotDropdown">
                            <?php foreach ($sectors as $sector): ?>
                                <li>
                                    <form method="post" action="" class="dropdown-item-form">
                                        <input type="hidden" name="action" value="add_spot">
                                        <input type="hidden" name="active_tab" value="general">
                                        <input type="hidden" name="sector_id" value="<?php echo $sector['id']; ?>">
                                        <button type="submit" class="dropdown-item">
                                            <?php echo htmlspecialchars($sector['name']); ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($parking_spots)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover border">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">Spot Number</th>
                                        <th class="fw-semibold">Sector</th>
                                        <th class="fw-semibold">Status</th>
                                        <th class="fw-semibold text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parking_spots as $spot): ?>
                                        <tr class="align-middle">
                                            <td class="fw-medium"><?php echo htmlspecialchars($spot['spot_number']); ?></td>
                                            <td>
                                                <?php if (!empty($spot['sector_name'])): ?>
                                                    <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo htmlspecialchars($spot['sector_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary rounded-pill px-3 py-2">Default</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($spot['is_occupied']): ?>
                                                    <span class="badge bg-danger rounded-pill px-3 py-2">Occupied</span>
                                                <?php elseif (isset($spot['is_rented']) && $spot['is_rented'] == 1): ?>
                                                    <span class="badge bg-info rounded-pill px-3 py-2">Rented</span>
                                                <?php elseif (isset($spot['is_reserved']) && $spot['is_reserved'] == 1): ?>
                                                    <span class="badge bg-warning rounded-pill px-3 py-2">Reserved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success rounded-pill px-3 py-2">Available</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-danger delete-spot-btn"
                                                    <?php if (!$spot['is_occupied'] && !(isset($spot['is_rented']) && $spot['is_rented'] == 1) && !(isset($spot['is_reserved']) && $spot['is_reserved'] == 1)): ?>
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteSpotModal"
                                                    <?php else: ?>
                                                        title="Cannot delete a spot that is occupied, rented, or reserved. Please check out, end rental, or cancel reservation first."
                                                    <?php endif; ?>
                                                    data-spot-id="<?php echo $spot['id']; ?>"
                                                    data-spot-number="<?php echo htmlspecialchars($spot['spot_number']); ?>"
                                                    <?php echo ($spot['is_occupied'] || (isset($spot['is_rented']) && $spot['is_rented'] == 1) || (isset($spot['is_reserved']) && $spot['is_reserved'] == 1)) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No parking spots defined yet. Add one using the button above.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- User Management -->
        <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Add New User</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="add_user">
                        <input type="hidden" name="active_tab" value="users">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="operator">Operator/Cashier</option>
                                        <option value="admin">Administrator</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>User List</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <p>No users found. Add a user using the form above.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover border">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">Username</th>
                                        <th class="fw-semibold">Role</th>
                                        <th class="fw-semibold">Created Date</th>
                                        <th class="fw-semibold text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="align-middle">
                                            <td class="fw-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'success'; ?> rounded-pill px-3 py-2">
                                                    <?php echo $user['role'] === 'admin' ? 'Administrator' : 'Operator/Cashier'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $user['created_at']; ?></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-warning edit-user-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal"
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                    data-role="<?php echo $user['role']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-user-btn"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteUserModal"
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reports -->
        <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Transaction History</h3>
                    
                    <div class="d-flex align-items-center">
                        <a href="earnings_reports.php" class="btn btn-primary me-2">
                            <i class="fas fa-chart-line me-1"></i> Earnings Reports
                        </a>
                        
                        <form method="get" action="" class="d-flex me-2">
                            <input type="hidden" name="active_tab" value="reports">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search transactions..." 
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (!empty($_GET['search'])): ?>
                                <a href="?active_tab=reports" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <a href="export_transactions_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn btn-secondary">
                            <i class="fas fa-file-export me-1"></i> Export
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php include 'includes/components/transaction_filters.php'; ?>
                    
                    <?php if (empty($recent_transactions)): ?>
                        <p>No transactions found.</p>
                    <?php else: ?>
                        <!-- Display active filters -->
                        <?php
                        $active_filters = [];
                        if (!empty($_GET['search'])) $active_filters[] = 'Search: ' . htmlspecialchars($_GET['search']);
                        if (!empty($_GET['date_from'])) $active_filters[] = 'From: ' . htmlspecialchars($_GET['date_from']);
                        if (!empty($_GET['date_to'])) $active_filters[] = 'To: ' . htmlspecialchars($_GET['date_to']);
                        if (!empty($_GET['transaction_type'])) $active_filters[] = 'Type: ' . ucfirst(htmlspecialchars($_GET['transaction_type']));
                        if (!empty($_GET['status'])) $active_filters[] = 'Status: ' . ucfirst(htmlspecialchars($_GET['status']));
                        if (!empty($_GET['sector_id']) && isset($sectors)) {
                            foreach ($sectors as $sector) {
                                if ($sector['id'] == $_GET['sector_id']) {
                                    $active_filters[] = 'Sector: ' . htmlspecialchars($sector['name']);
                                    break;
                                }
                            }
                        }
                        if (!empty($_GET['fee_min'])) $active_filters[] = 'Min Fee: ₱' . htmlspecialchars($_GET['fee_min']);
                        if (!empty($_GET['fee_max'])) $active_filters[] = 'Max Fee: ₱' . htmlspecialchars($_GET['fee_max']);
                        ?>
                        
                        <?php if (!empty($active_filters)): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <strong class="me-2">Active Filters:</strong>
                                <?php foreach ($active_filters as $filter): ?>
                                    <span class="badge bg-info me-1 px-3 py-2"><?php echo $filter; ?></span>
                                <?php endforeach; ?>
                                <a href="?active_tab=reports" class="btn btn-sm btn-outline-secondary ms-2">
                                    <i class="fas fa-times me-1"></i>Clear All
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            Showing <?php echo count($recent_transactions); ?> of <?php echo $total_records; ?> transactions
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover border">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">ID</th>
                                        <th class="fw-semibold">Type</th>
                                        <th class="fw-semibold">Spot</th>
                                        <th class="fw-semibold">Sector</th>
                                        <th class="fw-semibold">Customer</th>
                                        <th class="fw-semibold">Vehicle ID</th>
                                        <th class="fw-semibold">Vehicle</th>
                                        <th class="fw-semibold">Entry Time</th>
                                        <th class="fw-semibold">Exit Time</th>
                                        <th class="fw-semibold">Duration</th>
                                        <th class="fw-semibold text-end">Fee</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_transactions as $transaction): 
                                        // Calculate duration if both entry and exit times exist
                                        $duration = '';
                                        if (!empty($transaction['entry_time']) && !empty($transaction['exit_time'])) {
                                            $entry = new DateTime($transaction['entry_time']);
                                            $exit = new DateTime($transaction['exit_time']);
                                            $interval = $entry->diff($exit);
                                            
                                            if ($interval->days > 0) {
                                                $duration .= $interval->days . 'd ';
                                            }
                                            $duration .= $interval->h . 'h ' . $interval->i . 'm';
                                        }
                                    ?>
                                        <tr class="align-middle">
                                            <td class="fw-medium"><?php echo $transaction['id']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $transaction['transaction_type'] == 'rental' ? 'info' : ($transaction['transaction_type'] == 'reservation' ? 'warning' : 'primary'); ?> rounded-pill px-3 py-2">
                                                    <?php echo $transaction['transaction_type_display']; ?>
                                                </span>
                                            </td>
                                            <td class="fw-medium"><?php echo htmlspecialchars($transaction['spot_number']); ?></td>
                                            <td>
                                                <?php if (!empty($transaction['sector_name'])): ?>
                                                    <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo htmlspecialchars($transaction['sector_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary rounded-pill px-3 py-2">Default</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['customer_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['vehicle_id'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['vehicle_type'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($transaction['transaction_type'] == 'rental'): ?>
                                                    <?php echo date('M d, Y', strtotime($transaction['rental_start_date'])); ?>
                                                <?php else: ?>
                                                    <?php echo date('M d, Y g:i A', strtotime($transaction['entry_time'])); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($transaction['exit_time']): ?>
                                                    <?php if ($transaction['transaction_type'] == 'rental'): ?>
                                                        <?php echo date('M d, Y', strtotime($transaction['rental_end_date'])); ?>
                                                    <?php else: ?>
                                                        <?php echo date('M d, Y g:i A', strtotime($transaction['exit_time'])); ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-info rounded-pill px-3 py-2">
                                                        <?php echo $transaction['transaction_type'] == 'rental' ? 'Active Rental' : 'Still Parked'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($transaction['transaction_type'] == 'rental'): ?>
                                                    <?php 
                                                        $start = new DateTime($transaction['rental_start_date']);
                                                        $end = !empty($transaction['rental_end_date']) ? new DateTime($transaction['rental_end_date']) : new DateTime();
                                                        $interval = $start->diff($end);
                                                        $months = $interval->y * 12 + $interval->m;
                                                        echo $months . ' month' . ($months != 1 ? 's' : '');
                                                    ?>
                                                <?php elseif ($transaction['transaction_type'] == 'reservation'): ?>
                                                    <span class="text-success">₱<?php echo number_format($transaction['reservation_fee'] ?? 0, 2); ?></span>
                                                <?php elseif ($transaction['fee']): ?>
                                                    <span class="text-success">₱<?php echo number_format($transaction['fee'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">₱0.00</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Transaction pagination">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?active_tab=reports&page=<?php echo $page-1; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                                
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $start_page + 4);
                                $start_page = max(1, $end_page - 4);
                                
                                for ($i = $start_page; $i <= $end_page; $i++): 
                                ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?active_tab=reports&page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?active_tab=reports&page=<?php echo $page+1; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Database Management -->
        <div class="tab-pane fade" id="database" role="tabpanel" aria-labelledby="database-tab">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Database Operations</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Initialize Database</h5>
                                    <p class="card-text">Create database and tables</p>
                                    <a href="database/init_db.php" class="btn btn-success">Initialize</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Backup Database</h5>
                                    <p class="card-text">Export all data to SQL file</p>
                                    <a href="database/backup_db.php" class="btn btn-info">Backup</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include all modal components
include_once 'includes/modals/edit_user_modal.php';
include_once 'includes/modals/delete_user_modal.php';
include_once 'includes/modals/delete_spot_modal.php';
include_once 'includes/modals/add_sector_modal.php';
include_once 'includes/modals/edit_sector_modal.php';
include_once 'includes/modals/delete_sector_modal.php';
?>

<!-- Include JavaScript file -->
<script src="assets/js/system_settings.js"></script>

<script>
// Password visibility toggle
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Set default password when username is entered
document.getElementById('username').addEventListener('input', function() {
    const passwordInput = document.getElementById('password');
    if (this.value.length > 0 && passwordInput.value === '') {
        passwordInput.value = 'password123'; // Default password
    }
});

document.getElementById('logoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    
    fetch('includes/handlers/upload_logo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the logo in the navigation bar
            document.querySelector('.navbar-brand .brand-icon').outerHTML = data.logo_html;
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(error => {
        showToast('Error uploading logo: ' + error, 'danger');
    })
    .finally(() => {
        submitButton.disabled = false;
        this.reset();
    });
});
</script>

<?php
// Check if there are any messages or errors to display
if (!empty($message) || !empty($error)):
?>
<script>
    // This prevents the automatic toast conversion on this page
    document.addEventListener('DOMContentLoaded', function() {
        // If there's a PHP message, show it as a toast
        <?php if (!empty($message)): ?>
            showToast('<?php echo addslashes($message); ?>', 'success');
        <?php endif; ?>
        
        // If there's a PHP error, show it as a toast
        <?php if (!empty($error)): ?>
            showToast('<?php echo addslashes($error); ?>', 'danger');
        <?php endif; ?>
        
        // Mark all other alerts as no-toast to prevent double conversion
        document.querySelectorAll('.alert:not(.convert-to-toast)').forEach(alert => {
            alert.classList.add('no-toast');
        });
    });
</script>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>
