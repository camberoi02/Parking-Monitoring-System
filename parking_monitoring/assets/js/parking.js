document.addEventListener('DOMContentLoaded', function() {
    // Add auto-uppercase functionality to specific input fields
    const autoUppercaseFields = document.querySelectorAll('#customerName, #vehicleId, #renterName, #reserverName');
    autoUppercaseFields.forEach(field => {
        field.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });

    // Filtering functionality
    const filterButtons = {
        all: document.getElementById('filterAll'),
        available: document.getElementById('filterAvailable'),
        occupied: document.getElementById('filterOccupied'),
        rented: document.getElementById('filterRented'),
        reserved: document.getElementById('filterReserved')
    };
    
    const spotItems = document.querySelectorAll('.spot-item');
    let currentStatusFilter = 'all';
    let currentSectorFilter = 'all';
    
    // Get filter values from URL parameters
    function getFilterParamsFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const statusFilter = urlParams.get('status');
        const sectorFilter = urlParams.get('sector');
        
        return {
            status: statusFilter || 'all',
            sector: sectorFilter || 'all',
            sectorName: urlParams.get('sectorName') || 'All Sectors'
        };
    }
    
    // Update URL with current filter values
    function updateURLParams() {
        const url = new URL(window.location);
        
        // Set or update status parameter
        if (currentStatusFilter === 'all') {
            url.searchParams.delete('status');
        } else {
            url.searchParams.set('status', currentStatusFilter);
        }
        
        // Set or update sector parameters
        if (currentSectorFilter === 'all') {
            url.searchParams.delete('sector');
            url.searchParams.delete('sectorName');
        } else {
            url.searchParams.set('sector', currentSectorFilter);
            const sectorName = document.querySelector(`.sector-filter .dropdown-item[data-sector="${currentSectorFilter}"]`)?.textContent.trim();
            if (sectorName) {
                url.searchParams.set('sectorName', sectorName);
            }
        }
        
        // Update URL without page reload
        window.history.replaceState({}, '', url);
    }
    
    function applyFilter() {
        let visibleCount = 0;
        
        spotItems.forEach(item => {
            const statusMatch = currentStatusFilter === 'all' || item.dataset.status === currentStatusFilter;
            const sectorMatch = currentSectorFilter === 'all' || item.dataset.sectorId === currentSectorFilter;
            
            if (statusMatch && sectorMatch) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Get the spots container
        const spotsContainer = document.getElementById('spotsContainer');
        const noResultsMessage = document.getElementById('noFilterResults');
        
        // If no spots are visible after filtering, show the message
        if (visibleCount === 0) {
            // Remove existing message if it exists
            if (noResultsMessage) {
                noResultsMessage.remove();
            }
            
            // Create new message
            const messageDiv = document.createElement('div');
            messageDiv.id = 'noFilterResults';
            messageDiv.className = 'col-12 text-center py-5';
            messageDiv.innerHTML = `
                <div class="py-5">
                    <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No parking spots found</h5>
                    <p class="mb-0">No parking spots match the current filter criteria.</p>
                </div>
            `;
            
            // Add message to the container
            spotsContainer.appendChild(messageDiv);
        } else {
            // Remove the message if spots are visible
            if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }
    }
    
    function updateStatusFilterButtons(status) {
        currentStatusFilter = status;
        
        Object.keys(filterButtons).forEach(key => {
            if (key === status) {
                filterButtons[key].classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-danger', 'btn-outline-info');
                filterButtons[key].classList.add(key === 'all' ? 'btn-primary' : 
                                              key === 'available' ? 'btn-success' : 
                                              key === 'occupied' ? 'btn-danger' : 'btn-info');
            } else {
                filterButtons[key].classList.remove('btn-primary', 'btn-success', 'btn-danger', 'btn-info');
                filterButtons[key].classList.add(key === 'all' ? 'btn-outline-primary' : 
                                              key === 'available' ? 'btn-outline-success' : 
                                              key === 'occupied' ? 'btn-outline-danger' : 'btn-outline-info');
            }
        });
        
        updateURLParams();
        applyFilter();
    }
    
    function updateSectorFilterButton(sectorId, sectorName) {
        currentSectorFilter = sectorId;
        
        // Update dropdown button text
        const dropdownButton = document.getElementById('sectorFilterDropdown');
        if (dropdownButton) {
            dropdownButton.innerHTML = '<i class="fas fa-map-marker-alt me-1"></i> ' + 
                (sectorId === 'all' ? 'All Sectors' : sectorName);
        }
        
        // Update active class in dropdown items
        document.querySelectorAll('.sector-filter .dropdown-item').forEach(item => {
            if (item.dataset.sector === sectorId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
        updateURLParams();
        applyFilter();
    }
    
    // Restore filters from URL parameters on page load
    const savedFilters = getFilterParamsFromURL();
    
    // Initialize with saved values or defaults
    if (savedFilters.status && savedFilters.status !== 'all') {
        updateStatusFilterButtons(savedFilters.status);
    } else {
        // Set initial active filter (default)
        updateStatusFilterButtons('all');
    }
    
    // Restore sector filter if available
    if (savedFilters.sector && savedFilters.sector !== 'all') {
        updateSectorFilterButton(savedFilters.sector, savedFilters.sectorName);
    }
    
    // Add event listeners to filter buttons
    if (filterButtons.all) filterButtons.all.addEventListener('click', () => updateStatusFilterButtons('all'));
    if (filterButtons.available) filterButtons.available.addEventListener('click', () => updateStatusFilterButtons('available'));
    if (filterButtons.occupied) filterButtons.occupied.addEventListener('click', () => updateStatusFilterButtons('occupied'));
    if (filterButtons.rented) filterButtons.rented.addEventListener('click', () => updateStatusFilterButtons('rented'));
    if (filterButtons.reserved) filterButtons.reserved.addEventListener('click', () => updateStatusFilterButtons('reserved'));
    
    // Add event listeners to sector filter dropdown items
    document.querySelectorAll('.sector-filter .dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const sectorId = this.getAttribute('data-sector');
            const sectorName = this.textContent.trim();
            updateSectorFilterButton(sectorId, sectorName);
        });
    });
    
    // Initialize modals
    const checkInModal = new bootstrap.Modal(document.getElementById('checkInModal'));
    const checkOutModal = new bootstrap.Modal(document.getElementById('checkOutModal'));
    const rentSpotModal = new bootstrap.Modal(document.getElementById('rentSpotModal'));
    const endRentalModal = new bootstrap.Modal(document.getElementById('endRentalModal'));
    const reserveSpotModal = new bootstrap.Modal(document.getElementById('reserveSpotModal'));
    const cancelReservationModal = new bootstrap.Modal(document.getElementById('cancelReservationModal'));
    const actionSelectionModal = new bootstrap.Modal(document.getElementById('actionSelectionModal'));
    
    // Make parking spot cards clickable
    document.querySelectorAll('.clickable-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger if direct button or link was clicked
            if (e.target.closest('.btn') || 
                e.target.closest('a') || 
                e.target.closest('.check-in-btn') || 
                e.target.closest('.rent-spot-btn') ||
                e.target.closest('.reserve-spot-btn') ||
                e.target.closest('.parking-option')) {
                return;
            }
            
            const spotId = this.getAttribute('data-spot-id');
            const spotNumber = this.getAttribute('data-spot-number');
            const status = this.getAttribute('data-status');
            
            if (status === 'available') {
                // Show action selection modal instead of directly showing check-in modal
                document.getElementById('actionSelectionSpotNumber').textContent = 'Parking Spot ' + spotNumber;
                
                // Store spot data for action handlers
                document.getElementById('actionSelectionModal').setAttribute('data-spot-id', spotId);
                document.getElementById('actionSelectionModal').setAttribute('data-spot-number', spotNumber);
                
                actionSelectionModal.show();
            } else if (status === 'occupied') {
                // Show check-out modal with details
                document.getElementById('checkOutSpotId').value = spotId;
                document.getElementById('checkOutSpotNumber').textContent = 'Parking Spot ' + spotNumber;
                document.getElementById('checkOutCustomerName').textContent = this.getAttribute('data-customer-name') || 'N/A';
                document.getElementById('checkOutVehicleId').textContent = this.getAttribute('data-vehicle-id');
                document.getElementById('checkOutVehicleType').textContent = this.getAttribute('data-vehicle-type') || 'N/A';
                document.getElementById('checkOutEntryTime').textContent = this.getAttribute('data-entry-time-formatted');
                document.getElementById('checkOutDuration').textContent = this.getAttribute('data-duration');
                
                // Check if this is free parking
                const isFree = this.getAttribute('data-is-free') === '1';
                if (isFree) {
                    document.getElementById('checkOutFee').classList.add('d-none');
                    document.getElementById('checkOutFreeParking').classList.remove('d-none');
                } else {
                    document.getElementById('checkOutFee').classList.remove('d-none');
                    document.getElementById('checkOutFreeParking').classList.add('d-none');
                    document.getElementById('checkOutFee').textContent = '₱' + this.getAttribute('data-fee');
                }
                
                checkOutModal.show();
            } else if (status === 'rented') {
                // Show end rental modal with details
                document.getElementById('endRentalSpotId').value = spotId;
                document.getElementById('endRentalSpotNumber').textContent = 'Parking Spot ' + spotNumber;
                document.getElementById('endRentalRenterName').textContent = this.getAttribute('data-renter-name');
                document.getElementById('endRentalRenterContact').textContent = this.getAttribute('data-renter-contact');
                document.getElementById('endRentalStartDate').textContent = this.getAttribute('data-rental-start-formatted');
                document.getElementById('endRentalEndDate').textContent = this.getAttribute('data-rental-end-formatted');
                document.getElementById('endRentalRate').textContent = '₱' + this.getAttribute('data-rental-rate') + ' / month';
                
                endRentalModal.show();
            } else if (status === 'reserved') {
                // Show cancel reservation modal with details
                document.getElementById('cancelReservationSpotId').value = spotId;
                document.getElementById('cancelReservationSpotNumber').textContent = 'Parking Spot ' + spotNumber;
                document.getElementById('cancelReservationName').textContent = this.getAttribute('data-reserver-name');
                document.getElementById('cancelReservationContact').textContent = this.getAttribute('data-reserver-contact');
                document.getElementById('cancelReservationStartTime').textContent = this.getAttribute('data-reservation-start-formatted');
                document.getElementById('cancelReservationEndTime').textContent = this.getAttribute('data-reservation-end-formatted');
                
                // Add reservation fee to the modal
                const reservationFee = this.getAttribute('data-reservation-fee');
                const feeContainer = document.getElementById('cancelReservationFee');
                
                if (feeContainer) {
                    feeContainer.textContent = '₱' + reservationFee;
                }
                
                cancelReservationModal.show();
            }
        });
    });
    
    // Action Selection Modal option handlers
    document.getElementById('option-check-in').addEventListener('click', function() {
        const modal = document.getElementById('actionSelectionModal');
        const spotId = modal.getAttribute('data-spot-id');
        const spotNumber = modal.getAttribute('data-spot-number');
        
        // Hide action selection modal
        actionSelectionModal.hide();
        
        // Show check-in modal
        document.getElementById('checkInSpotId').value = spotId;
        document.getElementById('checkInSpotNumber').textContent = 'Parking Spot ' + spotNumber;
        
        // Small delay to avoid modal animation conflicts
        setTimeout(() => {
            checkInModal.show();
            // Focus on customer name input
            document.getElementById('customerName').focus();
        }, 400);
    });
    
    document.getElementById('option-reserve').addEventListener('click', function() {
        const modal = document.getElementById('actionSelectionModal');
        const spotId = modal.getAttribute('data-spot-id');
        const spotNumber = modal.getAttribute('data-spot-number');
        
        // Hide action selection modal
        actionSelectionModal.hide();
        
        // Show reserve spot modal with appropriate setup
        document.getElementById('reserveSpotId').value = spotId;
        document.getElementById('reserveSpotNumber').textContent = 'Parking Spot ' + spotNumber;
        
        // Small delay to avoid modal animation conflicts
        setTimeout(() => {
            reserveSpotModal.show();
            // Focus on reserver name input
            document.getElementById('reserverName').focus();
        }, 400);
    });
    
    document.getElementById('option-rent').addEventListener('click', function() {
        const modal = document.getElementById('actionSelectionModal');
        const spotId = modal.getAttribute('data-spot-id');
        const spotNumber = modal.getAttribute('data-spot-number');
        
        // Hide action selection modal
        actionSelectionModal.hide();
        
        // Show rent spot modal with appropriate setup
        document.getElementById('rentSpotId').value = spotId;
        document.getElementById('rentSpotNumber').textContent = 'Parking Spot ' + spotNumber;
        
        // Small delay to avoid modal animation conflicts
        setTimeout(() => {
            rentSpotModal.show();
            // Focus on renter name input
            document.getElementById('renterName').focus();
        }, 400);
    });
    
    // Handle check-in form submission
    const submitCheckInBtn = document.getElementById('submitCheckIn');
    if (submitCheckInBtn) {
        submitCheckInBtn.addEventListener('click', function() {
            const form = document.getElementById('checkInForm');
            
            // Validate form
            if (form.reportValidity()) {
                // Show loading state
                const submitBtn = this;
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                
                // Submit form
                fetch(window.location.href, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => response.text())
                .then(html => {
                    document.open();
                    document.write(html);
                    document.close();
                    // Toast will be shown by the PHP-injected script after page reload
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    showToast('An error occurred. Please try again.', 'danger');
                });
            }
        });
    }
    
    // Handle check-out form submission
    const submitCheckOutBtn = document.getElementById('submitCheckOut');
    if (submitCheckOutBtn) {
        submitCheckOutBtn.addEventListener('click', function() {
            // Show loading state
            const submitBtn = this;
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
            
            // Submit form
            const form = document.getElementById('checkOutForm');
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.text())
            .then(html => {
                document.open();
                document.write(html);
                document.close();
                // Toast will be shown by the PHP-injected script after page reload
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                showToast('An error occurred. Please try again.', 'danger');
            });
        });
    }
    
    // Handle rent spot form submission
    const submitRentSpotBtn = document.getElementById('submitRentSpot');
    if (submitRentSpotBtn) {
        submitRentSpotBtn.addEventListener('click', function() {
            const form = document.getElementById('rentSpotForm');
            
            // Validate form
            if (form.reportValidity()) {
                // Get flatpickr dates
                const startDate = document.getElementById('rentalStartDate')._flatpickr.selectedDates[0];
                const endDate = document.getElementById('rentalEndDate')._flatpickr.selectedDates[0];
                
                // Check that end date is after start date
                if (endDate <= startDate) {
                    showToast('End date must be after start date', 'danger');
                    return;
                }
                
                // Show loading state
                const submitBtn = this;
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                
                // Submit form
                fetch(window.location.href, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => response.text())
                .then(html => {
                    document.open();
                    document.write(html);
                    document.close();
                    // Toast will be shown by the PHP-injected script after page reload
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    showToast('An error occurred. Please try again.', 'danger');
                });
            }
        });
    }
    
    // Handle reserve spot form submission
    const submitReservationBtn = document.getElementById('submitReservation');
    if (submitReservationBtn) {
        submitReservationBtn.addEventListener('click', function() {
            const form = document.getElementById('reserveSpotForm');
            
            // Validate form
            if (form.reportValidity()) {
                // Get flatpickr dates
                const startTime = document.getElementById('reservationStartTime')._flatpickr.selectedDates[0];
                const endTime = document.getElementById('reservationEndTime')._flatpickr.selectedDates[0];
                
                // Check that end time is after start time
                if (endTime <= startTime) {
                    showToast('End time must be after start time', 'danger');
                    return;
                }
                
                // Show loading state
                const submitBtn = this;
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                
                // Submit form
                fetch(window.location.href, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => response.text())
                .then(html => {
                    document.open();
                    document.write(html);
                    document.close();
                    // Toast will be shown by the PHP-injected script after page reload
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    showToast('An error occurred. Please try again.', 'danger');
                });
            }
        });
    }
    
    // Handle cancel reservation form submission
    const submitCancelReservationBtn = document.getElementById('submitCancelReservation');
    if (submitCancelReservationBtn) {
        submitCancelReservationBtn.addEventListener('click', function() {
            // Show loading state
            const submitBtn = this;
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
            
            // Submit form
            const form = document.getElementById('cancelReservationForm');
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.text())
            .then(html => {
                document.open();
                document.write(html);
                document.close();
                // Toast will be shown by the PHP-injected script after page reload
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                showToast('An error occurred. Please try again.', 'danger');
            });
        });
    }
    
    // Handle end rental form submission
    const submitEndRentalBtn = document.getElementById('submitEndRental');
    if (submitEndRentalBtn) {
        console.log('End Rental button found:', submitEndRentalBtn);
        
        submitEndRentalBtn.addEventListener('click', function(e) {
            console.log('End Rental button clicked!');
            
            // Show loading state
            const submitBtn = this;
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
            
            // Submit form
            const form = document.getElementById('endRentalForm');
            console.log('End Rental form found:', form);
            
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.text())
            .then(html => {
                document.open();
                document.write(html);
                document.close();
                // Toast will be shown by the PHP-injected script after page reload
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                showToast('An error occurred. Please try again.', 'danger');
            });
        });
    } else {
        console.error('End Rental button with ID "submitEndRental" not found in the DOM');
    }
    
    // Enhance forms with AJAX
    document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
            
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.text())
            .then(html => {
                // Replace the entire page content with the new HTML
                document.open();
                document.write(html);
                document.close();
                
                // Show success message
                showToast('Operation completed successfully', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                // Show error message
                showToast('An error occurred. Please try again.', 'danger');
            });
        });
    });

    // Handle customer type selection
    const customerTypeSelect = document.getElementById('customerType');
    if (customerTypeSelect) {
        customerTypeSelect.addEventListener('change', function() {
            const isFreeParking = document.getElementById('freeParking');
            const isPaidParking = document.getElementById('paidParking');
            const isFreeField = document.getElementById('isFreeField');
            
            // Default to paid parking for all customer types
            isFreeParking.checked = false;
            isPaidParking.checked = true;
            isFreeField.value = '0';
            
            // Enable payment option buttons for all customer types
            isFreeParking.disabled = false;
            isPaidParking.disabled = false;
        });
    }
    
    // Initialize payment option state on page load
    const paidParking = document.getElementById('paidParking');
    const isFreeField = document.getElementById('isFreeField');
    if (paidParking && paidParking.checked) {
        isFreeField.value = '0';
    }
    
    // Handle payment option radio buttons
    document.querySelectorAll('input[name="payment_option"]').forEach(input => {
        input.addEventListener('change', function() {
            const isFreeField = document.getElementById('isFreeField');
            if (this.value === 'free') {
                isFreeField.value = '1';
            } else if (this.value === 'paid') {
                isFreeField.value = '0';
            }
        });
    });
    
    // Handle parking type selection
    document.querySelectorAll('input[name="parking_type"]').forEach(input => {
        input.addEventListener('change', function() {
            document.getElementById('isOvernightField').value = this.value === 'overnight' ? '1' : '0';
        });
    });
});

// Handle payment option radio buttons
document.addEventListener('DOMContentLoaded', function() {
    // Get the radio buttons and the hidden field
    const freeParking = document.getElementById('freeParking');
    const paidParking = document.getElementById('paidParking');
    const isFreeField = document.getElementById('isFreeField');
    
    if(freeParking && paidParking && isFreeField) {
        // Add event listeners to the radio buttons
        freeParking.addEventListener('change', function() {
            if(this.checked) {
                isFreeField.value = '1';
            }
        });
        
        paidParking.addEventListener('change', function() {
            if(this.checked) {
                isFreeField.value = '0';
            }
        });
    }
});
