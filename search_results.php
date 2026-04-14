<?php
include("db_connect.php");

// FIXED: Updated Base URL to remove the /backup/ folder
$base_url = "/PWD/";

// Get the search query from the URL
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($search_query)) {
    echo "<div style='padding: 20px;'>Please enter a name to search.</div>";
    exit;
}

// 1. Determine User Role and Barangay ID (Ensure session is started in index.php)
$user_role = $_SESSION['role'] ?? 'Barangay'; 
$brgy_id = $_SESSION['barangay_id'] ?? 0;

// 2. Prepare the Search Parameter
$clean_query = str_replace(',', ' ', $search_query); 
$search_param = "%" . str_replace(' ', '%', $clean_query) . "%";

// 3. Build the Query
$sql = "SELECT p.id, p.first_name, p.last_name, p.status, b.brgy_name, d.disability_name 
        FROM pwd p 
        LEFT JOIN barangay b ON p.barangay_id = b.id 
        LEFT JOIN disability_type d ON p.disability_type = d.id 
        WHERE (CONCAT(p.first_name, ' ', p.last_name) LIKE ? 
            OR CONCAT(p.last_name, ' ', p.first_name) LIKE ?) ";

// Security: If not Admin/MSWDO, only show their Barangay's PWDs
if ($user_role !== 'super_admin' && $user_role !== 'MSWDO') {
    $sql .= " AND p.barangay_id = " . intval($brgy_id);
}

$sql .= " ORDER BY p.last_name ASC LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$results = $stmt->get_result();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div style="padding: 20px; background: white; border-radius: 8px; margin: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    
    <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 15px;">
        <a href="<?= $base_url ?>information_hub" style="text-decoration: none; color: #1a2a6c; font-size: 20px; padding: 5px; transition: 0.2s;" title="Back to Information Hub">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 style="color: #1a2a6c; margin: 0;">Search Results for: "<?php echo htmlspecialchars($search_query); ?>"</h2>
            <p style="color: #666; font-size: 14px; margin: 5px 0 0 0;">Searching across Official, Screening, Pending, and For Approval records.</p>
        </div>
    </div>

    <hr style="border: 0; border-top: 1px solid #eee;">

    <table border="1" style="width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #eee;">
        <thead style="background: #f8f9fa;">
            <tr>
                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Full Name</th>
                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Barangay</th>
                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Disability Type</th>
                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Status</th>
                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($results->num_rows > 0): ?>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px; text-align: left;">
                            <strong><?php echo htmlspecialchars($row['last_name'] . ", " . $row['first_name']); ?></strong>
                        </td>
                        <td style="padding: 12px; text-align: left;">
                            <?php echo htmlspecialchars($row['brgy_name'] ?? 'N/A'); ?>
                        </td>
                        <td style="padding: 12px; text-align: left;">
                            <?php echo htmlspecialchars($row['disability_name'] ?? 'Unspecified'); ?>
                        </td>
                        <td style="padding: 12px; text-align: left;">
                            <?php 
                                $status = $row['status'];
                                $color = '#856404'; // Default (Pending)
                                $bg = '#fff3cd';
                                
                                if($status == 'Official') { $color = '#155724'; $bg = '#d4edda'; }
                                elseif($status == 'For Approval') { $color = '#004085'; $bg = '#cce5ff'; }
                                elseif($status == 'Screening') { $color = '#0c5460'; $bg = '#d1ecf1'; }
                            ?>
                            <span style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="<?= $base_url ?>view_search_pwd/<?php echo $row['id']; ?>" 
                               style="background: #1a2a6c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 12px; display: inline-block; font-weight: bold;">
                               <i class="fas fa-eye"></i> View Profile
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 40px; text-align: center; color: #888;">
                        <div style="font-size: 30px; margin-bottom: 10px;">🔍</div>
                        No PWD records found matching "<strong><?php echo htmlspecialchars($search_query); ?></strong>".<br>
                        <small>Try searching by only the last name or only the first name.</small>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>