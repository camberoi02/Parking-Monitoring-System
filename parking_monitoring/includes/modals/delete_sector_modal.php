<!-- Delete Sector Modal -->
<div class="modal fade" id="deleteSectorModal" tabindex="-1" aria-labelledby="deleteSectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSectorModalLabel">Confirm Sector Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the sector: <strong id="delete-sector-name"></strong>?</p>
                <div id="sector-has-spots" class="alert alert-warning no-toast">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This sector contains <span id="sector-spots-count"></span> parking spots. You must reassign or delete these spots before deleting the sector.
                </div>
                <p class="mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteSectorForm" method="post" action="">
                    <input type="hidden" name="action" value="delete_sector">
                    <input type="hidden" name="sector_id" id="delete_sector_id">
                    <input type="hidden" name="active_tab" value="general">
                    <button type="submit" class="btn btn-danger" id="deleteSectorBtn">
                        <i class="fas fa-trash me-2"></i>Delete Sector
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
