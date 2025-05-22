<?php
require_once 'config/db_config.php';
require_once 'libs/fpdf/fpdf.php';

// Use the same authentication approach as in system_settings.php
// This is a placeholder - in a real app, you'd use proper session validation
$isAdmin = true;

if (!$isAdmin) {
    echo "<div class='alert alert-danger'>Access denied. Admin privileges required.</div>";
    exit;
}

// Use the database connection already created in db_config.php

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
class TransactionPDF extends FPDF {
    protected $system_name;
    
    function __construct($system_name) {
        parent::__construct();
        $this->system_name = $system_name;
    }
    
    // Page header
    function Header() {
        // Add logo if available
        // $this->Image('assets/img/logo.png', 10, 10, 30);
        
        // Set company info
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 102, 204);
        $this->Cell(0, 10, $this->system_name, 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(66, 66, 66);
        $this->Cell(0, 10, 'Transaction Report', 0, 1, 'C');
        
        // Add date and time
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Generated on ' . date('F d, Y \a\t h:i A'), 0, 1, 'C');
        
        // Add a separator line
        $this->Ln(3);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 287, $this->GetY());
        $this->Ln(5);
    }

    // Page footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
        
        // Add company info in footer
        $this->SetX(10);
        $this->Cell(0, 10, $this->system_name . ' - Transaction Report', 0, 0, 'L');
        
        // Add date on right
        $this->SetX(-60);
        $this->Cell(50, 10, date('Y-m-d'), 0, 0, 'R');
    }
    
    // Better looking table header
    function ImprovedTableHeader($headers, $widths) {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(66, 133, 244);
        $this->SetTextColor(255);
        
        foreach ($headers as $i => $header) {
            $this->Cell($widths[$i], 10, $header, 1, 0, 'C', true);
        }
        $this->Ln();
    }
    
    // Summary section
    function AddSummary($data) {
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(0, 102, 204);
        $this->Cell(0, 10, 'Summary', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0);
        $this->Cell(80, 8, 'Total Transactions:', 0, 0, 'L');
        $this->Cell(30, 8, $data['total_count'], 0, 1, 'L');
        
        $this->Cell(80, 8, 'Active Transactions:', 0, 0, 'L');
        $this->Cell(30, 8, $data['active_count'], 0, 1, 'L');
        
        $this->Cell(80, 8, 'Completed Transactions:', 0, 0, 'L');
        $this->Cell(30, 8, $data['completed_count'], 0, 1, 'L');
        
        if (isset($data['total_fee']) && $data['total_fee'] > 0) {
            $this->Cell(80, 8, 'Total Revenue:', 0, 0, 'L');
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(0, 128, 0);
            $this->Cell(30, 8, 'Php ' . number_format($data['total_fee'], 2), 0, 1, 'L');
        }
    }
}

// Get filter parameters (same as in system_settings.php)
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';

if (!empty($search_term)) {
    $search_condition = " AND (t.vehicle_id LIKE '%$search_term%' 
                      OR t.customer_name LIKE '%$search_term%' 
                      OR p.spot_number LIKE '%$search_term%')";
}

// Date range filter
if (!empty($_GET['date_from'])) {
    $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
    $search_condition .= " AND t.entry_time >= '$date_from 00:00:00'";
}

if (!empty($_GET['date_to'])) {
    $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
    $search_condition .= " AND t.entry_time <= '$date_to 23:59:59'";
}

// Transaction type filter
if (!empty($_GET['transaction_type'])) {
    $transaction_type = mysqli_real_escape_string($conn, $_GET['transaction_type']);
    $search_condition .= " AND t.transaction_type = '$transaction_type'";
}

// Status filter
if (!empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    if ($status == 'active') {
        $search_condition .= " AND t.exit_time IS NULL";
    } else if ($status == 'completed') {
        $search_condition .= " AND t.exit_time IS NOT NULL";
    }
}

// Sector filter
if (!empty($_GET['sector_id'])) {
    $sector_id = mysqli_real_escape_string($conn, $_GET['sector_id']);
    $search_condition .= " AND p.sector_id = $sector_id";
}

// Fee range filter
if (!empty($_GET['fee_min'])) {
    $fee_min = mysqli_real_escape_string($conn, $_GET['fee_min']);
    $search_condition .= " AND (t.fee >= $fee_min OR t.rental_rate >= $fee_min)";
}

if (!empty($_GET['fee_max'])) {
    $fee_max = mysqli_real_escape_string($conn, $_GET['fee_max']);
    $search_condition .= " AND (t.fee <= $fee_max OR t.rental_rate <= $fee_max)";
}

