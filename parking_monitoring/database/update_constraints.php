<?php
require_once '../config/db_config.php';

// Select the database
mysqli_select_db($conn, DB_NAME);

// First, drop the existing foreign key constraint
$sql = "ALTER TABLE transactions DROP FOREIGN KEY transactions_ibfk_1";
if(mysqli_query($conn, $sql)){
    echo "Dropped old foreign key constraint successfully<br>";
} else {
    echo "Error dropping foreign key constraint: " . mysqli_error($conn) . "<br>";
}

// Change spot_id to allow NULL
$sql = "ALTER TABLE transactions MODIFY spot_id INT NULL";
if(mysqli_query($conn, $sql)){
    echo "Modified spot_id column to allow NULL successfully<br>";
} else {
    echo "Error modifying spot_id column: " . mysqli_error($conn) . "<br>";
}

// Add the new foreign key constraint with ON DELETE SET NULL
$sql = "ALTER TABLE transactions ADD CONSTRAINT transactions_ibfk_1 
        FOREIGN KEY (spot_id) REFERENCES parking_spots(id) ON DELETE SET NULL";
if(mysqli_query($conn, $sql)){
    echo "Added new foreign key constraint with ON DELETE SET NULL successfully<br>";
} else {
    echo "Error adding new foreign key constraint: " . mysqli_error($conn) . "<br>";
}

// Also update the earnings table constraint
$sql = "ALTER TABLE earnings DROP FOREIGN KEY earnings_ibfk_1";
if(mysqli_query($conn, $sql)){
    echo "Dropped old earnings foreign key constraint successfully<br>";
} else {
    echo "Error dropping earnings foreign key constraint: " . mysqli_error($conn) . "<br>";
}

$sql = "ALTER TABLE earnings ADD CONSTRAINT earnings_ibfk_1 
        FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL";
if(mysqli_query($conn, $sql)){
    echo "Added new earnings foreign key constraint with ON DELETE SET NULL successfully<br>";
} else {
    echo "Error adding new earnings foreign key constraint: " . mysqli_error($conn) . "<br>";
}

echo "Database update complete!";
?> 