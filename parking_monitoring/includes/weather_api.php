<?php
// Weather API handler
header('Content-Type: application/json');

// API key - should be stored in a more secure way in production
$api_key = 'YOUR_OPENWEATHERMAP_API_KEY';

// Get parameters from request
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 14.5995; // Default to Philippines
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : 120.9842;

// Validate parameters
if (!is_numeric($lat) || !is_numeric($lon)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

// Build API URL
$url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid={$api_key}";

// Initialize cURL session
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

// Execute cURL request
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if ($status !== 200) {
    // Handle API errors
    http_response_code(502);
    echo json_encode([
        'error' => 'Weather service unavailable',
        'status' => $status
    ]);
    exit;
}

// Close cURL
curl_close($ch);

// Output API response
echo $response;
?> 