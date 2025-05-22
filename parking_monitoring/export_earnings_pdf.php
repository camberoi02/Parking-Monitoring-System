<?php
require_once 'config/db_config.php';
require_once 'libs/fpdf/fpdf.php';

// Check if user is admin
$isAdmin = true; // In a real app, check from session

if (!$isAdmin) {
    echo "<div class='alert alert-danger'>Access denied. Admin privileges required.</div>";
    exit;
}

// Get system name from settings
$system_name = "Parking Monitoring System"; // Default value
if (in_array('settings', mysqli_fetch_all(mysqli_query($conn, "SHOW TABLES"), MYSQLI_ASSOC))) {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'system_name'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $system_name = $row['setting_value'];
    }
}

// Create a custom PDF class extending FPDF
class EarningsPDF extends FPDF {
    protected $system_name;
    
    function __construct($system_name) {
        parent::__construct();
        $this->system_name = $system_name;
    }
    
    // Page header
    function Header() {
        // Logo - left side
        $this->SetFillColor(66, 133, 244);
        $this->Rect(10, 10, 8, 20, 'F');
        
        // Title area
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 102, 204);
        $this->SetX(20);
        $this->Cell(0, 10, $this->system_name, 0, 1, 'L');
        
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(66, 66, 66);
        $this->SetX(20);
        $this->Cell(0, 10, 'Earnings Report', 0, 1, 'L');
        
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(100, 100, 100);
        $this->SetX(20);
        $this->Cell(0, 5, 'Generated on ' . date('F d, Y \a\t h:i A'), 0, 1, 'L');
        
        $this->Ln(5);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    // Page footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
        
        $this->SetX(10);
        $this->Cell(0, 10, $this->system_name . ' - Earnings Report', 0, 0, 'L');
        
