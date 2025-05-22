<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Audit Trail Filters</h5>
    </div>
    <div class="card-body">
        <form method="get" action="" id="auditFilterForm">
            <!-- Keep existing search parameter if present -->
            <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
            <?php endif; ?>
            
            <div class="row g-3">
                <!-- Date Range -->
                <div class="col-md-6">
                    <label class="form-label">Date Range</label>
                    <div class="input-group">
                        <input type="text" class="form-control datepicker" name="date_from" id="dateFrom" placeholder="From date" 
                               value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                        <span class="input-group-text">to</span>
                        <input type="text" class="form-control datepicker" name="date_to" id="dateTo" placeholder="To date"
                               value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                    </div>
                </div>
                
                <!-- Action Type Filter -->
                <div class="col-md-3">
                    <label class="form-label">Action Type</label>
                    <select class="form-select" name="action_type">
                        <option value="">All Actions</option>
                        <option value="insert" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'insert') ? 'selected' : ''; ?>>Insert</option>
                        <option value="update" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'update') ? 'selected' : ''; ?>>Update</option>
                        <option value="delete" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'delete') ? 'selected' : ''; ?>>Delete</option>
                    </select>
                </div>
                
                <!-- Table Filter -->
                <div class="col-md-3">
                    <label class="form-label">Table</label>
                    <select class="form-select" name="table_name">
                        <option value="">All Tables</option>
                        <?php foreach ($table_names as $name): ?>
                            <option value="<?php echo htmlspecialchars($name); ?>" 
                                <?php echo (isset($_GET['table_name']) && $_GET['table_name'] == $name) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Username Filter -->
                <div class="col-md-3">
                    <label class="form-label">User</label>
                    <select class="form-select" name="username">
                        <option value="">All Users</option>
                        <?php foreach ($usernames as $name): ?>
                            <option value="<?php echo htmlspecialchars($name); ?>" 
                                <?php echo (isset($_GET['username']) && $_GET['username'] == $name) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="audit_trail.php" class="btn btn-outline-secondary">Clear</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers if not already initialized by the global script
    if (typeof flatpickr === 'function') {
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            allowInput: true
        });
    }
    
    // Auto-submit form when select fields change
    const selectFields = document.querySelectorAll('#auditFilterForm select');
    selectFields.forEach(field => {
        field.addEventListener('change', function() {
            document.getElementById('auditFilterForm').submit();
        });
    });
});
</script>
