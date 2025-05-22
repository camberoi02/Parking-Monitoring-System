<!-- Edit Sector Modal -->
<div class="modal fade" id="editSectorModal" tabindex="-1" aria-labelledby="editSectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editSectorModalLabel">Edit Sector</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSectorForm" method="post" action="">
                    <input type="hidden" name="action" value="edit_sector">
                    <input type="hidden" name="active_tab" value="general">
                    <input type="hidden" name="sector_id" id="edit_sector_id">
                    
                    <div class="mb-3">
                        <label for="edit_sector_name" class="form-label">Sector Name</label>
                        <input type="text" class="form-control" id="edit_sector_name" name="sector_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_sector_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="edit_sector_description" name="sector_description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateSectorBtn">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
