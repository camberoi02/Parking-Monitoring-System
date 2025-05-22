<?php
// Include config file
require_once "config/db_config.php";

// Select database
mysqli_select_db($conn, DB_NAME);

// Execute query to get all users
$result = mysqli_query($conn, "SELECT * FROM users");

// Display all users
echo "Users in the Database:\n";
echo "--------------------\n";
echo "ID\tUsername\tPassword (partial)\tRole\tCreated At\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['id'] . "\t" . 
         $row['username'] . "\t" . 
         substr($row['password'], 0, 15) . "...\t" . 
         $row['role'] . "\t" . 
         $row['created_at'] . "\n";
}
echo "\n";

// Test password verification for the 'cash' user
$cash_user = mysqli_query($conn, "SELECT * FROM users WHERE username = 'cash'");
if (mysqli_num_rows($cash_user) > 0) {
    $user = mysqli_fetch_assoc($cash_user);
    $test_password = 'cash123';
    $hashed_password = $user['password'];
    
    echo "Testing Password Verification for 'cash' User:\n";
    echo "------------------------------------------\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Test password: " . $test_password . "\n";
    echo "Full hash: " . $hashed_password . "\n";
    echo "Hash algorithm: " . (strpos($hashed_password, '$2y$') === 0 ? 'bcrypt (correct)' : 'other (may be incorrect)') . "\n";
    echo "Password verify result: " . (password_verify($test_password, $hashed_password) ? "SUCCESS" : "FAILED") . "\n\n";
    
    // If verification fails, let's check all password algorithms
    if (!password_verify($test_password, $hashed_password)) {
        echo "Testing alternate methods:\n";
        echo "md5: " . (md5($test_password) === $hashed_password ? "SUCCESS" : "FAILED") . "\n";
        echo "sha1: " . (sha1($test_password) === $hashed_password ? "SUCCESS" : "FAILED") . "\n";
        echo "Direct comparison: " . ($test_password === $hashed_password ? "SUCCESS" : "FAILED") . "\n";
    }
} else {
    echo "User 'cash' not found in the database\n";
}

// Close connection
mysqli_close($conn);
?> 