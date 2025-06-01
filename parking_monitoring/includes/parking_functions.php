<?php
// Functions for parking operations

/**
 * Check in a vehicle to a parking spot
 */
function checkInVehicle($conn, $spot_id, $vehicle_id) {
    $spot_id = mysqli_real_escape_string($conn, $spot_id);
    $vehicle_id = strtoupper(mysqli_real_escape_string($conn, $vehicle_id)); // Convert to uppercase
    $customer_name = strtoupper(mysqli_real_escape_string($conn, $_POST['customer_name'] ?? '')); // Convert to uppercase
    $vehicle_type = mysqli_real_escape_string($conn, $_POST['vehicle_type'] ?? '');
    $customer_type = mysqli_real_escape_string($conn, $_POST['customer_type'] ?? '');
    $is_overnight = (isset($_POST['is_overnight']) && $_POST['is_overnight'] == '1') ? 1 : 0;
    
    // Set is_free based on payment_option, regardless of customer type
    $is_free = (isset($_POST['is_free']) && $_POST['is_free'] == '1') ? 1 : 0;
    
    // Get current time in MySQL format
    $current_time = date("Y-m-d H:i:s");
    
    // Update parking spot
    $sql = "UPDATE parking_spots SET 
            is_occupied = 1, 
            vehicle_id = '$vehicle_id', 
            customer_name = '$customer_name', 
            vehicle_type = '$vehicle_type',
            is_free = $is_free, 
            is_overnight = $is_overnight,
            customer_type = '$customer_type',
            entry_time = '$current_time' 
            WHERE id = $spot_id";
            
    if (mysqli_query($conn, $sql)) {
        // Create transaction record
        $sql = "INSERT INTO transactions 
                (spot_id, vehicle_id, customer_name, vehicle_type, entry_time, is_free, is_overnight, customer_type) 
                VALUES ($spot_id, '$vehicle_id', '$customer_name', '$vehicle_type', '$current_time', $is_free, $is_overnight, '$customer_type')";
                
        if (mysqli_query($conn, $sql)) {
            // Log the action to audit trail
            $transaction_id = mysqli_insert_id($conn);
            logAudit($conn, 'insert', 'transactions', $transaction_id, null, null, "Vehicle check-in: $vehicle_id at spot $spot_id");
            logAudit($conn, 'update', 'parking_spots', $spot_id, 'status', 'available', 'occupied');
            
            $message = "Vehicle checked in successfully";
            if ($is_free) {
                $message .= " (Free Parking)";
            }
            if ($is_overnight) {
                $message .= " (Overnight Parking)";
            }
            
            return ["success" => true, "message" => $message];
        } else {
            return ["success" => false, "message" => "Error creating transaction: " . mysqli_error($conn)];
        }
    } else {
        return ["success" => false, "message" => "Error updating spot: " . mysqli_error($conn)];
    }
}

/**
 * Check out a vehicle from a parking spot
 */
