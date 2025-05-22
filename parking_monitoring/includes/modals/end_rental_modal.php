<!-- End Rental Modal -->
<div class="modal fade" id="endRentalModal" tabindex="-1" aria-labelledby="endRentalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="endRentalModalLabel">End Parking Spot Rental</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="endRentalForm" method="post" action="">
                    <input type="hidden" name="action" value="end_rental">
                    <input type="hidden" name="spot_id" id="endRentalSpotId">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-calendar-times fa-3x text-warning mb-3"></i>
                        <h5 id="endRentalSpotNumber">Parking Spot</h5>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Renter:</div>
                                <div class="col-7 fw-bold" id="endRentalRenterName"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Contact:</div>
                                <div class="col-7" id="endRentalRenterContact"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Start Date:</div>
                                <div class="col-7" id="endRentalStartDate"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">End Date:</div>
                                <div class="col-7" id="endRentalEndDate"></div>
                            </div>
                            <div class="row">
                                <div class="col-5 text-muted">Rate:</div>
                                <div class="col-7 fw-bold" id="endRentalRate"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning no-toast">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Are you sure you want to end this rental? This will make the spot available for regular parking again.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <a href="#" class="text-info text-decoration-none me-auto" id="downloadContract" onclick="downloadContract(event)">
                    <i class="fas fa-file-contract me-1"></i>View Contract
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="submitEndRental" onclick="document.getElementById('endRentalForm').submit();">
                    <i class="fas fa-check me-2"></i>End Rental
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const downloadContractBtn = document.getElementById('downloadContract');
    
    // Update the contract download link when the modal is shown
    document.getElementById('endRentalModal').addEventListener('show.bs.modal', function(event) {
        const spotId = document.getElementById('endRentalSpotId').value;
        console.log('Spot ID:', spotId); // Debug log
        
        if (!spotId) {
            console.error('No spot ID found');
            return;
        }
        
        const contractUrl = '/parking_monitoring/contracts/generate_rental_contract.php?spot_id=' + spotId;
        console.log('Contract URL:', contractUrl); // Debug log
        downloadContractBtn.href = contractUrl;
    });
});

function downloadContract(event) {
    event.preventDefault();
    const spotId = document.getElementById('endRentalSpotId').value;
    console.log('Downloading contract for spot ID:', spotId); // Debug log
    
    if (!spotId) {
        alert('Error: No spot ID found');
        return;
    }
    
    // Direct download
    window.location.href = '/parking_monitoring/contracts/generate_rental_contract.php?spot_id=' + spotId;
}
</script>
