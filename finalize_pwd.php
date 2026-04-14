<?php
include 'db_connect.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // 1. Get PWD info
    $pwd_query = "SELECT first_name, last_name, email, barangay_id FROM pwd WHERE id = ?";
    $pwd_stmt = $conn->prepare($pwd_query);
    $pwd_stmt->bind_param("i", $id);
    $pwd_stmt->execute();
    $pwd = $pwd_stmt->get_result()->fetch_assoc();
    $pwd_stmt->close();

    if (!$pwd) {
        die("error: PWD record not found");
    }

    $pwd_fullname = $pwd['first_name'] . " " . $pwd['last_name'];
    $barangay_id = $pwd['barangay_id'];
    $email = $pwd['email'];
    
    // --- PASSWORD LOGIC ---
    $first_initial = strtolower(substr(trim($pwd['first_name']), 0, 1)); 
    $last_name_clean = strtolower(str_replace(' ', '', trim($pwd['last_name']))); 
    $raw_password = $last_name_clean . $first_initial . "2026"; 
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        // A. Update status
        $update_query = "UPDATE pwd SET status = 'Official' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $id);
        $update_stmt->execute();

        // B. Create Account
        $user_sql = "INSERT INTO myusers (full_name, email, password, role, related_pwd_id) VALUES (?, ?, ?, 'pwd', ?)";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("sssi", $pwd_fullname, $email, $hashed_password, $id);
        $user_stmt->execute();

        // C. Create Notification
        $notif_message = "Registration Finalized: $pwd_fullname. Login: $email / Password: $raw_password";
        $notif_sql = "INSERT INTO notifications (barangay_id, message, read_by_admin, read_by_brgy) VALUES (?, ?, 0, 0)";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("is", $barangay_id, $notif_message);
        $notif_stmt->execute();

        $conn->commit();
        
        // This is what the AJAX 'success' function will receive
        echo "success|$email|$raw_password";

    } catch (Exception $e) {
        $conn->rollback();
        echo "error: " . $e->getMessage();
    }
    $conn->close();
} else {
    echo "error: No ID provided";
}
?>