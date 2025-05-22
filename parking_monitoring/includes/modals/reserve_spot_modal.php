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