function checkOutVehicle($conn, $spot_id) {
    $spot_id = mysqli_real_escape_string($conn, $spot_id);
    
    // Get vehicle information
    $sql = "SELECT vehicle_id, customer_name, vehicle_type, entry_time, is_free, is_overnight, customer_type FROM parking_spots WHERE id = $spot_id";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        $vehicle_id = $row['vehicle_id'];
        $customer_name = $row['customer_name'];
        $vehicle_type = $row['vehicle_type'];
        $entry_time = $row['entry_time'];
        $is_free = $row['is_free'] ?? 0;
        $is_overnight = $row['is_overnight'] ?? 0;
        $customer_type = $row['customer_type'] ?? '';
        
        // Use the same calculation function for consistency
        $calc = calculateDurationAndFee($conn, $entry_time, $is_free, $vehicle_type, $customer_type, $is_overnight);
        $fee = $calc['fee'];
        
        // Update transaction
        $sql = "UPDATE transactions SET 
                exit_time = NOW(), 
                fee = $fee,
                is_overnight = $is_overnight
                WHERE spot_id = $spot_id 
                AND vehicle_id = '$vehicle_id' 
                AND exit_time IS NULL 
                ORDER BY entry_time DESC LIMIT 1";
        mysqli_query($conn, $sql);
        
        // Get the transaction ID for audit logging
        $transaction_id = 0;
        $get_transaction = mysqli_query($conn, "SELECT id FROM transactions WHERE spot_id = $spot_id AND vehicle_id = '$vehicle_id' ORDER BY entry_time DESC LIMIT 1");
        if ($get_transaction && $transaction_row = mysqli_fetch_assoc($get_transaction)) {
            $transaction_id = $transaction_row['id'];
        }
        
        // Update parking spot - clear all relevant fields
        $sql = "UPDATE parking_spots SET 
                is_occupied = 0, 
                vehicle_id = NULL, 
                customer_name = NULL, 
                vehicle_type = NULL,
                customer_type = NULL,
                is_free = 0,
                is_overnight = 0,
                entry_time = NULL 
                WHERE id = $spot_id";
                
        if (mysqli_query($conn, $sql)) {
            // Log to audit trail
            logAudit($conn, 'update', 'transactions', $transaction_id, 'exit_time', null, date('Y-m-d H:i:s'));
            logAudit($conn, 'update', 'transactions', $transaction_id, 'fee', null, $fee);
            logAudit($conn, 'update', 'parking_spots', $spot_id, 'status', 'occupied', 'available');
            
            // Create a more detailed message
            $message = "Vehicle checked out successfully.";
            if ($is_free) {
                $message .= " (Free Parking - " . ($customer_type === 'pasig_employee' ? 'Pasig City Employee' : 'Special Rate') . ")";
            } else {
                $message .= " Fee: ₱" . number_format($fee, 2);
            }
            if ($is_overnight) {
                $message .= " (Overnight Parking)";
            }
            
            return [
                "success" => true, 
                "message" => $message, 
                "fee" => $fee, 
                "is_free" => $is_free,
                "is_overnight" => $is_overnight,
                "duration" => $calc['duration']
            ];
        } else {
            return ["success" => false, "message" => "Error updating spot: " . mysqli_error($conn)];
        }
    } else {
        return ["success" => false, "message" => "Error retrieving spot information"];
    }
}

/**
 * Check and update rental statuses
 */
function checkRentalStatuses($conn) {
    $current_date = date('Y-m-d');
    
    // Get all rented spots
    $sql = "SELECT id, rental_start_date, rental_end_date FROM parking_spots WHERE is_rented = 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $spot_id = $row['id'];
            $start_date = new DateTime($row['rental_start_date']);
            $end_date = new DateTime($row['rental_end_date']);
            $current = new DateTime($current_date);
            
            // If rental has ended, mark it as completed
            if ($current > $end_date) {
                endParkingSpotRental($conn, $spot_id);
            }
        }
    }
}

/**
 * Get parking statistics
 */
function getParkingStatistics($conn) {
    // First check and update rental statuses
    checkRentalStatuses($conn);
    
    $statistics = [
        'total_spots' => 0,
        'available_spots' => 0,
        'occupied_spots' => 0
    ];
    
    $sql = "SELECT COUNT(*) as total FROM parking_spots";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $statistics['total_spots'] = $row['total'];
    }
    
    // Count truly available spots (not occupied, not rented, not reserved with active reservation)
    $current_time = date('Y-m-d H:i:s');
    $sql = "SELECT COUNT(*) as available FROM parking_spots 
            WHERE is_occupied = 0 
            AND is_rented = 0 
            AND (is_reserved = 0 OR reservation_start_time > '$current_time')";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $statistics['available_spots'] = $row['available'];
    }
    
    // Count occupied spots (including active reservations and rented spots)
    $sql = "SELECT COUNT(*) as occupied FROM parking_spots 
            WHERE is_occupied = 1 
            OR is_rented = 1 
            OR (is_reserved = 1 AND reservation_start_time <= '$current_time' AND reservation_end_time >= '$current_time')";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $statistics['occupied_spots'] = $row['occupied'];
    }
    
    return $statistics;
}

/**
 * Get all parking spots
 */
function getAllParkingSpots($conn) {
    $sql = "SELECT * FROM parking_spots ORDER BY 
            CASE WHEN spot_number REGEXP '^[A-Z][0-9]+$' THEN 1 ELSE 2 END,
            SUBSTRING(spot_number, 1, 1),
            CAST(SUBSTRING(spot_number, 2) AS UNSIGNED)";
    $result = mysqli_query($conn, $sql);
    $parking_spots = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $parking_spots[] = $row;
        }
    }
    return $parking_spots;
}

/**
 * Get vehicle base fee from settings
 */
