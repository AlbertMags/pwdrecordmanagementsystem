<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../db_connect.php");

if (!isset($_SESSION['barangay_id'])) {
    echo "Access Denied. Please log in.";
    exit();
}

$my_barangay_id = $_SESSION['barangay_id'];

// Fetch Barangay Name
$brgy_query = "SELECT brgy_name FROM barangay WHERE id = ?";
$brgy_stmt = $conn->prepare($brgy_query);
$brgy_stmt->bind_param("i", $my_barangay_id);
$brgy_stmt->execute();
$brgy_res = $brgy_stmt->get_result();
$brgy_row = $brgy_res->fetch_assoc();
$display_brgy_name = $brgy_row['brgy_name'] ?? 'Unknown Barangay';

// Fetch disabilities for the filter
$disabilityQuery = "SELECT * FROM disability_type ORDER BY disability_name ASC";
$disabilityResult = $conn->query($disabilityQuery);

// Fetch Official PWDs
$query = "SELECT pwd.*, disability_type.disability_name 
          FROM pwd 
          JOIN disability_type ON pwd.disability_type = disability_type.id
          WHERE pwd.status = 'Official' AND pwd.barangay_id = ?
          ORDER BY pwd.last_name ASC, pwd.first_name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $my_barangay_id);
$stmt->execute();
$result = $stmt->get_result();

