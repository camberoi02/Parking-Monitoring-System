<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the request
error_log("Contract generation requested. GET params: " . print_r($_GET, true));

// Check if FPDF exists
if (!file_exists(__DIR__ . '/../libs/fpdf/fpdf.php')) {
    die('Error: FPDF library not found. Please ensure it is installed in the libs/fpdf directory.');
}

require_once(__DIR__ . '/../libs/fpdf/fpdf.php');

// Check database connection
try {
    require_once(__DIR__ . '/../config/db_config.php');
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die('Database Error: ' . $e->getMessage());
}

class RentalContract extends FPDF {
    function __construct() {
        parent::__construct('P', 'mm', 'A4');
    }

    function Header() {
        // Logo (if you have one)
        // $this->Image('logo.png', 10, 10, 30);
        
        // Title
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'PARKING SPOT RENTAL AGREEMENT', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Contract No: ' . date('Ymd') . '-' . rand(1000, 9999), 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SectionTitle($title) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, $title, 0, 1, 'L');
        $this->Ln(2);
    }

    function SectionContent($content) {
        $this->SetFont('Arial', '', 11);
        $this->MultiCell(0, 6, $content);
        $this->Ln(5);
    }
}

// Get spot details from database
if (!isset($_GET['spot_id'])) {
    error_log("Error: No spot ID provided in request");
    die('Error: No spot ID provided');
}

$spot_id = $_GET['spot_id'];
error_log("Processing contract for spot ID: " . $spot_id);

