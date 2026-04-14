<?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['user_id'];
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $barangay_id = $_POST['barangay_id'];

    // Check if email is already used by another user
    $emailCheck = $conn->prepare("SELECT user_id FROM myusers WHERE email = ? AND user_id != ?");
    $emailCheck->bind_param("si", $email, $id);
    $emailCheck->execute();
    $emailCheck->store_result();
    if ($emailCheck->num_rows > 0) {
        echo "Error: Email already exists for another user!";
        exit;
    }

    // Check if barangay is already assigned to another user
    $barangayCheck = $conn->prepare("SELECT user_id FROM myusers WHERE barangay_id = ? AND user_id != ?");
    $barangayCheck->bind_param("ii", $barangay_id, $id);
    $barangayCheck->execute();
    $barangayCheck->store_result();
    if ($barangayCheck->num_rows > 0) {
        echo "Error: This barangay already has a user!";
        exit;
    }

    // Check if password is being updated
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE myusers SET full_name=?, email=?, password=?, barangay_id=? WHERE user_id=?");
        $stmt->bind_param("sssii", $name, $email, $password, $barangay_id, $id);
    } else {
        $stmt = $conn->prepare("UPDATE myusers SET full_name=?, email=?, barangay_id=? WHERE user_id=?");
        $stmt->bind_param("ssii", $name, $email, $barangay_id, $id);
    }

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error updating user: " . $stmt->error;
    }

    $stmt->close();
    $emailCheck->close();
    $barangayCheck->close();
}
?>
