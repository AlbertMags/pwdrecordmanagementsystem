<?php
include 'db_connect.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Capture Form 2 Data
    $diagnosis = $_POST['diagnosis'];
    $physician = $_POST['physician_name'];
    $license = $_POST['physician_license'];
    $officer = $_POST['screening_officer'];
    $assistive_devices = $_POST['assistive_devices'];

    // Capture Functional Assessment Checkboxes (Converting Array to String)
    $functional_list = "";
    if(isset($_POST['functional_assessments']) && is_array($_POST['functional_assessments'])){
        $functional_list = implode(", ", $_POST['functional_assessments']);
    }

    // 1. Get PWD info for notification
    $pwd_query = "SELECT first_name, last_name, barangay_id FROM pwd WHERE id = ?";
    $pwd_stmt = $conn->prepare($pwd_query);
    $pwd_stmt->bind_param("i", $id);
    $pwd_stmt->execute();
    $pwd = $pwd_stmt->get_result()->fetch_assoc();
    $pwd_stmt->close();

    if (!$pwd) {
        die("error: PWD not found");
    }

    $pwd_fullname = $pwd['first_name'] . " " . $pwd['last_name'];
    $barangay_id = $pwd['barangay_id'];

    // 2. Update PWD Table 
    // FIXED: Changed status to 'For Approval' to match your pwd.php table logic
    $sql = "UPDATE pwd SET 
            status = 'For Approval', 
            diagnosis = ?, 
            physician_name = ?, 
            physician_license = ?, 
            screening_officer = ?,
            functional_assessments = ?,
            assistive_devices = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) { die("Prepare failed: " . $conn->error); }

    $stmt->bind_param("ssssssi", 
        $diagnosis, $physician, $license, $officer,
        $functional_list, $assistive_devices, $id
    );

    if ($stmt->execute()) {
        // 3. Create notification
        $notif_message = "Medical Screening Completed for $pwd_fullname (Ready for Final Approval)";
        $notif_sql = "INSERT INTO notifications (barangay_id, message, read_by_admin, read_by_brgy) VALUES (?, ?, 0, 0)";
        
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("is", $barangay_id, $notif_message);
        $notif_stmt->execute();
        $notif_stmt->close();

        echo "success";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "No ID provided.";
}
?>