<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("db_connect.php");

// 1. ADD NEWS ENTRY (With Notifications)
if (isset($_POST['add_news'])) {
    $title      = $_POST['title'];
    $content    = $_POST['content'];
    $event_date = $_POST['event_date'];
    
    $image_name = "";
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . '_' . $_FILES['image']['name'];
        $target = "uploads/news/" . $image_name;
        
        if (!is_dir('uploads/news')) {
            mkdir('uploads/news', 0777, true);
        }
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }

    $stmt = $conn->prepare("INSERT INTO news (title, content, event_date, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $content, $event_date, $image_name);
    
    if($stmt->execute()){
        $notif_msg = "MSWDO added new news: " . $title;

        // 1. Notify all Barangays
        $brgy_res = $conn->query("SELECT u.barangay_id FROM myusers u INNER JOIN barangay b ON u.barangay_id = b.id WHERE u.role = 'barangay_admin'");
        $brgy_notif = $conn->prepare("INSERT INTO notifications (barangay_id, message, user_type, status) VALUES (?, ?, 'barangay', 'unread')");
        
        while ($row = $brgy_res->fetch_assoc()) {
            $brgy_notif->bind_param("is", $row['barangay_id'], $notif_msg);
            $brgy_notif->execute();
        }

        // 2. Notify all PWDs
        $pwd_res = $conn->query("SELECT related_pwd_id FROM myusers WHERE role = 'pwd' AND related_pwd_id IS NOT NULL");
        $pwd_notif = $conn->prepare("INSERT INTO notifications (pwd_id, message, user_type, status) VALUES (?, ?, 'pwd', 'unread')");
        
        while ($row = $pwd_res->fetch_assoc()) {
            $pwd_notif->bind_param("is", $row['related_pwd_id'], $notif_msg);
            $pwd_notif->execute();
        }

        // --- CLEAN URL REDIRECT ---
        $_SESSION['active_tab'] = 'news';
        header("Location: support_center"); 
    } else {
        echo "Error: " . $conn->error;
    }
    exit;
}

// 2. UPDATE NEWS ENTRY
if (isset($_POST['update_news'])) {
    $id         = intval($_POST['id']);
    $title      = $_POST['title'];
    $content    = $_POST['content'];
    $event_date = $_POST['event_date'];

    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . '_' . $_FILES['image']['name'];
        $target = "uploads/news/" . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
        
        $stmt = $conn->prepare("UPDATE news SET title=?, content=?, event_date=?, image=? WHERE id=?");
        $stmt->bind_param("ssssi", $title, $content, $event_date, $image_name, $id);
    } else {
        $stmt = $conn->prepare("UPDATE news SET title=?, content=?, event_date=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $content, $event_date, $id);
    }

    if($stmt->execute()){
        // --- CLEAN URL REDIRECT ---
        $_SESSION['active_tab'] = 'news';
        header("Location: support_center");
    } else {
        echo "Error: " . $conn->error;
    }
    exit;
}

// 3. DELETE NEWS ENTRY
if (isset($_GET['delete_news'])) {
    $id = intval($_GET['delete_news']);

    $res = $conn->query("SELECT image FROM news WHERE id=$id");
    if($row = $res->fetch_assoc()){
        if (!empty($row['image']) && file_exists("uploads/news/" . $row['image'])) {
            @unlink("uploads/news/" . $row['image']);
        }
    }

    $conn->query("DELETE FROM news WHERE id = $id");
    
    // --- CLEAN URL REDIRECT ---
    $_SESSION['active_tab'] = 'news';
    header("Location: support_center");
    exit;
}
?>