<?php
// Get current year
$current_year = date('Y');
?>
</main>
    <footer class="footer mt-auto py-3 bg-dark text-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start">
                    <span class="text-light">&copy; <?php echo $current_year; ?> Parking Monitoring System</span>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-light text-decoration-none" data-bs-toggle="modal" data-bs-target="#developersModal">
                        <i class="fas fa-code me-1"></i>Developers
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Developers Modal -->
    <div class="modal fade" id="developersModal" tabindex="-1" aria-labelledby="developersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="developersModalLabel">
                        <i class="fas fa-code me-2"></i>Development Team
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row g-4 justify-content-center">
                            <!-- Aurel -->
                            <div class="col-md-4 text-center">
                                <div class="developer-card">
                                    <div class="developer-image mb-2">
                                        <img src="developers/kly.jpg" alt="Aurel Klyrhon" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <h5 class="developer-name">Aurel, Klyrhon Miko R.</h5>
                                    <p class="developer-contact text-muted">09361090745</p>
                                </div>
                            </div>
                            <!-- Bellen -->
                            <div class="col-md-4 text-center">
                                <div class="developer-card">
                                    <div class="developer-image mb-2">
                                        <img src="developers/bellen.jpg" alt="Jace Bellen" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <h5 class="developer-name">Bellen, Jace H.</h5>
                                    <p class="developer-contact text-muted">09274929257</p>
                                </div>
                            </div>
                            <!-- Cambe -->
                            <div class="col-md-4 text-center">
                                <div class="developer-card">
                                    <div class="developer-image mb-2">
                                        <img src="developers/roi.png" alt="Roi Cambe" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <h5 class="developer-name">Cambe, Roi Yvann M.</h5>
                                    <p class="developer-contact text-muted">09215288612</p>
                                </div>
                            </div>
                            <!-- Folloso -->
                            <div class="col-md-4 text-center">
                                <div class="developer-card">
                                    <div class="developer-image mb-2">
                                        <img src="developers/chris.jpg" alt="Chris Folloso" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <h5 class="developer-name">Folloso, Chris Nicolai Z.</h5>
                                    <p class="developer-contact text-muted">09628569702</p>
                                </div>
                            </div>
                            <!-- Jarina -->
                            <div class="col-md-4 text-center">
                                <div class="developer-card">
                                    <div class="developer-image mb-2">
                                        <img src="developers/genrey.jpg" alt="Gen-Rey Jarina" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <h5 class="developer-name">Jarina, Gen-Rey B.</h5>
                                    <p class="developer-contact text-muted">09765955640</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/date-pickers.js"></script>
    
    <!-- Listen for theme changes to update flatpickr theme -->
    <script>
        document.addEventListener('themeChanged', function(e) {
            const theme = e.detail.theme;
            const flatpickrTheme = document.getElementById('flatpickr-theme');
            
            if (theme === 'dark') {
                flatpickrTheme.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css';
            } else {
                flatpickrTheme.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css';
            }
        });
    </script>

    <style>
        .developer-card {
            padding: 1rem;
            border-radius: 10px;
            transition: transform 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .developer-card:hover {
            transform: translateY(-5px);
        }

        .developer-image img {
            border: 3px solid #007bff;
            padding: 3px;
            transition: transform 0.3s ease;
            background-color: #fff;
        }

        .developer-card:hover .developer-image img {
            transform: scale(1.05);
        }

        .developer-name {
            margin: 10px 0 5px;
            font-weight: 600;
            font-size: 1rem;
        }

        .developer-contact {
            font-size: 0.9rem;
            margin-bottom: 0;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .developer-card {
                margin-bottom: 2rem;
            }
            .developer-image img {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</body>
</html>