function getVehicleBaseFee($conn, $vehicle_type, $customer_type = 'private') {
    $setting_key = '';
    if ($customer_type === 'pasig_employee') {
        $setting_key = $vehicle_type === 'Vehicle' ? 'pasig_vehicle_base_fee' : 'pasig_motorcycle_base_fee';
    } else {
        $setting_key = $vehicle_type === 'Vehicle' ? 'vehicle_base_fee' : 'motorcycle_base_fee';
    }
    
    $sql = "SELECT setting_value FROM settings WHERE setting_key = '$setting_key'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return floatval($row['setting_value']);
    }
    return $vehicle_type === 'Vehicle' ? 40.00 : 20.00; // Default values
}

/**
 * Get vehicle hourly rate from settings
 */
function getVehicleHourlyRate($conn, $vehicle_type, $customer_type = 'private') {
    $setting_key = '';
    if ($customer_type === 'pasig_employee') {
        $setting_key = $vehicle_type === 'Vehicle' ? 'pasig_vehicle_hourly_rate' : 'pasig_motorcycle_hourly_rate';
    } else {
        $setting_key = $vehicle_type === 'Vehicle' ? 'vehicle_hourly_rate' : 'motorcycle_hourly_rate';
    }
    
    $sql = "SELECT setting_value FROM settings WHERE setting_key = '$setting_key'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return floatval($row['setting_value']);
    }
    return $vehicle_type === 'Vehicle' ? 20.00 : 10.00; // Default values
}

/**
 * Get base hours from settings
 */
function getBaseHours($conn) {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'base_hours'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return intval($row['setting_value']);
    }
    return 3; // Default value
}

/**
 * Calculate duration and fee for a spot
 */
function calculateDurationAndFee($conn, $entry_time, $is_free = 0, $vehicle_type = '', $customer_type = '', $is_overnight = 0) {
    if (empty($entry_time) || $is_free) {
        return ['duration' => '', 'fee' => 0];
    }
    
    $entry = new DateTime($entry_time);
    $now = new DateTime();
    $entry->setTimezone($now->getTimezone());
    $interval = $now->diff($entry);
    
    // Format duration
    $duration = '';
    if ($interval->days > 0) {
        $duration .= $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ', ';
    }
    $duration .= $interval->h . ' hour' . ($interval->h != 1 ? 's' : '');
    
    if ($interval->i > 0 || ($interval->days == 0 && $interval->h == 0)) {
        $duration .= ', ' . $interval->i . ' minute' . ($interval->i != 1 ? 's' : '');
    }

    // Get settings from database
    $settings = [];
    $sql = "SELECT setting_key, setting_value FROM settings";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row['setting_key']] = floatval($row['setting_value']);
    }

    // If overnight parking is selected, only charge the overnight fee
    if ($is_overnight) {
        $overnight_fee_key = strtolower($vehicle_type) . '_overnight_fee';
        $overnight_fee = $settings[$overnight_fee_key] ?? ($vehicle_type === 'Motorcycle' ? 50.00 : 100.00);
        return ['duration' => $duration, 'fee' => $overnight_fee];
    }

    // For regular parking, calculate based on vehicle type and customer type
    $base_fee = 0;
    $hourly_rate = 0;
    $base_hours = $settings['base_hours'] ?? 3;

    if ($customer_type === 'pasig_employee') {
        // Use Pasig employee rates
        if ($vehicle_type === 'Vehicle') {
            $base_fee = $settings['pasig_vehicle_base_fee'] ?? 50.00;
            $hourly_rate = $settings['pasig_vehicle_hourly_rate'] ?? 0.00;
        } else { // Motorcycle
            $base_fee = $settings['pasig_motorcycle_base_fee'] ?? 20.00;
            $hourly_rate = $settings['pasig_motorcycle_hourly_rate'] ?? 0.00;
        }
    } else {
        // Use regular rates
        if ($vehicle_type === 'Vehicle') {
            $base_fee = $settings['vehicle_base_fee'] ?? 40.00;
            $hourly_rate = $settings['vehicle_hourly_rate'] ?? 20.00;
        } else { // Motorcycle
            $base_fee = $settings['motorcycle_base_fee'] ?? 20.00;
            $hourly_rate = $settings['motorcycle_hourly_rate'] ?? 10.00;
        }
    }
    
    // Calculate total hours
    $total_hours = ($interval->days * 24) + $interval->h;
    if ($interval->i > 0) {
        $total_hours++; // Round up to the next hour
    }
    
    // Calculate fee
    $fee = $base_fee; // Start with base fee
    
    // Add hourly rate for hours beyond base hours if hourly rate is enabled
    if ($hourly_rate > 0 && $total_hours > $base_hours) {
        $additional_hours = $total_hours - $base_hours;
        $fee += ($additional_hours * $hourly_rate);
    }
    
    return ['duration' => $duration, 'fee' => $fee];
}

