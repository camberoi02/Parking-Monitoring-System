</main>
    <footer class="footer mt-auto py-3 bg-dark text-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start">
                    <span class="text-light">&copy; <?php echo date('Y'); ?> Parking Monitoring System</span>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
</body>
</html>
