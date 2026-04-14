<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../db_connect.php");

// Ensure the user is logged in to get their barangay_id
if (!isset($_SESSION['barangay_id'])) {
    echo "Access Denied. Please log in.";
    exit();
}

$my_barangay_id = $_SESSION['barangay_id'];

// 1. Fetch announcements, services, and news
$ann_list = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
$serv_list = $conn->query("SELECT * FROM services WHERE status = 'Active' ORDER BY created_at DESC");
$news_list = $conn->query("SELECT * FROM news ORDER BY event_date DESC");

// 2. Fetch PWDs from the 'pwd' table where status is 'Official' 
// and they belong to the current user's barangay
$pwd_query = "SELECT id, first_name, last_name 
              FROM pwd 
              WHERE status = 'Official' AND barangay_id = ? 
              ORDER BY last_name ASC";

$pwd_stmt = $conn->prepare($pwd_query);
$pwd_stmt->bind_param("i", $my_barangay_id);
$pwd_stmt->execute();
$pwd_list_result = $pwd_stmt->get_result();
// Convert to array to allow multiple iterations (once for modal, once for logic if needed)
$pwd_residents = [];
while($row = $pwd_list_result->fetch_assoc()){
    $pwd_residents[] = $row;
}

// 3. Fetch Request History for this Barangay
$history_query = "SELECT sr.*, p.first_name, p.last_name 
                  FROM service_requests sr 
                  JOIN pwd p ON sr.pwd_id = p.id 
                  WHERE sr.barangay_id = ? 
                  ORDER BY sr.created_at DESC";

$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("i", $my_barangay_id);
$history_stmt->execute();
$history_list = $history_stmt->get_result();

