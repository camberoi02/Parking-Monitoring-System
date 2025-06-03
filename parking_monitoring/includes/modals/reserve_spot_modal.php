<!-- Reserve Spot Modal -->
<div class="modal fade" id="reserveSpotModal" tabindex="-1" aria-labelledby="reserveSpotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="reserveSpotModalLabel">Check Out Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info no-toast">
                    <i class="fas fa-info-circle me-2"></i>
                    Reserve this spot for a specific time period.
                </div>
                <form id="reserveSpotForm" method="post" action="">
                    <input type="hidden" name="action" value="reserve_spot">
                    <input type="hidden" name="spot_id" id="reserveSpotId">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-calendar-check fa-3x text-warning mb-3"></i>
                        <h5 id="reserveSpotNumber">Parking Spot</h5>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reserverName" class="form-label">Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-user text-warning"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="reserverName" 
                                   name="reserver_name" placeholder="Enter your name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reserverContact" class="form-label">Contact Information</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-phone text-warning"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="reserverContact" 
                                   name="reserver_contact" placeholder="Enter contact information" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="reservationStartTime" class="form-label">Start Time</label>
                            <div class="input-group flex-nowrap">
                                <span class="input-group-text border-end-0">
                                    <i class="fas fa-clock text-warning"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="reservationStartTime" 
                                       name="reservation_start_time" placeholder="Select start time" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="reservationEndTime" class="form-label">End Time</label>
                            <div class="input-group flex-nowrap">
                                <span class="input-group-text border-end-0">
                                    <i class="fas fa-clock text-warning"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="reservationEndTime" 
                                       name="reservation_end_time" placeholder="Select end time" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reservationNotes" class="form-label">Notes (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-sticky-note text-warning"></i>
                            </span>
                            <textarea class="form-control border-start-0" id="reservationNotes" name="reservation_notes" rows="2" placeholder="Add any notes about this reservation"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reservationFee" class="form-label">Reservation Fee (₱)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-money-bill-wave text-warning"></i>
                            </span>
                            <input type="number" class="form-control border-start-0" id="reservationFee" 
                                   name="reservation_fee" min="0" step="0.01" value="0.00" required>
                        </div>
                        <div class="form-text">Enter the amount to be charged for this reservation</div>
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="submitReservation">
                    <i class="fas fa-calendar-check me-2"></i>Confirm Reservation
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize datetime pickers for reservations using Flatpickr
    const now = new Date();
    const startDefault = new Date(now.getTime() + 30 * 60000); // 30 minutes from now
    const endDefault = new Date(now.getTime() + 120 * 60000); // 2 hours from now
    
    // Common configuration for both pickers
    const commonConfig = {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minuteIncrement: 15,
        time_24hr: true,
        allowInput: true,
        disableMobile: true,
        static: true,
        appendTo: document.querySelector('#reserveSpotModal .modal-body'),
        onOpen: function(selectedDates, dateStr, instance) {
            setTimeout(() => {
                const input = instance.element;
                const calendar = instance.calendarContainer;
                const inputRect = input.getBoundingClientRect();
                
                // Position below the input
                calendar.style.position = 'fixed';
                calendar.style.top = (inputRect.bottom + 2) + 'px';
                calendar.style.left = inputRect.left + 'px';
                calendar.style.width = input.offsetWidth + 'px';
                
                // Ensure the calendar is visible within the viewport
                const calendarRect = calendar.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                if (calendarRect.bottom > viewportHeight) {
                    calendar.style.top = (inputRect.top - calendarRect.height - 2) + 'px';
                }
            }, 0);
        }
    };
    
    // Start time picker
    const reservationStartPicker = flatpickr("#reservationStartTime", {
        ...commonConfig,
        minDate: "today",
        defaultDate: startDefault,
        onChange: function(selectedDates) {
            if (selectedDates[0]) {
                // Update end time minDate when start time changes
                reservationEndPicker.set('minDate', selectedDates[0]);
                
                // If end time is before or equal to new start time, update it
                if (reservationEndPicker.selectedDates[0] <= selectedDates[0]) {
                    // Set end time to 1.5 hours after new start time
                    const newEndTime = new Date(selectedDates[0]);
                    newEndTime.setMinutes(newEndTime.getMinutes() + 90);
                    reservationEndPicker.setDate(newEndTime);
                }
            }
        }
    });
    
    // End time picker
    const reservationEndPicker = flatpickr("#reservationEndTime", {
        ...commonConfig,
        minDate: endDefault,
        defaultDate: endDefault
    });
    
    // Update fee calculation when dates change
    [reservationStartPicker, reservationEndPicker].forEach(picker => {
        picker.config.onChange.push(() => calculateReservationFee());
    });
    
    // Handle modal events
    const reserveSpotModal = document.getElementById('reserveSpotModal');
    reserveSpotModal.addEventListener('shown.bs.modal', function() {
        // Reposition pickers when modal is shown
        reservationStartPicker.redraw();
        reservationEndPicker.redraw();
    });
    
    reserveSpotModal.addEventListener('scroll', function() {
        // Close pickers on modal scroll
        reservationStartPicker.close();
        reservationEndPicker.close();
    });
});

// Function to calculate reservation fee
function calculateReservationFee() {
    const startTime = document.getElementById('reservationStartTime')._flatpickr.selectedDates[0];
    const endTime = document.getElementById('reservationEndTime')._flatpickr.selectedDates[0];
    
    if (startTime && endTime) {
        // Calculate hours difference
        const hours = (endTime - startTime) / (1000 * 60 * 60);
        const baseFee = 100; // Base reservation fee
        const fee = Math.max(baseFee, hours * 20); // Minimum fee of 100
        document.getElementById('reservationFee').value = fee.toFixed(2);
    }
}

// Function to calculate rental fee
function calculateRentalFee() {
    const startDate = $('#rentalStartDate').datepicker('getDate');
    const endDate = $('#rentalEndDate').datepicker('getDate');
    
    if (startDate && endDate) {
        // Add your rental fee calculation logic here
        const months = (endDate.getFullYear() - startDate.getFullYear()) * 12 + 
                      (endDate.getMonth() - startDate.getMonth());
        const monthlyRate = parseFloat($('#rentalRate').val()) || 3000;
        const totalFee = months * monthlyRate;
        $('#rentalRate').val(totalFee.toFixed(2));
    }
}
</script>
