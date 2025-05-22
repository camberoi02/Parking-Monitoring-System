<!-- Add Sector Modal -->
<div class="modal fade" id="addSectorModal" tabindex="-1" aria-labelledby="addSectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addSectorModalLabel">Add New Parking Area</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSectorForm" method="post" action="">
                    <input type="hidden" name="action" value="add_sector">
                    <input type="hidden" name="active_tab" value="general">
                    
                    <div class="mb-3">
                        <label for="sector_name" class="form-label">Parking Area Name</label>
                        <input type="text" class="form-control" id="sector_name" name="sector_name" required 
                               placeholder="e.g., Floor 1, Building A, Zone 2">
                    </div>
                    
                    <div class="mb-3">
                        <label for="sector_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="sector_description" name="sector_description" rows="3"
                                  placeholder="Enter additional details about this sector"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveSectorBtn">
                    <i class="fas fa-plus-circle me-2"></i>Add Parking Area
                </button>
            </div>
        </div>
    </div>
</div>