// Set active tab logic
$active_tab = 'tab-ann';
if(isset($_GET['tab'])){
    if($_GET['tab'] == 'services') $active_tab = 'tab-serv';
    if($_GET['tab'] == 'news') $active_tab = 'tab-news';
    if($_GET['tab'] == 'history') $active_tab = 'tab-history';
}
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay Support Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* --- CORE STYLING --- */
        * { box-sizing: border-box; }
        
        body, html { 
            background-color: #e9ecef; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; padding: 0; width: 100%; height: 100%;
        }

        /* --- UNIFIED NAVBAR --- */
        .top-nav {
            background: #fff; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 20px 40px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            position: fixed; 
            top: 0; 
            left: 250px; 
            width: calc(100% - 250px); 
            z-index: 1000;
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
            font-size: 14px; 
            color: #777; 
            font-weight: normal; 
            margin: 0;
            line-height: 1.2;
        }

        /* --- LAYOUT & CONTENT --- */
        .dashboard-wrapper { 
            padding: 110px 25px 25px 25px; 
            width: 100%; 
        }

        .content-card {
            background: #fff; border-radius: 12px; padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%;
        }

        /* --- TABS --- */
        .tabs { 
            display: flex; 
            justify-content: flex-start; 
            gap: 12px; 
            margin-bottom: 25px; 
            border-bottom: 2px solid #eee; 
            padding-bottom: 15px; 
        }

        .tab-btn { 
            padding: 10px 35px; 
            cursor: pointer; 
            border: none; 
            background: #f0f0f0; 
            border-radius: 6px; 
            font-weight: bold; 
            color: #555; 
            transition: 0.2s; 
        }
        .tab-btn.active { background: #3498db; color: white; }

        .content-section { display: none; width: 100%; }
        .content-section.active { display: block; }

        /* --- ITEM CARDS --- */
        .item-card { 
            background: white; 
            border-radius: 12px; 
            padding: 30px; 
            margin-bottom: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            border: 1px solid #eee; 
            width: 100%;
        }

        .announcement-card { border-left: 8px solid #3498db; }
        .service-card { border-left: 8px solid #2ecc71; flex-direction: column; }
        .news-card { border-left: 8px solid #e67e22; }

        .service-cat {
            color: #3498db; 
            font-weight: bold; 
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        /* --- IMAGE HANDLING --- */
        .ann-image-wrapper {
            width: 300px; 
            height: 180px; 
            overflow: hidden; 
            border-radius: 10px;
            margin-left: 30px;
            flex-shrink: 0;
            border: 1px solid #eee;
        }

        .ann-image-wrapper img {
            width: 100%; 
            height: 100%; 
            object-fit: cover;
        }

        /* --- INFO GRID --- */
        .info-grid { 
            width: 100%; 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-top: 20px; 
            background: #f9f9f9; 
            padding: 20px; 
            border-radius: 10px; 
            font-size: 14px; 
        }

        .info-grid div i {
            color: #3498db;
            margin-right: 8px;
        }

        /* --- MODAL STYLING --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            width: 500px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        .form-control { 
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px;
        }

        .btn-submit {
            background: #27ae60; color: white; border: none; padding: 12px 25px;
            border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%;
        }

        /* --- TABLE STYLING --- */
        .history-table {
            width: 100%; border-collapse: collapse; margin-top: 15px;
        }
        .history-table th { background: #3498db; text-align: left; padding: 12px; border-bottom: 2px solid #dee2e6; }
        .history-table td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }

        @media (max-width: 992px) {
            .top-nav { left: 0; width: 100%; }
            .item-card { flex-direction: column; }
            .ann-image-wrapper { width: 100%; margin: 20px 0 0 0; }
        }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-brand-wrapper">
            <div class="nav-text-stack">
                <h1>Support Center</h1>
                <p class="nav-sub">View community announcements, news, and available services.</p>
            </div>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <main class="content-card">

            <div style="display: flex; justify-content: flex-start; margin-bottom: 25px;">
                <button onclick="document.getElementById('requestModal').style.display='block'" 
                        style="background: #1a3a5f; color: white; padding: 12px 25px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(26,58,95,0.2);">
                    <i class="fas fa-plus-circle"></i> Request Service for Resident
                </button>
            </div>
            
            <div class="tabs">
                <button id="btn-ann" class="tab-btn <?= ($active_tab == 'tab-ann') ? 'active' : '' ?>" onclick="openTab('tab-ann')">Announcements</button>
                <button id="btn-news" class="tab-btn <?= ($active_tab == 'tab-news') ? 'active' : '' ?>" onclick="openTab('tab-news')">News & Activities</button>
                <button id="btn-serv" class="tab-btn <?= ($active_tab == 'tab-serv') ? 'active' : '' ?>" onclick="openTab('tab-serv')">Available Services</button>
                <button id="btn-history" class="tab-btn <?= ($active_tab == 'tab-history') ? 'active' : '' ?>" onclick="openTab('tab-history')">Request History</button>
            </div>

            <div id="tab-ann" class="content-section <?= ($active_tab == 'tab-ann') ? 'active' : '' ?>">
                <?php if($ann_list && $ann_list->num_rows > 0): ?>
                    <?php while($a = $ann_list->fetch_assoc()): ?>
                        <div class="item-card announcement-card">
                            <div style="flex:1;">
                                <div style="font-size: 1.8rem; font-weight: 700; color: #2c3e50;"><?= htmlspecialchars($a['title']) ?></div>
                                <div style="font-size: 0.9rem; color: #888; margin-bottom: 15px;">
                                    <i class="far fa-calendar-alt"></i> <?= date('F d, Y', strtotime($a['created_at'])) ?>
                                </div>
                                <p style="color: #444; line-height: 1.8; font-size: 16px;">
                                    <?= nl2br(htmlspecialchars($a['message'])) ?>
                                </p>
                            </div>
                            <?php if(!empty($a['image'])): ?>
                                <div class="ann-image-wrapper">
                                    <img src="../uploads/announcements/<?= $a['image'] ?>" alt="Announcement Image">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="item-card" style="justify-content: center; color: #999;">
                        <h3>No announcements available at the moment.</h3>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-news" class="content-section <?= ($active_tab == 'tab-news') ? 'active' : '' ?>">
                <?php if($news_list && $news_list->num_rows > 0): ?>
                    <?php while($n = $news_list->fetch_assoc()): ?>
                        <div class="item-card news-card">
                            <div style="flex:1;">
                                <div style="font-size: 1.8rem; font-weight: 700; color: #2c3e50;"><?= htmlspecialchars($n['title']) ?></div>
                                <div style="font-size: 0.95rem; color: #e67e22; font-weight: bold; margin-bottom: 15px;">
                                    <i class="fas fa-calendar-check"></i> Event Date: <?= date('F d, Y', strtotime($n['event_date'])) ?>
                                </div>
                                <p style="color: #444; line-height: 1.8; font-size: 16px;">
                                    <?= nl2br(htmlspecialchars($n['content'])) ?>
                                </p>
                            </div>
                            <?php if(!empty($n['image'])): ?>
                                <div class="ann-image-wrapper">
                                    <img src="../uploads/news/<?= $n['image'] ?>" alt="News Image">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="item-card" style="justify-content: center; color: #999;">
                        <h3>No news or activities posted yet.</h3>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-serv" class="content-section <?= ($active_tab == 'tab-serv') ? 'active' : '' ?>">
                <?php if($serv_list && $serv_list->num_rows > 0): ?>
                    <?php while($s = $serv_list->fetch_assoc()): ?>
                        <div class="item-card service-card">
                            <div class="service-cat"><?= htmlspecialchars($s['category'] ?? 'General') ?></div>
                            <div style="font-size: 1.8rem; font-weight: 700; color: #2c3e50; margin: 8px 0;"><?= htmlspecialchars($s['title']) ?></div>
                            <div style="font-weight: 600; color: #555; margin-bottom: 15px;">Provider: <?= htmlspecialchars($s['provider']) ?></div>
                            <p style="color: #666; font-size: 16px; line-height: 1.6;"><?= nl2br(htmlspecialchars($s['description'])) ?></p>
                            <div class="info-grid">
                                <div><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <?= htmlspecialchars($s['location'] ?: 'Not Specified') ?></div>
                                <div><i class="fas fa-phone"></i> <strong>Contact:</strong> <?= htmlspecialchars($s['contact'] ?: 'No Contact Provided') ?></div>
                                <div><i class="fas fa-clock"></i> <strong>Schedule:</strong> <?= htmlspecialchars($s['schedule'] ?: 'Contact for Details') ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="item-card" style="justify-content: center; color: #999;">
                        <h3>No community services currently active.</h3>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-history" class="content-section <?= ($active_tab == 'tab-history') ? 'active' : '' ?>">
                <div class="item-card" style="flex-direction: column;">
                    <h2 style="margin-top:0; color: #1a3a5f;"><i class="fas fa-history"></i> Submitted Requests</h2>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Service</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($history_list && $history_list->num_rows > 0): ?>
                                <?php while($row = $history_list->fetch_assoc()): 
                                    $badge_color = '#f39c12'; // Pending
                                    if($row['status'] == 'Approved') $badge_color = '#3498db';
                                    if($row['status'] == 'Completed') $badge_color = '#27ae60';
                                    if($row['status'] == 'Cancelled') $badge_color = '#e74c3c';
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['service_type']) ?></strong><br>
                                            <small style="color: #777;"><?= htmlspecialchars($row['remarks']) ?></small>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <span style="background: <?= $badge_color ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="padding: 30px; text-align: center; color: #999;">No requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <h2 style="margin:0; color: #1a3a5f;">Request Service</h2>
                <span onclick="document.getElementById('requestModal').style.display='none'" style="cursor:pointer; font-size: 24px;">&times;</span>
            </div>
            
            <form action="save_request.php" method="POST">
                <div class="form-group">
                    <label>Select PWD Beneficiary</label>
                    <select name="pwd_id" class="form-control" required>
                        <option value="">-- Choose PWD --</option>
                        <?php foreach($pwd_residents as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Service Needed</label>
                    <input type="text" name="service_type" class="form-control" placeholder="e.g. Wheelchair, Medicine, etc." required>
                </div>
                
                <div class="form-group">
                    <label>Reason / Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3" placeholder="Briefly explain why they need this service..."></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Submit Request</button>
            </form>
        </div>
    </div>

<script>
function openTab(id, updateUrl = true) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Deactivate all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show target
    const targetSection = document.getElementById(id);
    if(targetSection) {
        targetSection.classList.add('active');
        
        let btnId = 'btn-ann';
        if(id === 'tab-serv') btnId = 'btn-serv';
        if(id === 'tab-news') btnId = 'btn-news';
        if(id === 'tab-history') btnId = 'btn-history';
        document.getElementById(btnId).classList.add('active');
    }

    if(updateUrl) {
        let tabName = 'announcements';
        if(id === 'tab-serv') tabName = 'services';
        if(id === 'tab-news') tabName = 'news';
        if(id === 'tab-history') tabName = 'history';
        window.history.pushState({}, '', 'index.php?page=brgy_support_center&tab=' + tabName);
    }
}

// Handle outside click for modal
window.onclick = function(event) {
    let modal = document.getElementById('requestModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Initial tab load check from URL
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTabParam = urlParams.get('tab');

    if (activeTabParam === 'services') openTab('tab-serv', false);
    else if (activeTabParam === 'news') openTab('tab-news', false);
    else if (activeTabParam === 'history') openTab('tab-history', false);
    else openTab('tab-ann', false);
});
</script>

</body>
</html>