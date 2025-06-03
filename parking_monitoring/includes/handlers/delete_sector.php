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
    
    if (isset($_POST['sector_id'])) {
        $sector_id = mysqli_real_escape_string($conn, $_POST['sector_id']);
        
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
            $response['message'] = "Cannot delete parking area because it contains $spots_count parking spot(s). Please reassign or delete those spots first.";
        } else {
            $sql = "DELETE FROM sectors WHERE id = $sector_id";
            if (mysqli_query($conn, $sql)) {
                logAudit($conn, 'delete', 'sectors', $sector_id, null, null, "Parking Area deleted");
                logAudit($conn, 'delete', 'sectors', $sector_id, 'sector_management', 
                        "Parking Area existed", 
                        "Parking Area $sector_name deleted (Deleted by: Admin)");
                $response['success'] = true;
                $response['message'] = "Parking Area deleted successfully";
            } else {
                $response['message'] = "Error deleting parking area: " . mysqli_error($conn);
            }
        }
    } else {
        $response['message'] = "Sector ID not provided";
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']); 