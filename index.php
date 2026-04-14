<?php
session_start();

// 1. GLOBAL LOGIN CHECK
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// 2. TRAFFIC CONTROL
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'barangay_admin') {
        header("Location: brgy_data/index.php"); 
        exit();
    } elseif ($_SESSION['role'] === 'doctor') {
        header("Location: doctor/index.php");
        exit();
    } elseif ($_SESSION['role'] === 'pwd') {
        header("Location: pwd/index.php");
        exit();
    }
}

// 3. PAGE LOADING LOGIC
$page = isset($_GET['page']) ? $_GET['page'] : 'information_hub';

// Safeguard: if page is empty or just the root, go to hub
if (empty($page) || $page == 'index.php') {
    $page = 'information_hub';
}

// FIXED: Updated to match your new folder location
$base_path = "/PWD/"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWD Management System | Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>style.css">
    
    <script src="<?php echo $base_path; ?>script.js" defer></script>
    <style>
        body { display: flex; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        .content { flex-grow: 1; padding: 20px; background: #f4f7f6; min-height: 100vh; }
    </style>
</head>
<body>
    
    <?php include("includes/sidebar.php"); ?> 

    <div class="content">
        <?php
        $file_to_load = "$page.php";
        
        if (file_exists($file_to_load)) {
            include $file_to_load;
        } else {
            echo "<div style='padding:20px; background:white; border-radius:8px;'>
                    <h2><i class='fas fa-exclamation-triangle'></i> 404 - Page Not Found</h2>
                    <p>The page you are looking for ('".htmlspecialchars($page)."') does not exist.</p>
                    <a href='information_hub'>Return to Dashboard</a>
                  </div>";
        }
        ?>
    </div>

</body>
</html>