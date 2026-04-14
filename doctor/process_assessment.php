<?php
session_start();
include("../db_connect.php");

if (isset($_POST['btn_submit_assessment'])) {
    $id = $_POST['pwd_id'];
    $dr_id = $_SESSION['user_id'];

    // Convert Arrays to Comma-Separated Strings
    $musculo = isset($_POST['musculo']) ? implode(",", $_POST['musculo']) : 'None';
    $motor = isset($_POST['motor']) ? implode(",", $_POST['motor']) : 'None';
    $visual = isset($_POST['visual']) ? implode(",", $_POST['visual']) : 'None';
    $hearing = isset($_POST['hearing']) ? implode(",", $_POST['hearing']) : 'None';
    $mental = isset($_POST['mental']) ? implode(",", $_POST['mental']) : 'None';
    $devices = isset($_POST['devices']) ? implode(",", $_POST['devices']) : 'None';
    $speech = isset($_POST['speech']) ? implode(",", $_POST['speech']) : 'None';
    $deformities = isset($_POST['deformities']) ? implode(",", $_POST['deformities']) : 'None';

    // Text Inputs
    $etiology = $_POST['etiology'] ?? 'N/A';
    $acquired_details = $_POST['acquired_details'] ?? '';
    $other_device = $_POST['other_device'] ?? '';
    $medical_remarks = $_POST['medical_remarks'] ?? '';
    $physician_name = $_POST['physician_name'] ?? '';
    $physician_license = $_POST['physician_license'] ?? '';
    $physician_ptr = $_POST['physician_ptr'] ?? '';

    // 1. UPDATE PWD RECORD
    $sql = "UPDATE pwd SET 
                functional_assessments = ?, 
                motor_disability = ?, 
                visual_impairment = ?, 
                hearing_impairment = ?, 
                mental_impairment = ?, 
                speech_impairment = ?, 
                deformity_details = ?, 
                assistive_devices = ?, 
                assessment_etiology = ?, 
                etiology_details = ?, 
                assistive_devices_other = ?, 
                diagnosis = ?, 
                physician_name = ?, 
                physician_license = ?, 
                physician_ptr = ?, 
                validated_by_id = ?, 
                status = 'For Approval' 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssii", 
        $musculo, $motor, $visual, $hearing, $mental, $speech, $deformities, 
        $devices, $etiology, $acquired_details, $other_device, $medical_remarks, 
        $physician_name, $physician_license, $physician_ptr, $dr_id, $id
    );

    if ($stmt->execute()) {
        
        // 2. CREATE NOTIFICATION FOR ADMIN
        // First, get PWD name and Barangay ID for the notification
        $pwd_info = $conn->query("SELECT first_name, last_name, barangay_id FROM pwd WHERE id = $id")->fetch_assoc();
        $fullname = $pwd_info['first_name'] . " " . $pwd_info['last_name'];
        $brgy_id = $pwd_info['barangay_id'];

        $notif_msg = "Medical Assessment completed for $fullname. Record is now Waiting for Approval.";
        
        $notif_sql = "INSERT INTO notifications (barangay_id, message, read_by_admin) VALUES (?, ?, 0)";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("is", $brgy_id, $notif_msg);
        $notif_stmt->execute();

        echo "<script>
                alert('Assessment successfully submitted! The admin has been notified for final approval.');
                window.location.href = 'index.php?page=medical_screening';
              </script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>