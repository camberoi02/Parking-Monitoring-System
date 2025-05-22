<?php
// Start the session before any output
session_start();

$title = "Home - Parking Monitoring System";
include_once 'includes/header.php';
include_once 'includes/navigation.php';
require_once 'config/db_config.php';

// Include parking functions for statistics
if (file_exists('includes/parking_functions.php')) {
    include_once 'includes/parking_functions.php';
}

// Get parking statistics if database exists
$statistics = [
    'total_spots' => 0,
    'available_spots' => 0,
    'occupied_spots' => 0
];

// Check if database exists
$result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
if (mysqli_num_rows($result) > 0) {
    mysqli_select_db($conn, DB_NAME);
    
    // Get statistics if function exists
    if (function_exists('getParkingStatistics')) {
        $statistics = getParkingStatistics($conn);
    }
}

// Calculate occupancy percentage
$occupancy_percentage = $statistics['total_spots'] > 0 ? round(($statistics['occupied_spots'] / $statistics['total_spots']) * 100) : 0;

// Determine color based on occupancy percentage
$progress_color = "success";
if ($occupancy_percentage > 70) {
    $progress_color = "danger";
} elseif ($occupancy_percentage > 50) {
    $progress_color = "warning";
}
?>

<!-- Hero section with background image -->
<div class="position-relative overflow-hidden bg-primary text-white">
    <div class="position-absolute w-100 h-100" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.9), rgba(30, 64, 175, 0.95)); z-index: 1;"></div>
    <div class="container position-relative py-5" style="z-index: 2;">
        <div class="row align-items-center py-4">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="display-4 fw-bold mb-3">Smart Parking Management System</h1>
                <p class="lead opacity-90 mb-4">Streamline your parking operations with our comprehensive monitoring and management solution.</p>
                
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <a href="parking_management.php" class="btn btn-light btn-lg px-4 py-2">
                        <i class="fas fa-car me-2"></i>Manage Parking
                    </a>
                    <a href="system_settings.php" class="btn btn-outline-light btn-lg px-4 py-2">
                        <i class="fas fa-cogs me-2"></i>System Settings
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-light btn-lg px-4 py-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to System
                    </a>
                    <a href="#features" class="btn btn-outline-light btn-lg px-4 py-2">
                        <i class="fas fa-info-circle me-2"></i>Learn More
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Features highlights -->
                <div class="d-flex flex-wrap gap-4 text-white">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-shield-alt fs-4 me-2"></i>
                        <span>Secure Access</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-tachometer-alt fs-4 me-2"></i>
                        <span>Real-time Updates</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-chart-line fs-4 me-2"></i>
                        <span>Analytics</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <!-- Live parking status overview -->
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header bg-white text-dark py-3 text-center border-0">
                        <h5 class="mb-0">Live Parking Status</h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Occupancy progress -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-bold text-dark">Occupancy Rate</h6>
                                <span class="badge bg-<?php echo $progress_color; ?> rounded-pill"><?php echo $occupancy_percentage; ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-<?php echo $progress_color; ?>" 
                                    role="progressbar" 
                                    style="width: <?php echo $occupancy_percentage; ?>%;" 
                                    aria-valuenow="<?php echo $occupancy_percentage; ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100"></div>
                            </div>
                        </div>
                        
                        <!-- Statistics row -->
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="text-center p-3 rounded-3 bg-light">
                                    <div class="mb-2">
                                        <i class="fas fa-car-side fa-2x text-primary"></i>
                                    </div>
                                    <h3 class="mb-0 fw-bold text-dark"><?php echo $statistics['total_spots']; ?></h3>
                                    <p class="text-muted small mb-0">Total Spots</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="text-center p-3 rounded-3 bg-light">
                                    <div class="mb-2">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                    <h3 class="mb-0 fw-bold text-dark"><?php echo $statistics['available_spots']; ?></h3>
                                    <p class="text-muted small mb-0">Available</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 rounded-3 bg-light">
                                    <div class="mb-2">
                                        <i class="fas fa-clock fa-2x text-danger"></i>
                                    </div>
                                    <h3 class="mb-0 fw-bold text-dark"><?php echo $statistics['occupied_spots']; ?></h3>
                                    <p class="text-muted small mb-0">Occupied</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="<?php echo isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true ? 'parking_management.php' : 'login.php'; ?>" 
                               class="btn btn-primary">
                                <?php echo isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true ? 'View Details' : 'Login to View Details'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Wave separator -->
    <div class="position-absolute bottom-0 w-100" style="z-index: 3;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100" preserveAspectRatio="none" style="width: 100%; height: 60px;">
            <path fill="#f8fafc" fill-opacity="1" d="M0,32L80,37.3C160,43,320,53,480,48C640,43,800,21,960,16C1120,11,1280,21,1360,26.7L1440,32L1440,100L1360,100C1280,100,1120,100,960,100C800,100,640,100,480,100C320,100,160,100,80,100L0,100Z"></path>
        </svg>
    </div>
