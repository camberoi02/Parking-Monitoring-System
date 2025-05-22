// Main JavaScript file for Parking Monitoring System

// Apply theme immediately before anything else
const savedTheme = localStorage.getItem('theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

// Immediate theme initialization
(function() {
    const isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);
    
    if (isDark) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.body.classList.add('dark-mode');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        document.body.classList.remove('dark-mode');
    }
    
    // Apply initial navbar styles immediately
    document.addEventListener('DOMContentLoaded', function() {
        // Force correct theme on navbar - immediate application
        const navbar = document.querySelector('#mainNav');
        if (navbar) {
            if (isDark) {
                navbar.style.setProperty('background-color', 'rgba(30, 41, 59, 0.95)', 'important');
            } else {
                navbar.style.setProperty('background-color', 'rgba(255, 255, 255, 0.98)', 'important');
            }
        }
    });
})();

// Prevent overscroll/bounce effect with JavaScript - enhanced version
document.addEventListener('DOMContentLoaded', function() {
    // Common function to prevent default on boundary events
    function preventOverscroll(e) {
        // Don't interfere with scrollable elements
        if (e.target.closest('.table-responsive, .overflow-auto, .modal-body, [style*="overflow: auto"], [style*="overflow-y: auto"]')) {
            return;
        }
        
        const scrollTop = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
        const scrollHeight = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
        const clientHeight = document.documentElement.clientHeight;
        
        // Detect if we're at boundaries
        const atTop = scrollTop <= 0;
        const atBottom = scrollTop + clientHeight >= scrollHeight - 5; // 5px threshold
        
        // For wheel events, check direction
        if (e.type === 'wheel') {
            if ((atTop && e.deltaY < 0) || (atBottom && e.deltaY > 0)) {
                e.preventDefault();
                return false;
            }
        } 
        // For touch events, more aggressive prevention at boundaries
        else if (e.type.startsWith('touch') || e.type === 'mousewheel' || e.type === 'DOMMouseScroll') {
            if (atTop || atBottom) {
                e.preventDefault();
                return false;
            }
        }
    }
    
    // Apply passive: false to ensure preventDefault works
    const options = { passive: false };
    
    // Add multiple event listeners for different input types
    document.addEventListener('wheel', preventOverscroll, options);
    document.addEventListener('mousewheel', preventOverscroll, options);
    document.addEventListener('DOMMouseScroll', preventOverscroll, options);
    document.addEventListener('touchmove', preventOverscroll, options);
    
    // Additional touchpad-specific handling
    // This helps catch more touchpad events that may be triggering the bounce
    document.addEventListener('gesturechange', function(e) {
        if (e.scale !== 1) e.preventDefault();
    }, options);
    
    // Additional handler for Mac touchpads
    document.addEventListener('scroll', function() {
        const scrollTop = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
        const scrollHeight = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
        const clientHeight = document.documentElement.clientHeight;
        
        if (scrollTop <= 0 || scrollTop + clientHeight >= scrollHeight) {
            // Lock scroll position at boundary
            window.scrollTo(0, scrollTop);
        }
    }, { passive: true });
    
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Theme switcher
    initThemeSwitcher();
    
    // AJAX form handling
    initAjaxForms();
    
    // Search functionality
    initSearchFilters();
    
    // Center modals that may be missing the centered class
    centerModals();

    // Convert any alerts on page load
    convertAlertsToToasts();
});

// Center modals that need it
function centerModals() {
    // Fix database drop confirmation modal centering
    const dropDbModal = document.getElementById('dropDbModal');
    if (dropDbModal) {
        const modalDialog = dropDbModal.querySelector('.modal-dialog');
        if (modalDialog && !modalDialog.classList.contains('modal-dialog-centered')) {
            modalDialog.classList.add('modal-dialog-centered');
        }
    }
}

