<?php
// Include authentication check
include_once 'includes/auth_session.php';

$title = "Parking Management";
include_once 'includes/header.php';
include_once 'includes/navigation.php';
require_once 'config/db_config.php';
require_once 'includes/parking_functions.php';
require_once 'includes/parking_ui.php';

// Set default timezone to match your MySQL server timezone
date_default_timezone_set('Asia/Manila'); // Change to your local timezone

// Check if database exists
$result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
$database_exists = mysqli_num_rows($result) > 0;

if (!$database_exists) {
    echo '<div class="container mt-4"><div class="alert alert-warning">Database doesn\'t exist yet. Please <a href="system_settings.php">initialize the database</a> first.</div></div>';
    include_once 'includes/footer.php';
    exit;
}

mysqli_select_db($conn, DB_NAME);

// Process form submissions
$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'check_in':
                $result = checkInVehicle($conn, $_POST['spot_id'], $_POST['vehicle_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'check_out':
                $result = checkOutVehicle($conn, $_POST['spot_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'rent_spot':
                $result = rentParkingSpot($conn, $_POST['spot_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'end_rental':
                $result = endParkingSpotRental($conn, $_POST['spot_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'reserve_spot':
                $result = reserveParkingSpot($conn, $_POST['spot_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'cancel_reservation':
                $result = cancelReservation($conn, $_POST['spot_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get parking statistics and spots
$statistics = getParkingStatistics($conn);
$parking_spots = getAllParkingSpots($conn);
?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="fas fa-parking me-3 text-primary"></i>Parking Management</h1>
        </div>
        <div class="col-md-6">
            <div class="d-flex justify-content-md-end align-items-center flex-wrap">
                <div class="input-group me-2 mb-2 mb-md-0" style="max-width: 300px;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" id="spotSearch" class="form-control border-start-0 search-input" 
                           data-search-target="spotsContainer" placeholder="Search spots...">
                </div>
                
                <!-- Removed filters from here -->
            </div>
        </div>
    </div>
    
    <!-- Status Cards -->
    <?php echo generateStatusCardsHTML($statistics); ?>
    
    <!-- Parking Spots Section -->
    <?php echo generateParkingSpotsHTML($conn, $parking_spots); ?>
</div>

<!-- Modals -->
<?php echo generateModalsHTML(); ?>
<?php include_once 'includes/modals/action_selection_modal.php'; ?>

<!-- Add notification triggers for PHP messages -->
<?php if (!empty($message)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showToast('<?php echo addslashes($message); ?>', 'success');
    });
</script>
<?php endif; ?>

<?php if (!empty($error)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showToast('<?php echo addslashes($error); ?>', 'danger');
    });
</script>
<?php endif; ?>

<!-- Include JavaScript -->
<script src="assets/js/parking.js"></script>

<?php
include_once 'includes/footer.php';
?>
