<?php
require_once 'config/db_config.php';
require_once 'libs/fpdf/fpdf.php';

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
class AuditPDF extends FPDF {
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
        $this->Cell(0, 10, 'Audit Trail Report', 0, 1, 'C');
        
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
        $this->Cell(0, 10, $this->system_name . ' - Audit Trail Report', 0, 0, 'L');
        
        // Add date on right
        $this->SetX(-60);
        $this->Cell(50, 10, date('Y-m-d'), 0, 0, 'R');
    }
    
    // Better looking table header
    function ImprovedTableHeader($headers, $widths) {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(66, 133, 244); // Blue header
        $this->SetTextColor(255);

        foreach ($headers as $i => $header) {
            $this->Cell($widths[$i], 10, $header, 1, 0, 'C', true);
        }
        $this->Ln();

        // Reset font and color for table body to avoid style leaking to next rows/pages
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(50, 50, 50);
        // Reset fill color for data rows (white by default)
        $this->SetFillColor(255, 255, 255);
    }
    
    // Completely rewrite the row rendering system to properly handle alignment
    
    // Calculate exact text height with proper line breaks
    function GetMultiCellHeight($w, $txt, $h=5) {
        $lines = explode("\n", $txt);
        $nb = 0;
        
        foreach($lines as $line) {
            $nb += max(1, ceil($this->GetStringWidth($line) / ($w - 4)));
        }
        
        return $nb * $h;
    }
    
    // Draw a cell with proper alignment and borders
    function DrawAlignedCell($w, $h, $txt, $border=1, $ln=0, $align='L', $fill=false) {
        // Calculate text height
        $textHeight = $this->GetMultiCellHeight($w, $txt, 5);
        
        // Save current position
        $x = $this->GetX();
        $y = $this->GetY();
        
        // Draw background if needed
        if($fill) {
            $this->Rect($x, $y, $w, $h, 'F');
        }
        
        // Draw borders
        if($border) {
            $this->Rect($x, $y, $w, $h);
        }
        
        // Calculate vertical padding for centering
        $vPadding = ($h - $textHeight) / 2;
        
        // Print text
        $this->SetXY($x, $y + $vPadding);
        $this->MultiCell($w, 5, $txt, 0, $align);
        
        // Restore position
        if($ln == 0) {
            $this->SetXY($x + $w, $y);
        } else {
            $this->SetY($y + $h);
        }
    }

    // Draw a multi-cell with fixed height
    function FixedHeightMultiCell($w, $h, $txt, $height, $border=1, $align='L', $fill=false) {
        // Save initial position
        $x = $this->GetX();
        $y = $this->GetY();
        
        // Draw background and border if needed
        if($fill || $border) {
            $this->Rect($x, $y, $w, $height, $fill ? 'DF' : 'D');
        }
        
        // Calculate text height
        $textHeight = $this->GetMultiCellHeight($w, $txt, $h);
        
        // Calculate vertical padding to center text
        $verticalPadding = ($height - $textHeight) / 2;
        
        // Print text within cell with vertical centering
        $this->SetXY($x, $y + $verticalPadding);
        $this->MultiCell($w, $h, $txt, 0, $align);
        
        // Restore position to the right of the cell
        $this->SetXY($x + $w, $y);
    }
    
    // New table row renderer with proper alignment
    function ImprovedTableRow($data, $widths, $lineHeight = 5, $fill = false) {
        // Initial position
        $startX = $this->GetX();
        $startY = $this->GetY();

        // Calculate required heights for all cells
        $heights = [];
        $maxHeight = $lineHeight;

        // Calculate height for old value and new value columns
        $oldValueHeight = $this->GetMultiCellHeight($widths[5], $data[5], $lineHeight);
        $newValueHeight = $this->GetMultiCellHeight($widths[6], $data[6], $lineHeight);

        // Set row height based on the tallest content + padding
        $maxHeight = max($maxHeight, $oldValueHeight, $newValueHeight, 8);

        // Check if this row would exceed page height and add a new page if needed
        if($startY + $maxHeight > $this->PageBreakTrigger) {
            $this->AddPage('L');

            // Re-add header on new page
            $headers = ['Date & Time', 'Action', 'Table', 'ID', 'Field', 'Old Value', 'New Value', 'User'];
            $this->ImprovedTableHeader($headers, $widths);

            // Reset position for the new page
            $startX = $this->GetX();
            $startY = $this->GetY();
        }

        // Draw standard cells (fixed height)
        $currentX = $startX;

        // Alignment: 0=Date(center), 1=Action(center), 2=Table(left), 3=ID(center), 4=Field(left), 5=Old(left), 6=New(left), 7=User(left)
        $aligns = ['C', 'C', 'L', 'C', 'L', 'L', 'L', 'L'];

        for($i = 0; $i < 5; $i++) {
            $this->SetXY($currentX, $startY);
            $this->DrawAlignedCell($widths[$i], $maxHeight, $data[$i], 1, 0, $aligns[$i], $fill);
            $currentX += $widths[$i];
        }

        // Draw old value cell
        $this->SetXY($currentX, $startY);
        $this->FixedHeightMultiCell($widths[5], $lineHeight, $data[5], $maxHeight, 1, $aligns[5], $fill);
        $currentX += $widths[5];

        // Draw new value cell
        $this->SetXY($currentX, $startY);
        $this->FixedHeightMultiCell($widths[6], $lineHeight, $data[6], $maxHeight, 1, $aligns[6], $fill);
        $currentX += $widths[6];

        // Draw user cell
        $this->SetXY($currentX, $startY);
        $this->DrawAlignedCell($widths[7], $maxHeight, $data[7], 1, 0, $aligns[7], $fill);

        // Move position to after this row
        $this->SetXY($startX, $startY + $maxHeight);
    }
}

