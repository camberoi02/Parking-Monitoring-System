<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Transaction Filters</h5>
    </div>
    <div class="card-body">
        <form method="get" action="" id="transactionFilterForm">
            <input type="hidden" name="active_tab" value="reports">
            
            <!-- Keep existing search parameter if present -->
            <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
            <?php endif; ?>
            
            <div class="row g-3">
                <!-- Date Range -->
                <div class="col-md-6">
                    <label class="form-label">Date Range</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="date_from" id="dateFrom" placeholder="From date" 
                               value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                        <span class="input-group-text"><i class="fas fa-arrow-right"></i></span>
                        <input type="text" class="form-control" name="date_to" id="dateTo" placeholder="To date"
                               value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                    </div>
                </div>
                
                <!-- Transaction Type -->
                <div class="col-md-3">
                    <label class="form-label">Transaction Type</label>
                    <select class="form-select" name="transaction_type">
                        <option value="">All Types</option>
                        <option value="parking" <?php echo (isset($_GET['transaction_type']) && $_GET['transaction_type'] == 'parking') ? 'selected' : ''; ?>>Parking</option>
                        <option value="rental" <?php echo (isset($_GET['transaction_type']) && $_GET['transaction_type'] == 'rental') ? 'selected' : ''; ?>>Rental</option>
                        <option value="reservation" <?php echo (isset($_GET['transaction_type']) && $_GET['transaction_type'] == 'reservation') ? 'selected' : ''; ?>>Reservation</option>
                    </select>
                </div>
                
                <!-- Status -->
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <!-- Sector Filter (if sectors table exists) -->
                <?php if (isset($sectors) && !empty($sectors)): ?>
                <div class="col-md-4">
                    <label class="form-label">Sector</label>
                    <select class="form-select" name="sector_id">
                        <option value="">All Sectors</option>
                        <?php foreach ($sectors as $sector): ?>
                            <option value="<?php echo $sector['id']; ?>" <?php echo (isset($_GET['sector_id']) && $_GET['sector_id'] == $sector['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sector['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <!-- Fee Range (Min/Max) -->
                <div class="col-md-4">
                    <label class="form-label">Fee Range (₱)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="fee_min" placeholder="Min" min="0" step="1"
                               value="<?php echo isset($_GET['fee_min']) ? htmlspecialchars($_GET['fee_min']) : ''; ?>">
                        <span class="input-group-text"><i class="fas fa-arrow-right"></i></span>
                        <input type="number" class="form-control" name="fee_max" placeholder="Max" min="0" step="1"
                               value="<?php echo isset($_GET['fee_max']) ? htmlspecialchars($_GET['fee_max']) : ''; ?>">
                    </div>
                </div>
                
                <!-- Buttons -->
                <div class="col-md-4 d-flex align-items-end">
                    <div class="d-grid gap-2 d-md-flex w-100">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                        <a href="?active_tab=reports" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date pickers for filters
        if(typeof flatpickr !== 'undefined') {
            flatpickr("#dateFrom", {
                dateFormat: "Y-m-d",
                allowInput: true,
                position: "below",
                disableMobile: true
            });
            
            flatpickr("#dateTo", {
                dateFormat: "Y-m-d",
                allowInput: true,
                position: "below",
                disableMobile: true
            });
        }
    });
</script>
