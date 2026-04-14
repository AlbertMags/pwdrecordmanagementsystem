<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("db_connect.php");

// Fetch barangays and disabilities for the filters
$barangayQuery = "SELECT * FROM barangay ORDER BY brgy_name ASC";
$barangayResult = $conn->query($barangayQuery);

$disabilityQuery = "SELECT * FROM disability_type ORDER BY disability_name ASC";
$disabilityResult = $conn->query($disabilityQuery);

// Fetch ONLY Official PWDs - Sorted Alphabetically by Name
$query = "SELECT pwd.*, barangay.brgy_name, disability_type.disability_name AS disability_name 
          FROM pwd 
          JOIN barangay ON pwd.barangay_id = barangay.id 
          JOIN disability_type ON pwd.disability_type = disability_type.id
          WHERE pwd.status = 'Official'
          ORDER BY last_name ASC, first_name ASC";

$result = $conn->query($query);

// Function to calculate age
function calculateAge($birthDate) {
    if(empty($birthDate)) return "N/A";
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* --- INTEGRATED NAVBAR STYLING --- */
        * { box-sizing: border-box; }
        
        body, html { 
            background-color: #e9ecef; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; padding: 0; width: 100%; height: 100%;
        }

        .top-nav {
            background: #fff; 
            display: flex; 
            justify-content: flex-start; 
            align-items: center;
            padding: 40px 40px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            position: fixed; 
            top: 0; 
            left: 250px; 
            width: calc(100% - 250px); 
            z-index: 1000;
            height: 70px;
        }

        .nav-brand-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-text-stack {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .top-nav h1 { 
            margin: 0; 
            color: #1a3a5f; 
            font-size: 22px; 
            font-weight: 700; 
            line-height: 1.2;
        }

       .nav-sub { 
            font-size: 16px; 
            color: #4b4848; 
            font-weight: normal; 
            margin: 0;
            line-height: 1.2;
        }

        /* --- CONTENT WRAPPER --- */
        .dashboard-wrapper { 
            padding: 100px 25px 25px 25px; 
            width: 100%; 
        }

        .content-card {
            background: #fff; border-radius: 12px; padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%;
        }

        /* --- FILTERS (SINGLE LINE) --- */
        .filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            gap: 20px;
            align-items: center;
            border: 1px solid #eee;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .filters label { 
            font-weight: 600; 
            font-size: 14px; 
            color: #495057; 
        }

        .filters select { 
            padding: 8px; 
            border-radius: 5px; 
            border: 1px solid #ced4da; 
            min-width: 180px;
        }

        /* --- BUTTON STYLES --- */
        .btn-main-print { 
            background-color:  #0056b3; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 5px; 
            cursor: pointer; 
            color: white; 
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .print-btn { background-color:  #0056b3; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; color: white; margin-right: 5px; }
        .delete-btn { background-color: #dc3545; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; color: white; }

        /* --- TABLE STYLES --- */
        .col-left { text-align: left !important; padding-left: 20px; }
        .clickable-name { color: #000000; text-decoration: none; font-weight: bold; cursor: pointer; }
        .clickable-name:hover { text-decoration: underline; color: #0056b3; }

        /* --- MODAL STYLING --- */
        .pwd-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            overflow: hidden;
        }
        .pwd-modal-content {
            position: relative;
            background: #fff;
            width: 95%;
            max-width: 1100px;
            margin: 2vh auto;
            border-radius: 8px;
            height: 96vh;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }
        .close-view {
            position: absolute;
            right: 25px;
            top: 15px;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            color: #333;
            z-index: 10001;
        }
        .no-scroll { overflow: hidden !important; }

        /* --- PRINT LOGIC --- */
        .print-only-header { display: none; }

        @media print {
            @page {
                size: auto;
                margin: 0;
            }

            body {
                margin: 1.5cm;
                background-color: white !important;
                -webkit-print-color-adjust: exact;
            }

            body * { visibility: hidden; }
            
            .filters, .sidebar, .navbar, .btn-main-print, .action-column, .barangay-header, .pwd-modal-backdrop { 
                display: none !important; 
            }

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

            .th, .td { 
                border: 1px solid #000 !important; 
                color: black !important; 
                white-space: nowrap !important;
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
        }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-brand-wrapper">
            <div class="nav-text-stack">
                <h1>Official PWD List</h1>
                <p class="nav-sub">Verified records of registered PWDs in E.B. Magalona.</p>
            </div>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <main class="content-card">
            
            <div class="filters">
                <button class="btn-main-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Official List
                </button>

                <div class="filter-group">
                    <label>Barangay:</label>
                    <select id="barangayFilter">
                        <option value="">All Barangays</option>
                        <?php
                        $barangayResult->data_seek(0);
                        while ($row = $barangayResult->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['brgy_name']); ?></option>
                        <?php } ?>
                    </select>
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
                    <div class="header-flex">
                        <img src="uploads/mswdo.jpg" class="header-logo" alt="MSWDO">
                        <div class="header-text">
                            <h1 style="font-size: 22px; margin: 0;">Municipality of E. B. Magalona</h1>
                            <p style="margin: 5px 0;">Municipal Social Welfare Development Office</p>
                            <h2 style="font-size: 18px; margin: 0; text-transform: uppercase;">Official PWD List</h2>
                        </div>
                        <img src="uploads/ebmag.jpg" class="header-logo" alt="EB Magalona">
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th class="col-left">Name</th>
                            <th style="width: 80px;">Age</th>
                            <th>Barangay</th>
                            <th class="col-left">Disability</th>
                            <th class="action-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="officialTableBody">
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr data-barangay="<?php echo $row['barangay_id']; ?>" 
                            data-disability="<?php echo $row['disability_type']; ?>">
                            
                            <td class="row-number"></td>
                            <td class="col-left name-cell">
                                <span class="clickable-name" data-id="<?= $row['id']; ?>">
                                    <?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?>
                                </span>
                            </td>
                            <td><?php echo calculateAge($row['birth_date']); ?></td>
                            <td class="brgy-cell"><?php echo htmlspecialchars($row['brgy_name']); ?></td>
                            <td class="col-left disability-cell"><?php echo htmlspecialchars($row['disability_name']); ?></td>
                            <td class="action-column">
                                <button class="print-btn" onclick="printID(<?php echo $row['id']; ?>)">Print ID</button>
                                <button class="delete-btn" data-id="<?php echo $row['id']; ?>">Delete</button>
                            </td>
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
        // Handle Viewing Profile
        $(document).on("click", ".clickable-name", function() {
            var id = $(this).data("id");
            openModal("view_pwd.php?id=" + id);
        });

        // Main function to open modal
        window.openModal = function(url) {
            $("#viewFrame").attr("src", url);
            $(".pwd-modal-backdrop").fadeIn();
            $("body").addClass("no-scroll");
        }

        function closeModal() {
            $(".pwd-modal-backdrop").fadeOut();
            $("body").removeClass("no-scroll");
            setTimeout(function() {
                $("#viewFrame").attr("src", "");
            }, 300);
        }

        $(".close-view").click(function() {
            closeModal();
        });

        $(document).keyup(function(e) {
            if (e.key === "Escape") closeModal();
        });

        // Filtering Logic
        $("#barangayFilter, #disabilityFilter").on("change", function() {
            let b = $("#barangayFilter").val();
            let d = $("#disabilityFilter").val();

            $("#officialTableBody tr").each(function() {
                let rb = $(this).attr("data-barangay");
                let rd = $(this).attr("data-disability");

                if ((b === "" || rb == b) && (d === "" || rd == d)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            renumber();
        });

        function renumber() {
            $("#officialTableBody tr:visible").each(function(i) {
                $(this).find(".row-number").text(i + 1);
            });
        }

        // Delete Logic
        $(document).on("click", ".delete-btn", function () {
            let id = $(this).data("id");
            if(confirm("Permanently remove this PWD from the Official List?")) {
                $.post("delete_pwd.php", {delete_id:id}, function(res){
                    if(res.trim()==="success") location.reload();
                    else alert("Error deleting record.");
                });
            }
        });

        renumber();
    });

    // UPDATED: Now opens inside the modal instead of a new tab
    function printID(id) {
        openModal('print_id.php?id=' + id);
    }
    </script>
</body>
</html>