<?php
require_once 'config/db_config.php';
require_once 'includes/parking_functions.php';

header('Content-Type: application/json');

// Get parameters from request
$vehicle_type = $_GET['vehicle_type'] ?? 'Vehicle';
$customer_type = $_GET['customer_type'] ?? 'private';

// Get overnight fee from settings based on vehicle type
$overnight_fee = getVehicleOvernightFee($conn, $vehicle_type);

// Return response
echo json_encode([
    'success' => true,
    'fee' => $overnight_fee,
    'vehicle_type' => $vehicle_type,
    'customer_type' => $customer_type
]);
?> 