// Initialize the theme switcher
function initThemeSwitcher() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    // Check for saved theme preference or respect OS preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Set initial theme
    const initialIsDark = savedTheme === 'dark' || (!savedTheme && prefersDark);
    if (initialIsDark) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.body.classList.add('dark-mode');
        updateThemeIcon(true);
        fixDarkModeElements(true);
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        document.body.classList.remove('dark-mode');
        updateThemeIcon(false);
        fixDarkModeElements(false);
    }
    
    // Handle theme toggle click
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Update root element attribute
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Update body class and apply fixes
        if (newTheme === 'dark') {
            document.body.classList.add('dark-mode');
            fixDarkModeElements(true);
        } else {
            document.body.classList.remove('dark-mode');
            fixDarkModeElements(false);
        }
        
        // Force a hard reset on the navbar to ensure styles are applied correctly
        const navbar = document.querySelector('#mainNav');
        if (navbar) {
            if (newTheme === 'dark') {
                // Force dark theme styles
                navbar.style.setProperty('background-color', 'rgba(30, 41, 59, 0.95)', 'important');
                navbar.style.setProperty('border-bottom', '1px solid rgba(255, 255, 255, 0.05)', 'important');
                
                // Force dark styles on nav elements
                navbar.querySelectorAll('.nav-link').forEach(link => {
                    link.style.setProperty('color', 'var(--text-light)', 'important');
                });
                
                // Force dark style on brand text
                const brand = navbar.querySelector('.navbar-brand .brand-text');
                if (brand) {
                    brand.style.setProperty('color', 'var(--text-light)', 'important');
                }
            } else {
                // Force light theme styles
                navbar.style.setProperty('background-color', 'rgba(255, 255, 255, 0.98)', 'important');
                navbar.style.removeProperty('border-bottom');
                
                // Force light styles on nav elements
                navbar.querySelectorAll('.nav-link').forEach(link => {
                    link.style.setProperty('color', 'var(--text-color)', 'important');
                    if (link.classList.contains('active')) {
                        link.style.setProperty('color', 'var(--primary-color)', 'important');
                    }
                });
                
                // Force light style on brand text
                const brand = navbar.querySelector('.navbar-brand .brand-text');
                if (brand) {
                    brand.style.setProperty('color', 'var(--text-color)', 'important');
                }
            }
        }
        
        // Update theme icon
        updateThemeIcon(newTheme === 'dark');
        
        // Force a redraw to help with stubborn elements
        document.body.style.display = 'none';
        setTimeout(() => {
            document.body.style.display = '';
            // Dispatch a custom event for additional handlers
            document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
        }, 5);
    });
}

