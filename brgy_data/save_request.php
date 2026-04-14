<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../db_connect.php");

// Security Check: Ensure only logged-in barangay users can save
if (!isset($_SESSION['barangay_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    $barangay_id = $_SESSION['barangay_id'];
    $pwd_id = $_POST['pwd_id'];
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $status = 'Pending';

    // Prepare the SQL statement to prevent SQL Injection
    $query = "INSERT INTO service_requests (barangay_id, pwd_id, service_type, remarks, status) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $barangay_id, $pwd_id, $service_type, $remarks, $status);

    if ($stmt->execute()) {
        // Success: Redirect back with a success message
        echo "<script>
                alert('Service request submitted successfully!');
             window.location.href = 'index.php?page=brgy_support_center&tab=history'; </script>";
    } else {
        // Error handling
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect if accessed directly without POST
    header("Location: index.php?page=brgy_support_center");
    exit();
}
?>