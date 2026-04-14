<?php

include("db_connect.php");

/* =========================
ADD ANNOUNCEMENT
========================= */
if (isset($_POST['post_announcement'])) {
    $title   = $_POST['title'];
    $message = $_POST['message'];
    $origin  = $_POST['origin'] ?? 'information_hub';
    $image   = null;

    if (!empty($_FILES['image']['name'])) {
        $dir = "uploads/announcements/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $image = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $dir . $image);
    }

    $stmt = $conn->prepare("INSERT INTO announcements (title, message, image) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $message, $image);
    
    if($stmt->execute()) {
        $notif_msg = "MSWDO added new announcement: " . $title;

        // 1. Notify all Barangays 
        $brgy_res = $conn->query("SELECT u.barangay_id 
                                  FROM myusers u 
                                  INNER JOIN barangay b ON u.barangay_id = b.id 
                                  WHERE u.role = 'barangay_admin'");
                                  
        $notif_stmt = $conn->prepare("INSERT INTO notifications (barangay_id, message, user_type, status) VALUES (?, ?, 'barangay', 'unread')");
        
        while ($row = $brgy_res->fetch_assoc()) {
            $notif_stmt->bind_param("is", $row['barangay_id'], $notif_msg);
            $notif_stmt->execute();
        }

        // 2. Notify all PWDs
        $pwd_res = $conn->query("SELECT related_pwd_id FROM myusers WHERE role = 'pwd' AND related_pwd_id IS NOT NULL");
        $pwd_notif = $conn->prepare("INSERT INTO notifications (pwd_id, message, user_type, status) VALUES (?, ?, 'pwd', 'unread')");
        
        while ($row = $pwd_res->fetch_assoc()) {
            $pwd_notif->bind_param("is", $row['related_pwd_id'], $notif_msg);
            $pwd_notif->execute();
        }
    }

    // --- CLEAN URL REDIRECT ---
    $_SESSION['active_tab'] = 'announcements'; 
    // We remove index.php?page= and just use the origin (e.g., support_center)
    header("Location: " . $origin); 
    exit;
}


/* =========================
UPDATE ANNOUNCEMENT
========================= */
if (isset($_POST['update_announcement'])) {
    $id      = intval($_POST['id']);
    $title   = $_POST['title'];
    $message = $_POST['message'];
    $origin  = $_POST['origin'] ?? 'information_hub';

    if (!empty($_FILES['image']['name'])) {
        $dir = "uploads/announcements/";
        $oldImg_res = $conn->query("SELECT image FROM announcements WHERE id = $id");
        if($oldImg_res) {
            $oldImg = $oldImg_res->fetch_assoc();
            if(!empty($oldImg['image'])) @unlink($dir . $oldImg['image']);
        }

        $image = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $dir . $image);
        
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, message = ?, image = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $message, $image, $id);
    } else {
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, message = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $message, $id);
    }
    
    $stmt->execute();

    // --- CLEAN URL REDIRECT ---
    $_SESSION['active_tab'] = 'announcements';
    header("Location: " . $origin);
    exit;
}

/* =========================
DELETE ANNOUNCEMENT
========================= */
if (isset($_GET['delete_announcement'])) {
    $id = intval($_GET['delete_announcement']);
    $origin = $_GET['origin'] ?? 'information_hub';

    $imgQuery = $conn->query("SELECT image FROM announcements WHERE id = $id");
    if ($imgRow = $imgQuery->fetch_assoc()) {
        if (!empty($imgRow['image'])) @unlink("uploads/announcements/" . $imgRow['image']);
    }

    $conn->query("DELETE FROM announcements WHERE id = $id");

    // --- CLEAN URL REDIRECT ---
    $_SESSION['active_tab'] = 'announcements';
    header("Location: " . $origin);
    exit;
}
?>