try {
    $query = "SELECT p.*, s.name as sector_name 
              FROM parking_spots p 
              LEFT JOIN sectors s ON p.sector_id = s.id 
              WHERE p.id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $spot_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Failed to get result: ' . $stmt->error);
    }
    
    if ($row = $result->fetch_assoc()) {
        error_log("Found parking spot data: " . print_r($row, true));
        
        try {
            $pdf = new RentalContract();
            $pdf->AliasNbPages();
            $pdf->AddPage();
            
            // Agreement Date
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 10, 'Date: ' . date('F d, Y'), 0, 1, 'R');
            $pdf->Ln(5);
            
            // Parties Section
            $pdf->SectionTitle('PARTIES');
            $pdf->SectionContent("This Parking Spot Rental Agreement (the 'Agreement') is made and entered into on " . date('F d, Y') . " by and between:");
            
            // Lessor Details
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 10, 'LESSOR:', 0, 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 6, 'Parking Monitoring System', 0, 1);
            $pdf->Ln(5);
            
            // Lessee Details
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 10, 'LESSEE:', 0, 1);
            $pdf->SetFont('Arial', '', 11);
            if (!empty($row['renter_name'])) {
                $pdf->Cell(0, 6, $row['renter_name'], 0, 1);
                $pdf->Cell(0, 6, 'Contact: ' . $row['renter_contact'], 0, 1);
            } else {
                $pdf->Cell(0, 6, '[To be filled by renter]', 0, 1);
            }
            $pdf->Ln(5);
            
            // Parking Spot Details
            $pdf->SectionTitle('PARKING SPOT DETAILS');
            $pdf->SectionContent("The Lessor agrees to rent the following parking spot to the Lessee:");
            $pdf->Cell(0, 6, 'Parking Spot Number: ' . $row['spot_number'], 0, 1);
            if (!empty($row['sector_name'])) {
                $pdf->Cell(0, 6, 'Location: Please proceed to ' . $row['sector_name'] . ' and look for spot number ' . $row['spot_number'], 0, 1);
            } else {
                $pdf->Cell(0, 6, 'Location: Please proceed to your assigned parking area and look for spot number ' . $row['spot_number'], 0, 1);
            }
            $pdf->Ln(5);
            
            // Rental Period
            $pdf->SectionTitle('RENTAL PERIOD');
            if (!empty($row['rental_start_date']) && !empty($row['rental_end_date'])) {
                $pdf->SectionContent("The rental period shall be from " . date('F d, Y', strtotime($row['rental_start_date'])) . " to " . date('F d, Y', strtotime($row['rental_end_date'])) . ".");
            } else {
                $pdf->SectionContent("The rental period shall be from [Start Date] to [End Date].");
            }
            $pdf->Ln(5);
            
            // Rental Rate
            $pdf->SectionTitle('RENTAL RATE');
            if (!empty($row['rental_rate'])) {
                $pdf->SectionContent("The rental rate for the parking spot is " . number_format($row['rental_rate'], 2) . " per month. Payment is due [payment terms].");
            } else {
                $pdf->SectionContent("The rental rate for the parking spot is [Rate] per month. Payment is due [payment terms].");
            }
            $pdf->Ln(5);
            
            // Terms and Conditions
            $pdf->SectionTitle('TERMS AND CONDITIONS');
            $terms = array(
                "1. USE OF PARKING SPOT",
                "   The Lessee shall use the parking spot solely for parking purposes and shall not use it for any other purpose.",
                
                "2. ASSIGNMENT AND SUBLETTING",
                "   The Lessee shall not assign or sublet the parking spot without the prior written consent of the Lessor.",
                
                "3. MAINTENANCE AND REPAIRS",
                "   The Lessee shall maintain the parking spot in good condition and shall be responsible for any damage caused to the parking spot.",
                
                "4. INSURANCE",
                "   The Lessee shall maintain appropriate insurance coverage for their vehicle while using the parking spot.",
                
                "5. TERMINATION",
                "   The Lessor reserves the right to terminate this Agreement with proper notice if the Lessee violates any terms of this Agreement.",
                
                "6. LIABILITY",
                "   The Lessor shall not be liable for any damage to the Lessee's vehicle or personal property while using the parking spot.",
                
                "7. GOVERNING LAW",
                "   This Agreement shall be governed by and construed in accordance with the laws of [Your Jurisdiction].",
                
                "8. ENTIRE AGREEMENT",
                "   This Agreement constitutes the entire agreement between the parties and supersedes all prior agreements and understandings."
            );
            
            foreach ($terms as $term) {
                $pdf->SectionContent($term);
            }
            
            // Add new page for signatures
            $pdf->AddPage();
            
            // Signatures
            $pdf->Ln(10);
            $pdf->SectionTitle('SIGNATURES');
            $pdf->SectionContent("IN WITNESS WHEREOF, the parties have executed this Agreement as of the date first written above.");
            $pdf->Ln(10);
            
            // Lessor Signature
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(90, 10, 'LESSOR:', 0, 0, 'C');
            $pdf->Cell(90, 10, 'LESSEE:', 0, 1, 'C');
            $pdf->Ln(15);
            
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(90, 10, '_____________________', 0, 0, 'C');
            $pdf->Cell(90, 10, '_____________________', 0, 1, 'C');
            $pdf->Cell(90, 10, 'Authorized Signature', 0, 0, 'C');
            $pdf->Cell(90, 10, 'Signature', 0, 1, 'C');
            $pdf->Cell(90, 10, 'Date: ' . date('m/d/Y'), 0, 0, 'C');
            $pdf->Cell(90, 10, 'Date: ' . date('m/d/Y'), 0, 1, 'C');
            
            // Output PDF
            $filename = 'Parking_Rental_Contract_' . $row['spot_number'] . '.pdf';
            error_log("Generating PDF: " . $filename);
            $pdf->Output('D', $filename);
        } catch (Exception $e) {
            error_log("PDF Generation Error: " . $e->getMessage());
            die('PDF Generation Error: ' . $e->getMessage());
        }
    } else {
        error_log("No parking spot found with ID: " . $spot_id);
        die('Error: No parking spot found with ID ' . $spot_id);
    }
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    die('Database Error: ' . $e->getMessage());
}
?> 