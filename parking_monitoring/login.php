<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to index page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

$title = "Login - Parking Monitoring System";
include_once 'includes/header.php';

// Include config file
require_once "config/db_config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    try {
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
        } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
        } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
            $sql = "SELECT id, username, password, role, first_login FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                        mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role, $first_login);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                                $_SESSION["first_login"] = $first_login;
                            
                            // Log audit trail for login
                            logAudit($conn, 'login', 'users', $id, 'session', 'logged out', 'logged in');
                            
                                // Check if this is first login
                                if ($first_login) {
                                    // Show password change modal directly
                                    echo "<script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            var changePasswordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
                                            changePasswordModal.show();
                                        });
                                    </script>";
                                } else {
                                    // Redirect to home page
                            header("location: index.php");
                                    exit;
                                }
                            } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                    } else {
                    $login_err = "Invalid username or password.";
                }
                } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
        }
    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        $login_err = "An error occurred. Please try again later.";
    }
}
?>

<div class="login-page">
    <!-- Background decorative elements -->
    <div class="bg-shapes">
        <div class="shape-1"></div>
        <div class="shape-2"></div>
        <div class="shape-3"></div>
        <div class="shape-4"></div>
        <div class="shape-5"></div>
        <div class="shape-6"></div>
    </div>
    
    <!-- Animated parking icons in background -->
    <div class="bg-icons">
        <i class="fas fa-parking"></i>
        <i class="fas fa-car"></i>
        <i class="fas fa-ticket-alt"></i>
        <i class="fas fa-map-marker-alt"></i>
        <i class="fas fa-clock"></i>
        <i class="fas fa-chart-line"></i>
    </div>
    
    <div class="login-wrapper">
        <div class="container">
            <div class="row min-vh-100 align-items-center justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <!-- Login form card with modern design -->
                    <div class="card auth-card border-0 shadow-lg">
                        <!-- Left decorative element -->
                        <div class="auth-card-decoration"></div>

                        <!-- Card header with logo and title -->
                        <div class="card-header bg-transparent border-0 pt-5 text-center">
                            <div class="logo-circle mb-3 mx-auto">
                                <i class="fas fa-parking fa-2x"></i>
                            </div>
                            <h3 class="auth-title fw-bold mb-1">Welcome Back</h3>
                            <p class="text-muted">Sign in to your account to continue</p>
                            <a href="index.php" class="back-link">
                                <i class="fas fa-arrow-left"></i> Back to Homepage
                            </a>
                        </div>

                        <!-- Card body with login form -->
                        <div class="card-body px-4 px-lg-5 pb-5">
                            <?php
                            // Display error message if any
                            if(!empty($login_err)){
                                echo '<div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i class="fas fa-exclamation-circle flex-shrink-0 me-2"></i>
                                        <div>' . $login_err . '</div>
                                      </div>';
                            }        

                            // Display session error if any
                            if(isset($_SESSION['error'])) {
                                echo '<div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i class="fas fa-exclamation-circle flex-shrink-0 me-2"></i>
                                        <div>' . $_SESSION['error'] . '</div>
                                      </div>';
                                unset($_SESSION['error']); // Clear the error message
                            }
                            ?>
                            
                            <form id="loginForm" method="post" class="login-form">
                                <!-- Username field with icon -->
                                <div class="form-group mb-4">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-user text-primary"></i>
                                        </span>
                                        <input type="text" name="username" class="form-control border-start-0 <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                            id="username" placeholder="Enter your username" value="<?php echo $username; ?>">
                                    </div>
                                    <div class="invalid-feedback d-block"><?php echo $username_err; ?></div>
                                </div>
                                
                                <!-- Password field with icon and show/hide toggle -->
                                <div class="form-group mb-4">
                                    <div class="d-flex justify-content-between">
                                        <label for="password" class="form-label">Password</label>
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-lock text-primary"></i>
                                        </span>
                                        <input type="password" name="password" class="form-control border-start-0 border-end-0 <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                            id="password" placeholder="Enter your password">
                                        <span class="input-group-text bg-light border-start-0" id="togglePassword" style="cursor: pointer">
                                            <i class="fas fa-eye text-muted"></i>
                                        </span>
                                    </div>
                                    <div class="invalid-feedback d-block"><?php echo $password_err; ?></div>
                                </div>
                                
                                <!-- Remember me checkbox -->
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" value="" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">
                                        Remember me
                                    </label>
                                </div>
                                
                                <!-- Submit button -->
                                <div class="d-grid gap-2 mb-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add custom styles for the login page -->
