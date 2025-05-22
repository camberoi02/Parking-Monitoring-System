<?php
require_once dirname(__FILE__) . '/../../config/db_config.php';

// Check if file was uploaded
if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
    $response = ['success' => false, 'message' => '', 'logo_html' => ''];
    
    try {
        $file = $_FILES['logo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowedTypes) && $file['size'] <= 1048576) { // 1MB limit
            $imageData = file_get_contents($file['tmp_name']);
            
            // Prepare and execute the SQL statement
            $stmt = mysqli_prepare($conn, "INSERT INTO logo (image_data, file_name, mime_type) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $imageData, $file['name'], $file['type']);
            
            if (mysqli_stmt_execute($stmt)) {
                // Get the logo HTML for the response
                $logo_data = base64_encode($imageData);
                $logo_html = '<div class="me-2" style="width: 32px; height: 32px;">';
                $logo_html .= '<img src="data:' . $file['type'] . ';base64,' . $logo_data . '" alt="Logo" 
                                  style="width: 32px; height: 32px; object-fit: cover; border-radius: 50%;">';
                $logo_html .= '</div>';
                
                $response['success'] = true;
                $response['message'] = "Logo updated successfully.";
                $response['logo_html'] = $logo_html;
                
                // Log to audit trail
                logAudit($conn, 'update', 'logo', null, 'logo_image', 'previous_logo', 'new_logo_uploaded');
            } else {
                $response['message'] = "Error saving logo to database: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = "Invalid file type or size. Please upload a JPG, PNG, or GIF file under 1MB.";
        }
    } catch (Exception $e) {
        $response['message'] = "Error processing upload: " . $e->getMessage();
    }
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error occurred.']);
} 