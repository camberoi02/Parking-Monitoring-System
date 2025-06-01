<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'parking_monitoring');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Function to verify and repair database schema
function verify_db_schema($conn) {
    // First check if database exists
    $result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
    if (mysqli_num_rows($result) > 0) {
        mysqli_select_db($conn, DB_NAME);
        
        // Check if settings table exists
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
        if (mysqli_num_rows($result) > 0) {
            // Add overnight fee settings if they don't exist
            $settings_to_check = [
                'vehicle_overnight_fee' => '100.00',  // Default overnight fee for vehicles
                'motorcycle_overnight_fee' => '50.00'  // Default overnight fee for motorcycles
            ];
            
            foreach ($settings_to_check as $key => $default_value) {
                $result = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = '$key'");
                if (mysqli_num_rows($result) == 0) {
                    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$default_value')");
                    echo "<div class='alert alert-success'>Added $key setting with default value ₱$default_value</div>";
                }
            }
        }
        
        // Check if parking_spots table exists
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'parking_spots'");
        if (mysqli_num_rows($result) > 0) {
            // Check if vehicle_id column exists
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'vehicle_id'");
            if (mysqli_num_rows($result) == 0) {
                // Add vehicle_id column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN vehicle_id VARCHAR(20) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added vehicle_id column to parking_spots table.</div>";
            }
            
            // Check if entry_time column exists
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'entry_time'");
            if (mysqli_num_rows($result) == 0) {
                // Add entry_time column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN entry_time DATETIME NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added entry_time column to parking_spots table.</div>";
            }
            
            // Check if customer_name column exists
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'customer_name'");
            if (mysqli_num_rows($result) == 0) {
                // Add customer_name column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN customer_name VARCHAR(100) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added customer_name column to parking_spots table.</div>";
            }
            
            // Check if vehicle_type column exists
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'vehicle_type'");
            if (mysqli_num_rows($result) == 0) {
                // Add vehicle_type column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN vehicle_type VARCHAR(50) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added vehicle_type column to parking_spots table.</div>";
            }
            
            // Check if customer_type column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'customer_type'");
            if (mysqli_num_rows($result) == 0) {
                // Add customer_type column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN customer_type VARCHAR(50) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added customer_type column to parking_spots table.</div>";
            }
            
            // Check if is_free column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'is_free'");
            if (mysqli_num_rows($result) == 0) {
                // Add is_free column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN is_free BOOLEAN NOT NULL DEFAULT 0");
                echo "<div class='alert alert-success'>Database schema updated: Added is_free column to parking_spots table.</div>";
            }
            
            // Check if is_overnight column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'is_overnight'");
            if (mysqli_num_rows($result) == 0) {
                // Add is_overnight column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN is_overnight BOOLEAN NOT NULL DEFAULT 0");
                echo "<div class='alert alert-success'>Database schema updated: Added is_overnight column to parking_spots table.</div>";
            }
            
            // Check if is_rented column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'is_rented'");
            if (mysqli_num_rows($result) == 0) {
                // Add is_rented column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN is_rented BOOLEAN NOT NULL DEFAULT 0");
                echo "<div class='alert alert-success'>Database schema updated: Added is_rented column to parking_spots table.</div>";
            }
            
            // Check if renter_name column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'renter_name'");
            if (mysqli_num_rows($result) == 0) {
                // Add renter_name column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN renter_name VARCHAR(100) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added renter_name column to parking_spots table.</div>";
            }
            
            // Check if renter_contact column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'renter_contact'");
            if (mysqli_num_rows($result) == 0) {
                // Add renter_contact column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN renter_contact VARCHAR(100) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added renter_contact column to parking_spots table.</div>";
            }
            
            // Check if rental_start_date column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'rental_start_date'");
            if (mysqli_num_rows($result) == 0) {
                // Add rental_start_date column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN rental_start_date DATE NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added rental_start_date column to parking_spots table.</div>";
            }
            
            // Check if rental_end_date column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'rental_end_date'");
            if (mysqli_num_rows($result) == 0) {
                // Add rental_end_date column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN rental_end_date DATE NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added rental_end_date column to parking_spots table.</div>";
            }
            
            // Check if rental_rate column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'rental_rate'");
            if (mysqli_num_rows($result) == 0) {
                // Add rental_rate column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN rental_rate DECIMAL(10,2) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added rental_rate column to parking_spots table.</div>";
            }
            
            // Check if rental_notes column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'rental_notes'");
            if (mysqli_num_rows($result) == 0) {
                // Add rental_notes column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN rental_notes TEXT NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added rental_notes column to parking_spots table.</div>";
            }
            
            // Check if is_reserved column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'is_reserved'");
            if (mysqli_num_rows($result) == 0) {
                // Add is_reserved column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN is_reserved BOOLEAN NOT NULL DEFAULT 0");
                echo "<div class='alert alert-success'>Database schema updated: Added is_reserved column to parking_spots table.</div>";
            }
            
            // Check if reserver_name column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'reserver_name'");
            if (mysqli_num_rows($result) == 0) {
                // Add reserver_name column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN reserver_name VARCHAR(100) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added reserver_name column to parking_spots table.</div>";
            }
            
            // Check if reserver_contact column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'reserver_contact'");
            if (mysqli_num_rows($result) == 0) {
                // Add reserver_contact column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN reserver_contact VARCHAR(100) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added reserver_contact column to parking_spots table.</div>";
            }
            
            // Check if reservation_start_time column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'reservation_start_time'");
            if (mysqli_num_rows($result) == 0) {
                // Add reservation_start_time column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN reservation_start_time DATETIME NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added reservation_start_time column to parking_spots table.</div>";
            }
            
            // Check if reservation_end_time column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'reservation_end_time'");
            if (mysqli_num_rows($result) == 0) {
                // Add reservation_end_time column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN reservation_end_time DATETIME NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added reservation_end_time column to parking_spots table.</div>";
            }
            
            // Check if reservation_notes column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'reservation_notes'");
            if (mysqli_num_rows($result) == 0) {
                // Add reservation_notes column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN reservation_notes TEXT NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added reservation_notes column to parking_spots table.</div>";
            }
            
            // Check if reservation_fee column exists in parking_spots
            $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'reservation_fee'");
            if (mysqli_num_rows($result) == 0) {
                // Add reservation_fee column
                mysqli_query($conn, "ALTER TABLE parking_spots ADD COLUMN reservation_fee DECIMAL(10,2) NULL");
                echo "<div class='alert alert-success'>Database schema updated: Added reservation_fee column to parking_spots table.</div>";
            }
            
            // Check if transactions table exists
            $result = mysqli_query($conn, "SHOW TABLES LIKE 'transactions'");
            if (mysqli_num_rows($result) > 0) {
                // Check if customer_name column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'customer_name'");
                if (mysqli_num_rows($result) == 0) {
                    // Add customer_name column to transactions
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN customer_name VARCHAR(100) NULL");
                    echo "<div class='alert alert-success'>Database schema updated: Added customer_name column to transactions table.</div>";
                }
                
                // Check if vehicle_type column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'vehicle_type'");
                if (mysqli_num_rows($result) == 0) {
                    // Add vehicle_type column to transactions
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN vehicle_type VARCHAR(50) NULL");
                    echo "<div class='alert alert-success'>Database schema updated: Added vehicle_type column to transactions table.</div>";
                }
                
                // Check if is_free column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'is_free'");
                if (mysqli_num_rows($result) == 0) {
                    // Add is_free column to transactions
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN is_free BOOLEAN NOT NULL DEFAULT 0");
                    echo "<div class='alert alert-success'>Database schema updated: Added is_free column to transactions table.</div>";
                }
                
                // Check if transaction_type column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'transaction_type'");
                if (mysqli_num_rows($result) == 0) {
                    // Add transaction_type column to transactions
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN transaction_type ENUM('parking', 'rental', 'reservation') NOT NULL DEFAULT 'parking'");
                    echo "<div class='alert alert-success'>Database schema updated: Added transaction_type column to transactions table.</div>";
                } else {
                    // Check if 'reservation' is in the enum values and update if needed
                    $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'transaction_type'");
                    $row = mysqli_fetch_array($result);
                    $type = $row['Type'];
                    
                    if (strpos($type, 'reservation') === false) {
                        mysqli_query($conn, "ALTER TABLE transactions MODIFY COLUMN transaction_type ENUM('parking', 'rental', 'reservation') NOT NULL DEFAULT 'parking'");
                        echo "<div class='alert alert-success'>Database schema updated: Added 'reservation' type to transaction_type column.</div>";
                    }
                }
                
                // Check if rental_start_date column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'rental_start_date'");
                if (mysqli_num_rows($result) == 0) {
                    // Add rental_start_date column to transactions
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN rental_start_date DATE NULL");
                    echo "<div class='alert alert-success'>Database schema updated: Added rental_start_date column to transactions table.</div>";
                }
                
                // Check if rental_end_date column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'rental_end_date'");
                if (mysqli_num_rows($result) == 0) {
                    // Add rental_end_date column to transactions
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN rental_end_date DATE NULL");
                    echo "<div class='alert alert-success'>Database schema updated: Added rental_end_date column to transactions table.</div>";
                }
                
                // Check if rental_rate column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'rental_rate'");
                if (mysqli_num_rows($result) == 0) {
                    // Add rental_rate column to transactions
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN rental_rate DECIMAL(10,2) NULL");
                    echo "<div class='alert alert-success'>Database schema updated: Added rental_rate column to transactions table.</div>";
                }
                
                // Check if reservation_fee column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'reservation_fee'");
                if (mysqli_num_rows($result) == 0) {
                    // Add reservation_fee column to transactions
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN reservation_fee DECIMAL(10,2) NULL");
                    echo "<div class='alert alert-success'>Database schema updated: Added reservation_fee column to transactions table.</div>";
                }
                
                // Check if is_paid column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'is_paid'");
                if (mysqli_num_rows($result) == 0) {
                    // Add is_paid column to transactions
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN is_paid BOOLEAN NOT NULL DEFAULT 1");
                    echo "<div class='alert alert-success'>Database schema updated: Added is_paid column to transactions table.</div>";
                }
                
                // Check if customer_type column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'customer_type'");
                if (mysqli_num_rows($result) == 0) {
                    // Add customer_type column
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN customer_type VARCHAR(50) NULL");
                    echo "<div class='alert alert-success'>Database schema updated: Added customer_type column to transactions table.</div>";
                }
                
                // Check if is_overnight column exists in transactions
                $result = mysqli_query($conn, "SHOW COLUMNS FROM transactions LIKE 'is_overnight'");
                if (mysqli_num_rows($result) == 0) {
                    // Add is_overnight column
                    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN is_overnight BOOLEAN NOT NULL DEFAULT 0");
                    echo "<div class='alert alert-success'>Database schema updated: Added is_overnight column to transactions table.</div>";
                }
            }
        }
    }
}