// Check if sectors table exists
$sectors_table_exists = false;
$result = mysqli_query($conn, "SHOW TABLES LIKE 'sectors'");
if ($result && mysqli_num_rows($result) > 0) {
    $sectors_table_exists = true;
}

// Get the transactions without pagination
if ($sectors_table_exists) {
    $sql = "SELECT t.*, p.spot_number, s.name as sector_name,
            CASE 
                WHEN t.transaction_type = 'rental' THEN 'Rental' 
                WHEN t.transaction_type = 'reservation' THEN 'Reservation'
                ELSE 'Parking' 
            END as transaction_type_display   
            FROM transactions t
            JOIN parking_spots p ON t.spot_id = p.id
            LEFT JOIN sectors s ON p.sector_id = s.id
            WHERE 1=1 $search_condition
            ORDER BY entry_time DESC";
} else {
    $sql = "SELECT t.*, p.spot_number, NULL as sector_name,
            CASE 
                WHEN t.transaction_type = 'rental' THEN 'Rental' 
                WHEN t.transaction_type = 'reservation' THEN 'Reservation'
                ELSE 'Parking' 
            END as transaction_type_display   
            FROM transactions t
            JOIN parking_spots p ON t.spot_id = p.id
            WHERE 1=1 $search_condition
            ORDER BY entry_time DESC";
}

$result = mysqli_query($conn, $sql);
$transactions = [];
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
}

// Gather summary data
$summary_data = [
    'total_count' => count($transactions),
    'active_count' => 0,
    'completed_count' => 0,
    'total_fee' => 0
];

foreach ($transactions as $transaction) {
    if (empty($transaction['exit_time'])) {
        $summary_data['active_count']++;
    } else {
        $summary_data['completed_count']++;
    }
    
    // Add to total fee (either fee or rental_rate)
    if ($transaction['transaction_type'] == 'rental') {
        $summary_data['total_fee'] += floatval($transaction['rental_rate']);
    } else {
        $summary_data['total_fee'] += floatval($transaction['fee']);
    }
}

// Create new PDF document
$pdf = new TransactionPDF($system_name);
$pdf->AliasNbPages();
$pdf->AddPage('L'); // Landscape orientation
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetFont('Arial', '', 10);

// Add filters summary if any filters are applied
$appliedFilters = [];
if (!empty($search_term)) $appliedFilters[] = 'Search: ' . $search_term;
if (!empty($_GET['date_from'])) $appliedFilters[] = 'From: ' . $_GET['date_from'];
if (!empty($_GET['date_to'])) $appliedFilters[] = 'To: ' . $_GET['date_to'];
if (!empty($_GET['transaction_type'])) $appliedFilters[] = 'Type: ' . $_GET['transaction_type'];
if (!empty($_GET['status'])) $appliedFilters[] = 'Status: ' . $_GET['status'];
if (!empty($_GET['sector_id'])) {
    $sector_sql = "SELECT name FROM sectors WHERE id = " . intval($_GET['sector_id']);
    $sector_result = mysqli_query($conn, $sector_sql);
    if ($sector_result && $sector_row = mysqli_fetch_assoc($sector_result)) {
        $appliedFilters[] = 'Sector: ' . $sector_row['name'];
    }
}
if (!empty($_GET['fee_min'])) $appliedFilters[] = 'Min Fee: ₱' . $_GET['fee_min'];
if (!empty($_GET['fee_max'])) $appliedFilters[] = 'Max Fee: ₱' . $_GET['fee_max'];

if (!empty($appliedFilters)) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->Cell(0, 8, 'Applied Filters:', 0, 1);
    
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(80, 80, 80);
    
    // Display filters in a nicer format
    $filter_text = '';
    foreach ($appliedFilters as $index => $filter) {
        $filter_text .= $filter;
        if ($index < count($appliedFilters) - 1) {
            $filter_text .= ' | ';
        }
    }
    
    $pdf->MultiCell(0, 6, $filter_text, 0, 'L');
    $pdf->Ln(3);
}

// Define table column headers and widths
$headers = ['ID', 'Type', 'Spot', 'Sector', 'Customer', 'Vehicle ID', 'Entry Time', 'Exit Time', 'Duration', 'Fee'];
$widths = [15, 25, 20, 30, 30, 25, 30, 30, 30, 25];

// Add table header with improved styling
$pdf->ImprovedTableHeader($headers, $widths);