// Get filter parameters
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';

if (!empty($search_term)) {
    $search_condition = " AND (
        table_name LIKE '%$search_term%' OR 
        field_name LIKE '%$search_term%' OR 
        old_value LIKE '%$search_term%' OR 
        new_value LIKE '%$search_term%' OR 
        username LIKE '%$search_term%'
    )";
}

// Date range filter
if (!empty($_GET['date_from'])) {
    $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
    $search_condition .= " AND timestamp >= '$date_from 00:00:00'";
}

if (!empty($_GET['date_to'])) {
    $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
    $search_condition .= " AND timestamp <= '$date_to 23:59:59'";
}

// Action type filter
if (!empty($_GET['action_type'])) {
    $action_type = mysqli_real_escape_string($conn, $_GET['action_type']);
    $search_condition .= " AND action_type = '$action_type'";
}

// Table name filter
if (!empty($_GET['table_name'])) {
    $table_name = mysqli_real_escape_string($conn, $_GET['table_name']);
    $search_condition .= " AND table_name = '$table_name'";
}

// Username filters
if (!empty($_GET['username'])) {
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    $search_condition .= " AND username = '$username'";
}

// Get the audit logs
$sql = "SELECT * FROM audit_logs 
        WHERE 1=1 $search_condition
        ORDER BY timestamp DESC";

$result = mysqli_query($conn, $sql);
$audit_logs = [];
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $audit_logs[] = $row;
    }
}

// Create new PDF document
$pdf = new AuditPDF($system_name);
$pdf->AliasNbPages();
$pdf->AddPage('L'); // Landscape orientation
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetMargins(10, 15, 10);
$pdf->SetFont('Arial', '', 10);

// Add filters summary if any filters are applied
$appliedFilters = [];
if (!empty($search_term)) $appliedFilters[] = 'Search: ' . $search_term;
if (!empty($_GET['date_from'])) $appliedFilters[] = 'From: ' . $_GET['date_from'];
if (!empty($_GET['date_to'])) $appliedFilters[] = 'To: ' . $_GET['date_to'];
if (!empty($_GET['action_type'])) $appliedFilters[] = 'Action Type: ' . $_GET['action_type'];
if (!empty($_GET['table_name'])) $appliedFilters[] = 'Table: ' . $_GET['table_name'];
if (!empty($_GET['username'])) $appliedFilters[] = 'User: ' . $_GET['username'];

if (!empty($appliedFilters)) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->Cell(0, 8, 'Applied Filters:', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(0, 5, implode(' | ', $appliedFilters), 0, 'L');
    $pdf->Ln(5);
}

