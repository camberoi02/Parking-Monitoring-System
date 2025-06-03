<?php
$title = "Initialize Database";
include_once '../includes/header.php';

require_once '../config/db_config.php';

// Add Bootstrap container
echo '<div class="container mt-4">';
echo '<div class="card">';
echo '<div class="card-header bg-success text-white"><h3>Database Initialization</h3></div>';
echo '<div class="card-body">';

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Database created successfully</div>';
} else{
    echo '<div class="alert alert-danger">ERROR: Could not create database. ' . mysqli_error($conn) . '</div>';
}

// Select the database
mysqli_select_db($conn, DB_NAME);

// Create sectors table first
$sql = "CREATE TABLE IF NOT EXISTS sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Table sectors created successfully</div>';
} else{
    echo '<div class="alert alert-danger">ERROR: Could not create table. ' . mysqli_error($conn) . '</div>';
}

// Create parking_spots table
$sql = "CREATE TABLE IF NOT EXISTS parking_spots (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    spot_number VARCHAR(10) NOT NULL UNIQUE,
    sector_id INT NOT NULL,
    is_occupied BOOLEAN NOT NULL DEFAULT 0,
    vehicle_id VARCHAR(20) NULL,
    customer_name VARCHAR(100) NULL,
    vehicle_type VARCHAR(50) NULL,
    customer_type VARCHAR(50) NULL,
    is_free BOOLEAN NOT NULL DEFAULT 0,
    is_overnight BOOLEAN NOT NULL DEFAULT 0,
    entry_time DATETIME NULL,
    is_rented BOOLEAN NOT NULL DEFAULT 0,
    renter_name VARCHAR(100) NULL,
    renter_contact VARCHAR(100) NULL,
    rental_start_date DATE NULL,
    rental_end_date DATE NULL,
    rental_rate DECIMAL(10,2) NULL,
    rental_notes TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sector_id) REFERENCES sectors(id)
)";

if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Table parking_spots created successfully</div>';
} else{
    echo '<div class="alert alert-danger">ERROR: Could not create table. ' . mysqli_error($conn) . '</div>';
}

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator') NOT NULL DEFAULT 'operator',
    first_login BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Table users created successfully</div>';
    
    // Insert default admin user
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO users (username, password, role) VALUES ('admin', '$password_hash', 'admin')";
    
    if(mysqli_query($conn, $sql)){
        echo '<div class="alert alert-success">Default admin user created successfully</div>';
    } else{
        echo '<div class="alert alert-danger">ERROR: Could not create default admin user. ' . mysqli_error($conn) . '</div>';
    }
} else{
    echo '<div class="alert alert-danger">ERROR: Could not create table. ' . mysqli_error($conn) . '</div>';
}

// Create transactions table
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    spot_id INT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    customer_name VARCHAR(100) NULL,
    vehicle_type VARCHAR(50) NULL,
    customer_type VARCHAR(50) NULL,
    is_free BOOLEAN NOT NULL DEFAULT 0,
    is_overnight BOOLEAN NOT NULL DEFAULT 0,
    entry_time DATETIME NOT NULL,
    exit_time DATETIME NULL,
    fee DECIMAL(10,2) NULL,
    transaction_type ENUM('parking', 'rental', 'reservation') NOT NULL DEFAULT 'parking',
    rental_start_date DATE NULL,
    rental_end_date DATE NULL,
    rental_rate DECIMAL(10,2) NULL,
    reservation_fee DECIMAL(10,2) NULL,
    is_paid BOOLEAN NOT NULL DEFAULT 1
)";

if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Table transactions created successfully</div>';
} else{
    echo '<div class="alert alert-danger">ERROR: Could not create table. ' . mysqli_error($conn) . '</div>';
}

// Create settings table
$sql = "CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Table settings created successfully</div>';
    
    // Insert default settings
    $default_settings = [
        ['hourly_rate', '100'],
        ['vehicle_base_fee', '40.00'],
        ['vehicle_hourly_rate', '20.00'],
        ['motorcycle_base_fee', '20.00'],
        ['motorcycle_hourly_rate', '10.00'],
        ['base_hours', '3'],
        ['overnight_fee', '500.00']
    ];

    foreach ($default_settings as $setting) {
        $key = $setting[0];
        $value = $setting[1];
        $sql = "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('$key', '$value')";
        if (mysqli_query($conn, $sql)) {
            echo "<div class='alert alert-success'>Default $key set to $value successfully</div>";
        } else {
            echo "<div class='alert alert-danger'>ERROR: Could not set default $key. " . mysqli_error($conn) . "</div>";
        }
    }
} else{
    echo '<div class="alert alert-danger">ERROR: Could not create table. ' . mysqli_error($conn) . '</div>';
}

// Create logo table
$sql = "CREATE TABLE IF NOT EXISTS logo (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    image_data MEDIUMBLOB NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Table logo created successfully</div>';
} else{
    echo '<div class="alert alert-danger">ERROR: Could not create table. ' . mysqli_error($conn) . '</div>';
}

// Create earnings table
$sql = "CREATE TABLE IF NOT EXISTS earnings (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    type ENUM('parking', 'rental', 'reservation') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    transaction_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
)";

if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Table earnings created successfully</div>';
} else{
    echo '<div class="alert alert-danger">ERROR: Could not create table. ' . mysqli_error($conn) . '</div>';
}

// Create audit_trail table
$sql = "CREATE TABLE IF NOT EXISTS audit_trail (
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

if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Table audit_trail created successfully</div>';
} else{
    echo '<div class="alert alert-danger">ERROR: Could not create table. ' . mysqli_error($conn) . '</div>';
}

// Add new columns to existing tables if they don't exist
$new_columns = [
    'transactions' => [
        ['customer_type', 'VARCHAR(50) NULL'],
        ['is_overnight', 'BOOLEAN NOT NULL DEFAULT 0'],
        ['transaction_type', "ENUM('parking', 'rental', 'reservation') NOT NULL DEFAULT 'parking'"],
        ['rental_start_date', 'DATE NULL'],
        ['rental_end_date', 'DATE NULL'],
        ['rental_rate', 'DECIMAL(10,2) NULL'],
        ['reservation_fee', 'DECIMAL(10,2) NULL'],
        ['is_paid', 'BOOLEAN NOT NULL DEFAULT 1']
    ],
    'parking_spots' => [
        ['customer_type', 'VARCHAR(50) NULL'],
        ['is_overnight', 'BOOLEAN NOT NULL DEFAULT 0'],
        ['reservation_fee', 'DECIMAL(10,2) NULL']
    ]
];

foreach ($new_columns as $table => $columns) {
    foreach ($columns as $column) {
        $column_name = $column[0];
        $column_def = $column[1];
        
        // Check if column exists
        $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column_name'");
        if (!$result || mysqli_num_rows($result) == 0) {
            // Column doesn't exist, add it
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column_name` $column_def";
            if (mysqli_query($conn, $sql)) {
                echo "<div class='alert alert-success'>Added column $column_name to table $table</div>";
            } else {
                echo "<div class='alert alert-danger'>Error adding column $column_name to table $table: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}

echo '<div class="alert alert-info mt-3">Database initialization complete.</div>';

// Add navigation buttons
echo '<div class="mt-4">';
echo '<a href="../system_settings.php?active_tab=database" class="btn btn-primary">Back to System Settings</a>';
echo ' <a href="../parking_management.php" class="btn btn-secondary">Go to Parking Management</a>';
echo '</div>';

echo '</div>'; // card-body
echo '</div>'; // card
echo '</div>'; // container

// Close connection
mysqli_close($conn);

include_once '../includes/footer.php';
?>