// Fix elements that don't respond to CSS only
function fixDarkModeElements(isDark) {
    // Fix navbar background more aggressively
    const navbar = document.querySelector('#mainNav');
    if (navbar) {
        if (isDark) {
            // Apply dark theme to navbar
            navbar.style.setProperty('background-color', 'rgba(30, 41, 59, 0.95)', 'important');
            navbar.style.setProperty('border-bottom', '1px solid rgba(255, 255, 255, 0.05)', 'important');
            
            // Fix nav links
            navbar.querySelectorAll('.nav-link').forEach(link => {
                link.style.setProperty('color', 'var(--text-light)', 'important');
            });
            
            // Fix navbar brand
            const brand = navbar.querySelector('.navbar-brand .brand-text');
            if (brand) {
                brand.style.setProperty('color', 'var(--text-light)', 'important');
            }
            
            // Fix navbar toggler
            const toggler = navbar.querySelector('.navbar-toggler i');
            if (toggler) {
                toggler.style.setProperty('color', 'var(--primary-light)', 'important');
            }
        } else {
            // Remove all forced styles for light mode
            navbar.style.removeProperty('background-color');
            navbar.style.removeProperty('border-bottom');
            
            // Explicitly set background color for light mode
            navbar.style.setProperty('background-color', 'rgba(255, 255, 255, 0.98)', 'important');
            
            // Reset nav links with explicit colors
            navbar.querySelectorAll('.nav-link').forEach(link => {
                link.style.removeProperty('color');
                link.style.setProperty('color', 'var(--text-color)', 'important');
                
                if (link.classList.contains('active')) {
                    link.style.setProperty('color', 'var(--primary-color)', 'important');
                }
            });
            
            // Reset navbar brand with explicit color
            const brand = navbar.querySelector('.navbar-brand .brand-text');
            if (brand) {
                brand.style.removeProperty('color');
                brand.style.setProperty('color', 'var(--text-color)', 'important');
            }
            
            // Reset navbar toggler
            const toggler = navbar.querySelector('.navbar-toggler i');
            if (toggler) {
                toggler.style.removeProperty('color');
                toggler.style.setProperty('color', 'var(--primary-color)', 'important');
            }
        }
    }
    
    // Enhanced table fixing logic with more aggressive approach
    document.querySelectorAll('.table, .table-striped').forEach(table => {
        if (isDark) {
            table.classList.add('table-dark');
            
            // Force style overrides for stubborn tables
            table.style.setProperty('background-color', 'var(--bg-card)', 'important');
            table.style.setProperty('color', 'var(--text-color)', 'important');
            
            // Fix table headers more aggressively
            const headers = table.querySelectorAll('th');
            headers.forEach(header => {
                header.style.setProperty('background-color', 'var(--bg-card-header)', 'important');
                header.style.setProperty('color', 'var(--primary-light)', 'important');
                header.style.setProperty('border-color', 'var(--border-color)', 'important');
            });
            
            // Fix table cells more aggressively
            const cells = table.querySelectorAll('td');
            cells.forEach(cell => {
                cell.style.setProperty('border-color', 'var(--border-color)', 'important');
                cell.style.setProperty('color', 'var(--text-color)', 'important');
            });
            
            // Fix all rows
            const allRows = table.querySelectorAll('tbody tr');
            allRows.forEach(row => {
                row.style.setProperty('background-color', 'var(--bg-card)', 'important');
            });
            
            // Then specifically handle odd rows for striping
            const oddRows = table.querySelectorAll('tbody tr:nth-child(odd)');
            oddRows.forEach(row => {
                row.style.setProperty('background-color', 'rgba(255, 255, 255, 0.05)', 'important');
            });
        } else {
            table.classList.remove('table-dark');
            
            // Reset all forced styles
            table.style.removeProperty('background-color');
            table.style.removeProperty('color');
            
            // Reset headers
            const headers = table.querySelectorAll('th');
            headers.forEach(header => {
                header.style.removeProperty('background-color');
                header.style.removeProperty('color');
                header.style.removeProperty('border-color');
            });
            
            // Reset cells
            const cells = table.querySelectorAll('td');
            cells.forEach(cell => {
                cell.style.removeProperty('border-color');
                cell.style.removeProperty('color');
            });
            
            // Reset rows
            const allRows = table.querySelectorAll('tbody tr');
            allRows.forEach(row => {
                row.style.removeProperty('background-color');
            });
        }
    });
    
    // Fix table container elements more aggressively
    document.querySelectorAll('.table-responsive').forEach(container => {
        if (isDark) {
            container.style.setProperty('background-color', 'var(--bg-card)', 'important');
            container.style.setProperty('border-color', 'var(--border-color)', 'important');
        } else {
            container.style.removeProperty('background-color');
            container.style.removeProperty('border-color');
        }
    });
    
    // Fix any elements with explicitly set background colors
    document.querySelectorAll('[style*="background"]').forEach(el => {
        if (el.hasAttribute('data-original-bg') === false) {
            el.setAttribute('data-original-bg', el.style.backgroundColor || '');
        }
        
        if (isDark && !el.classList.contains('preserve-bg')) {
            el.style.backgroundColor = '';
        } else if (!isDark && el.hasAttribute('data-original-bg')) {
            el.style.backgroundColor = el.getAttribute('data-original-bg');
        }
    });
    
    // Ultra-aggressive fix for stubborn tables
    function fixStubornTables() {
        // Find the Manage Existing Spots table specifically
        const spotsTables = document.querySelectorAll('.card-body .table-responsive .table');
        spotsTables.forEach(table => {
            if (isDark) {
                // Apply styles directly to table
                table.setAttribute('style', 'background-color: var(--bg-card) !important; color: var(--text-color) !important;');
                
                // Force thead styles
                const thead = table.querySelector('thead');
                if (thead) {
                    thead.setAttribute('style', 'background-color: var(--bg-card-header) !important;');
                }
                
                // Force th styles
                const headers = table.querySelectorAll('th');
                headers.forEach(header => {
                    header.setAttribute('style', 'background-color: var(--bg-card-header) !important; color: var(--primary-light) !important; border-color: var(--border-color) !important;');
                });
                
                // Force all rows and cells
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    const bgColor = index % 2 === 0 ? 'var(--bg-card)' : 'rgba(255, 255, 255, 0.05)';
                    row.setAttribute('style', `background-color: ${bgColor} !important; color: var(--text-color) !important;`);
                    
                    // Handle cells in this row
                    const cells = row.querySelectorAll('td');
                    cells.forEach(cell => {
                        cell.setAttribute('style', 'color: var(--text-color) !important; border-color: var(--border-color) !important;');
                        
                        // Fix badges inside cells
                        const badges = cell.querySelectorAll('.badge');
                        badges.forEach(badge => {
                            if (badge.classList.contains('bg-success')) {
                                badge.setAttribute('style', 'background-color: var(--success-color) !important; color: white !important;');
                            } else if (badge.classList.contains('bg-danger')) {
                                badge.setAttribute('style', 'background-color: var(--danger-color) !important; color: white !important;');
                            }
                        });
                    });
                });
            } else {
                // IMPROVED LIGHT MODE RESET - thoroughly remove all forced styles
                // Remove style from table
                table.removeAttribute('style');
                table.classList.remove('table-dark');
                
                // Reset thead
                const thead = table.querySelector('thead');
                if (thead) thead.removeAttribute('style');
                
                // Reset all elements in the table with more explicit styling for light mode
                table.querySelectorAll('th').forEach(header => {
                    header.removeAttribute('style');
                    header.style.backgroundColor = '';
                    header.style.color = '';
                    header.style.borderColor = '';
                });
                
                table.querySelectorAll('td').forEach(cell => {
                    cell.removeAttribute('style');
                    cell.style.color = '';
                    cell.style.backgroundColor = '';
                    cell.style.borderColor = '';
                });
                
                table.querySelectorAll('tr').forEach(row => {
                    row.removeAttribute('style');
                    row.style.backgroundColor = '';
                    row.style.color = '';
                });
                
                // Reset badges
                table.querySelectorAll('.badge').forEach(badge => {
                    badge.removeAttribute('style');
                    if (badge.classList.contains('bg-success')) {
                        badge.style.backgroundColor = '';
                        badge.style.color = '';
                    } else if (badge.classList.contains('bg-danger')) {
                        badge.style.backgroundColor = '';
                        badge.style.color = '';
                    }
                });
                
                // Apply specific light mode styles
                table.style.backgroundColor = '#ffffff';
                table.querySelectorAll('th').forEach(th => {
                    th.style.backgroundColor = '#f1f5f9';
                });
            }
        });
    }
    
    // Run the aggressive table fix
    fixStubornTables();
}

