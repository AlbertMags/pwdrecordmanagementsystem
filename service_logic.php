<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("db_connect.php");

// ADD SERVICE
if (isset($_POST['add_service'])) {
    $title       = $_POST['title'];
    $category    = $_POST['category'];
    $status      = $_POST['status'];
    $provider    = $_POST['provider'];
    $description = $_POST['description'];
    $location    = $_POST['location'];
    $contact     = $_POST['contact'];
    $schedule    = $_POST['schedule'];

    $stmt = $conn->prepare("INSERT INTO services (title, category, status, provider, description, location, contact, schedule) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $title, $category, $status, $provider, $description, $location, $contact, $schedule);
    
    if($stmt->execute()){
        $notif_msg = "MSWDO added new services: " . $title;

        $brgy_res = $conn->query("SELECT u.barangay_id FROM myusers u INNER JOIN barangay b ON u.barangay_id = b.id WHERE u.role = 'barangay_admin'");
        $brgy_notif = $conn->prepare("INSERT INTO notifications (barangay_id, message, user_type, status) VALUES (?, ?, 'barangay', 'unread')");
        while ($row = $brgy_res->fetch_assoc()) {
            $brgy_notif->bind_param("is", $row['barangay_id'], $notif_msg);
            $brgy_notif->execute();
        }

        $pwd_res = $conn->query("SELECT related_pwd_id FROM myusers WHERE role = 'pwd' AND related_pwd_id IS NOT NULL");
        $pwd_notif = $conn->prepare("INSERT INTO notifications (pwd_id, message, user_type, status) VALUES (?, ?, 'pwd', 'unread')");
        while ($row = $pwd_res->fetch_assoc()) {
            $pwd_notif->bind_param("is", $row['related_pwd_id'], $notif_msg);
            $pwd_notif->execute();
        }

        // --- CLEAN URL REDIRECT ---
        $_SESSION['active_tab'] = 'services';
        header("Location: support_center");
    } else {
        echo "Error: " . $conn->error;
    }
    exit;
}

// UPDATE SERVICE
if (isset($_POST['update_service'])) {
    $id          = intval($_POST['id']);
    $title       = $_POST['title'];
    $category    = $_POST['category'];
    $status      = $_POST['status'];
    $provider    = $_POST['provider'];
    $description = $_POST['description'];
    $location    = $_POST['location'];
    $contact     = $_POST['contact'];
    $schedule    = $_POST['schedule'];

    $stmt = $conn->prepare("UPDATE services SET title=?, category=?, status=?, provider=?, description=?, location=?, contact=?, schedule=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $title, $category, $status, $provider, $description, $location, $contact, $schedule, $id);
    
    if($stmt->execute()){
        // --- CLEAN URL REDIRECT ---
        $_SESSION['active_tab'] = 'services';
        header("Location: support_center");
    } else {
        echo "Error: " . $conn->error;
    }
    exit;
}

// DELETE SERVICE
if (isset($_GET['delete_service'])) {
    $id = intval($_GET['delete_service']);
    $conn->query("DELETE FROM services WHERE id = $id");
    
    // --- CLEAN URL REDIRECT ---
    $_SESSION['active_tab'] = 'services';
    header("Location: support_center");
    exit;
}
?>