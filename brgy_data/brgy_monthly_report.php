<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../db_connect.php");

// 1. Security: Get the specific Barangay ID for this user
if (!isset($_SESSION['barangay_id'])) {
    echo "Access Denied. Please log in as a Barangay User.";
    exit();
}
$brgy_id = $_SESSION['barangay_id'];

$month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
$year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
$monthName = date("F", mktime(0, 0, 0, $month, 10));

// Fetch the Barangay Name for the header
$brgy_stmt = $conn->prepare("SELECT brgy_name FROM barangay WHERE id = ?");
$brgy_stmt->bind_param("i", $brgy_id);
$brgy_stmt->execute();
$brgy_row = $brgy_stmt->get_result()->fetch_assoc();
$display_brgy_name = $brgy_row['brgy_name'] ?? "Unknown Barangay";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWD Monthly Report - <?= htmlspecialchars($display_brgy_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- SYSTEM UNIFIED NAVBAR (Imported from Main) --- */
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

        .nav-branding-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

      

        .nav-text-branding { line-height: 1.2; }
        .nav-text-branding h1 {     
            margin: 0; 
            color: #1a3a5f; 
            font-size: 22px; 
            font-weight: 700; 
            line-height: 1.2;
        }
        .nav-text-branding p { margin: 0; font-size: 16px;  color: #4b4848; font-weight: normal; }

        
      
        /* --- DASHBOARD VIEW STYLING --- */
        body { background-color: #f4f7f6; margin: 0; padding-top: 100px; }

        #report-wrapper { 
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
            color: #111; 
            line-height: 1.5;
            width: 100%;
        }

        .report-container { 
            width: 100%; 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px;
        }

        /* --- STABLE 3-COLUMN HEADER (FOR PRINT) --- */
        .report-header { 
            display: none; 
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px; 
            padding: 10px 0;
            border-bottom: 2px solid #333;
        }
        
        .header-logos { display: flex; align-items: center; gap: 10px; flex: 0 0 180px; }
        .circle-logo { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; }
        .header-text { text-align: center; flex-grow: 1; }
        .header-text h2 { margin: 0; color: #000; font-size: 1.4rem; text-transform: uppercase; white-space: nowrap; }
        .header-text p { font-size: 1.1rem; font-weight: bold; margin-top: 5px; color: #000; }
        .header-spacer { flex: 0 0 180px; }

        .report-section { 
            background: #ffffff; 
            padding: 20px; 
            border-radius: 4px; 
            margin-bottom: 25px; 
            box-shadow: 0 1px 5px rgba(0,0,0,0.1);
            border: 1px solid #ccc;
        }

        h3.section-title { 
            font-size: 1.15rem; 
            color: #1a2a6c; 
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #1a2a6c;
            text-transform: uppercase;
        }

        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th { background-color: #f2f2f2; color: #000; font-weight: 800; text-transform: uppercase; font-size: 0.85rem; padding: 12px; border: 1px solid #ddd; text-align: left; }
        .report-table td { padding: 10px 12px; border: 1px solid #ddd; font-size: 0.9rem; color: #000 !important; vertical-align: middle; text-align: left; }

        .date-cell { white-space: nowrap; width: 1%; }

        /* --- STATUS BADGES --- */
        .status-badge { padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 0.7rem; text-transform: uppercase; display: inline-block; border: 1px solid rgba(0,0,0,0.1); }
        .official { background: #d4edda; color: #155724; }
        .screening { background: #fff3cd; color: #856404; }
        .pending { background: #e2e3e5; color: #ac5e48; }
        .forapproval { background: #cce5ff; color: #004085; }

        .no-print-controls { 
            display: flex; gap: 15px; margin-bottom: 20px; 
            background: #f8f9fa; padding: 20px; border-radius: 4px; border: 1px solid #ddd;
        }
        .btn-gen { background: #da7d0a;color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: 700; border-radius: 4px; }
        .btn-print { background: #0056b3; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: 700; border-radius: 4px; }
        .btn-export { background: #28a745; color: white; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; display: inline-block; }

        /* --- SIGNATURE SECTION --- */
        .signature-block { margin-top: 50px; width: 100%; display: flex; justify-content: space-between; }
        .sig-item { width: 45%; text-align: center; }
        .sig-line { border-top: 2px solid #000; margin-top: 45px; padding-top: 5px; font-weight: bold; text-transform: uppercase; color: #000; }
        .sig-title { font-size: 0.85rem; color: #333; font-style: italic; }
        .timestamp { font-size: 0.75rem; color: #666; margin-top: 40px; border-top: 1px dashed #ccc; padding-top: 10px; }

        /* --- ULTRA STABLE PRINT STYLING --- */
               /* --- ULTRA STABLE PRINT STYLING --- */
        @media print {
            /* Removes Browser Header (Date/Title) and Footer (URL) */
            @page { 
                margin: 0; 
            }
            
            body { 
                margin: 0 !important;
                padding: 1.5cm !important; /* This creates the margin inside the page since @page margin is 0 */
                width: 100% !important;
            }

            /* Completely remove sidebar and system UI elements */
            nav, .sidebar, .main-sidebar, .topbar, .no-print, .no-print-controls, .main-header, aside { 
                display: none !important; 
            }

            /* Force content to use 100% of the paper width */
            #report-wrapper, .wrapper, .content-wrapper, .main-panel, .content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                position: static !important;
            }

            .report-container {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .report-header { 
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                border-bottom: 2px solid #000 !important; 
                margin-bottom: 20px !important;
            }

            .header-logos, .header-spacer {
                flex: 0 0 180px !important;
            }

            .header-text h2 {
                font-size: 1.2rem !important;
                white-space: nowrap !important;
            }

            .circle-logo { 
                width: 70px !important; 
                height: 70px !important; 
                -webkit-print-color-adjust: exact; 
            }

            .report-section { 
                box-shadow: none !important; 
                border: 1px solid #000 !important; 
                margin-bottom: 15px !important;
                padding: 10px !important;
                page-break-inside: avoid;
            }

            .report-table th, .report-table td { 
                border: 1px solid #000 !important;
            }
            
            #print-footer { display: block !important; margin-top: 40px; }
        }
    </style>
</head>
<body>

<nav class="top-nav no-print">
    <div class="nav-branding-wrapper">
       
        <div class="nav-text-branding">
            <h1>Barangay <?= htmlspecialchars($display_brgy_name) ?></h1>
            <p>Monthly PWD Statistical Report: <?= $monthName ?> <?= $year ?></p>
        </div>
    </div>
</nav>

<div id="report-wrapper">
    <div class="report-container">
        
        <div class="report-header">
            <div class="header-logos">
                <img src="../uploads/logo.jpg" class="circle-logo" alt="Logo">
                <img src="../uploads/mswdo.jpg" class="circle-logo" alt="PWD">
            </div>
            <div class="header-text">
                <h2>Municipality of EB Magalona</h2>
                <h2>Barangay <?= htmlspecialchars($display_brgy_name) ?></h2>
                <p>Monthly PWD Statistical Report: <?= $monthName ?> <?= $year ?></p>
            </div>
            <div class="header-spacer"></div>
        </div>

        <div class="no-print-controls no-print">
            <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                <select name="month" style="padding: 8px; border-radius: 4px;">
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $selected = ($m == $month) ? "selected" : "";
                        echo "<option value='$m' $selected>" . date("F", mktime(0, 0, 0, $m, 10)) . "</option>";
                    }
                    ?>
                </select>
                <input type="number" name="year" value="<?= $year ?>" min="2000" max="<?= date('Y') ?>" style="padding: 8px; width: 100px; border-radius: 4px;">
                
                <button type="submit" class="btn-gen">Generate</button>
                <button type="button" class="btn-print" onclick="window.print()">Print PDF</button>
                <button type="submit" name="export_csv" formaction="brgy_export.php" class="btn-export">Export CSV</button>
            </form>
        </div>

        <div class="report-section">
            <h3 class="section-title">1. Application Pipeline Summary</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Process Status</th>
                        <th style="text-align: right;">Total Applications</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sqlPipe = "SELECT status, COUNT(*) as total FROM pwd WHERE barangay_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ? GROUP BY status";
                    $st = $conn->prepare($sqlPipe); $st->bind_param("iii", $brgy_id, $year, $month); $st->execute();
                    $res = $st->get_result();
                    if($res->num_rows > 0):
                        while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><span class="status-badge <?= strtolower(str_replace(' ', '', $row['status'])) ?>"><?= $row['status'] ?></span></td>
                                <td style="text-align: right; font-weight: 800;"><?= number_format($row['total']) ?></td>
                            </tr>
                        <?php endwhile;
                    else: echo "<tr><td colspan='2' style='text-align:center;'>No activity recorded for this month.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-section">
            <h3 class="section-title">2. Distribution by Disability Type (Official Only)</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Disability Category</th>
                        <th style="text-align: right;">Official Registrants</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sqlD = "SELECT d.disability_name, COUNT(p.id) AS total 
                             FROM pwd p 
                             JOIN disability_type d ON p.disability_type = d.id 
                             WHERE p.barangay_id = ? AND p.status = 'Official' AND YEAR(p.created_at) = ? AND MONTH(p.created_at) = ? 
                             GROUP BY d.id ORDER BY total DESC";
                    $st = $conn->prepare($sqlD); $st->bind_param("iii", $brgy_id, $year, $month); $st->execute();
                    $res = $st->get_result();
                    if($res->num_rows > 0):
                        while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['disability_name']) ?></td>
                                <td style="text-align: right; font-weight: 800;"><?= number_format($row['total']) ?></td>
                            </tr>
                        <?php endwhile;
                    else: echo "<tr><td colspan='2' style='text-align:center;'>No official records found.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-section">
            <h3 class="section-title">3. Monthly Master List (Official Registrations)</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th class="date-cell">Date Registered</th>
                        <th>Full Name</th>
                        <th>Residential Address</th>
                        <th>Disability Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sqlL = "SELECT p.created_at, p.first_name, p.last_name, p.address, d.disability_name 
                             FROM pwd p 
                             JOIN disability_type d ON p.disability_type = d.id
                             WHERE p.barangay_id = ? AND p.status = 'Official' AND YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?
                             ORDER BY p.created_at DESC";
                    $st = $conn->prepare($sqlL); $st->bind_param("iii", $brgy_id, $year, $month); $st->execute();
                    $res = $st->get_result();
                    if($res->num_rows > 0):
                        while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td class="date-cell"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['last_name'] . ", " . $row['first_name']) ?></td>
                                <td><?= htmlspecialchars($row['address'] ?: 'No address provided') ?></td>
                                <td><?= htmlspecialchars($row['disability_name']) ?></td>
                            </tr>
                        <?php endwhile;
                    else: echo "<tr><td colspan='4' style='text-align:center;'>No detailed records found.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="print-footer" style="display: none;">
            <div class="signature-block">
                <div class="sig-item">
                    <p style="text-align: left; font-weight: bold; margin-bottom: 0;">Prepared by:</p>
                    <div class="sig-line">BARANGAY PWD FOCAL PERSON</div>
                    <div class="sig-title">Signature over Printed Name</div>
                </div>
                <div class="sig-item">
                    <p style="text-align: left; font-weight: bold; margin-bottom: 0;">Attested by:</p>
                    <div class="sig-line">PUNONG BARANGAY</div>
                    <div class="sig-title">Signature over Printed Name</div>
                </div>
            </div>
            <div class="timestamp">
                Certified Report generated by EB Magalona PWD System on <?= date('F d, Y \a\t h:i A') ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>