<style>
.login-page {
    position: relative;
    min-height: 100vh;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.1) 100%);
    overflow: hidden;
}

/* Background shapes */
.bg-shapes div {
    position: absolute;
    border-radius: 50%;
    opacity: 0.2;
    filter: blur(10px);
    animation: float 15s infinite ease-in-out;
}

.shape-1 {
    width: 300px;
    height: 300px;
    background: var(--primary-light);
    top: -100px;
    right: -50px;
    animation-delay: 0s !important;
}

.shape-2 {
    width: 200px;
    height: 200px;
    background: var(--primary-color);
    bottom: 10%;
    left: -100px;
    animation-delay: -2s !important;
}

.shape-3 {
    width: 120px;
    height: 120px;
    background: var(--primary-color);
    top: 30%;
    right: 10%;
    animation-delay: -4s !important;
}

.shape-4 {
    width: 80px;
    height: 80px;
    background: var(--primary-light);
    bottom: 30%;
    right: 15%;
    animation-delay: -6s !important;
}

.shape-5 {
    width: 150px;
    height: 150px;
    background: var(--success-color);
    top: 60%;
    left: 15%;
    animation-delay: -8s !important;
}

.shape-6 {
    width: 100px;
    height: 100px;
    background: var(--primary-light);
    top: 10%;
    left: 10%;
    animation-delay: -10s !important;
}

/* Animation for floating shapes */
@keyframes float {
    0% {
        transform: translatey(0px) scale(1);
    }
    50% {
        transform: translatey(-20px) scale(1.05);
    }
    100% {
        transform: translatey(0px) scale(1);
    }
}

/* Background icons */
.bg-icons {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: 0;
    overflow: hidden;
}

.bg-icons i {
    position: absolute;
    color: var(--primary-color);
    opacity: 0.05;
    font-size: 2rem;
    animation: iconFloat 20s infinite linear;
}

.bg-icons i:nth-child(1) {
    top: 10%;
    left: 20%;
    font-size: 4rem;
    animation-duration: 30s;
}

.bg-icons i:nth-child(2) {
    top: 25%;
    left: 80%;
    font-size: 2.5rem;
    animation-duration: 25s;
    animation-delay: -5s;
}

.bg-icons i:nth-child(3) {
    top: 60%;
    left: 30%;
    font-size: 3rem;
    animation-duration: 35s;
    animation-delay: -10s;
}

.bg-icons i:nth-child(4) {
    top: 70%;
    left: 70%;
    font-size: 3.5rem;
    animation-duration: 28s;
    animation-delay: -7s;
}

.bg-icons i:nth-child(5) {
    top: 40%;
    left: 5%;
    font-size: 2.8rem;
    animation-duration: 32s;
    animation-delay: -15s;
}

.bg-icons i:nth-child(6) {
    top: 80%;
    left: 40%;
    font-size: 3.2rem;
    animation-duration: 26s;
    animation-delay: -12s;
}

/* Animation for floating icons */
@keyframes iconFloat {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }
    25% {
        transform: translate(10px, 15px) rotate(5deg);
    }
    50% {
        transform: translate(20px, 0) rotate(10deg);
    }
    75% {
        transform: translate(10px, -15px) rotate(5deg);
    }
    100% {
        transform: translate(0, 0) rotate(0deg);
    }
}

/* Geometric pattern */
.login-page::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        linear-gradient(var(--primary-color) 1px, transparent 1px),
        linear-gradient(90deg, var(--primary-color) 1px, transparent 1px);
    background-size: 40px 40px;
    background-position: center center;
    opacity: 0.03;
    z-index: 0;
}

.login-wrapper {
    position: relative;
    z-index: 10;
}

