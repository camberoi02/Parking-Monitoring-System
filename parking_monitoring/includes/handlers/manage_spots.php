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

    switch ($_POST['action']) {
        case 'add_spot':
            if (!isset($_POST['sector_id'])) {
                $response['message'] = 'Please select a specific sector for the new parking spot.';
                break;
            }
            
            $sector_id = mysqli_real_escape_string($conn, $_POST['sector_id']);
            
            // Auto-generate spot number
            $spot_number = getNextSpotNumber($conn, $sector_id);
            
            // Check if spot number already exists
            $check_sql = "SELECT id FROM parking_spots WHERE spot_number = '$spot_number'";
            $check_result = mysqli_query($conn, $check_sql);
            
            if (mysqli_num_rows($check_result) > 0) {
                // If duplicate found, try to find the next available number
                $base_letter = substr($spot_number, 0, strpos($spot_number, '-'));
                $number = intval(substr($spot_number, strpos($spot_number, '-') + 1));
                $found_available = false;
                
                // Try up to 100 next numbers
                for ($i = $number + 1; $i <= $number + 100; $i++) {
                    $try_spot_number = $base_letter . "-A" . $i;
                    $check_sql = "SELECT id FROM parking_spots WHERE spot_number = '$try_spot_number'";
                    $check_result = mysqli_query($conn, $check_sql);
                    
                    if (mysqli_num_rows($check_result) == 0) {
                        $spot_number = $try_spot_number;
                        $found_available = true;
                        break;
                    }
                }
                
                if (!$found_available) {
                    $response['message'] = 'Could not find an available spot number. Please try a different sector.';
                    break;
                }
            }
            
            // Insert the new spot
            $sql = "INSERT INTO parking_spots (spot_number, is_occupied, sector_id) VALUES ('$spot_number', 0, $sector_id)";
            if (mysqli_query($conn, $sql)) {
                $spot_id = mysqli_insert_id($conn);
                
                // Get sector name
                $sector_name = "Unknown";
                $sector_query = "SELECT name FROM sectors WHERE id = $sector_id";
                $sector_result = mysqli_query($conn, $sector_query);
                if ($sector_result && $row = mysqli_fetch_assoc($sector_result)) {
                    $sector_name = $row['name'];
                }
                
                // Log to audit trail
                logAudit($conn, 'insert', 'parking_spots', $spot_id, null, null, "Parking spot $spot_number added");
                logAudit($conn, 'insert', 'parking_spots', $spot_id, 'spots_management', null, 
                        "New parking spot created: $spot_number in sector: $sector_name (Created by: Admin)");
                
                $response['success'] = true;
                $response['message'] = "Parking spot $spot_number added successfully.";
                $response['spot'] = [
                    'id' => $spot_id,
                    'spot_number' => $spot_number,
                    'sector_name' => $sector_name
                ];
            } else {
                $response['message'] = "Error adding parking spot: " . mysqli_error($conn);
            }
            break;

        case 'delete_spot':
            if (!isset($_POST['spot_id']) || !is_numeric($_POST['spot_id'])) {
                $response['message'] = "Error: Invalid spot ID provided.";
                break;
            }

            $spot_id = intval($_POST['spot_id']);
            
            // Check if spot exists and get its details
            $check_sql = "SELECT spot_number, is_occupied, 
                                 COALESCE((SELECT 1 FROM transactions WHERE spot_id = parking_spots.id AND exit_time IS NULL LIMIT 1), 0) as is_in_use 
                          FROM parking_spots 
                          WHERE id = ?";
                          
            $stmt = mysqli_prepare($conn, $check_sql);
            if (!$stmt) {
                $response['message'] = "Database error: " . mysqli_error($conn);
                break;
            }
            
            mysqli_stmt_bind_param($stmt, "i", $spot_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (!$result) {
                $response['message'] = "Database error: " . mysqli_error($conn);
                mysqli_stmt_close($stmt);
                break;
            }
            
            if ($row = mysqli_fetch_assoc($result)) {
                $spot_number = $row['spot_number'];
                
                if ($row['is_occupied'] || $row['is_in_use']) {
                    $response['message'] = "Cannot delete an occupied or in-use parking spot. Please check out the vehicle first.";
                    mysqli_stmt_close($stmt);
                    break;
                }
                
                // Check if there are any transactions
                $check_transactions = "SELECT COUNT(*) as count FROM transactions WHERE spot_id = ?";
                $stmt_trans = mysqli_prepare($conn, $check_transactions);
                if (!$stmt_trans) {
                    $response['message'] = "Database error: " . mysqli_error($conn);
                    mysqli_stmt_close($stmt);
                    break;
                }
                
                mysqli_stmt_bind_param($stmt_trans, "i", $spot_id);
                mysqli_stmt_execute($stmt_trans);
                $result_trans = mysqli_stmt_get_result($stmt_trans);
                $has_transactions = false;
                
                if ($result_trans && $row_trans = mysqli_fetch_assoc($result_trans)) {
                    $has_transactions = $row_trans['count'] > 0;
                }
                mysqli_stmt_close($stmt_trans);
                
                // Delete transactions if they exist and confirmation is given
                if ($has_transactions && isset($_POST['confirm_delete_with_history']) && $_POST['confirm_delete_with_history'] == '1') {
                    $sql = "DELETE FROM transactions WHERE spot_id = ?";
                    $stmt_del_trans = mysqli_prepare($conn, $sql);
                    if (!$stmt_del_trans) {
                        $response['message'] = "Error preparing transaction deletion: " . mysqli_error($conn);
                        mysqli_stmt_close($stmt);
                        break;
                    }
                    
                    mysqli_stmt_bind_param($stmt_del_trans, "i", $spot_id);
                    if (!mysqli_stmt_execute($stmt_del_trans)) {
                        $response['message'] = "Error deleting transactions: " . mysqli_error($conn);
                        mysqli_stmt_close($stmt_del_trans);
                        mysqli_stmt_close($stmt);
                        break;
                    }
                    mysqli_stmt_close($stmt_del_trans);
                    
                    logAudit($conn, 'delete', 'transactions', null, null, null, "All transactions deleted for spot $spot_id");
                } else if ($has_transactions && (!isset($_POST['confirm_delete_with_history']) || $_POST['confirm_delete_with_history'] != '1')) {
                    $response['message'] = "This spot has transaction history. Please confirm deletion of transaction history to proceed.";
                    mysqli_stmt_close($stmt);
                    break;
                }
                
                // Delete the spot
                $sql = "DELETE FROM parking_spots WHERE id = ? AND is_occupied = 0";
                $stmt_del = mysqli_prepare($conn, $sql);
                if (!$stmt_del) {
                    $response['message'] = "Error preparing spot deletion: " . mysqli_error($conn);
                    mysqli_stmt_close($stmt);
                    break;
                }
                
                mysqli_stmt_bind_param($stmt_del, "i", $spot_id);
                if (mysqli_stmt_execute($stmt_del)) {
                    if (mysqli_stmt_affected_rows($stmt_del) > 0) {
                        logAudit($conn, 'delete', 'parking_spots', $spot_id, null, null, "Parking spot deleted");
                        logAudit($conn, 'delete', 'parking_spots', $spot_id, 'spots_management', 
                                "Parking spot $spot_number existed", 
                                "Parking spot $spot_number deleted" . ($has_transactions ? " including transaction history" : "") . 
                                " (Deleted by: Admin)");
                        
                        $response['success'] = true;
                        $response['message'] = "Parking spot deleted successfully" . ($has_transactions ? " (including its transaction history)" : "");
                    } else {
                        $response['message'] = "Spot could not be deleted. It may be occupied or already deleted.";
                    }
                } else {
                    $response['message'] = "Error deleting parking spot: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt_del);
            } else {
                $response['message'] = "Error: Parking spot not found.";
            }
            mysqli_stmt_close($stmt);
            break;

        default:
            $response['message'] = 'Invalid action.';
            break;
    }

    echo json_encode($response);
    exit;
} 