/**
 * Get the base fee for the first 3 hours from settings
 */
function getBaseFee($conn) {
    // First check if database exists
    $result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
    if (mysqli_num_rows($result) == 0) {
        // Database doesn't exist, return default rate
        return 50.00;
    }
    
    mysqli_select_db($conn, DB_NAME);
    
    // Check if settings table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
    if (mysqli_num_rows($result) == 0) {
        // Settings table doesn't exist, return default value
        return 50.00;
    }
    
    // Get base fee from settings
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'base_fee'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return floatval($row['setting_value']);
    } else {
        // Default value if not found
        return 50.00;
    }
}

/**
 * Update parking rates (both base fee and hourly rate)
 */
function updateParkingRates($conn, $base_fee, $hourly_rate) {
    mysqli_select_db($conn, DB_NAME);
    
    // Check if settings table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
    if (mysqli_num_rows($result) == 0) {
        // Create settings table if it doesn't exist
        $sql = "CREATE TABLE settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        if (!mysqli_query($conn, $sql)) {
            return false;
        }
    }
    
    // Update base fee
    $sql = "INSERT INTO settings (setting_key, setting_value) 
            VALUES ('base_fee', '$base_fee') 
            ON DUPLICATE KEY UPDATE setting_value = '$base_fee'";
    if (!mysqli_query($conn, $sql)) {
        return false;
    }
    
    // Update hourly rate
    $sql = "INSERT INTO settings (setting_key, setting_value) 
            VALUES ('hourly_rate', '$hourly_rate') 
            ON DUPLICATE KEY UPDATE setting_value = '$hourly_rate'";
    return mysqli_query($conn, $sql);
}

