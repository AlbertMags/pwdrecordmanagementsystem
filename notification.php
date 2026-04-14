<?php
include("db_connect.php");

// FIXED: Updated the base path to remove the /backup/ folder
$base_url = "/PWD/";

// 1. HANDLE MARK AS READ
$notif_id = isset($_POST['id']) ? $_POST['id'] : (isset($_POST['mark_read_id']) ? $_POST['mark_read_id'] : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $notif_id !== null) {
    $id = intval($notif_id);
    $stmt = $conn->prepare("UPDATE notifications SET read_by_admin = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    header('Content-Type: application/json');
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
    exit; 
}

// 2. FETCH NOTIFICATIONS
$sql = "SELECT n.* FROM notifications n 
        WHERE n.user_type = 'all' 
        AND n.barangay_id IS NOT NULL 
        AND n.message NOT LIKE '%MSWDO posted%'
        AND n.message NOT LIKE '%Community Service%'
        ORDER BY n.created_at DESC LIMIT 20";

$result = $conn->query($sql);
$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['status'] = ($row['read_by_admin'] == 1) ? 'read' : 'unread';
        
        // --- CLEAN UP MESSAGE ---
        $msg = $row['message'];
        $msg = str_ireplace("System: ", "", $msg);
        $row['message'] = htmlspecialchars_decode($msg);
        
        /**
         * REDIRECTION MAPPING
         * The '?' allows us to send the admin to specific TABS while keeping the 'PWD' slug
         * for the sidebar's active link logic.
         */
        if (stripos($msg, 'Registration Finalized') !== false) {
            $row['redirect_link'] = $base_url . "official_list";
        } 
        else if (
            stripos($msg, 'Medical Assessment completed') !== false || 
            stripos($msg, 'Waiting for Approval') !== false ||
            stripos($msg, 'Approval') !== false
        ) {
            $row['redirect_link'] = $base_url . "PWD?tab=approval";
        }
        else if (stripos($msg, 'Screening') !== false) {
            $row['redirect_link'] = $base_url . "PWD?tab=screening";
        } 
        else if (
            stripos($msg, 'New Application') !== false || 
            stripos($msg, 'Barangay Application') !== false || 
            stripos($msg, 'added new PWD') !== false || 
            stripos($msg, 'Pending') !== false
        ) {
            $row['redirect_link'] = $base_url . "PWD?tab=pending";
        } 
        else {
            $row['redirect_link'] = $base_url . "PWD";
        }

        $row['time_ago'] = date("M d, h:i A", strtotime($row['created_at']));
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>