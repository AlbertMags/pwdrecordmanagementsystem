<?php
include("../db_connect.php");

$brgy_id = $_SESSION['barangay_id'];

// Fetch PENDING, SCREENING, and FOR APPROVAL applications for this specific Barangay
// Ordered by Status Priority (For Approval -> Screening -> Pending) then by Date Applied DESC
$query_list = "
SELECT 
    CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', COALESCE(p.suffix, '')) AS full_name, 
    d.disability_name, 
    p.date_applied,
    p.status
FROM pwd p
JOIN disability_type d ON p.disability_type = d.id
WHERE (p.status = 'Pending' OR p.status = 'Screening' OR p.status = 'For Approval') AND p.barangay_id = ?
ORDER BY 
    CASE 
        WHEN p.status = 'For Approval' THEN 1 
        WHEN p.status = 'Screening' THEN 2 
        ELSE 3 
    END, 
    p.date_applied DESC
";

$stmt = $conn->prepare($query_list);
$stmt->bind_param("i", $brgy_id);
$stmt->execute();
$result_list = $stmt->get_result();

$count = 1; 
?>

<div class="barangay-header">
    <h2>Application Tracking</h2>
    <p>Records currently being processed or awaiting final approval by MSWDO.</p>
</div>

<table border="1" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
    <thead style="background: #f8f9fa;">
        <tr>
            <th style="padding: 12px; text-align: left;">#</th>
            <th style="padding: 12px; text-align: left;">Full Name</th>
            <th style="padding: 12px; text-align: left;">Disability Type</th>
            <th style="padding: 12px; text-align: left;">Date Applied</th>
            <th style="padding: 12px; text-align: left;">Current Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result_list->num_rows > 0): ?>
            <?php while ($row = $result_list->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #eee;"> 
                    <td style="padding: 12px; text-align: left;"><?php echo $count++; ?></td> 
                    <td style="padding: 12px; text-align: left;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td style="padding: 12px; text-align: left;"><?php echo htmlspecialchars($row['disability_name']); ?></td>
                    <td style="padding: 12px; text-align: left;"><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                    <td style="padding: 12px; text-align: left;">
                        <?php if($row['status'] == 'For Approval'): ?>
                            <span style="background: #e7f3ff; color: #007bff; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; border: 1px solid #b8daff; text-transform: uppercase;">
                                <i class="fas fa-check-circle"></i> For Approval
                            </span>
                        <?php elseif($row['status'] == 'Screening'): ?>
                            <span style="background: #d1ecf1; color: #0c5460; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; border: 1px solid #bee5eb; text-transform: uppercase;">
                                <i class="fas fa-search"></i> Screening
                            </span>
                        <?php else: ?>
                            <span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; border: 1px solid #ffeeba; text-transform: uppercase;">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center; padding: 30px; color: #888;">No applications in progress for your barangay.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php
// End of file
?>