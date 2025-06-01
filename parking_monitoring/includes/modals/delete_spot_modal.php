<!-- Delete Spot Modal -->
<div class="modal fade" id="deleteSpotModal" tabindex="-1" aria-labelledby="deleteSpotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSpotModalLabel">Confirm Spot Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteSpotForm" method="post" action="includes/handlers/manage_spots.php">
                <div class="modal-body">
                    <p>Are you sure you want to delete the parking spot: <strong id="delete-spot-number"></strong>?</p>
                    <input type="hidden" name="action" value="delete_spot">
                    <input type="hidden" name="spot_id" id="delete_spot_id">
                    <input type="hidden" name="active_tab" value="general">
                    
                    <div class="alert alert-warning no-toast">
                        <p>This parking spot may have transaction history. If you wish to delete it along with its transaction history, please confirm below.</p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmDeleteWithHistory" name="confirm_delete_with_history" value="1">
                            <label class="form-check-label" for="confirmDeleteWithHistory">
                                <strong>I understand that this will delete all associated transaction history.</strong>
                            </label>
                        </div>
                    </div>
                    
                    <p class="mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteSpot">
                        <i class="fas fa-trash me-2"></i>Delete Spot
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
