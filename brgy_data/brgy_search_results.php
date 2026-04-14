<?php
include("../db_connect.php");

// Get the search query from the URL
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Security: Ensure the user is actually logged in
if (!isset($_SESSION['barangay_id'])) {
    echo "<div style='padding: 20px; color: red; font-family: sans-serif;'>
            <strong>Error:</strong> Session expired or Unauthorized access. Please log in again.
          </div>";
    exit;
}

$my_brgy_id = intval($_SESSION['barangay_id']);

if (empty($search_query)) {
    echo "<div style='padding: 20px; font-family: sans-serif;'>Please enter a name to search.</div>";
    exit;
}

// Prepare the Search Parameter
$clean_query = str_replace(',', ' ', $search_query); 
$search_param = "%" . str_replace(' ', '%', $clean_query) . "%";

// Build the Query
$sql = "SELECT p.id, p.first_name, p.last_name, p.status, b.brgy_name, d.disability_name, p.contact_number
        FROM pwd p 
        LEFT JOIN barangay b ON p.barangay_id = b.id 
        LEFT JOIN disability_type d ON p.disability_type = d.id 
        WHERE (CONCAT(p.first_name, ' ', p.last_name) LIKE ? 
           OR CONCAT(p.last_name, ' ', p.first_name) LIKE ?) 
        AND p.barangay_id = ? 
        ORDER BY p.last_name ASC LIMIT 50";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $search_param, $search_param, $my_brgy_id);
$stmt->execute();
$results = $stmt->get_result();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div style="padding: 30px; background: white; border-radius: 12px; margin: 20px auto; max-width: 1000px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); font-family: 'Segoe UI', Arial, sans-serif;">
    
    <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 25px;">
        <a href="/PWD/brgy_data/brgy_dashboard" style="text-decoration: none; color: #1a2a6c; font-size: 22px; padding: 5px; transition: 0.2s; display: flex; align-items: center; justify-content: center;" title="Back to Dashboard">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 style="color: #1a2a6c; margin: 0; font-size: 28px; line-height: 1.2;">Search Results for: "<?php echo htmlspecialchars($search_query); ?>"</h2>
            <p style="color: #777; font-size: 15px; margin: 5px 0 0 0;">Searching across Official, Screening, Pending, and For Approval records.</p>
        </div>
    </div>

    <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 25px;">

    <table style="width: 100%; border-collapse: collapse; border: 1px solid #eee; overflow: hidden; border-radius: 8px;">
        <thead>
            <tr style="background: #00bcd4; color: white;">
                <th style="padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">Full Name</th>
                <th style="padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">Barangay</th>
                <th style="padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">Disability Type</th>
                <th style="padding: 15px; text-align: center; font-size: 14px; text-transform: uppercase; border-right: 1px solid rgba(255,255,255,0.2);">Status</th>
                <th style="padding: 15px; text-align: center; font-size: 14px; text-transform: uppercase;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($results->num_rows > 0): ?>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='white'">
                        <td style="padding: 15px; font-weight: bold; color: #000;">
                            <?php echo htmlspecialchars(strtoupper($row['last_name'] . ", " . $row['first_name'])); ?>
                        </td>
                        <td style="padding: 15px; color: #333; border-left: 1px solid #eee;">
                            <?php echo htmlspecialchars($row['brgy_name']); ?>
                        </td>
                        <td style="padding: 15px; color: #333; border-left: 1px solid #eee;">
                            <?php echo htmlspecialchars($row['disability_name'] ?? 'Unspecified'); ?>
                        </td>
                        <td style="padding: 15px; text-align: center; border-left: 1px solid #eee;">
                            <?php 
                                $status = $row['status'];
                                $color = '#856404'; $bg = '#fff3cd'; 
                                if($status == 'Official') { $color = '#155724'; $bg = '#d4edda'; }
                                elseif($status == 'For Approval') { $color = '#004085'; $bg = '#cce5ff'; }
                                elseif($status == 'Screening') { $color = '#0c5460'; $bg = '#d1ecf1'; }
                            ?>
                            <span style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 5px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; border: 1px solid <?php echo $color; ?>30;">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td style="padding: 15px; text-align: center; border-left: 1px solid #eee;">
                            <a href="/PWD/brgy_data/view_search_pwd/<?php echo $row['id']; ?>" 
                               style="background: #1a237e; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px;">
                               <i class="fas fa-eye" style="font-size: 14px;"></i> View Profile
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 50px; text-align: center; color: #999;">
                        <div style="font-size: 40px; margin-bottom: 15px;">🔍</div>
                        No PWD records found matching your search in this barangay.<br>
                        <small>Try searching by only the last name or only the first name.</small>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>