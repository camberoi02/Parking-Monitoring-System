<?php
$title = "Drop Database";
include_once '../includes/header.php';

require_once '../config/db_config.php';

// Add Bootstrap container
echo '<div class="container mt-4">';
echo '<div class="card border-danger">';
echo '<div class="card-header bg-danger text-white"><h3>Database Deletion</h3></div>';
echo '<div class="card-body">';

// Drop database if it exists
$sql = "DROP DATABASE IF EXISTS " . DB_NAME;
if(mysqli_query($conn, $sql)){
    echo '<div class="alert alert-success">Database dropped successfully</div>';
    echo '<div class="alert alert-warning"><strong>Note:</strong> All data has been deleted. You will need to initialize the database again to use the system.</div>';
} else{
    echo '<div class="alert alert-danger">ERROR: Could not drop database. ' . mysqli_error($conn) . '</div>';
}

// Add navigation buttons
echo '<div class="mt-4">';
echo '<a href="../system_settings.php?active_tab=database" class="btn btn-primary">Back to System Settings</a>';
echo ' <a href="../database/init_db.php" class="btn btn-success">Initialize Database</a>';
echo '</div>';

echo '</div>'; // card-body
echo '</div>'; // card
echo '</div>'; // container

// Close connection
mysqli_close($conn);

include_once '../includes/footer.php';
?>
