<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once "../../config/db_config.php";

// Select the database
mysqli_select_db($conn, DB_NAME);

// Set header to return JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

// Check if the request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username from POST data
    $username = trim($_POST["username"]);

    // Validate username
    if (empty($username)) {
        $response['message'] = "Please enter your username.";
    } else {
        try {
            // Prepare a select statement
            $sql = "SELECT id, username FROM users WHERE username = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                // Bind variables to the prepared statement
                mysqli_stmt_bind_param($stmt, "s", $username);
                
                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {
                    // Store result
                    mysqli_stmt_store_result($stmt);
                    
                    // Check if username exists
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        // Bind result variables
                        mysqli_stmt_bind_result($stmt, $id, $username);
                        if (mysqli_stmt_fetch($stmt)) {
                            // Log the password reset request
                            $log_sql = "INSERT INTO password_reset_requests (user_id, username, request_date, status) VALUES (?, ?, NOW(), 'pending')";
                            if ($log_stmt = mysqli_prepare($conn, $log_sql)) {
                                mysqli_stmt_bind_param($log_stmt, "is", $id, $username);
                                mysqli_stmt_execute($log_stmt);
                                mysqli_stmt_close($log_stmt);
                                
                                $response['success'] = true;
                                $response['message'] = "Password reset request has been sent to the administrator.";
                            }
                        }
                    } else {
                        $response['message'] = "Username not found.";
                    }
                } else {
                    $response['message'] = "Oops! Something went wrong. Please try again later.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            }
        } catch (Exception $e) {
            error_log("Forgot Password Error: " . $e->getMessage());
            $response['message'] = "An error occurred. Please try again later.";
        }
    }
} else {
    $response['message'] = "Invalid request method.";
}

// Close connection
mysqli_close($conn);

// Return JSON response
echo json_encode($response);
?> 