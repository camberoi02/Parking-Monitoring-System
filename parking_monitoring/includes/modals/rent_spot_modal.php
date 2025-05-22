<!-- Rent Spot Modal -->
<div class="modal fade" id="rentSpotModal" tabindex="-1" aria-labelledby="rentSpotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="rentSpotModalLabel">Rent Parking Spot</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info no-toast">
                    <i class="fas fa-info-circle me-2"></i>
                    Fill out the form to rent this parking spot for an extended period.
                </div>
                <form id="rentSpotForm" method="post" action="">
                    <input type="hidden" name="action" value="rent_spot">
                    <input type="hidden" name="spot_id" id="rentSpotId">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                        <h5 id="rentSpotNumber">Parking Spot</h5>
                    </div>
                    
                    <div class="mb-3">
                        <label for="renterName" class="form-label">Renter Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-user text-primary"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="renterName" 
                                   name="renter_name" placeholder="Enter renter name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="renterContact" class="form-label">Contact Information</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-phone text-primary"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="renterContact" 
                                   name="renter_contact" placeholder="Enter contact information" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="rentalStartDate" class="form-label">Start Date</label>
                            <div class="input-group flex-nowrap">
                                <span class="input-group-text border-end-0">
                                    <i class="fas fa-calendar-day text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="rentalStartDate" 
                                       name="rental_start_date" placeholder="Select start date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="rentalEndDate" class="form-label">End Date</label>
                            <div class="input-group flex-nowrap">
                                <span class="input-group-text border-end-0">
                                    <i class="fas fa-calendar-day text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="rentalEndDate" 
                                       name="rental_end_date" placeholder="Select end date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rentalRate" class="form-label">Monthly Rental Rate (₱)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-money-bill text-primary"></i>
                            </span>
                            <input type="number" class="form-control border-start-0" id="rentalRate" 
                                   name="rental_rate" min="0" step="100" value="3000" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rentalNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="rentalNotes" name="rental_notes" rows="2" 
                                  placeholder="Additional notes about the rental"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitRentSpot">
                    <i class="fas fa-calendar-check me-2"></i>Confirm Rental
                </button>
            </div>
        </div>
    </div>
</div>
