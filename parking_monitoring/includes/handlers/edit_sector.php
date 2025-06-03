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
    
    if (isset($_POST['sector_id']) && isset($_POST['sector_name'])) {
        $sector_id = mysqli_real_escape_string($conn, $_POST['sector_id']);
        $sector_name = mysqli_real_escape_string($conn, $_POST['sector_name']);
        $sector_description = mysqli_real_escape_string($conn, $_POST['sector_description']);
        
        $sql = "UPDATE sectors SET name = '$sector_name', description = '$sector_description' WHERE id = $sector_id";
        if (mysqli_query($conn, $sql)) {
            logAudit($conn, 'update', 'sectors', $sector_id, 'name', null, $sector_name);
            logAudit($conn, 'update', 'sectors', $sector_id, 'sector_management', null, 
                    "Sector updated: Name: $sector_name, Description: $sector_description (Changed by: Admin)");
            $response['success'] = true;
            $response['message'] = "Sector updated successfully";
            $response['sector'] = [
                'id' => $sector_id,
                'name' => $sector_name,
                'description' => $sector_description
            ];
        } else {
            $response['message'] = "Error updating sector: " . mysqli_error($conn);
        }
    } else {
        $response['message'] = "Missing required fields";
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']); 