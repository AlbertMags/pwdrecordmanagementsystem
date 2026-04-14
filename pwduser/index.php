<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../db_connect.php");

// Security Check: Only allow 'pwd' role
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'pwd') {
    header("Location: ../login.php");
    exit();
}

// Default page for PWD
$page = isset($_GET['page']) ? $_GET['page'] : 'pwd_dashboard'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWD Portal - Member Access</title>
    
    <link rel="stylesheet" href="../style.css">
    <script src="../script.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include("includes/pwd_sidebar.php"); ?> 

    <div class="content">
        <?php
        // Look for the specific content page inside the pwduser folder
        if (file_exists("$page.php")) {
            include "$page.php";
        } else {
            echo "<h2>404 - Portal Page Not Found</h2>";
        }
        ?>
    </div>

</body>
</html>