/**
 * Rent a parking spot
 */
function rentParkingSpot($conn, $spot_id) {
    $spot_id = mysqli_real_escape_string($conn, $spot_id);
    $renter_name = mysqli_real_escape_string($conn, $_POST['renter_name']);
    $renter_contact = mysqli_real_escape_string($conn, $_POST['renter_contact']);
    $rental_start_date = mysqli_real_escape_string($conn, $_POST['rental_start_date']);
    $rental_end_date = mysqli_real_escape_string($conn, $_POST['rental_end_date']);
    $rental_notes = mysqli_real_escape_string($conn, $_POST['rental_notes'] ?? '');
    
    // IMPORTANT: Set is_paid correctly and ENSURE rental_rate is 0 when unpaid
    $is_paid = isset($_POST['is_paid']) ? intval($_POST['is_paid']) : 1;
    // Always force rental_rate to 0 when is_paid is 0, regardless of form input
    $rental_rate = ($is_paid == 0) ? 0 : floatval($_POST['rental_rate']);
    
    // Check if spot is available (not occupied and not already rented)
    $sql = "SELECT is_occupied, is_rented FROM parking_spots WHERE id = $spot_id";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['is_occupied']) {
            return ["success" => false, "message" => "Cannot rent an occupied parking spot."];
        }
        
        if ($row['is_rented']) {
            return ["success" => false, "message" => "This spot is already rented."];
        }
        
        // Update parking spot status
        $sql = "UPDATE parking_spots SET 
                is_rented = 1, 
                is_occupied = 0,
                renter_name = '$renter_name', 
                renter_contact = '$renter_contact', 
                rental_start_date = '$rental_start_date', 
                rental_end_date = '$rental_end_date', 
                rental_rate = $rental_rate,
                rental_notes = '$rental_notes'
                WHERE id = $spot_id";
        
        if (mysqli_query($conn, $sql)) {
            // Create transaction record for rental
            $sql = "INSERT INTO transactions 
                    (spot_id, transaction_type, customer_name, entry_time, rental_start_date, rental_end_date, rental_rate, is_paid) 
                    VALUES ($spot_id, 'rental', '$renter_name', NOW(), '$rental_start_date', '$rental_end_date', $rental_rate, $is_paid)";
            
            if (mysqli_query($conn, $sql)) {
                // Log to audit trail
                $transaction_id = mysqli_insert_id($conn);
                logAudit($conn, 'insert', 'transactions', $transaction_id, null, null, "Parking spot rental: $spot_id to $renter_name");
                logAudit($conn, 'update', 'parking_spots', $spot_id, 'status', 'available', 'rented');
                logAudit($conn, 'update', 'parking_spots', $spot_id, 'rental_rate', null, $rental_rate);
                
                return ["success" => true, "message" => "Parking spot rented successfully."];
            } else {
                return ["success" => false, "message" => "Spot was marked as rented but error creating transaction record: " . mysqli_error($conn)];
            }
        } else {
            return ["success" => false, "message" => "Error updating spot: " . mysqli_error($conn)];
        }
    } else {
        return ["success" => false, "message" => "Error retrieving spot information."];
    }
}

/**
 * End a parking spot rental
 */
