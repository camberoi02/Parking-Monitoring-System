<?php
session_start();
require_once "../../config/db_config.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../login.php");
    exit;
}

// Check if skip button was clicked
if(isset($_POST['skip'])) {
    $user_id = $_SESSION["id"];
    
    // Update first_login status
    $sql = "UPDATE users SET first_login = FALSE WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            // Log the action
            logAudit($conn, 'update', 'users', $user_id, 'first_login', 'true', 'false');
            
            // Redirect to home page
            header("location: ../../index.php");
            exit;
        } else {
            $_SESSION['error'] = "Error updating first login status.";
            header("location: ../../login.php");
            exit;
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Database error occurred.";
        header("location: ../../login.php");
        exit;
    }
} else {
    // If no skip parameter, redirect back to login
    header("location: ../../login.php");
    exit;
}
?> 