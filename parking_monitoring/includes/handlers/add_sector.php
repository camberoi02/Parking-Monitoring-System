<?php
require_once '../auth_session.php';
require_once '../../config/db_config.php';
require_once '../parking_functions.php';

// Check if user is admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = ['success' => false, 'message' => ''];
    
    $sector_name = trim(mysqli_real_escape_string($conn, $_POST['sector_name']));
    $sector_description = trim(mysqli_real_escape_string($conn, $_POST['sector_description']));
    
    // Validate that name is not empty
    if (empty($sector_name)) {
        $response['message'] = "Parking Area name is required.";
        echo json_encode($response);
        exit;
    }
    
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
        logAudit($conn, 'insert', 'sectors', $sector_id, null, null, "Parking Area $sector_name added");
        logAudit($conn, 'insert', 'sectors', $sector_id, 'sector_management', null, 
                "New parking area created: $sector_name with description: '$sector_description' (Created by: Admin)");
        $response['success'] = true;
        $response['message'] = "Parking Area added successfully.";
        $response['sector'] = [
            'id' => $sector_id,
            'name' => $sector_name,
            'description' => $sector_description
        ];
    } else {
        $response['message'] = "Error adding parking area: " . mysqli_error($conn);
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']); 