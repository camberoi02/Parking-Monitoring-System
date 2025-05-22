<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
    require_once '../../config/db_config.php';
    
    // Validate passwords
    if ($_POST['new_password'] !== $_POST['confirm_password']) {
        $_SESSION['error'] = "Passwords do not match.";
        header("location: ../../login.php");
        exit;
    }
    
    if (strlen($_POST['new_password']) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header("location: ../../login.php");
        exit;
    }
    
    // Hash the new password
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $user_id = $_SESSION["id"];
    
    // Update password and first_login status
    $sql = "UPDATE users SET password = ?, first_login = FALSE WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $new_password, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the password change
            logAudit($conn, 'update', 'users', $user_id, 'password', 'old_password', 'password_changed');
            logAudit($conn, 'update', 'users', $user_id, 'first_login', 'TRUE', 'FALSE');
            
            // Redirect to index page
            header("location: ../../index.php");
            exit;
        } else {
            $_SESSION['error'] = "Error updating password.";
            header("location: ../../login.php");
            exit;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
} else {
    header("location: ../../login.php");
    exit;
}
?> 