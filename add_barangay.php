<?php
require 'db_connect.php';


if($_SERVER["REQUEST_METHOD"]== "POST") {

    $name = trim($_POST['brgy_name']);
    $captain = trim($_POST['brgy_captain']);
    $contact = trim ($_POST['contact']);


    $check = $conn->prepare("SELECT id FROm barangay WHERE
     brgy_name
    =?");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0) {
        echo "duplicate";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO barangay (brgy_name, brgy_captain, contact) VALUES (?,?,?)");
    $stmt->bind_param ("sss", $name, $captain, $contact);

    if ($stmt->execute()){
        echo "success";

    } else {
        echo "error";
    }

}

?>


