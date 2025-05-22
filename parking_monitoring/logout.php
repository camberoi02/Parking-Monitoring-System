<?php
// Initialize the session
session_start();

// Include database configuration
require_once "config/db_config.php";

// Log audit trail for logout if user is logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    logAudit($conn, 'logout', 'users', $_SESSION["id"], 'session', 'logged in', 'logged out');
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to login page
header("location: login.php");
exit;
?> 