<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#3498db">
    <meta name="description" content="Parking management system for monitoring and managing parking spaces">
    <title><?php echo isset($title) ? $title : 'Parking Monitoring System'; ?></title>
    
    <!-- Add the overscroll prevention script before any other scripts -->
    <script src="assets/js/prevent-overscroll.js"></script>
    
    <!-- Early theme detection to prevent flash of wrong theme -->
    <script>
        // Apply theme immediately before any content loads
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', 
                savedTheme === 'dark' || (!savedTheme && prefersDark) ? 'dark' : 'light');
            
            // Add dark-mode class to body if needed
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css" id="flatpickr-theme">
    
    <!-- Dark mode support for better flicker prevention -->
    <style>
        .bg-dark-custom {
            background-color: var(--bg-card) !important;
        }
        
        /* Optional: Add default dark mode for early rendering to prevent flicker */
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg-color: #0f172a;
                --bg-card: #1e293b;
                --bg-card-header: #334155;
                --text-color: #e2e8f0;
            }
            
            :root:not([data-theme="light"]) body {
                background-color: var(--bg-color);
                color: var(--text-color);
            }
        }
    </style>
</head>
<body>
    <!-- Toast container for notifications -->
    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3"></div>
    
    <main class="d-flex flex-column min-vh-100">