function endParkingSpotRental($conn, $spot_id) {
    $spot_id = mysqli_real_escape_string($conn, $spot_id);
    
    // Check if spot is actually rented
    $sql = "SELECT is_rented, renter_name, rental_start_date, rental_end_date, rental_rate FROM parking_spots WHERE id = $spot_id";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Get rental information for audit log
        $renter_name = $row['renter_name'];
        $rental_start_date = $row['rental_start_date'];
        $rental_end_date = $row['rental_end_date'];
        $rental_rate = $row['rental_rate'];
        
        // Check if rental period has started
        $now = new DateTime();
        $start_date = new DateTime($rental_start_date);
        $end_date = new DateTime($rental_end_date);
        
        // Only proceed if rental period has started
        if ($now >= $start_date) {
            // Update transaction to mark as completed
            $sql = "UPDATE transactions 
                    SET exit_time = NOW() 
                    WHERE spot_id = $spot_id 
                    AND transaction_type = 'rental' 
                    AND exit_time IS NULL";
            mysqli_query($conn, $sql);
            
            // Get transaction ID for audit log
            $transaction_id = 0;
            $get_transaction = mysqli_query($conn, "SELECT id FROM transactions WHERE spot_id = $spot_id AND transaction_type = 'rental' ORDER BY entry_time DESC LIMIT 1");
            if ($get_transaction && $transaction_row = mysqli_fetch_assoc($get_transaction)) {
                $transaction_id = $transaction_row['id'];
            }
            
            // Record earnings for rental
            $earnings_date = date('Y-m-d');
            $earnings_type = 'rental';
            $earnings_amount = $rental_rate;
            $earnings_description = "Rental payment from $renter_name for spot $spot_id";
            
            $sql = "INSERT INTO earnings (date, type, amount, description, transaction_id) 
                    VALUES ('$earnings_date', '$earnings_type', $earnings_amount, '$earnings_description', $transaction_id)";
            mysqli_query($conn, $sql);
            
            // Update parking spot
            $sql = "UPDATE parking_spots SET 
                    is_rented = 0, 
                    renter_name = NULL, 
                    renter_contact = NULL, 
                    rental_start_date = NULL, 
                    rental_end_date = NULL, 
                    rental_rate = NULL,
                    rental_notes = NULL
                    WHERE id = $spot_id";
                    
            if (mysqli_query($conn, $sql)) {
                // Log to audit trail
                logAudit($conn, 'update', 'transactions', $transaction_id, 'exit_time', null, date('Y-m-d H:i:s'));
                logAudit($conn, 'update', 'parking_spots', $spot_id, 'status', 'rented', 'available');
                logAudit($conn, 'update', 'parking_spots', $spot_id, 'rental_info', $renter_name, null);
                
                // Force statistics update
                getParkingStatistics($conn);
                
                return ["success" => true, "message" => "Rental ended successfully."];
            } else {
                return ["success" => false, "message" => "Error updating spot: " . mysqli_error($conn)];
            }
        } else {
            return ["success" => false, "message" => "Cannot end rental before its start date."];
        }
    } else {
        return ["success" => false, "message" => "Error retrieving spot information."];
    }
}

/**
 * Reserve a parking spot
 */
function reserveParkingSpot($conn, $spot_id) {
    $spot_id = mysqli_real_escape_string($conn, $spot_id);
    $reserver_name = mysqli_real_escape_string($conn, $_POST['reserver_name']);
    $reserver_contact = mysqli_real_escape_string($conn, $_POST['reserver_contact']);
    $reservation_start_time = mysqli_real_escape_string($conn, $_POST['reservation_start_time']);
    $reservation_end_time = mysqli_real_escape_string($conn, $_POST['reservation_end_time']);
    $reservation_notes = mysqli_real_escape_string($conn, $_POST['reservation_notes'] ?? '');
    
    // Always set reservations as paid
    $is_paid = 1;
    $reservation_fee = floatval($_POST['reservation_fee']);
    
    // Check if the spot is available (not occupied, not rented, not already reserved)
    $sql = "SELECT is_occupied, is_rented, is_reserved FROM parking_spots WHERE id = $spot_id";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['is_occupied']) {
            return ["success" => false, "message" => "Cannot reserve an occupied parking spot."];
        }
        
        if ($row['is_rented']) {
            return ["success" => false, "message" => "Cannot reserve a rented parking spot."];
        }
        
        if ($row['is_reserved']) {
            return ["success" => false, "message" => "This spot is already reserved."];
        }
        
        // Validate reservation times
        $start = new DateTime($reservation_start_time);
        $end = new DateTime($reservation_end_time);
        $now = new DateTime();
        
        if ($start > $end) {
            return ["success" => false, "message" => "Reservation end time must be after start time."];
        }
        
        if ($start < $now && $start->format('Y-m-d') == $now->format('Y-m-d')) {
            // Allow reservations starting today if they're in the future
            if ($start->format('H:i') < $now->format('H:i')) {
                return ["success" => false, "message" => "Reservation start time must be in the future."];
            }
        }
        
        // Update the parking spot as reserved
        $sql = "UPDATE parking_spots SET 
                is_reserved = 1, 
                reserver_name = '$reserver_name', 
                reserver_contact = '$reserver_contact', 
                reservation_start_time = '$reservation_start_time', 
                reservation_end_time = '$reservation_end_time',
                reservation_notes = '$reservation_notes',
                reservation_fee = $reservation_fee
                WHERE id = $spot_id";
    
        if (mysqli_query($conn, $sql)) {
            // Create transaction record for reservation
            $sql = "INSERT INTO transactions 
                    (spot_id, transaction_type, customer_name, entry_time, exit_time, reservation_fee, is_paid) 
                    VALUES ($spot_id, 'reservation', '$reserver_name', '$reservation_start_time', '$reservation_end_time', $reservation_fee, $is_paid)";
            
            if (mysqli_query($conn, $sql)) {
                // Log to audit trail
                $transaction_id = mysqli_insert_id($conn);
                logAudit($conn, 'insert', 'transactions', $transaction_id, null, null, "Parking spot reservation: $spot_id by $reserver_name");
                logAudit($conn, 'update', 'parking_spots', $spot_id, 'status', 'available', 'reserved');
                
                return ["success" => true, "message" => "Parking spot reserved successfully."];
            } else {
                return ["success" => false, "message" => "Spot was marked as reserved but error creating transaction record: " . mysqli_error($conn)];
            }
        } else {
            return ["success" => false, "message" => "Error updating spot: " . mysqli_error($conn)];
        }
    } else {
        return ["success" => false, "message" => "Error retrieving spot information."]; 
    }
}

