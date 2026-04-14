<?php
// 1. Start session to read the redirect data
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

include("db_connect.php");
include("announcement.php");
include("service_logic.php");
include("news_logic.php");

// ACTION: APPROVE REQUEST
if (isset($_GET['approve_request'])) {
    $req_id = intval($_GET['approve_request']);
    $stmt = $conn->prepare("SELECT * FROM service_requests WHERE id = ?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if ($request) {
        // Update the request status
        $update = $conn->prepare("UPDATE service_requests SET status = 'Approved' WHERE id = ?");
        $update->bind_param("i", $req_id);
        $update->execute();

        // Automatically add to available services list
        $title = $request['service_type'];
        $desc = "Approved request: " . $request['remarks'];
        $add_service = $conn->prepare("INSERT INTO services (title, description, status) VALUES (?, ?, 'Active')");
        $add_service->bind_param("ss", $title, $desc);
        $add_service->execute();
        
        // Stay on the requests tab after the action
        header("Location: support_center?tab=requests"); 
        exit();
    }
}

// ACTION: DECLINE REQUEST
if (isset($_GET['decline_request'])) {
    $req_id = intval($_GET['decline_request']);
    $stmt = $conn->prepare("UPDATE service_requests SET status = 'Declined' WHERE id = ?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();

    header("Location: support_center?tab=requests");
    exit();
}

// 2. Set active tab logic
$active_tab = 'tab-ann'; // Default

// Priority 1: Session Check (Used after redirects from logic files)
if (isset($_SESSION['active_tab'])) {
    if ($_SESSION['active_tab'] == 'announcements') $active_tab = 'tab-ann';
    if ($_SESSION['active_tab'] == 'news') $active_tab = 'tab-news';
    if ($_SESSION['active_tab'] == 'services') $active_tab = 'tab-serv';
    if ($_SESSION['active_tab'] == 'requests') $active_tab = 'tab-req'; 
    unset($_SESSION['active_tab']);
} 
// Priority 2: URL Check
elseif (isset($_GET['tab'])) {
    if ($_GET['tab'] == 'services') $active_tab = 'tab-serv';
    if ($_GET['tab'] == 'news') $active_tab = 'tab-news';
    if ($_GET['tab'] == 'announcements') $active_tab = 'tab-ann';
    if ($_GET['tab'] == 'requests') $active_tab = 'tab-req'; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Center | Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
 <style>
        /* --- CORE STYLING --- */
        * { box-sizing: border-box; outline: none; }
        
        body, html { 
            background-color: #e9ecef; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; padding: 0; width: 100%; height: 100%;
        }

        /* --- THE NAVBAR --- */
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
            font-size: 16px; 
            color: #4b4848; 
            font-weight: normal; 
            margin: 0;
            line-height: 1.2;
        }

        /* --- LAYOUT & CONTENT --- */
        .dashboard-wrapper { 
            padding: 100px 25px 25px 25px; 
            width: 100%; 
            display: flex;
            justify-content: center;
        }

        .content-card {
            background: #fff; 
            border-radius: 12px; 
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
            width: 100%;
            max-width: 1400px; /* Limits width on ultra-wide screens but stays wide */
        }

        .tabs { 
            display: flex; 
            justify-content: flex-start; 
            gap: 12px; 
            margin-bottom: 25px; 
            border-bottom: 2px solid #eee; 
            padding-bottom: 15px; 
        }

        .tab-btn { 
            padding: 10px 30px; 
            cursor: pointer; 
            border: none; 
            background: #f0f0f0; 
            border-radius: 6px; 
            font-weight: bold; 
            color: #555; 
            transition: 0.3s; 
        }

        .tab-btn:hover {
            background: #07a0e2;
            color: white;
        }

        .tab-btn.active { 
            background: #07a0e2; 
            color: white; 
        }

        .content-section { display: none; width: 100%; }
        .content-section.active { display: block; }
        
        .toggle-btn { 
            background: #0056b3; 
            color: white; 
            border: none; 
            padding: 12px 25px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600; 
            margin-bottom: 25px; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px;
            float: left;
            transition: none;
            text-decoration: none;
        }
    
        .section-header { width: 100%; overflow: hidden; margin-bottom: 10px; }

        /* General Item Card - Shared by all */
        .item-card { 
            background: white; 
            border-radius: 12px; 
            padding: 25px; 
            margin-bottom: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            position: relative; 
            border: 1px solid #eee; 
            width: 100%;
        }

        /* Flex Layout only for Side-by-Side Announcements/News */
        .announcement-card, .news-card { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            min-height: 220px; 
        }

        .announcement-card { border-left: 6px solid #07a0e2; }
        .service-card { border-left: 6px solid #2ecc71; display: flex; flex-direction: column; }
        .news-card { border-left: 6px solid #e67e22; }

        /* Request Card (Table container) - Fixed for full width */
        .request-card { 
            border-left: 6px solid #3498db; 
            padding: 0 !important; 
            overflow: hidden;
            display: block; /* Overrides global flex if accidentally inherited */
        }

        /* --- TABLE STYLING --- */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 700;
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
            text-align: left;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
            color: #444;
        }

        .table tbody tr:hover {
            background-color: #fcfcfc;
        }

        /* Badge and Action UI */
        .badge {
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
        }

        .bg-success { background-color: #28a745 !important; color: #fff !important; }
        .bg-warning { background-color: #ffc107 !important; color: #333 !important; }
        .bg-danger { background-color: #dc3545 !important; color: #fff !important; }

        .btn-outline-success { border: 1px solid #28a745; color: #28a745; background: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .btn-outline-danger { border: 1px solid #dc3545; color: #dc3545; background: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .btn-outline-success:hover { background: #28a745; color: #fff; }
        .btn-outline-danger:hover { background: #dc3545; color: #fff; }

        .options-menu { position: absolute; top: 15px; right: 10px; z-index: 10; }
        .dots-btn { background: none; border: none; font-size: 18px; cursor: pointer; color: #888; padding: 8px; border-radius: 50%; transition: background 0.2s; }
        .dots-btn:hover, .dots-btn:focus { background: #f0f0f0; color: #333; outline: none; }
        
        .dropdown-content {
            display: none; position: absolute; right: 0; top: 40px;
            background-color: white; min-width: 140px; box-shadow: 0px 8px 16px rgba(0,0,0,0.15);
            z-index: 100; border-radius: 8px; overflow: hidden; border: 1px solid #eee;
        }
        .dropdown-content button, .dropdown-content a   { width: 100%; padding: 12px 15px; text-decoration: none; display: block; color: #333; font-size: 14px; text-align: left; border: none; background: none; cursor: pointer; }
        .dropdown-content button:hover, .dropdown-content a:hover { background-color: #f8f9fa; color: #333; }
        .dropdown-content a.del-link { color: #e74c3c; }
        .dropdown-content a.del-link:hover { background-color: #fff5f5; color: #c0392b; }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-bottom: 10px; }
        .badge-Active { background: #e8f8f0; color: #2ecc71; }
        .badge-Inactive { background: #fdf2f2; color: #e74c3c; }

        .info-grid { width: 100%; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; background: #f9f9f9; padding: 15px; border-radius: 8px; font-size: 14px; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index: 2000; overflow-y: auto; }
        .modal-content { background:#fff; padding:30px; margin:5% auto; width:600px; border-radius: 15px; position: relative; }
        
        .modal-header { display: flex; align-items: center; margin-bottom: 20px; justify-content: center; position: relative; }
        .modal-header h3 { margin: 0; text-align: center; flex-grow: 1; }
        .close-btn { background: none; border: none; font-size: 28px; cursor: pointer; color: #000; padding: 0; line-height: 1; }
        .close-btn:hover { color: #cc0000; }

        .btn-action { padding: 12px 18px; border-radius: 5px; color: white; cursor: pointer; border: none; font-weight: 600; width: 100%; text-align: center; }
        .modal-footer-stacked { display: flex; flex-direction: column; gap: 12px; margin-top: 25px; }
        .btn-cancel-alt { background: #f8f9fa; color: #495057; border: 1px solid #dee2e6; }

        @media (max-width: 992px) { .top-nav { left: 0; width: 100%; } }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-brand-wrapper">
            <div class="nav-text-stack">
                <h1>Support Center</h1>
                <p class="nav-sub">Manage announcements, news, and community services.</p>
            </div>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <main class="content-card">
            <div class="tabs">
                <button id="btn-ann" class="tab-btn <?= ($active_tab == 'tab-ann') ? 'active' : '' ?>" onclick="openTab('tab-ann')">Announcements</button>
                <button id="btn-news" class="tab-btn <?= ($active_tab == 'tab-news') ? 'active' : '' ?>" onclick="openTab('tab-news')">News</button>
                <button id="btn-serv" class="tab-btn <?= ($active_tab == 'tab-serv') ? 'active' : '' ?>" onclick="openTab('tab-serv')">Available Services</button>
                <button id="btn-req" class="tab-btn <?= ($active_tab == 'tab-req') ? 'active' : '' ?>" onclick="openTab('tab-req')">Requested Service</button>
            </div>

            <div id="tab-ann" class="content-section <?= ($active_tab == 'tab-ann') ? 'active' : '' ?>">
                <div class="section-header">
                    <button type="button" class="toggle-btn" onclick="openModal('addModalAnn')"><i class="fas fa-plus"></i> Create Announcement</button>
                </div>
                <div id="ann-list">
                    <?php
                    $ann_list = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
                    while($a = $ann_list->fetch_assoc()):
                    ?>
                        <div class="item-card announcement-card">
                            <div style="flex:1; padding-right:20px;">
                                <div style="font-size: 1.4rem; font-weight: 700; color: #2c3e50;"><?= htmlspecialchars($a['title']) ?></div>
                                <div style="font-size: 0.85rem; color: #888; margin-bottom: 10px;"><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($a['created_at'])) ?></div>
                                <p style="color: #555; line-height: 1.6;"><?= nl2br(htmlspecialchars($a['message'])) ?></p>
                            </div>
                            <?php if($a['image']): ?>
                                <div style="width: 200px; height: 130px; overflow: hidden; border-radius: 8px; margin-left: 15px; margin-right: 35px; flex-shrink: 0;">
                                    <img src="uploads/announcements/<?= $a['image'] ?>" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                            <?php endif; ?>
                            <div class="options-menu">
                                <button class="dots-btn" onclick="toggleDropdown(event, 'dropAnn<?= $a['id'] ?>')"><i class="fas fa-ellipsis-v"></i></button>
                                <div id="dropAnn<?= $a['id'] ?>" class="dropdown-content">
                                    <button onclick="openEditAnn(<?= htmlspecialchars(json_encode($a)) ?>)"><i class="fas fa-edit"></i> Edit</button>
                                    <a href="announcement.php?delete_announcement=<?= $a['id'] ?>&origin=support_center" class="del-link" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i> Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div id="tab-news" class="content-section <?= ($active_tab == 'tab-news') ? 'active' : '' ?>">
                <div class="section-header">
                    <button type="button" class="toggle-btn" onclick="openModal('addModalNews')"><i class="fas fa-newspaper"></i> Post News</button>
                </div>
                <div id="news-list">
                    <?php
                    $news_list = $conn->query("SELECT * FROM news ORDER BY event_date DESC");
                    while($n = $news_list->fetch_assoc()):
                    ?>
                        <div class="item-card news-card">
                            <div style="flex:1; padding-right:20px;">
                                <div style="font-size: 1.4rem; font-weight: 700; color: #2c3e50;"><?= htmlspecialchars($n['title']) ?></div>
                                <div style="font-size: 0.85rem; color: #e67e22; font-weight: bold; margin-bottom: 10px;"><i class="fas fa-calendar-day"></i> Event Date: <?= date('M d, Y', strtotime($n['event_date'])) ?></div>
                                <p style="color: #555; line-height: 1.6;"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
                            </div>
                            <?php if($n['image']): ?>
                                <div style="width: 200px; height: 130px; overflow: hidden; border-radius: 8px; margin-left: 15px; margin-right: 35px; flex-shrink: 0;">
                                    <img src="uploads/news/<?= $n['image'] ?>" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                            <?php endif; ?>
                            <div class="options-menu">
                                <button class="dots-btn" onclick="toggleDropdown(event, 'dropNews<?= $n['id'] ?>')"><i class="fas fa-ellipsis-v"></i></button>
                                <div id="dropNews<?= $n['id'] ?>" class="dropdown-content">
                                    <button onclick="openEditNews(<?= htmlspecialchars(json_encode($n)) ?>)"><i class="fas fa-edit"></i> Edit</button>
                                    <a href="news_logic.php?delete_news=<?= $n['id'] ?>" class="del-link" onclick="return confirm('Delete news?')"><i class="fas fa-trash"></i> Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div id="tab-serv" class="content-section <?= ($active_tab == 'tab-serv') ? 'active' : '' ?>">
                <div class="section-header">
                   <button class="toggle-btn" onclick="openModal('addModalServ')"><i class="fas fa-plus"></i> Add Service</button>
                </div>
                <div id="serv-list">
                    <?php
                    $serv_list = $conn->query("SELECT * FROM services ORDER BY created_at DESC");
                    while($s = $serv_list->fetch_assoc()):
                    ?>
                        <div class="item-card service-card">
                            <div class="options-menu">
                                <button class="dots-btn" onclick="toggleDropdown(event, 'dropServ<?= $s['id'] ?>')"><i class="fas fa-ellipsis-v"></i></button>
                                <div id="dropServ<?= $s['id'] ?>" class="dropdown-content">
                                    <button onclick='openEditServ(<?= json_encode($s) ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <a href="service_logic.php?delete_service=<?= $s['id'] ?>" class="del-link" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i> Delete</a>
                                </div>
                            </div>
                            <div style="padding-right: 40px;">
                                <span class="status-badge badge-<?= $s['status'] ?>"><?= $s['status'] ?></span>
                                <div style="color: #3498db; font-weight: bold; font-size: 11px;"><?= strtoupper(htmlspecialchars($s['category'] ?? '')) ?></div>
                                <div style="font-size: 1.4rem; font-weight: 700; color: #2c3e50; margin: 5px 0;"><?= htmlspecialchars($s['title']) ?></div>
                                <div style="font-weight: 600; color: #555;">Provider: <?= htmlspecialchars($s['provider']) ?></div>
                                <p style="color: #666; margin-top:10px;"><?= nl2br(htmlspecialchars($s['description'])) ?></p>
                            </div>
                            <div class="info-grid">
                                <div><i class="fas fa-map-marker-alt"></i> <strong>Loc:</strong> <?= htmlspecialchars($s['location'] ?? 'N/A') ?></div>
                                <div><i class="fas fa-phone"></i> <strong>Call:</strong> <?= htmlspecialchars($s['contact'] ?? 'N/A') ?></div>
                                <div><i class="fas fa-clock"></i> <strong>Time:</strong> <?= htmlspecialchars($s['schedule'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

          <div id="tab-req" class="content-section <?= ($active_tab == 'tab-req') ? 'active' : '' ?>">
                <div class="section-header">
                    <h4 class="text-primary"><i class="fas fa-tasks me-2"></i>Resident Service Applications</h4>
                </div>
                
                <div class="item-card request-card p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Resident</th>
                                    <th>Service</th>
                                    <th>Remarks</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                             <tbody>
                                <?php
                                $query = "SELECT r.*, p.first_name, p.last_name 
                                          FROM service_requests r 
                                          LEFT JOIN pwd p ON r.pwd_id = p.id 
                                          ORDER BY r.created_at DESC";
                                $result = mysqli_query($conn, $query);

                                if (mysqli_num_rows($result) > 0):
                                    while ($row = mysqli_fetch_assoc($result)):
                                        $status_class = 'bg-warning text-dark';
                                        if($row['status'] == 'Approved') $status_class = 'bg-success';
                                        if($row['status'] == 'Declined') $status_class = 'bg-danger';
                                        
                                        $fullname = $row['first_name'] . ' ' . $row['last_name'];
                                ?>
                                <tr>
                                    <td class="ps-4"><strong><?= !empty($row['first_name']) ? htmlspecialchars($fullname) : "ID: ".$row['pwd_id'] ?></strong></td>
                                    <td><?= htmlspecialchars($row['service_type']) ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($row['remarks']) ?></small></td>
                                    <td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
                                    <td><span class="badge <?= $status_class ?>"><?= $row['status'] ?></span></td>
                                    <td class="text-center pe-3">
                                        <?php if ($row['status'] == 'Pending'): ?>
                                            <a href="support_center?approve_request=<?= $row['id'] ?>" 
                                               class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this request?')">
                                               <i class="fas fa-check"></i>
                                            </a>
                                            <a href="support_center?decline_request=<?= $row['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger" onclick="return confirm('Decline this request?')">
                                               <i class="fas fa-times"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="6" class="text-center py-4">No requests found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="addModalAnn" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Post New Announcement</h3>
                <button class="close-btn" onclick="closeModal('addModalAnn')">&times;</button>
            </div>
            <form method="POST" action="announcement.php" enctype="multipart/form-data">
                <input type="hidden" name="origin" value="support_center">
                <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
                <div class="form-group"><label>Message</label><textarea name="message" style="height:120px;" required></textarea></div>
                <div class="form-group"><label>Image</label><input type="file" name="image"></div>
                <div class="modal-footer-stacked">
                    <button type="submit" name="post_announcement" class="btn-action" style="background:#0056b3;">Post Announcement</button>
                    <button type="button" onclick="closeModal('addModalAnn')" class="btn-action btn-cancel-alt">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalAnn" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Announcement</h3>
                <button class="close-btn" onclick="closeModal('modalAnn')">&times;</button>
            </div>
            <form method="POST" action="announcement.php" enctype="multipart/form-data">
                <input type="hidden" name="origin" value="support_center">
                <input type="hidden" name="id" id="ann_id">
                <div class="form-group"><label>Title</label><input type="text" name="title" id="ann_title" required></div>
                <div class="form-group"><label>Message</label><textarea name="message" id="ann_msg" style="height:120px;" required></textarea></div>
                <div class="form-group"><label>Change Image</label><input type="file" name="image"></div>
                <div class="modal-footer-stacked">
                    <button type="submit" name="update_announcement" class="btn-action" style="background:#27ae60;">Update Changes</button>
                    <button type="button" onclick="closeModal('modalAnn')" class="btn-action btn-cancel-alt">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addModalNews" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Post News / Activity</h3>
                <button class="close-btn" onclick="closeModal('addModalNews')">&times;</button>
            </div>
            <form method="POST" action="news_logic.php" enctype="multipart/form-data">
                <div class="form-group"><label>Headline</label><input type="text" name="title" required></div>
                <div class="form-group"><label>Event Date</label><input type="date" name="event_date" required></div>
                <div class="form-group"><label>Story Content</label><textarea name="content" style="height:120px;" required></textarea></div>
                <div class="form-group"><label>Upload Image</label><input type="file" name="image"></div>
                <div class="modal-footer-stacked">
                    <button type="submit" name="add_news" class="btn-action" style="background:#0056b3;">Post News</button>
                    <button type="button" onclick="closeModal('addModalNews')" class="btn-action btn-cancel-alt">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalNews" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit News Entry</h3>
                <button class="close-btn" onclick="closeModal('modalNews')">&times;</button>
            </div>
            <form method="POST" action="news_logic.php" enctype="multipart/form-data">
                <input type="hidden" name="id" id="news_id">
                <div class="form-group"><label>Headline</label><input type="text" name="title" id="news_title" required></div>
                <div class="form-group"><label>Event Date</label><input type="date" name="event_date" id="news_date" required></div>
                <div class="form-group"><label>Story Content</label><textarea name="content" id="news_content" style="height:120px;" required></textarea></div>
                <div class="form-group"><label>Change Image</label><input type="file" name="image"></div>
                <div class="modal-footer-stacked">
                    <button type="submit" name="update_news" class="btn-action" style="background:#27ae60;">Update News</button>
                    <button type="button" onclick="closeModal('modalNews')" class="btn-action btn-cancel-alt">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addModalServ" class="modal">
        <div class="modal-content" style="width: 700px;">
            <div class="modal-header">
                <h3>Add Service</h3>
                <button class="close-btn" onclick="closeModal('addModalServ')">&times;</button>
            </div>
            <form method="POST" action="service_logic.php">
                <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
                    <div class="form-group"><label>Category</label><input type="text" name="category"></div>
                    <div class="form-group"><label>Provider</label><input type="text" name="provider" required></div>
                    <div class="form-group"><label>Status</label>
                    <select name="status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" style="height:80px;" required></textarea></div>
                <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:10px;">
                    <div class="form-group"><label>Location</label><input type="text" name="location"></div>
                    <div class="form-group"><label>Contact</label><input type="text" name="contact"></div>
                    <div class="form-group"><label>Schedule</label><input type="text" name="schedule"></div>
                </div>
                <div class="modal-footer-stacked">
                    <button type="submit" name="add_service" class="btn-action" style="background:#0056b3;">Save Service</button>
                    <button type="button" onclick="closeModal('addModalServ')" class="btn-action btn-cancel-alt">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    

    <div id="modalServ" class="modal">
        <div class="modal-content" style="width: 700px;">
            <div class="modal-header">
                <h3>Edit Service</h3>
                <button class="close-btn" onclick="closeModal('modalServ')">&times;</button>
            </div>
            <form method="POST" action="service_logic.php">
                <input type="hidden" name="id" id="serv_id">
                <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group"><label>Title</label><input type="text" name="title" id="serv_title" required></div>
                    <div class="form-group"><label>Category</label><input type="text" name="category" id="serv_cat"></div>
                    <div class="form-group"><label>Provider</label><input type="text" name="provider" id="serv_prov" required></div>
                    <div class="form-group"><label>Status</label><select name="status" id="serv_stat"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" id="serv_desc" style="height:80px;"></textarea></div>
                <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group"><label>Location</label><input type="text" name="location" id="serv_loc"></div>
                    <div class="form-group"><label>Contact</label><input type="text" name="contact" id="serv_con"></div>
                    <div class="form-group"><label>Schedule</label><input type="text" name="schedule" id="serv_sch"></div>
                </div>
                <div class="modal-footer-stacked">
                    <button type="submit" name="update_service" class="btn-action" style="background:#27ae60;">Save Changes</button>
                    <button type="button" onclick="closeModal('modalServ')" class="btn-action btn-cancel-alt">Cancel</button>
                </div>
            </form>
        </div>
    </div>

<script>
/**
 * TAB SWITCHING LOGIC
 */
function openTab(id, updateUrl = true) {
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById(id).classList.add('active');
    
    let btnId = 'btn-ann';
    if(id === 'tab-serv') btnId = 'btn-serv';
    if(id === 'tab-news') btnId = 'btn-news';
    if(id === 'tab-req') btnId = 'btn-req'; 
    
    const activeBtn = document.getElementById(btnId);
    if(activeBtn) activeBtn.classList.add('active');

    if(updateUrl) {
        let tabName = id === 'tab-serv' ? 'services' : 
                     (id === 'tab-news' ? 'news' : 
                     (id === 'tab-req' ? 'requests' : 'announcements'));
        window.history.pushState({}, '', 'support_center?tab=' + tabName);
    }
}


function openModal(id) { document.getElementById(id).style.display = 'block'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function toggleDropdown(event, id) {
    event.stopPropagation();
    document.querySelectorAll('.dropdown-content').forEach(d => { if(d.id !== id) d.style.display = 'none'; });
    const drop = document.getElementById(id);
    drop.style.display = (drop.style.display === 'block') ? 'none' : 'block';
}

window.onclick = function(event) {
    if (!event.target.closest('.options-menu')) { document.querySelectorAll('.dropdown-content').forEach(d => d.style.display = 'none'); }
    if (event.target.className === 'modal') { event.target.style.display = 'none'; }
}


function openEditAnn(data) {
    document.getElementById('ann_id').value = data.id;
    document.getElementById('ann_title').value = data.title;
    document.getElementById('ann_msg').value = data.message;
    openModal('modalAnn');
}

function openEditNews(data) {
    document.getElementById('news_id').value = data.id;
    document.getElementById('news_title').value = data.title;
    document.getElementById('news_date').value = data.event_date;
    document.getElementById('news_content').value = data.content;
    openModal('modalNews');
}

function openEditServ(data) {
    document.getElementById('serv_id').value = data.id;
    document.getElementById('serv_title').value = data.title;
    document.getElementById('serv_cat').value = data.category || '';
    document.getElementById('serv_prov').value = data.provider || '';
    document.getElementById('serv_stat').value = data.status;
    document.getElementById('serv_desc').value = data.description;
    document.getElementById('serv_loc').value = data.location || '';
    document.getElementById('serv_con').value = data.contact || '';
    document.getElementById('serv_sch').value = data.schedule || '';
    openModal('modalServ');
}

/**
 * INITIAL TAB LOAD BASED ON URL
 */
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');

    if (activeTab === 'services') {
        openTab('tab-serv', false);
    } else if (activeTab === 'news') {
        openTab('tab-news', false);
    } else if (activeTab === 'requests') { 
        openTab('tab-req', false);
    } else {
        openTab('tab-ann', false);
    }
});
</script>
</body>
</html>