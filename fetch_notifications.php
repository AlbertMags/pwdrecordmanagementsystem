<?php
include("db_connect.php");

$notifQuery = "SELECT id, message, created_at FROM notifications ORDER BY created_at DESC LIMIT 10";
$notifResult = $conn->query($notifQuery);

$notifications = [];
if ($notifResult->num_rows > 0) {
    while ($notif = $notifResult->fetch_assoc()) {
        $notifications[] = [
            "id" => $notif['id'],
            "message" => $notif['message'],
            "created_at" => $notif['created_at']
        ];
    }
}

echo json_encode($notifications);
?>