// Add additional handling after theme switch
document.addEventListener('themeChanged', function(e) {
    // Force redraw tables after theme change
    setTimeout(() => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        document.querySelectorAll('.table').forEach(table => {
            // Toggle a class to force redraw
            table.classList.add('theme-redraw');
            setTimeout(() => table.classList.remove('theme-redraw'), 10);
            
            if (isDark) {
                // Apply dark mode specifics
                table.querySelectorAll('th').forEach(header => {
                    header.style.setProperty('background-color', 'var(--bg-card-header)', 'important');
                    header.style.setProperty('color', 'var(--primary-light)', 'important');
                });
            } else {
                // ADDED: Explicit light mode reset
                table.classList.remove('table-dark');
                table.style.removeProperty('background-color');
                table.style.removeProperty('color');
                
                // Reset headers for light mode
                table.querySelectorAll('th').forEach(header => {
                    header.style.removeProperty('background-color');
                    header.style.removeProperty('color');
                    header.style.removeProperty('border-color');
                    // Apply light mode style explicitly
                    header.style.backgroundColor = '#f1f5f9';
                    header.style.color = '';
                });
                
                // Reset rows for light mode
                table.querySelectorAll('tbody tr').forEach(row => {
                    row.style.removeProperty('background-color');
                    row.style.removeProperty('color');
                    // Reset to default striping if needed
                    if (table.classList.contains('table-striped')) {
                        // Let browser handle default striping
                        row.style.backgroundColor = '';
                    }
                });
            }
        });
    }, 100);
});