/**
 * Update base hours configuration
 */
function updateBaseHours($conn, $base_hours) {
    mysqli_select_db($conn, DB_NAME);
    
    // Check if settings table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
    if (mysqli_num_rows($result) == 0) {
        // Create settings table if it doesn't exist
        $sql = "CREATE TABLE settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        if (!mysqli_query($conn, $sql)) {
            return false;
        }
    }
    
    // Update base hours
    $sql = "INSERT INTO settings (setting_key, setting_value) 
            VALUES ('base_hours', '$base_hours') 
            ON DUPLICATE KEY UPDATE setting_value = '$base_hours'";
    return mysqli_query($conn, $sql);
}

// Update existing function to maintain backward compatibility
function updateHourlyRate($conn, $rate) {
    mysqli_select_db($conn, DB_NAME);
    
    // Check if settings table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
    if (mysqli_num_rows($result) == 0) {
        // Create settings table if it doesn't exist
        $sql = "CREATE TABLE settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        if (!mysqli_query($conn, $sql)) {
            return false;
        }
    }
    
    $sql = "INSERT INTO settings (setting_key, setting_value) 
            VALUES ('hourly_rate', '$rate') 
            ON DUPLICATE KEY UPDATE setting_value = '$rate'";
    return mysqli_query($conn, $sql);
}

