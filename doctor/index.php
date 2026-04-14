<?php
session_start();
include("../db_connect.php");

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

// Default page for doctor - Updated for Clean URL support
$page = isset($_GET['page']) ? $_GET['page'] : 'doctor_dashboard'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWD System - Medical Portal</title>
    
    <link rel="stylesheet" href="/PWD/style.css">
    <script src="/PWD/script.js" defer></script>
</head>
<body>

    <?php include("includes/doctor_sidebar.php"); ?> 

    <div class="content">
        <?php
        // Check if the file exists before including
        if (file_exists("$page.php")) {
            include "$page.php";
        } else {
            echo "<h2>404 - Medical Page Not Found</h2>";
        }
        ?>
    </div>

</body>
</html>