// Create a MutationObserver to watch for DOM changes and fix tables after they're rendered
document.addEventListener('DOMContentLoaded', function() {
    // Create an observer instance linked to the callback function
    const observer = new MutationObserver(function(mutationsList, observer) {
        // Check if we're in dark mode
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
            // Apply table fixes to any new or changed tables
            document.querySelectorAll('.table-responsive .table').forEach(table => {
                // Force header colors
                table.querySelectorAll('th').forEach(header => {
                    header.style.backgroundColor = 'var(--bg-card-header)';
                    header.style.color = 'var(--primary-light)';
                    header.style.borderColor = 'var(--border-color)';
                });
                
                // Force row colors
                table.querySelectorAll('tbody tr').forEach((row, index) => {
                    if (index % 2 === 0) {
                        row.style.backgroundColor = 'var(--bg-card)';
                    } else {
                        row.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
                    }
                });
            });
        }
    });
    
    // Start observing the document with the configured parameters
    observer.observe(document.body, { childList: true, subtree: true });
});

function updateThemeIcon(isDark) {
    const icon = document.querySelector('.theme-toggle i');
    if (icon) {
        // Update icon to sun or moon based on theme
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        
        // Explicitly set the color based on the theme
        if (isDark) {
            icon.style.color = 'var(--text-light)';
        } else {
            icon.style.color = 'var(--text-color)';
        }
    }
}

// Add specific event handler for tables when nav tabs are clicked
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Apply table styles after tab switch (longer delay to ensure DOM is fully updated)
            setTimeout(() => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                
                // Target ALL tables in the application, not just in specific containers
                document.querySelectorAll('.table').forEach(table => {
                    if (isDark) {
                        // Force dark theme styling
                        table.classList.add('table-dark');
                        table.style.setProperty('background-color', 'var(--bg-card)', 'important');
                        table.style.setProperty('color', 'var(--text-color)', 'important');
                        
                        // Force header styling
                        table.querySelectorAll('th').forEach(th => {
                            th.style.setProperty('background-color', 'var(--bg-card-header)', 'important');
                            th.style.setProperty('color', 'var(--primary-light)', 'important');
                            th.style.setProperty('border-color', 'var(--border-color)', 'important');
                        });
                        
                        // Force row styling
                        table.querySelectorAll('tbody tr').forEach((row, index) => {
                            if (index % 2 === 0) {
                                row.style.setProperty('background-color', 'var(--bg-card)', 'important');
                            } else {
                                row.style.setProperty('background-color', 'rgba(255, 255, 255, 0.05)', 'important');
                            }
                            
                            // Apply to all cells in this row
                            row.querySelectorAll('td').forEach(cell => {
                                cell.style.setProperty('color', 'var(--text-color)', 'important');
                                cell.style.setProperty('border-color', 'var(--border-color)', 'important');
                            });
                        });
                    } else {
                        // Force light theme reset
                        table.classList.remove('table-dark');
                        table.removeAttribute('style');
                        
                        // Reset all children
                        table.querySelectorAll('*').forEach(el => {
                            el.removeAttribute('style');
                        });
                        
                        // Apply light theme explicitly
                        table.style.backgroundColor = '#ffffff';
                        table.querySelectorAll('th').forEach(th => {
                            th.style.backgroundColor = '#f1f5f9';
                        });
                        
                        // Apply striping
                        if (table.classList.contains('table-striped')) {
                            table.querySelectorAll('tbody tr').forEach((row, index) => {
                                if (index % 2 !== 0) {
                                    row.style.backgroundColor = 'rgba(0, 0, 0, 0.05)';
                                } else {
                                    row.style.backgroundColor = '#ffffff';
                                }
                            });
                        }
                    }
                });
            }, 200); // Increased timeout for reliable rendering
        });
    });
    
    // Apply initial styles to all tables on page load
    setTimeout(() => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
            document.querySelectorAll('.table').forEach(table => {
                // Apply dark theme to all tables on initial load
                fixTableStyles(table, true);
            });
        }
    }, 100);
});