// Modify column widths to better fit content
$headers = ['Date & Time', 'Action', 'Table', 'ID', 'Field', 'Old Value', 'New Value', 'User'];
$widths = [35, 18, 25, 15, 30, 62, 62, 23]; // Increased date column width, balanced others

// Add table header
$pdf->ImprovedTableHeader($headers, $widths);

// Add table data with alternating row colors
$pdf->SetFont('Arial', '', 8);
$row_count = 0;

foreach ($audit_logs as $log) {
    $row_count++;
    // Alternate fill: even rows light gray, odd rows white
    $fill = ($row_count % 2 == 0);

    if ($fill) {
        $pdf->SetFillColor(240, 240, 250); // light gray
    } else {
        $pdf->SetFillColor(255, 255, 255); // white
    }

    // Format date and time
    $dateTime = date('M d, Y g:i A', strtotime($log['timestamp']));
    
    // Format action type with color
    $actionType = $log['action_type'];
    if ($log['action_type'] == 'insert') {
        $pdf->SetTextColor(0, 128, 0); // Green for insert
    } elseif ($log['action_type'] == 'update') {
        $pdf->SetTextColor(0, 102, 204); // Blue for update
    } elseif ($log['action_type'] == 'delete') {
        $pdf->SetTextColor(204, 0, 0); // Red for delete
    }
    
    // Handle special characters and trim long values
    $oldValue = $log['old_value'] ?? 'N/A';
    $newValue = $log['new_value'] ?? 'N/A';
    
    // Better sanitization to handle special characters
    $oldValue = str_replace("\r", "", $oldValue);
    $newValue = str_replace("\r", "", $newValue);
    
    // Format JSON and improve readability
    if (substr($oldValue, 0, 1) === '{' && substr($oldValue, -1) === '}') {
        $decodedOld = json_decode($oldValue, true);
        if ($decodedOld !== null) {
            $oldValue = json_encode($decodedOld, JSON_PRETTY_PRINT);
        }
    }
    
    if (substr($newValue, 0, 1) === '{' && substr($newValue, -1) === '}') {
        $decodedNew = json_decode($newValue, true);
        if ($decodedNew !== null) {
            $newValue = json_encode($decodedNew, JSON_PRETTY_PRINT);
        }
    }
    
    // Set character limit to prevent layout issues (adjust if needed)
    $charLimit = 1500;
    if (strlen($oldValue) > $charLimit) {
        $oldValue = substr($oldValue, 0, $charLimit) . "...";
    }
    
    if (strlen($newValue) > $charLimit) {
        $newValue = substr($newValue, 0, $charLimit) . "...";
    }
    
    // Prepare row data
    $rowData = [
        $dateTime,
        strtoupper($actionType),
        $log['table_name'],
        $log['record_id'] ?? 'N/A',
        $log['field_name'] ?? 'N/A',
        $oldValue,
        $newValue,
        $log['username'] ?? 'System'
    ];
    
    // Reset text color before rendering the row
    $pdf->SetTextColor(50, 50, 50);

    // Use our improved row rendering function
    $pdf->ImprovedTableRow($rowData, $widths, 5, $fill);
}

// Add summary
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(0, 102, 204);
$pdf->Cell(0, 10, 'Summary', 0, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);
$pdf->Cell(80, 8, 'Total Records:', 0, 0, 'L');
$pdf->Cell(30, 8, count($audit_logs), 0, 1, 'L');

// Count by action type
$insertCount = 0;
$updateCount = 0;
$deleteCount = 0;

foreach ($audit_logs as $log) {
    if ($log['action_type'] == 'insert') $insertCount++;
    elseif ($log['action_type'] == 'update') $updateCount++;
    elseif ($log['action_type'] == 'delete') $deleteCount++;
}

$pdf->Cell(80, 8, 'Insert Operations:', 0, 0, 'L');
$pdf->Cell(30, 8, $insertCount, 0, 1, 'L');

$pdf->Cell(80, 8, 'Update Operations:', 0, 0, 'L');
$pdf->Cell(30, 8, $updateCount, 0, 1, 'L');

$pdf->Cell(80, 8, 'Delete Operations:', 0, 0, 'L');
$pdf->Cell(30, 8, $deleteCount, 0, 1, 'L');

// Output PDF
$pdf->Output('D', 'audit_trail_report_' . date('Y-m-d') . '.pdf');
exit();
?>
