<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/db_config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    
    if ($request_id && $username) {
        // Find the user
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result($stmt, $user_id);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
                // Hash the new password
                $new_password = password_hash('password123', PASSWORD_DEFAULT);
                // Update the user's password
                $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                    mysqli_stmt_bind_param($update_stmt, 'si', $new_password, $user_id);
                    if (mysqli_stmt_execute($update_stmt)) {
                        // Update the password_reset_requests table
                        $now = date('Y-m-d H:i:s');
                        $update_req_sql = "UPDATE password_reset_requests SET status = 'approved', processed_date = ?, processed_by = ? WHERE id = ?";
                        $admin_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;
                        if ($update_req_stmt = mysqli_prepare($conn, $update_req_sql)) {
                            mysqli_stmt_bind_param($update_req_stmt, 'sii', $now, $admin_id, $request_id);
                            mysqli_stmt_execute($update_req_stmt);
                            mysqli_stmt_close($update_req_stmt);
                        }
                        $response['success'] = true;
                    } else {
                        $response['message'] = 'Failed to update user password.';
                    }
                    mysqli_stmt_close($update_stmt);
                } else {
                    $response['message'] = 'Failed to prepare password update.';
                }
            } else {
                $response['message'] = 'User not found.';
            }
        } else {
            $response['message'] = 'Failed to prepare user lookup.';
        }
    } else {
        $response['message'] = 'Invalid request.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response); 