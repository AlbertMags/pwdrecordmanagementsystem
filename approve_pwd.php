<?php
include 'db_connect.php';

// Check if ID is provided via POST
if (isset($_POST['id'])) {
    $id = intval($_POST['id']); // Secure the ID

    // 1. Get PWD info for the notification before updating
    $pwd_query = "SELECT first_name, last_name, barangay_id FROM pwd WHERE id = ?";
    $pwd_stmt = $conn->prepare($pwd_query);
    $pwd_stmt->bind_param("i", $id);
    $pwd_stmt->execute();
    $result = $pwd_stmt->get_result();
    $pwd = $result->fetch_assoc();
    $pwd_stmt->close();

    if (!$pwd) {
        echo "error: PWD record not found";
        exit();
    }

    $pwd_fullname = $pwd['first_name'] . " " . $pwd['last_name'];
    $barangay_id = $pwd['barangay_id'];

    // 2. Update the status to 'Screening'
    $query = "UPDATE pwd SET status = 'Screening' WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // 3. Create a notification for the Barangay and Admin
        $notif_message = "PWD application for $pwd_fullname has been moved to Screening.";
        
        // Ensure your notifications table has these columns
        $notif_sql = "INSERT INTO notifications (barangay_id, message, read_by_admin, read_by_brgy, created_at) 
                      VALUES (?, ?, 0, 0, NOW())";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("is", $barangay_id, $notif_message);
        $notif_stmt->execute();
        $notif_stmt->close();

        echo "success";
    } else {
        echo "error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "error " ;
}
?>