// Add table data with alternating row colors
$pdf->SetFont('Arial', '', 8);
$row_count = 0;

foreach ($transactions as $transaction) {
    // Set row background color (alternate)
    $row_count++;
    if ($row_count % 2 == 0) {
        $pdf->SetFillColor(240, 240, 250);
    } else {
        $pdf->SetFillColor(255, 255, 255);
    }
    
    // Calculate duration
    $duration = '';
    if (!empty($transaction['entry_time']) && !empty($transaction['exit_time'])) {
        $entry = new DateTime($transaction['entry_time']);
        $exit = new DateTime($transaction['exit_time']);
        $interval = $entry->diff($exit);
        
        if ($interval->days > 0) {
            $duration .= $interval->days . 'd ';
        }
        $duration .= $interval->h . 'h ' . $interval->i . 'm';
    }
    
    // Format rental duration for rental transactions
    if ($transaction['transaction_type'] == 'rental') {
        if (!empty($transaction['rental_start_date'])) {
            $start = new DateTime($transaction['rental_start_date']);
            $end = !empty($transaction['rental_end_date']) ? new DateTime($transaction['rental_end_date']) : new DateTime();
            $interval = $start->diff($end);
            $months = $interval->y * 12 + $interval->m;
            $duration = $months . ' month' . ($months != 1 ? 's' : '');
        }
    }
    
    // Format the exit time or status
    $exitTime = '';
    if (!empty($transaction['exit_time'])) {
        if ($transaction['transaction_type'] == 'rental') {
            $exitTime = date('M d, Y', strtotime($transaction['rental_end_date']));
        } else {
            $exitTime = date('M d, Y g:i A', strtotime($transaction['exit_time']));
        }
    } else {
        $exitTime = $transaction['transaction_type'] == 'rental' ? 'Active Rental' : 'Still Parked';
    }
    
    // Format entry time
    $entryTime = '';
    if ($transaction['transaction_type'] == 'rental') {
        $entryTime = date('M d, Y', strtotime($transaction['rental_start_date']));
    } else {
        $entryTime = date('M d, Y g:i A', strtotime($transaction['entry_time']));
    }
    
    // Format fee
    $fee = '';
    if ($transaction['transaction_type'] == 'rental') {
        $fee = 'Php ' . number_format($transaction['rental_rate'], 2) . '/mo';
    } elseif (!empty($transaction['fee'])) {
        $fee = 'Php ' . number_format($transaction['fee'], 2);
    } else {
        $fee = 'Php 0.00';
    }
    
    // Set text color based on transaction type
    if ($transaction['transaction_type'] == 'rental') {
        $pdf->SetTextColor(0, 100, 180); // Blue for rentals
    } elseif ($transaction['transaction_type'] == 'reservation') {
        $pdf->SetTextColor(200, 100, 0); // Orange for reservations
    } else {
        $pdf->SetTextColor(50, 50, 50); // Dark grey for regular
    }
    
    // Add row to PDF with fill
    $pdf->Cell($widths[0], 8, $transaction['id'], 1, 0, 'C', true);
    
    // For transaction type, use normal color but bold
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell($widths[1], 8, $transaction['transaction_type_display'], 1, 0, 'C', true);
    $pdf->SetFont('Arial', '', 8);
    
    // Reset text color for remaining fields
    $pdf->SetTextColor(50, 50, 50);
    
    $pdf->Cell($widths[2], 8, $transaction['spot_number'], 1, 0, 'C', true);
    $pdf->Cell($widths[3], 8, $transaction['sector_name'] ?? 'Default', 1, 0, 'L', true);
    $pdf->Cell($widths[4], 8, $transaction['customer_name'] ?? 'N/A', 1, 0, 'L', true);
    $pdf->Cell($widths[5], 8, $transaction['vehicle_id'] ?? 'N/A', 1, 0, 'C', true);
    $pdf->Cell($widths[6], 8, $entryTime, 1, 0, 'C', true);
    $pdf->Cell($widths[7], 8, $exitTime, 1, 0, 'C', true);
    $pdf->Cell($widths[8], 8, $duration, 1, 0, 'C', true);
    
    // Set fee in green
    $pdf->SetTextColor(0, 128, 0);
    $pdf->Cell($widths[9], 8, $fee, 1, 1, 'R', true);
    
    // Reset text color for next row
    $pdf->SetTextColor(50, 50, 50);
}

// Add summary section
$pdf->AddSummary($summary_data);

// Output PDF
$pdf->Output('D', 'transaction_report_' . date('Y-m-d') . '.pdf');
exit();
?>