// Helper function to fix table styles
function fixTableStyles(table, isDark) {
    if (isDark) {
        table.classList.add('table-dark');
        table.style.setProperty('background-color', 'var(--bg-card)', 'important');
        table.style.setProperty('color', 'var(--text-color)', 'important');
        
        // Force header styling
        table.querySelectorAll('th').forEach(th => {
            th.style.setProperty('background-color', 'var(--bg-card-header)', 'important');
            th.style.setProperty('color', 'var(--primary-light)', 'important');
            th.style.setProperty('border-color', 'var(--border-color)', 'important');
        });
        
        // Force row styling
        table.querySelectorAll('tbody tr').forEach((row, index) => {
            if (index % 2 === 0) {
                row.style.setProperty('background-color', 'var(--bg-card)', 'important');
            } else {
                row.style.setProperty('background-color', 'rgba(255, 255, 255, 0.05)', 'important');
            }
            
            // Apply to all cells in this row
            row.querySelectorAll('td').forEach(cell => {
                cell.style.setProperty('color', 'var(--text-color)', 'important');
                cell.style.setProperty('border-color', 'var(--border-color)', 'important');
            });
        });
    } else {
        // Force light theme reset
        table.classList.remove('table-dark');
        table.removeAttribute('style');
        
        // Reset all children
        table.querySelectorAll('*').forEach(el => {
            el.removeAttribute('style');
        });
        
        // Apply light theme explicitly
        table.style.backgroundColor = '#ffffff';
        table.querySelectorAll('th').forEach(th => {
            th.style.backgroundColor = '#f1f5f9';
        });
        
        // Apply striping
        if (table.classList.contains('table-striped')) {
            table.querySelectorAll('tbody tr').forEach((row, index) => {
                if (index % 2 !== 0) {
                    row.style.backgroundColor = 'rgba(0, 0, 0, 0.05)';
                } else {
                    row.style.backgroundColor = '#ffffff';
                }
            });
        }
    }
}

// Convert static alerts to toast notifications
function convertAlertsToToasts() {
    // Check if we're on system_settings.php page
    const isSystemSettingsPage = window.location.pathname.endsWith('system_settings.php');
    
    // Only convert alerts that are not inside modals and don't have the no-toast class
    // On system_settings.php, we'll only convert alerts that have the 'convert-to-toast' class
    const alertSelector = isSystemSettingsPage 
        ? '.alert.convert-to-toast:not(.modal .alert)' 
        : '.alert:not(.no-toast):not(.modal .alert)';
    
    const alerts = document.querySelectorAll(alertSelector);
    
    alerts.forEach(alert => {
        let type = 'info';
        if (alert.classList.contains('alert-success')) type = 'success';
        if (alert.classList.contains('alert-danger')) type = 'danger';
        if (alert.classList.contains('alert-warning')) type = 'warning';
        
        showToast(alert.innerHTML, type);
        
        // Remove the original alert
        alert.remove();
    });
}

// Enhanced toast notification function
window.showToast = function(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element with improved structure for accessibility
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white ms-auto me-2" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Show toast with animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 500); // Match the CSS transition duration
    }, 5000);
    
    // Add click handler to close button
    const closeButton = toast.querySelector('.btn-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        });
    }
}

// Initialize ajax forms
function initAjaxForms() {
    document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Store original button text for later
            submitBtn.setAttribute('data-original-text', originalText);
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>Processing...';
            
            // Submit form data
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: this.method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Show success/error message
                showToast(data.message, data.success ? 'success' : 'danger');
                
                // Reset form if successful
                if (data.success && !data.dontReset) {
                    this.reset();
                }
                
                // Reload page if needed
                if (data.reload) {
                    setTimeout(() => window.location.reload(), 1000);
                }
                
                // Redirect if needed
                if (data.redirect) {
                    setTimeout(() => window.location.href = data.redirect, 1000);
                }
            })
            .catch(error => {
                showToast('An error occurred while processing your request.', 'danger');
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    });
}

// Initialize search filters
function initSearchFilters() {
    document.querySelectorAll('.search-input').forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const targetId = this.getAttribute('data-search-target');
            const table = document.getElementById(targetId);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    });
}
