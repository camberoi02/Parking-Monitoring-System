<?php
// Include authentication check
include_once 'includes/auth_session.php';

$title = "Earnings Reports";
include_once 'includes/header.php';
include_once 'includes/navigation.php';
require_once 'config/db_config.php';

// Check if user is admin
$isAdmin = true; // In a real app, check from session

if (!$isAdmin) {
    echo "<div class='alert alert-danger'>Access denied. Admin privileges required.</div>";
    include_once 'includes/footer.php';
    exit;
}

// Check if database exists
$database_exists = false;
$result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
if (mysqli_num_rows($result) > 0) {
    $database_exists = true;
    mysqli_select_db($conn, DB_NAME);
}

// Set default values
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get report data
$earnings_data = array();
$chart_labels = array();
$chart_values = array();
$total_earnings = 0;
$average_earnings = 0;
$max_earnings = 0;
$max_earnings_date = '';
$transaction_count = 0;
$transaction_types = array(
    'parking' => 0,
    'rental' => 0,
    'reservation' => 0
);

if ($database_exists) {
    // Base query for all reports
    $base_sql = "SELECT 
                transaction_date, 
                SUM(amount) as daily_total, 
                COUNT(*) as transaction_count,
                SUM(CASE WHEN transaction_type = 'parking' OR transaction_type IS NULL THEN 1 ELSE 0 END) as parking_count,
                SUM(CASE WHEN transaction_type = 'rental' THEN 1 ELSE 0 END) as rental_count,
                SUM(CASE WHEN transaction_type = 'reservation' THEN 1 ELSE 0 END) as reservation_count
            FROM (
                SELECT 
                    DATE(exit_time) as transaction_date, 
                    fee as amount,
                    id,
                    NULL as transaction_type
                FROM transactions 
                WHERE exit_time IS NOT NULL 
                AND fee > 0
                AND (transaction_type IS NULL OR transaction_type = 'parking')
                
                UNION ALL
                
                SELECT 
                    DATE(exit_time) as transaction_date, 
                    rental_rate as amount,
                    id,
                    'rental' as transaction_type
                FROM transactions 
                WHERE exit_time IS NOT NULL 
                AND transaction_type = 'rental'
                AND rental_rate > 0
                
                UNION ALL
                
                SELECT 
                    DATE(entry_time) as transaction_date, 
                    reservation_fee as amount,
                    id,
                    'reservation' as transaction_type
                FROM transactions 
                WHERE transaction_type = 'reservation'
                AND entry_time IS NOT NULL
            ) as combined_transactions ";
    
    // Build the appropriate GROUP BY clause based on report type
    $where_clause = "WHERE transaction_date BETWEEN '$start_date' AND '$end_date' ";
    $group_clause = "";
    $order_clause = "";
    
    if ($report_type == 'daily') {
        $group_clause = "GROUP BY transaction_date ";
        $order_clause = "ORDER BY transaction_date ASC";
    } else if ($report_type == 'weekly') {
        $group_clause = "GROUP BY YEARWEEK(transaction_date, 1) ";
        $order_clause = "ORDER BY YEARWEEK(transaction_date, 1) ASC";
        
        // Adjust the query to include week information
        $base_sql = "SELECT 
                    YEARWEEK(transaction_date, 1) as year_week, 
                    MIN(transaction_date) as week_start,
                    MAX(transaction_date) as week_end, 
                    SUM(amount) as weekly_total, 
                    COUNT(*) as transaction_count,
                    SUM(CASE WHEN transaction_type = 'parking' OR transaction_type IS NULL THEN 1 ELSE 0 END) as parking_count,
                    SUM(CASE WHEN transaction_type = 'rental' THEN 1 ELSE 0 END) as rental_count,
                    SUM(CASE WHEN transaction_type = 'reservation' THEN 1 ELSE 0 END) as reservation_count
                FROM (
                    SELECT 
                        DATE(exit_time) as transaction_date, 
                        fee as amount,
                        id,
                        NULL as transaction_type
                    FROM transactions 
                    WHERE exit_time IS NOT NULL 
                    AND fee > 0
                    AND (transaction_type IS NULL OR transaction_type = 'parking')
                    
                    UNION ALL
                    
                    SELECT 
                        DATE(exit_time) as transaction_date, 
                        rental_rate as amount,
                        id,
                        'rental' as transaction_type
                    FROM transactions 
                    WHERE exit_time IS NOT NULL 
                    AND transaction_type = 'rental'
                    AND rental_rate > 0
                    
                    UNION ALL
                    
                    SELECT 
                        DATE(entry_time) as transaction_date, 
                        reservation_fee as amount,
                        id,
                        'reservation' as transaction_type
                    FROM transactions 
                    WHERE transaction_type = 'reservation'
                    AND entry_time IS NOT NULL
                ) as combined_transactions ";
    } else if ($report_type == 'monthly') {
        $group_clause = "GROUP BY YEAR(transaction_date), MONTH(transaction_date) ";
        $order_clause = "ORDER BY YEAR(transaction_date), MONTH(transaction_date) ASC";
        
        // Adjust the query to include month information
        $base_sql = "SELECT 
                    DATE_FORMAT(transaction_date, '%Y-%m-01') as month_start,
                    LAST_DAY(transaction_date) as month_end,
                    SUM(amount) as monthly_total, 
                    COUNT(*) as transaction_count,
                    SUM(CASE WHEN transaction_type = 'parking' OR transaction_type IS NULL THEN 1 ELSE 0 END) as parking_count,
                    SUM(CASE WHEN transaction_type = 'rental' THEN 1 ELSE 0 END) as rental_count,
                    SUM(CASE WHEN transaction_type = 'reservation' THEN 1 ELSE 0 END) as reservation_count
                FROM (
                    SELECT 
                        DATE(exit_time) as transaction_date, 
                        fee as amount,
                        id,
                        NULL as transaction_type
                    FROM transactions 
                    WHERE exit_time IS NOT NULL 
                    AND fee > 0
                    AND (transaction_type IS NULL OR transaction_type = 'parking')
                    
                    UNION ALL
                    
                    SELECT 
                        DATE(exit_time) as transaction_date, 
                        rental_rate as amount,
                        id,
                        'rental' as transaction_type
                    FROM transactions 
                    WHERE exit_time IS NOT NULL 
                    AND transaction_type = 'rental'
                    AND rental_rate > 0
                    
                    UNION ALL
                    
                    SELECT 
                        DATE(entry_time) as transaction_date, 
                        reservation_fee as amount,
                        id,
                        'reservation' as transaction_type
                    FROM transactions 
                    WHERE transaction_type = 'reservation'
                    AND entry_time IS NOT NULL
                ) as combined_transactions ";
    }
    
    $sql = $base_sql . $where_clause . $group_clause . $order_clause;
    
    // Debugging - Check for March 12-13 reservations
    $debug_sql = "SELECT id, entry_time, exit_time, transaction_type, reservation_fee 
                FROM transactions 
                WHERE transaction_type = 'reservation' 
                AND (DATE(entry_time) = '2023-03-12' 
                    OR DATE(entry_time) = '2023-03-13' 
                    OR DATE(exit_time) = '2023-03-12' 
                    OR DATE(exit_time) = '2023-03-13')";
    $debug_result = mysqli_query($conn, $debug_sql);
    if ($debug_result && mysqli_num_rows($debug_result) > 0) {
        error_log("EARNINGS REPORT: Found " . mysqli_num_rows($debug_result) . " reservations for March 12-13");
        while ($debug_row = mysqli_fetch_assoc($debug_result)) {
            error_log("EARNINGS REPORT: March 12-13 reservation: " . json_encode($debug_row));
        }
    } else {
        error_log("EARNINGS REPORT: No March 12-13 reservations found in database");
    }
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $total_records = mysqli_num_rows($result);
        if ($total_records > 0) {
            $total_earnings = 0;
            $max_earnings = 0;
            $total_transactions = 0;
            
            while ($row = mysqli_fetch_assoc($result)) {
                // Format data based on report type
                if ($report_type == 'daily') {
                    $label = date('M d, Y', strtotime($row['transaction_date']));
                    $amount = $row['daily_total'];
                    $date_key = $row['transaction_date'];
                    $transactions = $row['transaction_count'];
                    
                    $earnings_data[] = array(
                        'date' => $label,
                        'amount' => $amount,
                        'transactions' => $transactions,
                        'parking' => isset($row['parking_count']) ? $row['parking_count'] : 0,
                        'rental' => isset($row['rental_count']) ? $row['rental_count'] : 0,
                        'reservation' => isset($row['reservation_count']) ? $row['reservation_count'] : 0
                    );
                    
                } else if ($report_type == 'weekly') {
                    $week_start = date('M d', strtotime($row['week_start']));
                    $week_end = date('M d, Y', strtotime($row['week_end']));
                    $label = "$week_start - $week_end";
                    $amount = $row['weekly_total'];
                    $date_key = $row['year_week'];
                    $transactions = $row['transaction_count'];
                    
                    $earnings_data[] = array(
                        'date' => $label,
                        'amount' => $amount,
                        'start' => $row['week_start'],
                        'end' => $row['week_end'],
                        'transactions' => $transactions,
                        'parking' => isset($row['parking_count']) ? $row['parking_count'] : 0,
                        'rental' => isset($row['rental_count']) ? $row['rental_count'] : 0,
                        'reservation' => isset($row['reservation_count']) ? $row['reservation_count'] : 0
                    );
                    
                } else if ($report_type == 'monthly') {
                    $label = date('M Y', strtotime($row['month_start']));
                    $amount = $row['monthly_total'];
                    $date_key = $row['month_start'];
                    $transactions = $row['transaction_count'];
                    
                    $earnings_data[] = array(
                        'date' => $label,
                        'amount' => $amount,
                        'start' => $row['month_start'],
                        'end' => $row['month_end'],
                        'transactions' => $transactions,
                        'parking' => isset($row['parking_count']) ? $row['parking_count'] : 0,
                        'rental' => isset($row['rental_count']) ? $row['rental_count'] : 0,
                        'reservation' => isset($row['reservation_count']) ? $row['reservation_count'] : 0
                    );
                }
                
                // Build chart data
                $chart_labels[] = $label;
                $chart_values[] = $amount;
                
                // Update totals
                $total_earnings += $amount;
                $total_transactions += $transactions;
                
                // Update transaction type totals
                if (isset($row['parking_count'])) $transaction_types['parking'] += intval($row['parking_count']);
                if (isset($row['rental_count'])) $transaction_types['rental'] += intval($row['rental_count']);
                if (isset($row['reservation_count'])) $transaction_types['reservation'] += intval($row['reservation_count']);
                
                // Check if this is the max earnings day/week/month
                if ($amount > $max_earnings) {
                    $max_earnings = $amount;
                    $max_earnings_date = $label;
                }
            }
            
            // Calculate average
            $average_earnings = $total_earnings / count($earnings_data);
            $transaction_count = $total_transactions;
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <h1 class="mb-0">Earnings Reports</h1>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end">
            <?php if (!empty($earnings_data)): ?>
            <a href="export_earnings_pdf.php?report_type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-primary">
                <i class="fas fa-file-export me-2"></i>Export PDF
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card shadow mb-4 border-0">
        <div class="card-header bg-white py-3 d-flex align-items-center">
            <i class="fas fa-filter text-primary me-2"></i>
            <h5 class="mb-0">Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="report_type" class="form-label fw-semibold">Report Type</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-calendar-alt text-primary"></i>
                        </span>
                        <select class="form-select border-start-0 ps-0" id="report_type" name="report_type">
                            <option value="daily" <?php echo ($report_type == 'daily') ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo ($report_type == 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo ($report_type == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="start_date" class="form-label fw-semibold">Start Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-calendar-day text-primary"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0 flatpickr-date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label fw-semibold">End Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-calendar-check text-primary"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0 flatpickr-date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                </div>
                <div class="col-12 mt-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-sync-alt me-2"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$database_exists): ?>
        <div class="alert alert-warning d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-3 fa-2x text-warning"></i>
            <div>
                Database doesn't exist yet. Please <a href="system_settings.php" class="alert-link">initialize the database</a> first.
            </div>
        </div>
    <?php elseif (empty($earnings_data)): ?>
        <div class="alert alert-info d-flex align-items-center">
            <i class="fas fa-info-circle me-3 fa-2x text-info"></i>
            <div>
                No earnings data found for the selected date range and report type.
            </div>
        </div>
    <?php else: ?>
    
    <!-- Stats Overview Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow mb-3">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-chart-pie text-primary me-2"></i>
                    <h5 class="mb-0">Stats Overview</h5>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-3 p-4 border-end border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 bg-success rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-money-bill-wave fa-2x text-white"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1 small text-uppercase">Total Earnings</h6>
                                    <h2 class="mb-0 fs-3 fw-bold">₱<?php echo number_format($total_earnings, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 p-4 border-end border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 bg-info rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-calculator fa-2x text-white"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1 small text-uppercase">Average <?php echo ucfirst($report_type); ?></h6>
                                    <h2 class="mb-0 fs-3 fw-bold">₱<?php echo number_format($average_earnings, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 p-4 border-end border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 bg-warning rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-trophy fa-2x text-white"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1 small text-uppercase">Highest <?php echo ucfirst($report_type); ?></h6>
                                    <h2 class="mb-0 fs-3 fw-bold">₱<?php echo number_format($max_earnings, 2); ?></h2>
                                    <small class="text-muted"><?php echo $max_earnings_date; ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 p-4 border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 bg-primary rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-receipt fa-2x text-white"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1 small text-uppercase">Transactions</h6>
                                    <h2 class="mb-0 fs-3 fw-bold"><?php echo number_format($transaction_count); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction Type Breakdown -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow mb-3">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-tags text-primary me-2"></i>
                    <h5 class="mb-0">Transaction Type Breakdown</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="flex-shrink-0 rounded-circle bg-primary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-car fa-lg text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1 small text-uppercase">Parking</h6>
                                        <div class="d-flex align-items-center">
                                            <h3 class="mb-0 me-2"><?php echo number_format($transaction_types['parking']); ?></h3>
                                            <span class="badge bg-primary rounded-pill">
                                                <?php echo ($transaction_count > 0) ? round(($transaction_types['parking'] / $transaction_count) * 100) : 0; ?>%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="flex-shrink-0 rounded-circle bg-success p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-calendar-check fa-lg text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1 small text-uppercase">Rentals</h6>
                                        <div class="d-flex align-items-center">
                                            <h3 class="mb-0 me-2"><?php echo number_format($transaction_types['rental']); ?></h3>
                                            <span class="badge bg-success rounded-pill">
                                                <?php echo ($transaction_count > 0) ? round(($transaction_types['rental'] / $transaction_count) * 100) : 0; ?>%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="flex-shrink-0 rounded-circle bg-warning p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-bookmark fa-lg text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1 small text-uppercase">Reservations</h6>
                                        <div class="d-flex align-items-center">
                                            <h3 class="mb-0 me-2"><?php echo number_format($transaction_types['reservation']); ?></h3>
                                            <span class="badge bg-warning rounded-pill">
                                                <?php echo ($transaction_count > 0) ? round(($transaction_types['reservation'] / $transaction_count) * 100) : 0; ?>%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-chart-column text-primary me-2"></i>
                        <h5 class="mb-0">Revenue Trend</h5>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary chart-type active" data-type="bar">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary chart-type" data-type="line">
                            <i class="fas fa-chart-line"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="earningsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-list-ul text-primary me-2"></i>
                        <h5 class="mb-0">
                            <?php
                            switch($report_type) {
                                case 'daily':
                                    echo 'Daily Breakdown';
                                    break;
                                case 'weekly':
                                    echo 'Weekly Breakdown';
                                    break;
                                case 'monthly':
                                    echo 'Monthly Breakdown';
                                    break;
                            }
                            ?>
                        </h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Period</th>
                                    <th class="border-0">Total</th>
                                    <th class="border-0">P</th>
                                    <th class="border-0">R</th>
                                    <th class="border-0">Res</th>
                                    <th class="text-end border-0">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($earnings_data as $data): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo $data['date']; ?></td>
                                    <td><?php echo $data['transactions']; ?></td>
                                    <td><span class="badge bg-primary rounded-pill px-2"><?php echo $data['parking']; ?></span></td>
                                    <td><span class="badge bg-success rounded-pill px-2"><?php echo $data['rental']; ?></span></td>
                                    <td><span class="badge bg-warning rounded-pill px-2"><?php echo $data['reservation']; ?></span></td>
                                    <td class="text-end">
                                        <span class="badge rounded-pill bg-success px-3 py-2">₱<?php echo number_format($data['amount'], 2); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart data
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const chartValues = <?php echo json_encode($chart_values); ?>;
        
        // Chart colors
        const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();
        const primaryLight = getComputedStyle(document.documentElement).getPropertyValue('--primary-light').trim() || 'rgba(54, 162, 235, 0.5)';
        const successColor = getComputedStyle(document.documentElement).getPropertyValue('--success-color').trim() || '#10b981';
        
        // Chart configuration
        let chartConfig = {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: chartValues,
                    backgroundColor: primaryLight,
                    borderColor: primaryColor,
                    borderWidth: 1,
                    borderRadius: 4,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            drawBorder: false,
                            color: 'rgba(200, 200, 200, 0.15)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            },
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        padding: 10,
                        cornerRadius: 6,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString(undefined, {maximumFractionDigits: 2, minimumFractionDigits: 2});
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        };

        // Initialize chart
        const ctx = document.getElementById('earningsChart').getContext('2d');
        const earningsChart = new Chart(ctx, chartConfig);
        
        // Chart type toggle
        document.querySelectorAll('.chart-type').forEach(button => {
            button.addEventListener('click', function() {
                // Update active state
                document.querySelector('.chart-type.active').classList.remove('active');
                this.classList.add('active');
                
                // Get chart type
                const chartType = this.getAttribute('data-type');
                
                // Update chart type and redraw
                earningsChart.config.type = chartType;
                
                // Adjust line chart settings if needed
                if (chartType === 'line') {
                    earningsChart.data.datasets[0].backgroundColor = 'rgba(54, 162, 235, 0.1)';
                    earningsChart.data.datasets[0].fill = true;
                    earningsChart.data.datasets[0].pointBackgroundColor = primaryColor;
                    earningsChart.data.datasets[0].pointRadius = 4;
                    earningsChart.data.datasets[0].pointHoverRadius = 6;
                } else {
                    earningsChart.data.datasets[0].backgroundColor = primaryLight;
                    earningsChart.data.datasets[0].fill = undefined;
                }
                
                earningsChart.update();
            });
        });
    });
    </script>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers with specific configuration for earnings reports
    if (typeof flatpickr === 'function') {
        // Start date picker
        flatpickr("#start_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            animate: true,
            allowInput: true,
            position: "below",
            maxDate: document.getElementById("end_date").value || "today"
        });
        
        // End date picker with dynamic constraints based on start date
        flatpickr("#end_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            animate: true,
            allowInput: true,
            position: "below",
            maxDate: "today",
            minDate: document.getElementById("start_date").value
        });
        
        // Listen for start_date changes to update end_date minDate
        document.getElementById("start_date")._flatpickr.config.onChange.push(function(selectedDates, dateStr) {
            document.getElementById("end_date")._flatpickr.set('minDate', dateStr);
            
            // If end date is earlier than new start date, update it
            if (document.getElementById("end_date")._flatpickr.selectedDates[0] < selectedDates[0]) {
                document.getElementById("end_date")._flatpickr.setDate(selectedDates[0]);
            }
        });
        
        // Listen for end_date changes to update start_date maxDate
        document.getElementById("end_date")._flatpickr.config.onChange.push(function(selectedDates, dateStr) {
            document.getElementById("start_date")._flatpickr.set('maxDate', dateStr);
        });
    }
});
</script>

<?php include_once 'includes/footer.php'; ?>
