<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Path to look one folder up for the database connection
include("../db_connect.php");

$month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
$year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
$monthName = date("F", mktime(0, 0, 0, $month, 10));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWD Report</title>
    <style>
        #report-wrapper { 
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
            color: #111; 
            line-height: 1.5;
            width: 100%;
            background-color: #fff;
        }

        .report-container { 
            width: 100%; 
            max-width: 100%; 
            margin: 0; 
            padding: 15px; 
        }

        /* --- COMPACT FILTER BAR --- */
        .filter-controls { 
            display: flex; 
            margin-bottom: 25px; 
            background: #f8f9fa; 
            padding: 15px 20px; 
            border-radius: 6px;
            border: 1px solid #dee2e6;
            align-items: center;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        }

        .btn-gen { 
            background: #2c3e50; 
            color: white; 
            border: none; 
            padding: 8px 20px; 
            cursor: pointer; 
            font-weight: 600;
            border-radius: 4px;
            transition: background 0.2s;
            margin-left: 10px;
        }
        .btn-gen:hover { background: #1a252f; }

        select, input[type="number"] {
            padding: 7px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        /* --- SECTION STYLING --- */
        .report-section { 
            background: #ffffff; 
            padding: 20px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            border: 1px solid #e0e0e0;
        }

        h3.section-title { 
            font-size: 1.05rem; 
            color: #2c3e50; 
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
            text-transform: uppercase;
            font-weight: 700;
        }

        /* --- TABLE STYLING --- */
        .report-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        .report-table th { 
            background-color: #f8f9fa; 
            color: #333; 
            font-weight: 700; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            padding: 12px;
            border: 1px solid #dee2e6;
        }
        
        .report-table td { 
            padding: 12px; 
            border: 1px solid #dee2e6; 
            font-size: 0.9rem; 
            vertical-align: middle;
        }

        /* --- STATUS BADGES --- */
        .status-badge { 
            padding: 4px 10px; 
            border-radius: 4px; 
            font-weight: bold; 
            font-size: 0.7rem; 
            text-transform: uppercase; 
        }
        .official { background: #d4edda; color: #155724; }
        .screening { background: #fff3cd; color: #856404; }
        .pending { background: #e9ecef; color: #495057; }
        .forapproval { background: #cce5ff; color: #004085; }
    </style>
</head>
<body>

<div id="report-wrapper">
    <div class="report-container">
        
        <div class="filter-controls">
            <form method="POST" style="display: flex; gap: 20px; align-items: center; width: 100%;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label style="font-size: 0.9rem; font-weight: 600;">Month:</label>
                    <select name="month">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m == $month) ? "selected" : "";
                            echo "<option value='$m' $selected>" . date("F", mktime(0, 0, 0, $m, 10)) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <label style="font-size: 0.9rem; font-weight: 600;">Year:</label>
                    <input type="number" name="year" value="<?= $year ?>" min="2000" max="<?= date('Y') ?>">
                </div>
                
                <button type="submit" class="btn-gen">Refresh Report Data</button>
                
                <div style="margin-left: auto; font-style: italic; color: #666; font-size: 0.85rem;">
                    Reporting for: <strong><?= $monthName ?> <?= $year ?></strong>
                </div>
            </form>
        </div>

        <div class="report-section">
            <h3 class="section-title">1. Application Pipeline Summary</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Process Status</th>
                        <th style="text-align: right; width: 200px;">Total Applications</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sqlPipe = "SELECT status, COUNT(*) as total FROM pwd WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? GROUP BY status";
                    $st = $conn->prepare($sqlPipe); 
                    $st->bind_param("ii", $year, $month); 
                    $st->execute();
                    $res = $st->get_result();
                    if($res->num_rows > 0):
                        while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><span class="status-badge <?= strtolower(str_replace(' ', '', $row['status'])) ?>"><?= $row['status'] ?></span></td>
                                <td style="text-align: right; font-weight: 700;"><?= number_format($row['total']) ?></td>
                            </tr>
                        <?php endwhile;
                    else: echo "<tr><td colspan='2' style='text-align:center; color: #999; padding: 20px;'>No data available.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-section">
            <h3 class="section-title">2. Distribution by Barangay (Official Only)</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Barangay</th>
                        <th style="text-align: right; width: 200px;">Registrants</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sqlB = "SELECT b.brgy_name, COUNT(p.id) AS total FROM pwd p JOIN barangay b ON p.barangay_id = b.id WHERE p.status = 'Official' AND YEAR(p.created_at) = ? AND MONTH(p.created_at) = ? GROUP BY b.id ORDER BY total DESC";
                    $st = $conn->prepare($sqlB); $st->bind_param("ii", $year, $month); $st->execute();
                    $res = $st->get_result();
                    if($res->num_rows > 0):
                        while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['brgy_name']) ?></td>
                                <td style="text-align: right; font-weight: 700;"><?= number_format($row['total']) ?></td>
                            </tr>
                        <?php endwhile;
                    else: echo "<tr><td colspan='2' style='text-align:center; color: #999; padding: 20px;'>No records found.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-section">
            <h3 class="section-title">3. Monthly Master List (Official)</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th style="width: 110px;">Date</th>
                        <th>Full Name</th>
                        <th>Barangay</th>
                        <th>Disability Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sqlL = "SELECT p.created_at, p.first_name, p.last_name, b.brgy_name, d.disability_name 
                             FROM pwd p 
                             JOIN barangay b ON p.barangay_id = b.id 
                             JOIN disability_type d ON p.disability_type = d.id
                             WHERE p.status = 'Official' AND YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?
                             ORDER BY p.created_at DESC";
                    $st = $conn->prepare($sqlL); $st->bind_param("ii", $year, $month); $st->execute();
                    $res = $st->get_result();
                    
                    if($res->num_rows > 0):
                        $counter = 1; // Start the number count
                        while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center; font-weight: bold; background: #fcfcfc;"><?= $counter++ ?></td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['last_name'] . ", " . $row['first_name']) ?></td>
                                <td><?= htmlspecialchars($row['brgy_name']) ?></td>
                                <td><?= htmlspecialchars($row['disability_name']) ?></td>
                            </tr>
                        <?php endwhile;
                    else: echo "<tr><td colspan='5' style='text-align:center; color: #999; padding: 20px;'>No detailed registrations found.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>