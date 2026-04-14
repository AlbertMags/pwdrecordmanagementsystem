<?php
include("../db_connect.php");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Get the logged-in Barangay's User ID
$brgy_id = isset($_SESSION['barangay_id']) ? $_SESSION['barangay_id'] : null;

if(!$brgy_id){
    header('Content-Type: application/json');
    echo json_encode([]); 
    exit;
}

// 1. HANDLE MARK AS READ
$notif_id = isset($_POST['id']) ? $_POST['id'] : (isset($_POST['mark_read_id']) ? $_POST['mark_read_id'] : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $notif_id !== null) {
    $id = intval($notif_id);
    $stmt = $conn->prepare("UPDATE notifications SET read_by_brgy = 1 WHERE id = ? AND barangay_id = ?");
    $stmt->bind_param("ii", $id, $brgy_id);
    
    header('Content-Type: application/json');
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
    exit; 
}

// 2. FETCH NOTIFICATIONS
$sql = "SELECT * FROM notifications WHERE barangay_id = ? ORDER BY created_at DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $brgy_id);
$stmt->execute();
$result = $stmt->get_result();
$data = [];

// Define the base URL for clean links
$base_url = "/PWD/brgy_data/";

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['status'] = ($row['read_by_brgy'] == 1) ? 'read' : 'unread';
        $msg = $row['message'];
        
        /**
         * REDIRECTION MAPPING (CLEAN URL VERSION)
         */
        
        // 1. Support Center Logic (Tabs)
        if (stripos($msg, 'Service') !== false) {
            $row['redirect_link'] = $base_url . "brgy_support_center?tab=services";
        } 
        else if (stripos($msg, 'News') !== false || stripos($msg, 'Activity') !== false) {
            $row['redirect_link'] = $base_url . "brgy_support_center?tab=news";
        }
        else if (stripos($msg, 'Announcement') !== false || stripos($msg, 'MSWDO posted') !== false) {
            $row['redirect_link'] = $base_url . "brgy_support_center?tab=announcements";
        }
        
        // 2. PWD List Logic
        else if (stripos($msg, 'Registration Finalized') !== false || stripos($msg, 'Official') !== false) {
            $row['redirect_link'] = $base_url . "brgy_official_list";
        } 
        else if (stripos($msg, 'Screening') !== false) {
            $row['redirect_link'] = $base_url . "brgy_pwd?status=Screening";
        } 
        else if (stripos($msg, 'Approval') !== false || stripos($msg, 'Assessment completed') !== false) {
            $row['redirect_link'] = $base_url . "brgy_pwd?status=For Approval";
        } 
        else if (
            stripos($msg, 'New Application') !== false || 
            stripos($msg, 'Barangay Application') !== false || 
            stripos($msg, 'added new PWD') !== false || 
            stripos($msg, 'Pending') !== false
        ) {
            $row['redirect_link'] = $base_url . "brgy_pwd?status=Pending";
        } 
        else {
            // Fallback to dashboard
            $row['redirect_link'] = $base_url . "brgy_dashboard";
        }

        $row['message'] = htmlspecialchars_decode($row['message']);
        $row['time_ago'] = date("M d, h:i A", strtotime($row['created_at']));
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>