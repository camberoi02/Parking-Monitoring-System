<?php
/**
 * Generate status cards HTML
 */
function generateStatusCardsHTML($statistics) {
    ob_start();
    ?>
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary rounded-3 p-3 me-3">
                        <i class="fas fa-car-side fa-2x text-white"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Total Spots</h6>
                        <h2 class="mb-0"><?php echo $statistics['total_spots']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success rounded-3 p-3 me-3">
                        <i class="fas fa-check-circle fa-2x text-white"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Available Spots</h6>
                        <h2 class="mb-0"><?php echo $statistics['available_spots']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 bg-danger rounded-3 p-3 me-3">
                        <i class="fas fa-clock fa-2x text-white"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Occupied Spots</h6>
                        <h2 class="mb-0"><?php echo $statistics['occupied_spots']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Generate parking spots HTML
 */
function generateParkingSpotsHTML($conn, $parking_spots) {
    // Get sectors for filtering if the table exists
    $sectors = [];
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'sectors'");
    if (mysqli_num_rows($result) > 0) {
        $sql = "SELECT id, name FROM sectors ORDER BY name";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $sectors[] = $row;
            }
        }
    }
    
    ob_start();
    ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-4 mb-2 mb-md-0">
                    <h5 class="mb-0">Parking Spots</h5>
                </div>
                <div class="col-md-8">
                    <div class="d-flex flex-wrap justify-content-md-end align-items-center">
                        <!-- Filters Group -->
                        <div class="d-flex flex-wrap align-items-center me-2">
                            <!-- Status Filter Label -->
                            <span class="me-2 text-muted small">Status:</span>
                            
                            <div class="btn-group me-3 mb-2 mb-md-0">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="filterAll">All</button>
                                <button type="button" class="btn btn-sm btn-outline-success" id="filterAvailable">Available</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="filterOccupied">Occupied</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="filterReserved">Reserved</button>
                                <button type="button" class="btn btn-sm btn-outline-info" id="filterRented">Rented</button>
                            </div>
                            
                            <?php if (!empty($sectors)): ?>
                            <!-- Sector Filter with Label -->
                            <span class="me-2 text-muted small">Sector:</span>
                            <div class="dropdown mb-2 mb-md-0">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="sectorFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    All Sectors
                                </button>
                                <ul class="dropdown-menu sector-filter" aria-labelledby="sectorFilterDropdown">
                                    <li><a class="dropdown-item active" href="#" data-sector="all">All Sectors</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php foreach ($sectors as $sector): ?>
                                        <li><a class="dropdown-item" href="#" data-sector="<?php echo $sector['id']; ?>"><?php echo htmlspecialchars($sector['name']); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ms-auto me-0 me-md-2">
                            <a href="system_settings.php?active_tab=general" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>Add Spot
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($parking_spots)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-parking fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted">No parking spots found</h4>
                    <p>Add some parking spots in the System Settings page.</p>
                    <a href="system_settings.php?active_tab=general" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Parking Spot
                    </a>
                </div>
            <?php else: ?>
                <div class="row" id="spotsContainer">
                    <?php foreach ($parking_spots as $spot): ?>
                        <?php
                        // Calculate duration and fee for occupied spots
                        $calc = ['duration' => '', 'fee' => 0];
                        if ($spot['is_occupied'] && !empty($spot['entry_time'])) {
                            $is_free = isset($spot['is_free']) && $spot['is_free'] == 1;
                            $calc = calculateDurationAndFee($conn, $spot['entry_time'], $is_free, $spot['vehicle_type'], $spot['customer_type'], $spot['is_overnight']);
                        }
                        
                        // Determine spot status for filtering
                        $status = 'available';
                        if ($spot['is_occupied']) {
                            $status = 'occupied';
                        } elseif (isset($spot['is_rented']) && $spot['is_rented'] == 1) {
                            $status = 'rented';
                        } elseif (isset($spot['is_reserved']) && $spot['is_reserved'] == 1) {
                            $status = 'reserved';
                        }
                        
                        // Format rental dates if available
                        $rental_start_formatted = '';
                        $rental_end_formatted = '';
                        if (!empty($spot['rental_start_date'])) {
                            $rental_start_formatted = date('M d, Y', strtotime($spot['rental_start_date']));
                        }
                        if (!empty($spot['rental_end_date'])) {
                            $rental_end_formatted = date('M d, Y', strtotime($spot['rental_end_date']));
                        }
                        
                        // Format reservation times if available
                        $reservation_start_formatted = '';
                        $reservation_end_formatted = '';
                        if (!empty($spot['reservation_start_time'])) {
                            $reservation_start_formatted = date('M d, Y g:i A', strtotime($spot['reservation_start_time']));
                        }
                        if (!empty($spot['reservation_end_time'])) {
                            $reservation_end_formatted = date('M d, Y g:i A', strtotime($spot['reservation_end_time']));
                        }
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4 spot-item" 
                             data-status="<?php echo $status; ?>"
                             data-sector-id="<?php echo isset($spot['sector_id']) ? $spot['sector_id'] : ''; ?>">
                            <div class="card h-100 spot-card 
                                <?php if ($spot['is_occupied']): ?>
                                    bg-danger
                                <?php elseif (isset($spot['is_rented']) && $spot['is_rented'] == 1): ?>
                                    bg-info
                                <?php elseif (isset($spot['is_reserved']) && $spot['is_reserved'] == 1): ?>
                                    bg-warning
                                <?php else: ?>
                                    bg-success
                                <?php endif; ?> 
                                text-white clickable-card"
                                 style="cursor: pointer;" 
                                 data-spot-id="<?php echo $spot['id']; ?>" 
                                 data-spot-number="<?php echo htmlspecialchars($spot['spot_number']); ?>"
                                 data-status="<?php echo $status; ?>"
                                 <?php if ($spot['is_occupied']): ?>
                                 data-vehicle-id="<?php echo htmlspecialchars($spot['vehicle_id']); ?>"
                                 data-customer-name="<?php echo htmlspecialchars($spot['customer_name'] ?? ''); ?>"
                                 data-vehicle-type="<?php echo htmlspecialchars($spot['vehicle_type'] ?? ''); ?>"
                                 data-entry-time="<?php echo $spot['entry_time']; ?>"
                                 data-entry-time-formatted="<?php echo date('M d, Y g:i A', strtotime($spot['entry_time'])); ?>"
                                 data-duration="<?php echo $calc['duration']; ?>"
                                 data-fee="<?php echo number_format($calc['fee'], 2); ?>"
                                 data-is-free="<?php echo isset($spot['is_free']) && $spot['is_free'] == 1 ? '1' : '0'; ?>"
                                 data-is-overnight="<?php echo isset($spot['is_overnight']) && $spot['is_overnight'] == 1 ? '1' : '0'; ?>"
                                 <?php elseif (isset($spot['is_rented']) && $spot['is_rented'] == 1): ?>
                                 data-renter-name="<?php echo htmlspecialchars($spot['renter_name']); ?>"
                                 data-renter-contact="<?php echo htmlspecialchars($spot['renter_contact']); ?>"
                                 data-rental-start-date="<?php echo $spot['rental_start_date']; ?>"
                                 data-rental-end-date="<?php echo $spot['rental_end_date']; ?>"
                                 data-rental-start-formatted="<?php echo $rental_start_formatted; ?>"
                                 data-rental-end-formatted="<?php echo $rental_end_formatted; ?>"
                                 data-rental-rate="<?php echo number_format($spot['rental_rate'], 2); ?>"
                                 data-rental-notes="<?php echo htmlspecialchars($spot['rental_notes'] ?? ''); ?>"
                                 <?php elseif (isset($spot['is_reserved']) && $spot['is_reserved'] == 1): ?>
                                 data-reserver-name="<?php echo htmlspecialchars($spot['reserver_name']); ?>"
                                 data-reserver-contact="<?php echo htmlspecialchars($spot['reserver_contact']); ?>"
                                 data-reservation-start-time="<?php echo $spot['reservation_start_time']; ?>"
                                 data-reservation-end-time="<?php echo $spot['reservation_end_time']; ?>"
                                 data-reservation-start-formatted="<?php echo $reservation_start_formatted; ?>"
                                 data-reservation-end-formatted="<?php echo $reservation_end_formatted; ?>"
                                 data-reservation-notes="<?php echo htmlspecialchars($spot['reservation_notes'] ?? ''); ?>"
                                 data-reservation-fee="<?php echo number_format($spot['reservation_fee'] ?? 0, 2); ?>"
                                 data-is-paid="<?php echo isset($spot['is_paid']) ? $spot['is_paid'] : '1'; ?>"
                                 <?php endif; ?>>
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-<?php 
                                        if ($spot['is_occupied']) {
                                            echo 'car';
                                        } elseif (isset($spot['is_rented']) && $spot['is_rented'] == 1) {
                                            echo 'calendar-alt';
                                        } elseif (isset($spot['is_reserved']) && $spot['is_reserved'] == 1) {
                                            echo 'clock';
                                        } else {
                                            echo 'square-parking';
                                        } ?> me-2"></i>
                                        Spot <?php echo htmlspecialchars($spot['spot_number']); ?>
                                    </h5>
                                    <span class="badge rounded-pill bg-white text-<?php 
                                    if ($spot['is_occupied']) {
                                        echo 'danger';
                                    } elseif (isset($spot['is_rented']) && $spot['is_rented'] == 1) {
                                        echo 'info';
                                    } elseif (isset($spot['is_reserved']) && $spot['is_reserved'] == 1) {
                                        echo 'warning';
                                    } else {
                                        echo 'success';
                                    } ?>">
                                        <?php 
                                        if ($spot['is_occupied']) {
                                            echo 'Occupied';
                                        } elseif (isset($spot['is_rented']) && $spot['is_rented'] == 1) {
                                            echo 'Rented';
                                        } elseif (isset($spot['is_reserved']) && $spot['is_reserved'] == 1) {
                                            echo 'Reserved';
                                        } else {
                                            echo 'Available';
                                        } 
                                        ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php if ($spot['is_occupied']): ?>
                                        <!-- Layout for occupied spots - updated with better content fitting -->
                                        <div class="mb-3">
                                            <div class="row g-3">
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Customer</small>
                                                            <div class="fw-bold text-truncate"><?php echo htmlspecialchars($spot['customer_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-id-card fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Vehicle ID</small>
                                                            <div class="fw-bold text-truncate"><?php echo htmlspecialchars($spot['vehicle_id']); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-car fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Vehicle Type</small>
                                                            <div class="text-truncate"><?php echo htmlspecialchars($spot['vehicle_type'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user-tag fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Customer Type</small>
                                                            <div class="text-truncate">
                                                                <?php echo $spot['customer_type'] === 'pasig_employee' ? 'Pasig City Employee' : 'Private Individual'; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-clock fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Duration</small>
                                                            <div class="text-truncate"><?php echo $calc['duration']; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-calendar-check fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Entry Time</small>
                                                            <div class="text-truncate"><?php echo date('M d, g:i A', strtotime($spot['entry_time'])); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-money-bill-wave fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Current Fee</small>
                                                            <?php if (isset($spot['is_free']) && $spot['is_free'] == 1): ?>
                                                                <div class="fw-bold">
                                                                    <span class="badge rounded-pill bg-warning text-dark">Free Parking</span>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="fw-bold">
                                                                    ₱<?php echo number_format($calc['fee'], 2); ?>
                                                                    <?php if ($spot['customer_type'] === 'pasig_employee'): ?>
                                                                        <span class="badge rounded-pill bg-info text-dark ms-1">Pasig City Employee Rate</span>
                                                                    <?php endif; ?>
                                                                    <?php if ($spot['is_overnight']): ?>
                                                                        <span class="badge rounded-pill bg-primary text-white ms-1">Overnight</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-3">
                                            <span class="text-white-50 small">Click card to check out vehicle</span>
                                        </div>
                                    <?php elseif (isset($spot['is_rented']) && $spot['is_rented'] == 1): ?>
                                        <!-- Layout for rented spots - updated for better content fitting -->
                                        <div class="mb-3">
                                            <div class="row g-3">
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Renter</small>
                                                            <div class="fw-bold text-truncate"><?php echo htmlspecialchars($spot['renter_name']); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-phone fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Contact</small>
                                                            <div class="text-truncate"><?php echo htmlspecialchars($spot['renter_contact']); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-calendar-day fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Rental Period</small>
                                                            <div class="text-truncate"><?php echo $rental_start_formatted; ?> - <?php echo $rental_end_formatted; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-money-bill-wave fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Monthly Rate</small>
                                                            <div class="fw-bold text-truncate">₱<?php echo number_format($spot['rental_rate'], 2); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-3">
                                            <span class="text-white-50 small">Click card to manage rental</span>
                                        </div>
                                    <?php elseif (isset($spot['is_reserved']) && $spot['is_reserved'] == 1): ?>
                                        <!-- Layout for reserved spots - updated for better content fitting -->
                                        <div class="mb-3">
                                            <div class="row g-3">
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Reserved By</small>
                                                            <div class="fw-bold text-truncate"><?php echo htmlspecialchars($spot['reserver_name']); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-phone fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Contact</small>
                                                            <div class="text-truncate"><?php echo htmlspecialchars($spot['reserver_contact']); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-hourglass-start fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Start Time</small>
                                                            <div class="text-truncate"><?php echo $reservation_start_formatted; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-hourglass-end fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">End Time</small>
                                                            <div class="text-truncate"><?php echo $reservation_end_formatted; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12 col-xl-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-money-bill-wave fa-lg opacity-75 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <small class="text-white-50 d-block text-truncate">Reservation Fee</small>
                                                            <div class="fw-bold text-truncate">
                                                                ₱<?php echo number_format($spot['reservation_fee'] ?? 0, 2); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-3">
                                            <span class="text-white-50 small">Click card to manage reservation</span>
                                        </div>
                                    <?php else: ?>
                                        <!-- SIMPLIFIED UI FOR AVAILABLE SPOTS -->
                                        <div class="text-center py-4">
                                            <div class="mb-3">
                                                <i class="fas fa-square-parking fa-3x text-white"></i>
                                            </div>
                                            <h5 class="mb-2">Available</h5>
                                            <p class="text-muted small">Click to manage this spot</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Generate modals HTML (check-in, check-out, rent, and end rental)
 */
function generateModalsHTML() {
    ob_start();
    ?>
    <!-- Check-in Modal -->
    <div class="modal fade" id="checkInModal" tabindex="-1" aria-labelledby="checkInModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="checkInModalLabel">Check In Vehicle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="checkInForm" method="post" action="">
                        <input type="hidden" name="action" value="check_in">
                        <input type="hidden" name="spot_id" id="checkInSpotId">
                        
                        <div class="text-center mb-4">
                            <i class="fas fa-car-side fa-3x text-success mb-3"></i>
                            <h5 id="checkInSpotNumber">Parking Spot</h5>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customerType" class="form-label">Customer Type</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-user-tag text-success"></i>
                                </span>
                                <select class="form-select border-start-0" id="customerType" name="customer_type" required>
                                    <option value="">Select customer type</option>
                                    <option value="pasig_employee">Pasig City Employee</option>
                                    <option value="private">Private Individual</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customerName" class="form-label">Customer Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-user text-success"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="customerName" 
                                       name="customer_name" placeholder="Enter customer name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="vehicleId" class="form-label">Vehicle ID / Plate Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-id-card text-success"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="vehicleId" 
                                       name="vehicle_id" placeholder="Enter plate number" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="vehicleType" class="form-label">Vehicle Type</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-car text-success"></i>
                                </span>
                                <select class="form-select border-start-0" id="vehicleType" name="vehicle_type" required>
                                    <option value="">Select vehicle type</option>
                                    <option value="Vehicle">Vehicle</option>
                                    <option value="Motorcycle">Motorcycle</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-block">Parking Type</label>
                            <div class="btn-group w-100" role="group" aria-label="Parking type options">
                                <input type="radio" class="btn-check" name="parking_type" value="day" id="dayParking" autocomplete="off" checked>
                                <label class="btn btn-outline-success w-50" for="dayParking">
                                    <i class="fas fa-sun me-2"></i>Day Parking
                                </label>
                                <input type="radio" class="btn-check" name="parking_type" value="overnight" id="overnightParking" autocomplete="off">
                                <label class="btn btn-outline-primary w-50" for="overnightParking">
                                    <i class="fas fa-moon me-2"></i>Overnight
                                </label>
                            </div>
                            <input type="hidden" name="is_overnight" id="isOvernightField" value="0">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-block">Payment Option</label>
                            <div class="btn-group w-100" role="group" aria-label="Payment options">
                                <input type="radio" class="btn-check" name="payment_option" value="free" id="freeParking" autocomplete="off">
                                <label class="btn btn-outline-success w-50" for="freeParking">
                                    <i class="fas fa-tag me-2"></i>Free Parking
                                </label>
                                <input type="radio" class="btn-check" name="payment_option" value="paid" id="paidParking" autocomplete="off" checked>
                                <label class="btn btn-outline-primary w-50" for="paidParking">
                                    <i class="fas fa-money-bill-wave me-2"></i>With Payment
                                </label>
                            </div>
                            <input type="hidden" name="is_free" id="isFreeField" value="0">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="submitCheckIn">
                        <i class="fas fa-sign-in-alt me-2"></i>Check In Vehicle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Check-out Modal -->
    <div class="modal fade" id="checkOutModal" tabindex="-1" aria-labelledby="checkOutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="checkOutModalLabel">Check Out Vehicle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="checkOutForm" method="post" action="">
                        <input type="hidden" name="action" value="check_out">
                        <input type="hidden" name="spot_id" id="checkOutSpotId">
                        
                        <div class="text-center mb-4">
                            <i class="fas fa-car fa-3x text-danger mb-3"></i>
                            <h5 id="checkOutSpotNumber">Parking Spot</h5>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Customer:</div>
                                    <div class="col-7 fw-bold" id="checkOutCustomerName"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Vehicle ID:</div>
                                    <div class="col-7 fw-bold" id="checkOutVehicleId"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Vehicle Type:</div>
                                    <div class="col-7" id="checkOutVehicleType"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Entry Time:</div>
                                    <div class="col-7" id="checkOutEntryTime"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Duration:</div>
                                    <div class="col-7" id="checkOutDuration"></div>
                                </div>
                                <div class="row">
                                    <div class="col-5 text-muted">Fee:</div>
                                    <div class="col-7" id="checkOutFeeContainer">
                                        <span class="fw-bold fs-5 text-danger" id="checkOutFee"></span>
                                        <span class="badge bg-warning text-dark d-none" id="checkOutFreeParking">Free Parking</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info no-toast">
                            <i class="fas fa-info-circle me-2"></i>
                            Clicking "Confirm Check Out" will finalize this transaction.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="submitCheckOut">
                        <i class="fas fa-sign-out-alt me-2"></i>Confirm Check Out
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rent Spot Modal - Include the new modal -->
    <?php include_once 'modals/rent_spot_modal.php'; ?>
    
    <!-- End Rental Modal - Include the new modal -->
    <?php include_once 'modals/end_rental_modal.php'; ?>
    
    <!-- Reserve Spot Modal - Include the new modal -->
    <?php include_once 'modals/reserve_spot_modal.php'; ?>
    
    <!-- Cancel Reservation Modal - Include the new modal -->
    <?php include_once 'modals/cancel_reservation_modal.php'; ?>
    <?php
    return ob_get_clean();
}
?>
