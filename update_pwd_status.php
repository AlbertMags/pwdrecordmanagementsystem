<?php
include 'db_connect.php'; // Ensure database connection

if (isset($_POST['pwd_id']) && isset($_POST['status'])) {
    $pwd_id = $_POST['pwd_id'];
    $new_status = $_POST['status'];

    // Update the status in the database
    $query = "UPDATE pwd SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $new_status, $pwd_id);

    if ($stmt->execute()) {
        echo "success"; // Response if using AJAX
    } else {
        echo "error"; // Response if using AJAX
    }

    $stmt->close();
    $conn->close();
} else {
    echo "invalid"; // Response if missing data
}
?>