</div>

<!-- Weather API Integration Section -->
<div class="container-fluid py-4 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card border-0 shadow rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="row g-0">
                            <div class="col-md-4 bg-primary text-white p-4">
                                <h3 class="fw-bold mb-3">Local Weather</h3>
                                <p class="mb-4">Current conditions that may affect parking</p>
                                <div class="d-none d-md-block mt-5">
                                    <i class="fas fa-cloud-sun-rain fa-5x opacity-50"></i>
                                </div>
                            </div>
                            <div class="col-md-8 p-4">
                                <div id="weather-container" class="text-center">
                                    <div class="d-flex justify-content-center my-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <p>Loading weather data...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5" id="features">
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="fw-bold">Key Features</h2>
            <p class="text-muted">Streamline your parking operations with these powerful tools</p>
        </div>
    </div>
    
    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
    <!-- Features for logged-in users -->
    <div class="row gx-4 gy-5">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                <div class="card-body position-relative">
                    <div class="feature-icon bg-primary text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                        <i class="fas fa-car-side fa-2x"></i>
                    </div>
                    <h3 class="h4 card-title mt-4">Vehicle Management</h3>
                    <p class="card-text text-muted">Check vehicles in and out of parking spots with real-time tracking and occupancy monitoring.</p>
                    <a href="parking_management.php" class="btn btn-sm btn-outline-primary mt-3">
                        Manage Vehicles
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                <div class="card-body position-relative">
                    <div class="feature-icon bg-success text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <h3 class="h4 card-title mt-4">Billing System</h3>
                    <p class="card-text text-muted">Automatic fee calculation based on entry/exit times, hourly rates, and customizable pricing plans.</p>
                    <a href="system_settings.php?active_tab=reports" class="btn btn-sm btn-outline-success mt-3">
                        View Reports
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                <div class="card-body position-relative">
                    <div class="feature-icon bg-info text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                    <h3 class="h4 card-title mt-4">Analytics</h3>
                    <p class="card-text text-muted">Track usage patterns, view occupancy trends, and optimize your parking space allocation with data-driven insights.</p>
                    <a href="system_settings.php?active_tab=reports" class="btn btn-sm btn-outline-info mt-3">
                        View Analytics
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Features for non-logged-in users -->
    <div class="row gx-4 gy-5">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                <div class="card-body position-relative">
                    <div class="feature-icon bg-primary text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <h3 class="h4 card-title mt-4">Secure Access</h3>
                    <p class="card-text text-muted">Role-based authentication system ensures only authorized personnel can access and manage parking operations.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                <div class="card-body position-relative">
                    <div class="feature-icon bg-success text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                        <i class="fas fa-tachometer-alt fa-2x"></i>
                    </div>
                    <h3 class="h4 card-title mt-4">Real-time Dashboard</h3>
                    <p class="card-text text-muted">Monitor your parking facility with comprehensive real-time updates on spot occupancy and vehicle status.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                <div class="card-body position-relative">
                    <div class="feature-icon bg-info text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                        <i class="fas fa-file-invoice fa-2x"></i>
                    </div>
                    <h3 class="h4 card-title mt-4">Comprehensive Reports</h3>
                    <p class="card-text text-muted">Generate detailed reports on parking usage, revenue, occupancy trends, and other key performance indicators.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call to action section for non-logged-in users -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 bg-primary text-white shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-5 text-center">
                    <h2 class="fw-bold mb-3">Ready to optimize your parking management?</h2>
                    <p class="lead mb-4">Log in to access all features and start managing your parking spaces efficiently.</p>
                    <a href="login.php" class="btn btn-light btn-lg px-5 py-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login Now
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Additional custom styles for the landing page */
.transition-all {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.rounded-4 {
    border-radius: 1rem !important;
}

/* Dark mode adjustments */
[data-theme="dark"] .feature-icon {
    background-color: var(--bg-card-header) !important;
}

[data-theme="dark"] .bg-light {
    background-color: var(--bg-card) !important;
}

[data-theme="dark"] svg path {
    fill: var(--bg-color);
}

/* Weather card dark mode styles */
[data-theme="dark"] .container-fluid.bg-light {
    background-color: var(--bg-card) !important;
}

[data-theme="dark"] .bg-light {
    background-color: var(--bg-card) !important;
}

[data-theme="dark"] .col-md-4.bg-primary {
    background-color: var(--primary-dark) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch weather data
    fetchWeatherData(14.5995, 120.9842);
    
    // Get user's location if they allow it
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            fetchWeatherData(position.coords.latitude, position.coords.longitude);
        }, function(error) {
            console.log('Error getting location:', error);
        });
    }
});