function calculateAge($birthDate) {
    if(empty($birthDate)) return "0";
    $today = new DateTime();
    $diff = $today->diff(new DateTime($birthDate));
    return $diff->y;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official PWD List</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* --- INTEGRATED NAVBAR STYLING --- */
        body, html { 
            background-color: #e9ecef; 
            margin: 0; padding: 0; 
        }

        .top-nav {
            background: #fff; display: flex; justify-content: flex-start; align-items: center;
            padding: 40px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            position: fixed; top: 0; left: 250px; width: calc(100% - 250px); 
            z-index: 1000; height: 70px;
        }

        .nav-brand-wrapper { display: flex; align-items: center; gap: 15px; }
     
        .nav-text-stack { display: flex; flex-direction: column; justify-content: center; }
        .top-nav h1 { margin: 0; color: #1a3a5f; font-size: 22px; font-weight: 700; line-height: 1.2; }
      
        .nav-sub { 
            font-size: 16px; 
            color: #4b4848; 
            font-weight: normal; 
            margin: 0;
            line-height: 1.2;
        }
        /* --- DASHBOARD WRAPPER --- */
        .dashboard-wrapper { padding: 100px 25px 25px 25px; width: 100%; }
        .content-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }

        /* --- FILTERS (Single Line) --- */
        .filters {
            background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;
            display: flex; gap: 20px; align-items: center; border: 1px solid #eee;
        }
        .filter-group { display: flex; align-items: center; gap: 10px; }
        .filters select, .filters input { padding: 8px; border-radius: 5px; border: 1px solid #ced4da; }

        /* --- TABLE STYLES --- */
       
        .col-left { text-align: left !important; padding-left: 20px; }
        .clickable-name { color: #000000; text-decoration: none; font-weight: bold; cursor: pointer; }
        .clickable-name:hover { text-decoration: underline; color: #0056b3; }

        /* --- MODAL STYLES --- */
        .pwd-modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; }
        .pwd-modal-content { position: relative; background: #fff; width: 95%; max-width: 1100px; margin: 2vh auto; border-radius: 8px; height: 96vh; box-shadow: 0 0 30px rgba(0,0,0,0.5); overflow: hidden; }
        .close-view { position: absolute; right: 25px; top: 15px; font-size: 40px; font-weight: bold; cursor: pointer; color: #333; z-index: 10001; }
        .no-scroll { overflow: hidden !important; }

        /* --- PRINT LOGIC --- */
        .print-only-header { display: none; }
      @media print {
            @page {
                size: auto;
                margin: 0; /* Removes URL/Footer/Header */
            }

            body {
                margin: 1.5cm; /* Padding for the paper content */
                background-color: white !important;
                -webkit-print-color-adjust: exact;
            }

            body * { visibility: hidden; }
            
            /* Elements to hide */
            .filters, .sidebar, .navbar, .btn-main-print, .action-column, .barangay-header, .pwd-modal-backdrop { 
                display: none !important; 
            }

            /* Elements to show */
            .table-container, .table-container table, .table-container table *, .print-only-header, .print-only-header * { 
                visibility: visible !important; 
            }

            .table-container { 
                position: absolute; 
                left: 0; 
                top: 20px; 
                width: 90%; 
                padding-left: 90px;
            }

            /* Forces names to stay on one line */
            .th{

            border: 1px solid #000 !important; 
                color: white !important; 
                white-space: nowrap !important; /* NO WRAPPING */
                font-size: 11pt !important;
                padding: 10px 10px !important;
            }
            .td { 
                border: 1px solid #000 !important; 
                color: black !important; 
                white-space: nowrap !important; /* NO WRAPPING */
                font-size: 11pt !important;
                padding: 10px 10px !important;
            }

            .print-only-header { 
                display: block !important; 
                text-align: center; 
                margin-bottom: 25px; 
                border-bottom: 2px solid #000; 
                padding-bottom: 15px; 
            }

            .header-flex { 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                gap: 30px; 
            }

            .header-logo { width: 80px; height: 80px; }
        }  </style>
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-brand-wrapper">
           
            <div class="nav-text-stack">
                <h1>Official PWD List</h1>
                <p class="nav-sub">Verified records for Barangay <?php echo htmlspecialchars($display_brgy_name); ?></p>
            </div>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <main class="content-card">
            
            <div class="filters">
                <button class="btn-print" onclick="window.print()" style="background: #0056b3; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; font-weight:bold;">
                    <i class="fas fa-print"></i> Print Official List
                </button>

                <div class="filter-group">
                    <label>Search:</label>
                    <input type="text" id="nameSearch" placeholder="Find PWD...">
                </div>

                <div class="filter-group">
                    <label>Disability:</label>
                    <select id="disabilityFilter">
                        <option value="">All Types</option>
                        <?php 
                        $disabilityResult->data_seek(0);
                        while ($row = $disabilityResult->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>" . htmlspecialchars($row['disability_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <div class="print-only-header">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 40px;">
                        <img src="../uploads/mswdo.jpg" width="85" alt="MSWDO">
                        <div style="text-align: center;">
                            <h1 style="font-size: 22px; margin: 0;">Municipality of E. B. Magalona</h1>
                            <p style="margin: 5px 0;">Municipal Social Welfare Development Office</p>
                            <h2 style="font-size: 18px; margin: 0; text-transform: uppercase;">Official PWD List - <?php echo htmlspecialchars($display_brgy_name); ?></h2>
                        </div>
                        <img src="../uploads/ebmag.jpg" width="85" alt="EB Magalona">
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th class="col-left">Name</th>
                            <th style="width: 80px;">Age</th>
                            <th class="col-left">Disability</th>
                        </tr>
                    </thead>
                    <tbody id="officialTableBody">
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr data-disability="<?php echo $row['disability_type']; ?>">
                            <td class="row-number"></td>
                            <td class="col-left name-cell">
                                <span class="clickable-name" data-id="<?= $row['id']; ?>">
                                    <?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?>
                                </span>
                            </td>
                            <td><?php echo calculateAge($row['birth_date']); ?></td>
                            <td class="col-left disability-cell"><?php echo htmlspecialchars($row['disability_name']); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="pwd-modal-backdrop">
        <div class="pwd-modal-content">
            <span class="close-view">&times;</span>
            <iframe id="viewFrame" src="" style="width: 100%; height: 100%; border: none; display: block;"></iframe>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        // Modal Logic
        $(document).on("click", ".clickable-name", function() {
            var id = $(this).data("id");
            $("#viewFrame").attr("src", "../view_pwd.php?id=" + id);
            $(".pwd-modal-backdrop").fadeIn();
            $("body").addClass("no-scroll");
        });

        function closeModal() {
            $(".pwd-modal-backdrop").fadeOut();
            $("body").removeClass("no-scroll");
            setTimeout(function() { $("#viewFrame").attr("src", ""); }, 300);
        }

        $(".close-view, .pwd-modal-backdrop").click(function(e) {
            if (e.target !== this) return;
            closeModal();
        });

        // Filtering Logic
        function applyFilters() {
            let d = $("#disabilityFilter").val();
            let s = $("#nameSearch").val().toLowerCase();

            $("#officialTableBody tr").each(function() {
                let rd = $(this).attr("data-disability");
                let rt = $(this).find(".name-cell").text().toLowerCase();
                let matchD = (d === "" || rd == d);
                let matchS = (rt.indexOf(s) > -1);
                $(this).toggle(matchD && matchS);
            });
            renumber();
        }

        $("#disabilityFilter, #nameSearch").on("change keyup", applyFilters);

        function renumber() {
            $("#officialTableBody tr:visible").each(function(i) {
                $(this).find(".row-number").text(i + 1);
            });
        }
        renumber();
    });
    </script>
</body>
</html>