.auth-card {
    border-radius: 16px;
    overflow: hidden;
    position: relative;
    z-index: 1;
    backdrop-filter: blur(10px);
    background-color: rgba(255, 255, 255, 0.98);
    transition: all 0.3s ease;
    box-shadow: 0 15px 35px rgba(var(--bs-primary-rgb), 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
}

.auth-card-decoration {
    position: absolute;
    top: -50px;
    left: -50px;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: linear-gradient(45deg, var(--primary-color), var(--primary-light));
    opacity: 0.8;
    z-index: -1;
}

.logo-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 5px 15px rgba(var(--bs-primary-rgb), 0.3);
    transition: all 0.3s ease;
}

.logo-circle:hover {
    transform: rotate(15deg);
}

.auth-title {
    color: var(--text-color);
    font-size: 1.75rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    transition: all 0.3s ease;
    background-color: rgba(var(--bs-primary-rgb), 0.05);
    margin-top: 1rem;
}

.back-link:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    transform: translateX(-3px);
    color: var(--primary-color);
}

.back-link i {
    transition: transform 0.2s ease;
    margin-right: 0.5rem;
}

.back-link:hover i {
    transform: translateX(-3px);
}

[data-theme="dark"] .back-link {
    color: var(--primary-light);
    background-color: rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] .back-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: var(--primary-light);
}

.login-form .form-control {
    padding: 0.75rem 1rem;
    border-color: var(--border-color);
    background-color: var(--bg-input);
    color: var(--text-color);
    font-size: 0.95rem;
    transition: all 0.2s;
}

.login-form .form-control:focus {
    box-shadow: none;
    border-color: var(--primary-light);
    background-color: var(--bg-input-focus);
}

.login-form .input-group-text {
    border-color: var(--border-color);
    background-color: var(--bg-input);
    color: var(--text-muted);
    transition: all 0.2s;
}

.login-form .form-control:focus + .input-group-text,
.login-form .input-group-text + .form-control:focus {
    border-color: var(--primary-light);
    background-color: var(--bg-input-focus);
}

.login-form .form-label {
    color: var(--text-color);
    font-weight: 500;
}

.login-form .btn-primary {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s;
}

.login-form .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(var(--bs-primary-rgb), 0.3);
}

.login-form .form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Enhanced dark mode adjustments */
[data-theme="dark"] .login-page {
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.9) 100%);
}

[data-theme="dark"] .bg-shapes div {
    opacity: 0.15;
}

[data-theme="dark"] .login-page::before {
    opacity: 0.05;
}

[data-theme="dark"] .bg-icons i {
    color: var(--primary-light);
    opacity: 0.07;
}

[data-theme="dark"] .auth-card {
    background-color: rgba(30, 41, 59, 0.9);
    box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2) !important;
}

[data-theme="dark"] .input-group-text {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: var(--text-light) !important;
}

[data-theme="dark"] .form-control {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: var(--text-light) !important;
}

@media (max-width: 576px) {
    .auth-card {
        margin: 1rem;
    }
}
</style>

<!-- JavaScript for password show/hide toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    function setupPasswordToggle(inputId, toggleId) {
        const toggleBtn = document.getElementById(toggleId);
        const passwordInput = document.getElementById(inputId);
        
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle eye icon
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }
            });
        }
    }

    // Setup password toggles
    setupPasswordToggle('password', 'togglePassword');
    setupPasswordToggle('newPassword', 'toggleNewPassword');
    setupPasswordToggle('confirmPassword', 'toggleConfirmPassword');
});
</script>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm" method="post" action="includes/handlers/update_password.php">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="showPasswords">
                        <label class="form-check-label" for="showPasswords">Show Passwords</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update the JavaScript section -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle for login form
    function setupPasswordToggle(inputId, toggleId) {
        const toggleBtn = document.getElementById(toggleId);
        const passwordInput = document.getElementById(inputId);
        
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
        
                // Toggle eye icon
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }
            });
        }
    }

    // Setup password toggle for login form
    setupPasswordToggle('password', 'togglePassword');

    // Setup password visibility for change password form
    const showPasswordsCheckbox = document.getElementById('showPasswords');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    if (showPasswordsCheckbox && newPasswordInput && confirmPasswordInput) {
        showPasswordsCheckbox.addEventListener('change', function() {
            const type = this.checked ? 'text' : 'password';
            newPasswordInput.type = type;
            confirmPasswordInput.type = type;
        });
    }
});
</script>

<?php
include_once 'includes/footer.php';
?> 