<!-- Action Selection Modal -->
<div class="modal fade" id="actionSelectionModal" tabindex="-1" aria-labelledby="actionSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="actionSelectionModalLabel">Select Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-car-side fa-3x text-primary mb-3"></i>
                    <h5 id="actionSelectionSpotNumber">Parking Spot</h5>
                </div>
                
                <div class="row g-3">
                    <!-- Option 1: Check In Vehicle -->
                    <div class="col-12">
                        <div class="action-option" id="option-check-in">
                            <div class="card hover-effect border-0 shadow-sm">
                                <div class="card-body d-flex align-items-center p-4">
                                    <div class="option-icon bg-success rounded-circle p-3 me-3">
                                        <i class="fas fa-car-side fa-2x text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Short-term Parking</h5>
                                        <p class="text-muted mb-0">Check in a vehicle for temporary parking</p>
                                    </div>
                                    <i class="fas fa-chevron-right ms-auto text-muted"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Option 2: Reserve Spot -->
                    <div class="col-12">
                        <div class="action-option" id="option-reserve">
                            <div class="card hover-effect border-0 shadow-sm">
                                <div class="card-body d-flex align-items-center p-4">
                                    <div class="option-icon bg-warning rounded-circle p-3 me-3">
                                        <i class="fas fa-clock fa-2x text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Reserve for Later</h5>
                                        <p class="text-muted mb-0">Reserve this spot for a future time</p>
                                    </div>
                                    <i class="fas fa-chevron-right ms-auto text-muted"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Option 3: Rent Spot -->
                    <div class="col-12">
                        <div class="action-option" id="option-rent">
                            <div class="card hover-effect border-0 shadow-sm">
                                <div class="card-body d-flex align-items-center p-4">
                                    <div class="option-icon bg-info rounded-circle p-3 me-3">
                                        <i class="fas fa-calendar-alt fa-2x text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Long-term Rental</h5>
                                        <p class="text-muted mb-0">Reserve this spot for extended period</p>
                                    </div>
                                    <i class="fas fa-chevron-right ms-auto text-muted"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