        $this->SetX(-50);
        $this->Cell(40, 10, date('Y-m-d'), 0, 0, 'R');
    }
    
    // Better looking table header
    function ImprovedTableHeader($headers, $widths) {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(66, 133, 244);
        $this->SetTextColor(255);
        
        foreach ($headers as $i => $header) {
            // Ensure headers are properly aligned and have enough space
            $align = ($i == 0) ? 'L' : (($i == count($headers) - 1) ? 'R' : 'C');
            $this->Cell($widths[$i], 10, $header, 1, 0, $align, true);
        }
        $this->Ln();
    }
    
    // Add Transaction Type Breakdown
    function AddTransactionTypeBreakdown($data) {
        $this->Ln(5); // Reduced spacing before section
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(0, 102, 204);
        $this->Cell(0, 10, 'Transaction Type Distribution', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0);
        
        // Calculate percentages
        $total = $data['parking'] + $data['rental'] + $data['reservation'];
        if ($total == 0) $total = 1; // Avoid division by zero
        
        $parking_pct = round(($data['parking'] / $total) * 100);
        $rental_pct = round(($data['rental'] / $total) * 100);
        $reservation_pct = round(($data['reservation'] / $total) * 100);
        
        // Set colors
        $parking_color = [66, 133, 244]; // Blue
        $rental_color = [52, 168, 83];   // Green
        $reservation_color = [251, 188, 5]; // Yellow
        
        // Create a simple horizontal bar chart
        $bar_width = 160;
        $bar_height = 16; // Reduced height
        $x = 25;
        $y = $this->GetY() + 3; // Reduced spacing
        
        // Draw the stacked bar
        $parking_width = ($parking_pct / 100) * $bar_width;
        $rental_width = ($rental_pct / 100) * $bar_width;
        $reservation_width = ($reservation_pct / 100) * $bar_width;
        
        // Draw bar segments
        $this->SetFillColor($parking_color[0], $parking_color[1], $parking_color[2]);
        $this->Rect($x, $y, $parking_width, $bar_height, 'F');
        
        $this->SetFillColor($rental_color[0], $rental_color[1], $rental_color[2]);
        $this->Rect($x + $parking_width, $y, $rental_width, $bar_height, 'F');
        
        $this->SetFillColor($reservation_color[0], $reservation_color[1], $reservation_color[2]);
        $this->Rect($x + $parking_width + $rental_width, $y, $reservation_width, $bar_height, 'F');
        
        // Draw border around the entire bar
        $this->SetDrawColor(200, 200, 200);
        $this->Rect($x, $y, $bar_width, $bar_height, 'D');
        
        // Add percentages on top of the bar
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9); // Smaller font
        
        // Only add percentage text if segment is wide enough
        if ($parking_pct >= 10) {
            $this->Text($x + ($parking_width / 2) - 5, $y + ($bar_height / 2) + 2, $parking_pct . '%');
        }
        
        if ($rental_pct >= 10) {
            $this->Text($x + $parking_width + ($rental_width / 2) - 5, $y + ($bar_height / 2) + 2, $rental_pct . '%');
        }
        
        if ($reservation_pct >= 10) {
            $this->Text($x + $parking_width + $rental_width + ($reservation_width / 2) - 5, $y + ($bar_height / 2) + 2, $reservation_pct . '%');
        }
        
        // Add single-line legend
        $y += $bar_height + 8; // Spacing after bar
        $legend_box_size = 4; // Small boxes
        $this->SetFont('Arial', '', 8); // Small font
        $this->SetTextColor(0, 0, 0);
        
        // All legends in one line with proper spacing
        // Parking legend
        $this->SetFillColor($parking_color[0], $parking_color[1], $parking_color[2]);
        $this->Rect($x, $y, $legend_box_size, $legend_box_size, 'F');
        $this->Text($x + $legend_box_size + 3, $y + 3, 'Parking: ' . number_format($data['parking']));
        
        // Rental legend - position after the parking legend with proper spacing
        $rental_x = $x + 60; // Adjusted spacing
        $this->SetFillColor($rental_color[0], $rental_color[1], $rental_color[2]);
        $this->Rect($rental_x, $y, $legend_box_size, $legend_box_size, 'F');
        $this->Text($rental_x + $legend_box_size + 3, $y + 3, 'Rental: ' . number_format($data['rental']));
        
        // Reservation legend - position after the rental legend with proper spacing
        $reservation_x = $rental_x + 60; // Adjusted spacing
        $this->SetFillColor($reservation_color[0], $reservation_color[1], $reservation_color[2]);
        $this->Rect($reservation_x, $y, $legend_box_size, $legend_box_size, 'F');
        $this->Text($reservation_x + $legend_box_size + 3, $y + 3, 'Reservation: ' . number_format($data['reservation']));
        
        $this->Ln(25); // Increased spacing after the chart and legend
    }
    
    // Add summary section
    function AddSummary($data) {
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(0, 102, 204);
        $this->Cell(0, 10, 'Financial Summary', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0);
        
        $this->Cell(80, 8, 'Total Earnings:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(0, 128, 0);
        $this->Cell(30, 8, 'PHP ' . number_format($data['total_earnings'], 2), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0);
        
        $this->Cell(80, 8, 'Average ' . ucfirst($data['report_type']) . ' Earnings:', 0, 0, 'L');
        $this->Cell(30, 8, 'PHP ' . number_format($data['average_earnings'], 2), 0, 1, 'L');
        
        $this->Cell(80, 8, 'Highest ' . ucfirst($data['report_type']) . ' Earnings:', 0, 0, 'L');
        $this->Cell(30, 8, 'PHP ' . number_format($data['max_earnings'], 2) . ' (' . $data['max_earnings_date'] . ')', 0, 1, 'L');
        
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(0, 102, 204);
        $this->Cell(0, 10, 'Transaction Summary', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0);
        
        $this->Cell(80, 8, 'Total Transactions:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(30, 8, number_format($data['transaction_count']), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        // Add transaction type breakdown
        if (isset($data['transaction_types'])) {
            $total_count = $data['transaction_count'] > 0 ? $data['transaction_count'] : 1; // Avoid division by zero
            
            $parking_count = $data['transaction_types']['parking'];
            $parking_pct = round(($parking_count / $total_count) * 100);
            $this->Cell(80, 8, 'Parking Transactions:', 0, 0, 'L');
            $this->Cell(30, 8, number_format($parking_count) . ' (' . $parking_pct . '%)', 0, 1, 'L');
                
            $rental_count = $data['transaction_types']['rental'];
            $rental_pct = round(($rental_count / $total_count) * 100);
            $this->Cell(80, 8, 'Rental Transactions:', 0, 0, 'L');
            $this->Cell(30, 8, number_format($rental_count) . ' (' . $rental_pct . '%)', 0, 1, 'L');
                
            $reservation_count = $data['transaction_types']['reservation'];
            $reservation_pct = round(($reservation_count / $total_count) * 100);
            $this->Cell(80, 8, 'Reservation Transactions:', 0, 0, 'L');
            $this->Cell(30, 8, number_format($reservation_count) . ' (' . $reservation_pct . '%)', 0, 1, 'L');
        }
        
        $this->Ln(5);
        $this->SetFont('Arial', '', 10);
        $this->Cell(80, 8, 'Date Range:', 0, 0, 'L');
        $this->Cell(30, 8, $data['start_date'] . ' to ' . $data['end_date'], 0, 1, 'L');
    }
}

// Get filter parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Check if database exists
$database_exists = false;
$result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
if (mysqli_num_rows($result) > 0) {
    $database_exists = true;
    mysqli_select_db($conn, DB_NAME);
}

// Initialize data arrays
$earnings_data = array();
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
    // Similar query as in earnings_reports.php
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
    
    // Add debugging query to check for reservation transactions
    $debug_sql = "SELECT COUNT(*) as res_count FROM transactions WHERE transaction_type = 'reservation'";
    $debug_result = mysqli_query($conn, $debug_sql);
    $debug_row = mysqli_fetch_assoc($debug_result);
    error_log("Total reservation transactions: " . ($debug_row['res_count'] ?? 0));
    
    // Check specifically for March 12-13 reservations
    $march_debug_sql = "SELECT id, entry_time, exit_time, transaction_type, reservation_fee 
                       FROM transactions 
                       WHERE transaction_type = 'reservation' 
                       AND (DATE(entry_time) = '2023-03-12' 
                            OR DATE(entry_time) = '2023-03-13' 
                            OR DATE(exit_time) = '2023-03-12' 
                            OR DATE(exit_time) = '2023-03-13')";
    $march_result = mysqli_query($conn, $march_debug_sql);
    if ($march_result && mysqli_num_rows($march_result) > 0) {
        while ($res_row = mysqli_fetch_assoc($march_result)) {
            error_log("March 12-13 reservation: " . json_encode($res_row));
        }
    } else {
        error_log("No March 12-13 reservations found in database");
    }
    
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
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $total_records = mysqli_num_rows($result);
        if ($total_records > 0) {
            $total_earnings = 0;
            $max_earnings = 0;
            $total_transactions = 0;
            
            while ($row = mysqli_fetch_assoc($result)) {
                // Debug the reservation count from the database
                error_log("Row data: " . json_encode($row));
                
                // Format data based on report type
                if ($report_type == 'daily') {
                    $label = date('M d, Y', strtotime($row['transaction_date']));
                    $amount = $row['daily_total'];
                    $date_key = $row['transaction_date'];
                    $transactions = $row['transaction_count'];
                    $parking = isset($row['parking_count']) ? intval($row['parking_count']) : 0;
                    $rental = isset($row['rental_count']) ? intval($row['rental_count']) : 0;
                    $reservation = isset($row['reservation_count']) ? intval($row['reservation_count']) : 0;
                    
                    // Log the extracted reservation count
                    error_log("Daily report - Reservation count for $label: $reservation");
                    
                    $earnings_data[] = array(
                        'date' => $label,
                        'amount' => $amount,
                        'transactions' => $transactions,
                        'parking' => $parking,
                        'rental' => $rental,
                        'reservation' => $reservation
                    );
                    
                } else if ($report_type == 'weekly') {
                    $week_start = date('M d', strtotime($row['week_start']));
                    $week_end = date('M d, Y', strtotime($row['week_end']));
                    $label = "$week_start - $week_end";
                    $amount = $row['weekly_total'];
                    $date_key = $row['year_week'];
                    $transactions = $row['transaction_count'];
                    $parking = isset($row['parking_count']) ? intval($row['parking_count']) : 0;
                    $rental = isset($row['rental_count']) ? intval($row['rental_count']) : 0;
                    $reservation = isset($row['reservation_count']) ? intval($row['reservation_count']) : 0;
                    
                    // Log the extracted reservation count
                    error_log("Weekly report - Reservation count for $label: $reservation");
                    
                    $earnings_data[] = array(
                        'date' => $label,
                        'amount' => $amount,
                        'start' => $row['week_start'],
                        'end' => $row['week_end'],
                        'transactions' => $transactions,
                        'parking' => $parking,
                        'rental' => $rental,
                        'reservation' => $reservation
                    );
                    
                } else if ($report_type == 'monthly') {
                    $label = date('M Y', strtotime($row['month_start']));
                    $amount = $row['monthly_total'];
                    $date_key = $row['month_start'];
                    $transactions = $row['transaction_count'];
                    $parking = isset($row['parking_count']) ? intval($row['parking_count']) : 0;
                    $rental = isset($row['rental_count']) ? intval($row['rental_count']) : 0;
                    $reservation = isset($row['reservation_count']) ? intval($row['reservation_count']) : 0;
                    
                    // Log the extracted reservation count
                    error_log("Monthly report - Reservation count for $label: $reservation");
                    
                    $earnings_data[] = array(
                        'date' => $label,
                        'amount' => $amount,
                        'start' => $row['month_start'],
                        'end' => $row['month_end'],
                        'transactions' => $transactions,
                        'parking' => $parking,
                        'rental' => $rental,
                        'reservation' => $reservation
                    );
                }
                
                // Update totals
                $total_earnings += $amount;
                $total_transactions += $transactions;
                
                // Update transaction type counts
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

// Create new PDF document
$pdf = new EarningsPDF($system_name);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);

// Add report title based on type
$pdf->SetFont('Arial', 'B', 12); // Slightly smaller font
$pdf->SetTextColor(0, 102, 204);

$report_title = "";
switch($report_type) {
    case 'daily':
        $report_title = "Daily Earnings Report";
        break;
    case 'weekly':
        $report_title = "Weekly Earnings Report";
        break;
    case 'monthly':
        $report_title = "Monthly Earnings Report";
        break;
}

// Create a background for the title
$titleWidth = 190; // Full width of the content area
$titleHeight = 20; // Increased height for better vertical padding
$pdf->SetFillColor(240, 247, 255);
$pdf->Rect(10, $pdf->GetY(), $titleWidth, $titleHeight, 'F');
$pdf->SetDrawColor(200, 220, 240);
$pdf->Rect(10, $pdf->GetY(), $titleWidth, $titleHeight, 'D');

// Print title with proper vertical position and padding
$pdf->SetY($pdf->GetY() + 6); // Increased top padding for text
$pdf->SetX(15); // Add left padding
$pdf->Cell(100, 6, $report_title, 0, 1, 'L'); // Reduced width to better fit text

// Add date range
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->SetX(15); // Set same X position as title for proper alignment
$pdf->Cell(180, 6, 'Period: ' . date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)), 0, 1, 'L');
$pdf->Ln(3);

