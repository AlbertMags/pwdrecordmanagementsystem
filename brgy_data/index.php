<?php
include("session_check.php"); 
include("../db_connect.php"); 

// Get the page from the URL (set by .htaccess)
$page = isset($_GET['page']) ? $_GET['page'] : 'brgy_dashboard'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWD System - Barangay Portal</title>
    
    <link rel="stylesheet" href="/PWD/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script src="/PWD/script.js" defer></script>
</head>
<body>

    <?php 
    // This includes your sidebar (now with the fixed logo path)
    include("includes/brgy_sidebar.php"); 
    ?> 

    <div class="content">
        <?php
        // 1. Check for the specific search result page first
        if ($page === 'brgy_search_results') {
            if (file_exists("brgy_search_results.php")) {
                include "brgy_search_results.php";
            } else {
                echo "Search file missing: brgy_search_results.php";
            }
        } 
        // 2. Otherwise, check for the general page
        elseif (file_exists("$page.php")) {
            include "$page.php";
        } 
        // 3. Handle the view profile specifically if needed
        elseif ($page === 'view_search_pwd' && file_exists("brgy_view_search.php")) {
            include "brgy_view_search.php";
        }
        else {
            echo "<div style='padding:50px; text-align:center;'>
                    <h2>404 - Page Not Found</h2>
                    <p>The page <b>$page</b> could not be loaded.</p>
                  </div>";
        }
        ?>
    </div>

</body>
</html>