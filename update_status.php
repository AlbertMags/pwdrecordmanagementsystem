<?php
include "db_connect.php"; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];
    $action = $_POST["action"];

    if ($action === "approve") {
        $newStatus = "Approved";
    } elseif ($action === "reject") {
        $newStatus = "Rejected";
    } else {
        echo "error";
        exit();
    }

    // Update the PWD status in the database
    $sql = "UPDATE pwd SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $newStatus, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
}
?>
