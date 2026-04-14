<?php
// 1. Set header to JSON so the browser knows how to read it
header('Content-Type: application/json');

include("db_connect.php");

// 2. Suppress errors from showing in the JSON output (prevents breaking the format)
error_reporting(0);

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // 3. Fetch all columns from the pwd table
    $query = "SELECT * FROM pwd WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            // 4. Return the data object directly
            echo json_encode([
                "status" => "success", 
                "data" => $data
            ]);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "PWD not found in database."
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Database prepare statement failed."
        ]);
    }
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "No ID provided."
    ]);
}

$conn->close();
?>