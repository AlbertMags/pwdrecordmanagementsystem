<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../db_connect.php"); // adjust path if needed

// Check if logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

// Get user details
$email = $_SESSION['user'];
$query = "SELECT role, barangay_id FROM myusers WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// If not a barangay admin, block access
if (!$user || $user['role'] !== 'barangay_admin') {
    header("Location: ../index.php");
    exit();
}

// Store barangay_id for later use in pages
$_SESSION['barangay_id'] = $user['barangay_id'];
?>