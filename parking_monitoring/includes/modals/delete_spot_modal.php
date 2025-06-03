<!-- Delete Spot Modal -->
<div class="modal fade" id="deleteSpotModal" tabindex="-1" aria-labelledby="deleteSpotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSpotModalLabel">Confirm Spot Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
                <div class="modal-body">
                <p>Are you sure you want to delete parking spot <strong id="delete-spot-number"></strong>?</p>
                <p class="mb-0"><small class="text-muted">Note: Transaction history will be preserved for record-keeping purposes.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteSpot">Delete Spot</button>
                </div>
        </div>
    </div>
</div>
