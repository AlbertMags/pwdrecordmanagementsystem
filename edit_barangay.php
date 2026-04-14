<?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = $_POST['brgy_name'];
    $captain = $_POST['brgy_captain'];
    $contact = $_POST['contact'];

    $stmt = $conn->prepare("UPDATE barangay SET brgy_name=?, brgy_captain=?, contact=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $captain, $contact, $id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>


