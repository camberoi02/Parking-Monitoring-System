<?php
// Include database configuration
require_once dirname(__FILE__) . '/../config/db_config.php';
?>
<nav class="navbar navbar-expand-lg sticky-top py-2" id="mainNav">
    <div class="container">
        <!-- Brand Logo and Name -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <?php
            // Only try to get logo if database exists
            $has_logo = false;
            try {
                if (mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'")) {
                    mysqli_select_db($conn, DB_NAME);
                    // Check if logo table exists
                    $table_result = mysqli_query($conn, "SHOW TABLES LIKE 'logo'");
                    if ($table_result && mysqli_num_rows($table_result) > 0) {
                        // Try to get the most recent logo
                        $logo_query = "SELECT * FROM logo ORDER BY uploaded_at DESC LIMIT 1";
                        $logo_result = mysqli_query($conn, $logo_query);
                        if ($logo_result && mysqli_num_rows($logo_result) > 0) {
                            $logo = mysqli_fetch_assoc($logo_result);
                            $logo_data = base64_encode($logo['image_data']);
                            echo '<div class="brand-icon me-2" style="width: 32px; height: 32px;">';
                            echo '<img src="data:' . $logo['mime_type'] . ';base64,' . $logo_data . '" alt="Logo" 
                                      style="width: 32px; height: 32px; object-fit: cover; border-radius: 50%;">';
                            echo '</div>';
                            $has_logo = true;
                        }
                    }
                }
            } catch (Exception $e) {
                // Log error if needed, but continue with default icon
                error_log("Error loading logo: " . $e->getMessage());
            }
            
            // If no logo was found or displayed, show the default icon
            if (!$has_logo) {
                echo '<div class="brand-icon bg-primary d-flex align-items-center justify-content-center rounded-circle me-2" style="width: 32px; height: 32px;">';
                echo '<i class="fas fa-parking text-white"></i>';
                echo '</div>';
            }
            ?>
            <span class="fw-bold fs-5 brand-text">Parking Monitoring</span>
        </a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars text-primary"></i>
        </button>
        
        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'parking_management.php') ? 'active' : ''; ?>" href="parking_management.php">
                        <i class="fas fa-parking me-1"></i> Parking
                    </a>
                </li>
                <?php if(isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'system_settings.php') ? 'active' : ''; ?>" href="system_settings.php">
                        <i class="fas fa-cogs me-1"></i> Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'audit_trail.php') ? 'active' : ''; ?>" href="audit_trail.php">
                        <i class="fas fa-history me-1"></i> Audit
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'earnings_reports.php') ? 'active' : ''; ?>" href="earnings_reports.php">
                        <i class="fas fa-chart-line me-1"></i> Reports
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center nav-right">
                <!-- Theme toggle button -->
                <button id="themeToggle" class="theme-toggle btn rounded-circle p-1 me-2" title="Toggle light/dark mode">
                    <i class="fas fa-moon"></i>
                </button>
                
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <!-- Logout button when logged in -->
                <div class="d-flex align-items-center">
                    <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill" title="Logout">
                        <i class="fas fa-sign-out-alt me-1"></i>
                        <span class="d-none d-md-inline">Logout</span>
                    </a>
                </div>
                <?php else: ?>
                <!-- Login button when not logged in -->
                <a href="login.php" class="btn btn-primary btn-sm rounded-pill shadow-sm px-3 py-1">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<style>
/* Navigation Bar Styles */
#mainNav {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease-in-out;
    background-color: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(10px);
    padding: 0.5rem 0;
}

/* Improved navbar layout */
#mainNav .navbar-nav {
    display: flex;
    align-items: center;
}

#mainNav .navbar-nav .nav-item {
    margin: 0 0.15rem;
}

#mainNav .navbar-nav .nav-link {
    color: var(--text-color);
    font-weight: 500;
    border-radius: 0.35rem;
    transition: all 0.2s ease;
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
}

#mainNav .navbar-nav .nav-link:hover {
    color: var(--primary-color);
    background-color: rgba(37, 99, 235, 0.05);
}

#mainNav .navbar-nav .nav-link.active {
    color: var(--primary-color);
    font-weight: 600;
    background-color: rgba(37, 99, 235, 0.1);
}

/* Brand styles */
#mainNav .brand-icon {
    transition: transform 0.3s ease;
}

#mainNav .navbar-brand {
    margin-right: 1.5rem;
}

#mainNav .navbar-brand:hover .brand-icon {
    transform: rotate(15deg);
}

/* Right side section */
.nav-right {
    margin-left: 1rem;
}

/* Theme toggle styles */
.theme-toggle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-color);
    background-color: rgba(37, 99, 235, 0.1);
    border: none;
    transition: all 0.3s ease;
}

.theme-toggle:hover {
    background-color: rgba(37, 99, 235, 0.2);
    transform: rotate(15deg);
}

.theme-toggle i {
    color: var(--text-color);
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

/* Logout button styles */
.btn-outline-danger {
    border-color: rgba(220, 38, 38, 0.5);
    color: var(--danger-color);
    transition: all 0.2s ease;
    padding: 0.4rem 0.8rem;
}

.btn-outline-danger:hover {
    background-color: var(--danger-color);
    border-color: var(--danger-color);
    color: white;
    box-shadow: 0 2px 5px rgba(var(--bs-danger-rgb), 0.3);
}

[data-theme="dark"] .btn-outline-danger {
    border-color: rgba(248, 113, 113, 0.5);
    color: var(--danger-light);
}

[data-theme="dark"] .btn-outline-danger:hover {
    background-color: var(--danger-color);
    border-color: var(--danger-color);
    color: white;
}

/* Dark mode styles */
[data-theme="dark"] #mainNav {
    background-color: rgba(30, 41, 59, 0.95) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

[data-theme="dark"] #mainNav .navbar-brand .brand-text {
    color: var(--text-light);
}

[data-theme="dark"] #mainNav .navbar-toggler i {
    color: var(--primary-light);
}

[data-theme="dark"] #mainNav .nav-link {
    color: var(--text-light) !important;
}

[data-theme="dark"] #mainNav .nav-link:hover {
    color: var(--primary-light) !important;
    background-color: rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] #mainNav .nav-link.active {
    color: var(--primary-light) !important;
    background-color: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .theme-toggle {
    background-color: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .theme-toggle i {
    color: var(--text-light);
}

[data-theme="dark"] .theme-toggle:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Mobile view adjustments */
@media (max-width: 991.98px) {
    #mainNav .navbar-nav {
        padding: 0.75rem 0;
        gap: 0.25rem;
    }
    
    #mainNav .navbar-nav .nav-link {
        padding: 0.5rem 0.75rem;
        margin: 0;
    }
    
    #mainNav .navbar-collapse {
        margin-top: 0.75rem;
        border-radius: 0.5rem;
        background-color: var(--bg-card);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        padding: 0.75rem;
    }
    
    .nav-right {
        margin-top: 0.5rem;
        margin-left: 0;
        justify-content: center;
    }
}
</style>