// Function to fetch weather data using our PHP API handler
function fetchWeatherData(lat, lon) {
    // Use our server-side API handler
    const url = `includes/weather_api.php?lat=${lat}&lon=${lon}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Weather data not available');
            }
            return response.json();
        })
        .then(data => {
            displayWeatherData(data);
        })
        .catch(error => {
            console.error('Error fetching weather data:', error);
            // Show error or fallback to demo data
            displayDemoWeatherData();
        });
}

function displayWeatherData(data) {
    const weatherContainer = document.getElementById('weather-container');
    const weatherIcon = `https://openweathermap.org/img/wn/${data.weather[0].icon}@2x.png`;
    
    let weatherClass = 'info';
    let parkingMessage = 'Parking conditions are normal.';
    
    // Determine message based on weather
    if (data.weather[0].main === 'Rain' || data.weather[0].main === 'Thunderstorm') {
        weatherClass = 'warning';
        parkingMessage = 'Caution: Rain may affect parking visibility and conditions.';
    } else if (data.weather[0].main === 'Snow') {
        weatherClass = 'danger';
        parkingMessage = 'Warning: Snowy conditions may impact parking access.';
    } else if (data.main.temp > 35) {
        weatherClass = 'warning';
        parkingMessage = 'Note: Hot weather. Covered parking spots recommended.';
    }
    
    weatherContainer.innerHTML = `
        <div class="text-start">
            <div class="d-flex align-items-center mb-3">
                <img src="${weatherIcon}" alt="${data.weather[0].description}" class="img-fluid me-2" style="width: 64px;">
                <div>
                    <h3 class="mb-0 fw-bold">${Math.round(data.main.temp)}°C</h3>
                    <p class="text-capitalize mb-0">${data.weather[0].description}</p>
                </div>
            </div>
            
            <div class="alert alert-${weatherClass} mb-3">
                <i class="fas fa-info-circle me-2"></i>
                ${parkingMessage}
            </div>
            
            <div class="row mb-0 g-2">
                <div class="col-6">
                    <div class="p-2 rounded bg-light">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-wind text-primary me-2"></i>
                            <div>
                                <p class="mb-0 small">Wind Speed</p>
                                <p class="mb-0 fw-bold">${data.wind.speed} m/s</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded bg-light">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tint text-primary me-2"></i>
                            <div>
                                <p class="mb-0 small">Humidity</p>
                                <p class="mb-0 fw-bold">${data.main.humidity}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end mt-2">
                <small class="text-muted">${data.name}, ${data.sys.country}</small>
            </div>
        </div>
    `;
}

function displayDemoWeatherData() {
    // Fallback demo data if API fails or key isn't set
    const weatherContainer = document.getElementById('weather-container');
    
    weatherContainer.innerHTML = `
        <div class="text-start">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-cloud-sun fa-3x text-primary me-3"></i>
                <div>
                    <h3 class="mb-0 fw-bold">28°C</h3>
                    <p class="text-capitalize mb-0">Partly Cloudy</p>
                </div>
            </div>
            
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                Parking conditions are normal.
            </div>
            
            <div class="row mb-0 g-2">
                <div class="col-6">
                    <div class="p-2 rounded bg-light">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-wind text-primary me-2"></i>
                            <div>
                                <p class="mb-0 small">Wind Speed</p>
                                <p class="mb-0 fw-bold">3.5 m/s</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded bg-light">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tint text-primary me-2"></i>
                            <div>
                                <p class="mb-0 small">Humidity</p>
                                <p class="mb-0 fw-bold">65%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end mt-2">
                <small class="text-muted">Manila, PH</small>
            </div>
        </div>
    `;
}
</script>

<?php
include_once 'includes/footer.php';
?>