// Check if we have data
if (empty($earnings_data)) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(150, 0, 0);
    $pdf->Cell(0, 10, 'No earnings data found for the selected period.', 0, 1, 'C');
} else {
    // Define table column headers and widths
    $headers = ['Period', 'Total', 'Parking', 'Rental', 'Reserv.', 'Amount'];
    $widths = [65, 20, 22, 22, 22, 40];
    
    // Add table header
    $pdf->ImprovedTableHeader($headers, $widths);
    
    // Add debug output to log the entire array structure before processing
    error_log("Complete earnings data before table generation: " . json_encode($earnings_data));
    
    // Add table data with alternating row colors
    $pdf->SetFont('Arial', '', 9);
    $row_count = 0;
    
    foreach ($earnings_data as $data) {
        // Set row background color (alternate)
        $row_count++;
        if ($row_count % 2 == 0) {
            $pdf->SetFillColor(240, 240, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        
        // Fixed version - directly output the period text
        $pdf->SetTextColor(0, 0, 0);  // Reset to black text
        if (isset($data['date'])) {
            $period_text = $data['date']; 
        } else {
            $period_text = "N/A";
        }
        $pdf->Cell($widths[0], 8, $period_text, 1, 0, 'L', true);
        
        // Total transactions
        if (isset($data['transactions'])) {
            $transaction_text = number_format($data['transactions']);
        } else {
            $transaction_text = "0";
        }
        $pdf->Cell($widths[1], 8, $transaction_text, 1, 0, 'C', true);
        
        // Parking transactions
        $parking_count = isset($data['parking']) ? number_format($data['parking']) : "0";
        $pdf->Cell($widths[2], 8, $parking_count, 1, 0, 'C', true);
        
        // Rental transactions
        $rental_count = isset($data['rental']) ? number_format($data['rental']) : "0";
        $pdf->Cell($widths[3], 8, $rental_count, 1, 0, 'C', true);
        
        // Reservation transactions
        $reservation_count = isset($data['reservation']) ? number_format($data['reservation']) : "0";
        // Debug log to trace reservation values
        error_log("Reservation count for period " . ($data['date'] ?? 'unknown') . ": " . ($data['reservation'] ?? 'null'));
        $pdf->Cell($widths[4], 8, $reservation_count, 1, 0, 'C', true);
        
        // Amount (in green)
        $pdf->SetTextColor(0, 128, 0);
        if (isset($data['amount'])) {
            $amount_text = "PHP " . number_format($data['amount'], 2);
        } else {
            $amount_text = "PHP 0.00";
        }
        $pdf->Cell($widths[5], 8, $amount_text, 1, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);  // Reset text color
    }
    
    // Add summary
    $summary_data = [
        'total_earnings' => $total_earnings,
        'average_earnings' => $average_earnings,
        'max_earnings' => $max_earnings,
        'max_earnings_date' => $max_earnings_date,
        'transaction_count' => $transaction_count,
        'start_date' => date('M d, Y', strtotime($start_date)),
        'end_date' => date('M d, Y', strtotime($end_date)),
        'report_type' => $report_type,
        'transaction_types' => $transaction_types
    ];
    
    // Add transaction type breakdown visualization
    $remainingSpace = $pdf->GetPageHeight() - $pdf->GetY() - 15; // 15 is footer height
    
    // Check if there's enough space for the chart (at least 60mm)
    if ($remainingSpace < 60) {
        $pdf->AddPage(); // Add a page break if not enough space
    }
    
    $pdf->AddTransactionTypeBreakdown([
        'parking' => $transaction_types['parking'],
        'rental' => $transaction_types['rental'],
        'reservation' => $transaction_types['reservation']
    ]);
    
    // Check if we need a page break before summary
    $remainingSpace = $pdf->GetPageHeight() - $pdf->GetY() - 15;
    if ($remainingSpace < 80) { // Summary needs about 80mm
        $pdf->AddPage();
    }
    
    $pdf->AddSummary($summary_data);
}

// Output PDF
$pdf->Output('D', 'earnings_report_' . $report_type . '_' . date('Y-m-d') . '.pdf');
exit();
?>
