<?php
// Clear any previous output buffers to ensure only JSON is sent
if (ob_get_length()) ob_clean(); 
header('Content-Type: application/json');

include("../db_connect.php");

// Set error reporting to 0 to prevent PHP warnings from corrupting the JSON
error_reporting(0); 

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Fetch all columns from pwd table
    // We select p.* to ensure every single one of those 44 fields is sent to the form
    $query = "SELECT p.*, d.disability_name 
              FROM pwd p 
              LEFT JOIN disability_type d ON p.disability_type = d.id 
              WHERE p.id = ?";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        /**
         * PHOTO MAPPING
         * This ensures the frontend always sees a field named 'photo' 
         * regardless of whether the DB uses 'photo' or 'profile_picture'.
         */
        if (empty($row['photo']) && !empty($row['profile_picture'])) {
            $row['photo'] = $row['profile_picture'];
        }

        // Sanitize numeric fields for JavaScript
        $row['id'] = (int)$row['id'];
        $row['barangay_id'] = (int)$row['barangay_id'];
        $row['disability_type'] = (int)$row['disability_type'];
        
        // Final JSON Output
        echo json_encode([
            "status" => "success", 
            "data" => $row
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "PWD record not found."]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "No ID provided."]);
}

$conn->close();
?>