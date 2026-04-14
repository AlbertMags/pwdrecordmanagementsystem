<?php
include("db_connect.php");

if (isset($_POST['month']) && isset($_POST['year'])) {
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $monthName = date("F", mktime(0, 0, 0, $month, 10));

    // Clear any previous output to prevent corrupting the CSV
    if (ob_get_length()) ob_end_clean();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="PWD_Report_' . $monthName . '_' . $year . '.csv"');

    $output = fopen('php://output', 'w');
    
    // Fix for garbled characters in Excel (UTF-8 BOM)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // 1. Header Summary
    fputcsv($output, ["Monthly PWD Report for", $monthName . " " . $year]);
    
    // Get Total Official PWDs (Updated status to 'Official')
    $totalSql = "SELECT COUNT(*) AS total FROM pwd WHERE status = 'Official' AND YEAR(created_at) = ? AND MONTH(created_at) = ?";
    $stmt = $conn->prepare($totalSql);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    fputcsv($output, ["Total Official PWDs", $total]);
    fputcsv($output, []);

    // 2. Breakdown per Barangay (Updated status to 'Official')
    fputcsv($output, ["Barangay Breakdown (Official Only)"]);
    fputcsv($output, ["Barangay", "Total"]);
    $sqlB = "SELECT b.brgy_name, COUNT(p.id) AS total FROM pwd p JOIN barangay b ON p.barangay_id = b.id WHERE p.status = 'Official' AND YEAR(p.created_at) = ? AND MONTH(p.created_at) = ? GROUP BY b.id";
    $stmtB = $conn->prepare($sqlB);
    $stmtB->bind_param("ii", $year, $month);
    $stmtB->execute();
    $resB = $stmtB->get_result();
    while($r = $resB->fetch_assoc()) fputcsv($output, [$r['brgy_name'], $r['total']]);
    fputcsv($output, []);

    // 3. Detailed List (Updated status to 'Official')
    fputcsv($output, ["Detailed List of Official PWDs"]);
    fputcsv($output, ["Date Added", "Last Name", "First Name", "Barangay", "Disability"]);
    $sqlL = "SELECT p.created_at, p.last_name, p.first_name, b.brgy_name, d.disability_name 
             FROM pwd p 
             JOIN barangay b ON p.barangay_id = b.id 
             JOIN disability_type d ON p.disability_type = d.id
             WHERE p.status = 'Official' AND YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?
             ORDER BY p.created_at ASC";
    $stmtL = $conn->prepare($sqlL);
    $stmtL->bind_param("ii", $year, $month);
    $stmtL->execute();
    $resL = $stmtL->get_result();
    while($r = $resL->fetch_assoc()) {
        fputcsv($output, [
            date('M d, Y', strtotime($r['created_at'])), 
            $r['last_name'], 
            $r['first_name'], 
            $r['brgy_name'], 
            $r['disability_name']
        ]);
    }

    fclose($output);
    exit();
}
?>