// Function to get the hourly rate in PHP (Philippine Pesos)
function getHourlyRate($conn) {
    // First check if database exists
    $result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
    if (mysqli_num_rows($result) == 0) {
        // Database doesn't exist, return default rate
        return 100.00;
    }
    
    mysqli_select_db($conn, DB_NAME);
    
    // Check if settings table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
    if (mysqli_num_rows($result) == 0) {
        // Create settings table if it doesn't exist
        $sql = "CREATE TABLE settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        mysqli_query($conn, $sql);
        
        // Insert default hourly rate (100 PHP)
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('hourly_rate', '100')";
        mysqli_query($conn, $sql);
        return 100.00;
    }
    
    // Get hourly rate from settings
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'hourly_rate'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return floatval($row['setting_value']);
    } else {
        // Insert default rate and return it
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('hourly_rate', '100')";
        mysqli_query($conn, $sql);
        return 100.00;
    }
}

// Function to get the next available spot number
function getNextSpotNumber($conn, $sector_id = NULL) {
    mysqli_select_db($conn, DB_NAME);
    
    // Check if the table exists first
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'parking_spots'");
    if (mysqli_num_rows($result) == 0) {
        return "A1"; // Default starting spot if table doesn't exist
    }
    
    // Check if sector_id column exists in parking_spots table
    $sector_column_exists = false;
    $result = mysqli_query($conn, "SHOW COLUMNS FROM parking_spots LIKE 'sector_id'");
    if ($result && mysqli_num_rows($result) > 0) {
        $sector_column_exists = true;
    }
    
    // Get sector prefix if sector is provided
    $sector_prefix = '';
    if ($sector_id && $sector_id != 'NULL' && $sector_column_exists) {
        $sql = "SELECT name FROM sectors WHERE id = $sector_id";
        $result = mysqli_query($conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            // Use first letter of each word in the sector name
            $words = explode(' ', $row['name']);
            foreach ($words as $word) {
                if (!empty($word)) {
                    $sector_prefix .= strtoupper(substr($word, 0, 1));
                }
            }
            $sector_prefix .= '-';
        }
    }
    
    // Find the highest spot number for this sector
    if ($sector_column_exists && $sector_id && $sector_id != 'NULL') {
        // Look for spots with the pattern PREFIX-A1, PREFIX-B2, etc. for this specific sector
        $sql = "SELECT spot_number FROM parking_spots 
                WHERE sector_id = $sector_id 
                ORDER BY 
                    CASE WHEN spot_number REGEXP '^[A-Z]+-[A-Z][0-9]+$' THEN 1 ELSE 2 END,
                    LENGTH(spot_number),
                    spot_number DESC 
                LIMIT 1";
    } else {
        // Look for any spots to find the highest number
        $sql = "SELECT spot_number FROM parking_spots 
                ORDER BY 
                    CASE WHEN spot_number REGEXP '^[A-Z][0-9]+$' THEN 1 ELSE 2 END,
                    LENGTH(spot_number),
                    spot_number DESC 
                LIMIT 1";
    }
    
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastSpot = $row['spot_number'];
        
        // If it has a sector prefix, remove it to get the base spot number
        if (strpos($lastSpot, '-') !== false) {
            $lastSpot = substr($lastSpot, strpos($lastSpot, '-') + 1);
        }
        
        // Extract the letter and number parts
        preg_match('/([A-Z])([0-9]+)/', $lastSpot, $matches);
        if (count($matches) >= 3) {
            $letter = $matches[1];
            $number = (int)$matches[2];
            
            // Increment the number
            $nextNumber = $number + 1;
            return $sector_prefix . $letter . $nextNumber;
        }
    }
    
    // If no spots found with the pattern or parsing failed, return default A1
    return $sector_prefix . "A1";
}

/**
 * Log an action to the audit log
 */
