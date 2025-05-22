<?php
// Include config file
require_once "config/db_config.php";

// Select database
mysqli_select_db($conn, DB_NAME);

echo "=================================================\n";
echo "DIAGNOSTIC TOOL FOR USER LOGIN ISSUES\n";
echo "=================================================\n\n";

// Step 1: Check if database is accessible
echo "Step 1: Database Connection Check\n";
if ($conn) {
    echo "✓ Database connection successful\n\n";
} else {
    echo "✗ Database connection failed: " . mysqli_connect_error() . "\n\n";
    exit();
}

// Step 2: Check if users table exists
echo "Step 2: Users Table Check\n";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($result) > 0) {
    echo "✓ Users table exists\n\n";
} else {
    echo "✗ Users table does not exist\n\n";
    exit();
}

// Step 3: Display all current users
echo "Step 3: Current Users in Database\n";
$result = mysqli_query($conn, "SELECT id, username, role, LEFT(password, 10) as password_start FROM users");
if (mysqli_num_rows($result) > 0) {
    echo "Found " . mysqli_num_rows($result) . " users:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- ID: " . $row['id'] . ", Username: " . $row['username'] . ", Role: " . $row['role'] . 
             ", Password starts with: " . $row['password_start'] . "...\n";
    }
    echo "\n";
} else {
    echo "✗ No users found in the database\n\n";
}

// Step 4: Delete the 'cashier' user if it exists
echo "Step 4: Removing existing 'cashier' user if it exists\n";
$result = mysqli_query($conn, "DELETE FROM users WHERE username = 'cashier'");
if (mysqli_affected_rows($conn) > 0) {
    echo "✓ Removed existing 'cashier' user\n\n";
} else {
    echo "✓ No existing 'cashier' user found\n\n";
}

// Step 5: Create new test user
echo "Step 5: Creating Test User\n";
$test_username = 'cashier';
$test_password = 'cashier123';
$test_role = 'operator';

// Hash the password using PASSWORD_DEFAULT
$password_hash = password_hash($test_password, PASSWORD_DEFAULT);

// Insert the user
$sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "sss", $test_username, $password_hash, $test_role);
    
    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);
        echo "✓ Test user created successfully\n";
        echo "  Username: $test_username\n";
        echo "  Password: $test_password\n";
        echo "  Role: $test_role\n";
        echo "  Generated hash: $password_hash\n\n";
    } else {
        echo "✗ Failed to create test user: " . mysqli_error($conn) . "\n\n";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "✗ Error preparing statement: " . mysqli_error($conn) . "\n\n";
}

// Step 6: Verify the user was added correctly
echo "Step 6: Verifying Test User\n";
if ($result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$test_username'")) {
    if ($row = mysqli_fetch_assoc($result)) {
        echo "✓ User found in database\n";
        echo "  Username: " . $row['username'] . "\n";
        echo "  Stored Hash: " . $row['password'] . "\n\n";
    } else {
        echo "✗ User not found in database\n\n";
    }
} else {
    echo "✗ Error querying database: " . mysqli_error($conn) . "\n\n";
}

// Step 7: Test the login functionality
echo "Step 7: Testing Login Functionality\n";
// Get the stored hash for the test user
$result = mysqli_query($conn, "SELECT password FROM users WHERE username = '$test_username'");
$row = mysqli_fetch_assoc($result);
$stored_hash = $row['password'];

// Test password_verify function
if (password_verify($test_password, $stored_hash)) {
    echo "✓ Password verification works correctly\n";
    echo "  Test password '$test_password' is verified against the stored hash\n\n";
} else {
    echo "✗ Password verification failed\n";
    echo "  Test password '$test_password' failed to verify against the stored hash\n\n";
}

// Step 8: For the 'cash' user, if it exists
echo "Step 8: Checking 'cash' User Status\n";
if ($result = mysqli_query($conn, "SELECT * FROM users WHERE username = 'cash'")) {
    if ($row = mysqli_fetch_assoc($result)) {
        echo "✓ 'cash' user found in database\n";
        echo "  Username: " . $row['username'] . "\n";
        echo "  Stored Hash: " . $row['password'] . "\n";
        
        // Test password verification for 'cash' user
        if (password_verify('cash123', $row['password'])) {
            echo "✓ Password 'cash123' verifies correctly\n\n";
        } else {
            echo "✗ Password 'cash123' verification failed\n";
            echo "  Let's reset the password to fix the issue\n";
            
            // Reset the password for 'cash' user
            $new_hash = password_hash('cash123', PASSWORD_DEFAULT);
            if (mysqli_query($conn, "UPDATE users SET password = '$new_hash' WHERE username = 'cash'")) {
                echo "✓ Password for 'cash' user has been reset\n";
                echo "  New hash: $new_hash\n\n";
            } else {
                echo "✗ Failed to reset password: " . mysqli_error($conn) . "\n\n";
            }
        }
    } else {
        echo "✗ 'cash' user not found in database\n\n";
    }
} else {
    echo "✗ Error querying database: " . mysqli_error($conn) . "\n\n";
}

echo "=================================================\n";
echo "DIAGNOSTIC COMPLETE\n";
echo "=================================================\n";
echo "You can now attempt to log in with:\n";
echo "- Username: cashier\n";
echo "- Password: cashier123\n";
if ($result = mysqli_query($conn, "SELECT * FROM users WHERE username = 'cash'")) {
    if (mysqli_num_rows($result) > 0) {
        echo "Or try the 'cash' user with password 'cash123'\n";
    }
}
echo "=================================================\n";

// Close connection
mysqli_close($conn);
?> 