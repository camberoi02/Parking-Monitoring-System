<!-- Cancel Reservation Modal -->
<div class="modal fade" id="cancelReservationModal" tabindex="-1" aria-labelledby="cancelReservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelReservationModalLabel">Cancel Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="cancelReservationForm" method="post" action="">
                    <input type="hidden" name="action" value="cancel_reservation">
                    <input type="hidden" name="spot_id" id="cancelReservationSpotId">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-calendar-times fa-3x text-danger mb-3"></i>
                        <h5 id="cancelReservationSpotNumber">Parking Spot</h5>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Reserved By:</div>
                                <div class="col-7 fw-bold" id="cancelReservationName"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Contact:</div>
                                <div class="col-7" id="cancelReservationContact"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Start Time:</div>
                                <div class="col-7" id="cancelReservationStartTime"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">End Time:</div>
                                <div class="col-7 fw-bold" id="cancelReservationEndTime"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Reservation Fee:</div>
                                <div class="col-7 fw-bold" id="cancelReservationFee"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning no-toast" id="cancelReservationWarning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Are you sure you want to cancel this reservation? This action cannot be undone.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                <button type="button" class="btn btn-danger" id="submitCancelReservation">
                    <i class="fas fa-times me-2"></i>Cancel Reservation
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to check if reservation has started
    function checkReservationStatus() {
        const startTimeStr = document.getElementById('cancelReservationStartTime').textContent;
        if (!startTimeStr) return;
        
        const startTime = new Date(startTimeStr);
        const now = new Date();
        
        const submitBtn = document.getElementById('submitCancelReservation');
        const warningDiv = document.getElementById('cancelReservationWarning');
        const modalTitle = document.getElementById('cancelReservationModalLabel');
        const modalHeader = document.querySelector('#cancelReservationModal .modal-header');
        
        if (now >= startTime) {
            // Reservation has started
            submitBtn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Check Out';
            submitBtn.classList.remove('btn-danger');
            submitBtn.classList.add('btn-success');
            warningDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>This reservation has started. Click "Check Out" to mark it as completed.';
            warningDiv.classList.remove('alert-warning');
            warningDiv.classList.add('alert-info');
            modalTitle.textContent = 'Check Out Reservation';
            modalHeader.classList.remove('bg-danger');
            modalHeader.classList.add('bg-success');
        } else {
            // Reservation hasn't started yet
            submitBtn.innerHTML = '<i class="fas fa-times me-2"></i>Cancel Reservation';
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-danger');
            warningDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Are you sure you want to cancel this reservation? This action cannot be undone.';
            warningDiv.classList.remove('alert-info');
            warningDiv.classList.add('alert-warning');
            modalTitle.textContent = 'Cancel Reservation';
            modalHeader.classList.remove('bg-success');
            modalHeader.classList.add('bg-danger');
        }
    }
    
    // Check status when modal is shown
    const cancelReservationModal = document.getElementById('cancelReservationModal');
    if (cancelReservationModal) {
        cancelReservationModal.addEventListener('shown.bs.modal', checkReservationStatus);
    }
});
</script>
