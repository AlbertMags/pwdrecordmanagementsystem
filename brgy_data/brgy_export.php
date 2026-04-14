<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../db_connect.php");

// Security Check
if (!isset($_SESSION['barangay_id']) || !isset($_POST['month']) || !isset($_POST['year'])) {
    exit("Unauthorized access.");
}

$brgy_id = $_SESSION['barangay_id'];
$month = intval($_POST['month']);
$year = intval($_POST['year']);
$monthName = date("F", mktime(0, 0, 0, $month, 10));

// Fetch Brgy Name for filename
$brgy_stmt = $conn->prepare("SELECT brgy_name FROM barangay WHERE id = ?");
$brgy_stmt->bind_param("i", $brgy_id);
$brgy_stmt->execute();
$brgy_name = $brgy_stmt->get_result()->fetch_assoc()['brgy_name'] ?? "Barangay";

// Clean output buffer
if (ob_get_length()) ob_end_clean();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="PWD_Report_'.$brgy_name.'_'.$monthName.'_'.$year.'.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

// 1. Header
fputcsv($output, ["Barangay Monthly PWD Report"]);
fputcsv($output, ["Barangay", $brgy_name]);
fputcsv($output, ["Period", $monthName . " " . $year]);
fputcsv($output, []);

// 2. Summary Section
fputcsv($output, ["1. Application Pipeline Summary"]);
fputcsv($output, ["Status", "Total"]);
$sqlPipe = "SELECT status, COUNT(*) as total FROM pwd WHERE barangay_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ? GROUP BY status";
$st = $conn->prepare($sqlPipe);
$st->bind_param("iii", $brgy_id, $year, $month);
$st->execute();
$res = $st->get_result();
while($row = $res->fetch_assoc()) {
    fputcsv($output, [$row['status'], $row['total']]);
}
fputcsv($output, []);

// 3. Disability Breakdown
fputcsv($output, ["2. Distribution by Disability Type (Official Only)"]);
fputcsv($output, ["Disability Type", "Count"]);
$sqlD = "SELECT d.disability_name, COUNT(p.id) AS total FROM pwd p JOIN disability_type d ON p.disability_type = d.id WHERE p.barangay_id = ? AND p.status = 'Official' AND YEAR(p.created_at) = ? AND MONTH(p.created_at) = ? GROUP BY d.id";
$st = $conn->prepare($sqlD);
$st->bind_param("iii", $brgy_id, $year, $month);
$st->execute();
$res = $st->get_result();
while($row = $res->fetch_assoc()) {
    fputcsv($output, [$row['disability_name'], $row['total']]);
}
fputcsv($output, []);

// 4. Master List
fputcsv($output, ["3. Monthly Master List (Official Registrations)"]);
fputcsv($output, ["Date Registered", "Last Name", "First Name", "Address", "Disability"]);
$sqlL = "SELECT p.created_at, p.last_name, p.first_name, p.address, d.disability_name 
         FROM pwd p 
         JOIN disability_type d ON p.disability_type = d.id
         WHERE p.barangay_id = ? AND p.status = 'Official' AND YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?
         ORDER BY p.created_at ASC";
$st = $conn->prepare($sqlL);
$st->bind_param("iii", $brgy_id, $year, $month);
$st->execute();
$res = $st->get_result();
while($row = $res->fetch_assoc()) {
    fputcsv($output, [
        date('M d, Y', strtotime($row['created_at'])),
        $row['last_name'],
        $row['first_name'],
        $row['address'],
        $row['disability_name']
    ]);
}

fclose($output);
exit();