function logAudit($conn, $action_type, $table_name, $record_id = null, $field_name = null, $old_value = null, $new_value = null) {
    // Select the database first
    mysqli_select_db($conn, DB_NAME);
    
    // Check if audit_trail table exists, if not create it
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'audit_trail'");
    if (mysqli_num_rows($check_table) == 0) {
        $create_sql = "CREATE TABLE IF NOT EXISTS audit_trail (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            action_type VARCHAR(50) NOT NULL,
            table_name VARCHAR(50) NOT NULL,
            record_id VARCHAR(50) NULL,
            field_name VARCHAR(50) NULL,
            old_value TEXT NULL,
            new_value TEXT NULL,
            user_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        mysqli_query($conn, $create_sql);
    }
    
    $action_type = mysqli_real_escape_string($conn, $action_type);
    $table_name = mysqli_real_escape_string($conn, $table_name);
    $record_id = $record_id ? mysqli_real_escape_string($conn, $record_id) : 'NULL';
    $field_name = $field_name ? "'" . mysqli_real_escape_string($conn, $field_name) . "'" : 'NULL';
    $old_value = $old_value !== null ? "'" . mysqli_real_escape_string($conn, $old_value) . "'" : 'NULL';
    $new_value = $new_value !== null ? "'" . mysqli_real_escape_string($conn, $new_value) . "'" : 'NULL';
    
    $sql = "INSERT INTO audit_trail (action_type, table_name, record_id, field_name, old_value, new_value) 
            VALUES ('$action_type', '$table_name', $record_id, $field_name, $old_value, $new_value)";
    
    return mysqli_query($conn, $sql);
}

/**
 * Update vehicle-specific parking rates
 */
function updateVehicleRates($conn, $vehicle_type, $base_fee, $hourly_rate, $overnight_fee) {
    mysqli_select_db($conn, DB_NAME);
    
    // Check if settings table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
    if (mysqli_num_rows($result) == 0) {
        // Create settings table if it doesn't exist
        $sql = "CREATE TABLE settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        if (!mysqli_query($conn, $sql)) {
            return false;
        }
    }
    
    // Update base fee
    $base_fee_key = strtolower($vehicle_type) . '_base_fee';
    $sql = "INSERT INTO settings (setting_key, setting_value) 
            VALUES ('$base_fee_key', '$base_fee') 
            ON DUPLICATE KEY UPDATE setting_value = '$base_fee'";
    if (!mysqli_query($conn, $sql)) {
        return false;
    }
    
    // Update hourly rate
    $hourly_rate_key = strtolower($vehicle_type) . '_hourly_rate';
    $sql = "INSERT INTO settings (setting_key, setting_value) 
            VALUES ('$hourly_rate_key', '$hourly_rate') 
            ON DUPLICATE KEY UPDATE setting_value = '$hourly_rate'";
    if (!mysqli_query($conn, $sql)) {
        return false;
    }
    
    // Update overnight fee
    $overnight_fee_key = strtolower($vehicle_type) . '_overnight_fee';
    $sql = "INSERT INTO settings (setting_key, setting_value) 
            VALUES ('$overnight_fee_key', '$overnight_fee') 
            ON DUPLICATE KEY UPDATE setting_value = '$overnight_fee'";
    return mysqli_query($conn, $sql);
}

/**
 * Get the overnight fee for a specific vehicle type
 */
function getVehicleOvernightFee($conn, $vehicle_type) {
    // First check if database exists
    $result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
    if (mysqli_num_rows($result) == 0) {
        // Database doesn't exist, return default rate
        return $vehicle_type == 'Motorcycle' ? 50.00 : 100.00;
    }
    
    mysqli_select_db($conn, DB_NAME);
    
    // Check if settings table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
    if (mysqli_num_rows($result) == 0) {
        // Settings table doesn't exist, return default value
        return $vehicle_type == 'Motorcycle' ? 50.00 : 100.00;
    }
    
    // Get overnight fee from settings based on vehicle type
    $setting_key = strtolower($vehicle_type) . '_overnight_fee';
    $sql = "SELECT setting_value FROM settings WHERE setting_key = '$setting_key'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return floatval($row['setting_value']);
    } else {
        // Default values if not found
        return $vehicle_type == 'Motorcycle' ? 50.00 : 100.00;
    }
}

// Run schema verification if we're not in the database initialization process
$current_script = basename($_SERVER['SCRIPT_NAME']);
if ($current_script != 'init_db.php' && $current_script != 'drop_db.php') {
    verify_db_schema($conn);
}
?>