/**
 * Cancel a parking spot reservation
 */
function cancelReservation($conn, $spot_id) {
    $spot_id = mysqli_real_escape_string($conn, $spot_id);
    
    // Check if spot is actually reserved
    $sql = "SELECT is_reserved, reserver_name, reservation_start_time, reservation_fee FROM parking_spots WHERE id = $spot_id";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Get reserver name for audit log
        $reserver_name = $row['reserver_name'];
        $reservation_start_time = $row['reservation_start_time'];
        $reservation_fee = $row['reservation_fee'];
        
        // Get transaction ID for audit log
        $transaction_id = 0;
        $get_transaction = mysqli_query($conn, "SELECT id FROM transactions WHERE spot_id = $spot_id AND transaction_type = 'reservation' ORDER BY entry_time DESC LIMIT 1");
        if ($get_transaction && $transaction_row = mysqli_fetch_assoc($get_transaction)) {
            $transaction_id = $transaction_row['id'];
        }
        
        // Check if reservation has started
        $now = new DateTime();
        $start_time = new DateTime($reservation_start_time);
        $is_checkout = $now >= $start_time;
        
        // Update parking spot
        $sql = "UPDATE parking_spots SET 
                is_reserved = 0, 
                reserver_name = NULL, 
                reserver_contact = NULL, 
                reservation_start_time = NULL, 
                reservation_end_time = NULL,
                reservation_notes = NULL,
                reservation_fee = NULL
                WHERE id = $spot_id";
                
        if (mysqli_query($conn, $sql)) {
            // If this is a checkout (reservation has started), record earnings
            if ($is_checkout) {
                $earnings_date = date('Y-m-d');
                $earnings_type = 'reservation';
                $earnings_amount = $reservation_fee;
                $earnings_description = "Reservation payment from $reserver_name for spot $spot_id";
                
                $sql = "INSERT INTO earnings (date, type, amount, description, transaction_id) 
                        VALUES ('$earnings_date', '$earnings_type', $earnings_amount, '$earnings_description', $transaction_id)";
                mysqli_query($conn, $sql);
            }
            
            // Log to audit trail
            if ($is_checkout) {
                logAudit($conn, 'update', 'transactions', $transaction_id, 'status', 'active', 'completed');
                logAudit($conn, 'update', 'parking_spots', $spot_id, 'status', 'reserved', 'available');
                logAudit($conn, 'update', 'parking_spots', $spot_id, 'reservation_info', $reserver_name, null);
                
                return ["success" => true, "message" => "Reservation checked out successfully."];
            } else {
                logAudit($conn, 'update', 'transactions', $transaction_id, 'status', 'active', 'cancelled');
                logAudit($conn, 'update', 'parking_spots', $spot_id, 'status', 'reserved', 'available');
                logAudit($conn, 'update', 'parking_spots', $spot_id, 'reservation_info', $reserver_name, null);
                
                return ["success" => true, "message" => "Reservation cancelled successfully."];
            }
        } else {
            return ["success" => false, "message" => "Error updating spot: " . mysqli_error($conn)];
        }
    } else {
        return ["success" => false, "message" => "Error retrieving spot information."];
    }
}
?>
