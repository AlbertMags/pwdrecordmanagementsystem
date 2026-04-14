<?php
include("db_connect.php");
include("announcement.php");
include("service_logic.php");
include("news_logic.php");

// Set active tab logic
$active_tab = 'tab-ann';
if(isset($_GET['tab'])){
    if($_GET['tab'] == 'services') $active_tab = 'tab-serv';
    if($_GET['tab'] == 'news') $active_tab = 'tab-news';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Center</title>
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

        /* --- THE NAVBAR RE-FIX --- */
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
        }

        .content-card {
            background: #fff; border-radius: 12px; padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%;
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
            transition: 0.2s; 
        }
        .tab-btn.active { background: #0056b3  ; color: white; }

        .content-section { display: none; width: 100%; }
        .content-section.active { display: block; }
        
        .toggle-btn { 
            background: #07a0e2; 
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
        }
        
        .section-header { width: 100%; overflow: hidden; margin-bottom: 10px; }

        .item-card { 
            clear: both; 
            background: white; 
            border-radius: 12px; 
            padding: 25px; 
            margin-bottom: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            position: relative; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            border: 1px solid #eee; 
        }

        .announcement-card { border-left: 6px solid #07a0e2; }
        .service-card { border-left: 6px solid #2ecc71; flex-direction: column; }
        .news-card { border-left: 6px solid #e67e22; }

        /* DROPDOWN & MENU */
        .options-menu { position: absolute; top: 15px; right: 10px; z-index: 10; }
        .dots-btn { background: none; border: none; font-size: 18px; cursor: pointer; color: #888; padding: 8px; border-radius: 50%; }
        
        .dropdown-content {
            display: none; position: absolute; right: 0; top: 40px;
            background-color: white; min-width: 140px; box-shadow: 0px 8px 16px rgba(0,0,0,0.15);
            z-index: 100; border-radius: 8px; overflow: hidden; border: 1px solid #eee;
        }
        .dropdown-content button, .dropdown-content a { width: 100%; padding: 12px 15px; text-decoration: none; display: block; color: #333; font-size: 14px; text-align: left; border: none; background: none; cursor: pointer; }
        .dropdown-content button:hover, .dropdown-content a:hover { background-color: #f8f9fa; color: #3498db; }
        .dropdown-content a.del-link { color: #e74c3c; }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-bottom: 10px; }
        .badge-Active { background: #e8f8f0; color: #2ecc71; }
        .badge-Inactive { background: #fdf2f2; color: #e74c3c; }

        .info-grid { width: 100%; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; background: #f9f9f9; padding: 15px; border-radius: 8px; font-size: 14px; }

        /* FORM & MODAL */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index: 2000; overflow-y: auto; }
        .modal-content { background:#fff; padding:30px; margin:5% auto; width:600px; border-radius: 15px; position: relative; }
        .btn-action { padding: 12px 18px; border-radius: 5px; color: white; cursor: pointer; border: none; font-weight: 600; }

        @media (max-width: 992px) { 
            .top-nav { left: 0; width: 100%; }
        }
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
                <button id="btn-news" class="tab-btn <?= ($active_tab == 'tab-news') ? 'active' : '' ?>" onclick="openTab('tab-news')">News </button>
                <button id="btn-serv" class="tab-btn <?= ($active_tab == 'tab-serv') ? 'active' : '' ?>" onclick="openTab('tab-serv')">Available Services</button>
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
                    <button type="button" class="toggle-btn" style="background:#07a0e2;" onclick="openModal('addModalNews')"><i class="fas fa-newspaper"></i> Post News</button>
                </div>
                <div id="news-list">
                    <?php
                    $news_list = $conn->query("SELECT * FROM news ORDER BY event_date DESC");
                    while($n = $news_list->fetch_assoc()):
                    ?>
                        <div class="item-card news-card">
                            <div style="flex:1; padding-right:20px;">
                                <div style="font-size: 1.4rem; font-weight: 700; color: #2c3e50;"><?= htmlspecialchars($n['title']) ?></div>
                                <div style="font-size: 0.85rem; color: #e67e22; font-weight: bold; margin-bottom: 10px;"><i class="fas fa-calendar-day"></i> Event Date: <?= date(
                                    'M d, Y', strtotime($n['event_date'])) ?></div>
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
                    <button class="toggle-btn" style="background:#07a0e2;" onclick="openModal('addModalServ')"><i class="fas fa-plus"></i> Add Service</button>
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
        </main>
    </div>

    <div id="addModalAnn" class="modal"><div class="modal-content"><h3>Post New Announcement</h3><form method="POST" action="announcement.php" enctype="multipart/form-data"><input type="hidden"
     name="origin" value="support_center"><div class="form-group"><label>Title</label><input type="text" name="title" required></div><div class="form-group"><label>Message</label><textarea name=
     "message" style="height:120px;" required></textarea></div><div class="form-group"><label>Image</label><input type="file" name="image"></div><div style="display:flex; gap:10px; margin-top:20px;
     "><button type="submit" name="post_announcement" class="btn-action" style="background:#0056b3  ; flex:1;">Post</button><button type="button" onclick="closeModal('addModalAnn')" 
     class="btn-
     action" style="background:#95a5a6; flex:1;">Cancel</button></div></form></div></div>

    <div id="modalAnn" class="modal"><div class="modal-content"><h3>Edit Announcement</h3><form method="POST" action="announcement.php" enctype="multipart/form-data"><input type="hidden" name="
    origin" value="support_center"><input type="hidden" name="id" id="ann_id"><div class="form-group"><label>Title</label><input type="text" name="title" id="ann_title" required></div><div class=
    "form-group"><label>Message</label><textarea name="message" id="ann_msg" style="height:120px;" required></textarea></div><div class="form-group"><label>Change Image</label><input type="file" 
    name="image"></div><div style="display:flex; gap:10px; margin-top:20px;"><button type="submit" name="update_announcement" class="btn-action" style="background:#27ae60; flex:1;">Update</button>
    <button type="button" onclick="closeModal('modalAnn')" class="btn-action" style="background:#95a5a6; flex:1;">Cancel</button></div></form></div></div>



    <div id="addModalNews" class="modal"><div class="modal-content"><h3>Post News / Activity</h3><form method="POST" action="news_logic.php" enctype="multipart/form-data"><div class="form-group">
        <label>Headline</label><input type="text" name="title" required></div><div class="form-group"><label>Event Date</label><input type="date" name="event_date" required></div><div class="form
        -group"><label>Story Content</label><textarea name="content" style="height:120px;" required></textarea></div><div class="form-group"><label>Upload Image</label><input type="file" name=
        "image"></div><div style="display:flex; gap:10px; margin-top:20px;"><button type="submit" name="add_news" class="btn-action" style="background:#0056b3  ; flex:1;">Post News</button><button 
        type="button" onclick="closeModal('addModalNews')" class="btn-action" style="background:#95a5a6; flex:1;">Cancel</button></div></form></div></div>

    <div id="modalNews" class="modal"><div class="modal-content"><h3>Edit News Entry</h3><form method="POST" action="news_logic.php" enctype="multipart/form-data"><input type="hidden" name="id" 
    id="news_id"><div class="form-group"><label>Headline</label><input type="text" name="title" id="news_title" required></div><div class="form-group"><label>Event Date</label><input type="date" 
    name="event_date" id="news_date" required></div><div class="form-group"><label>Story Content</label><textarea name="content" id="news_content" style="height:120px;" required></textarea></div>
    <div class="form-group"><label>Change Image</label><input type="file" name="image"></div><div style="display:flex; gap:10px; margin-top:20px;"><button type="submit" name="update_news" class=
    "btn-action" style="background:#e67e22; flex:1;">Update News</button><button type="button" onclick="closeModal('modalNews')" class="btn-action" style="background:#95a5a6; flex:1;">Cancel<
    /button></div></form></div></div>



    <div id="addModalServ" class="modal"><div class="modal-content" style="width: 700px;"><h3>Add Service</h3><form method="POST" action="service_logic.php"><div class="form-grid" style=
    "display:grid; grid-template-columns:1fr 1fr; gap:15px;"><div class="form-group"><label>Title</label><input type="text" name="title" required></div><div class="form-group"><label>Category
    </label><input type="text" name="category"></div><div class="form-group"><label>Provider</label><input type="text" name="provider" required></div><div class="form-group"><label>Status</label>
    <select name="status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div></div><div class="form-group"><label>Description</label><textarea name="
    description" style="height:80px;" required></textarea></div><div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-top:10px;"><div class="form-group">
        <label>Location</label><input type="text" name="location"></div><div class="form-group"><label>Contact</label><input type="text" name="contact"></div><div class="form-group"><label>Schedule<
        /label><input type="text" name="schedule"></div></div><div style="display:flex; gap:10px; margin-top:20px;"><button type="submit" name="add_service" class="btn-action"
         style="background:#0056b3  ; flex:1;">Save</button><button type="button" onclick="closeModal('addModalServ')" class="btn-action" style="background:#95a5a6; flex:1;">Cancel
         </button></div></form></div></div>
   
   
   
    <div id="modalServ" class="modal"><div class="modal-content" style="width: 700px;"><h3>Edit Service</h3><form method="POST" action="service_logic.php"><input type="hidden" name="id" id="serv_id"
    ><div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;"><div class="form-group"><label>Title</label><input type="text" name="title" id="serv_title" required>
</div><div class="form-group"><label>Category</label><input type="text" name="category" id="serv_cat"></div><div class="form-group"><label>Provider</label><input type="text" name="provider" 
id="serv_prov" required></div><div class="form-group"><label>Status</label><select name="status" id="serv_stat"><option value="Active">Active</option><option value="Inactive">Inactive</option>
</select></div></div><div class="form-group"><label>Description</label><textarea name="description" id="serv_desc" style="height:80px;"></textarea></div><div class="form-grid" style="display:grid; 
grid-template-columns:1fr 1fr 1fr; gap:15px;"><div class="form-group"><label>Location</label><input type="text" name="location" id="serv_loc"></div><div class="form-group"><label>Contact</label>
<input type="text" name="contact" id="serv_con"></div><div class="form-group"><label>Schedule</label><input type="text" name="schedule" id="serv_sch"></div></div><div style="display:flex; gap:10px;
 margin-top:20px;"><button type="submit" name="update_service" class="btn-action" style="background:#27ae60; flex:1;">Save Changes</button><button type="button" onclick="closeModal('modalServ')"
  class="btn-action" style="background:#0056b3  ; flex:1;">Cancel</button></div></form></div></div>

<script>
function openTab(id) {
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    
    let btnId = 'btn-ann';
    if(id === 'tab-serv') btnId = 'btn-serv';
    if(id === 'tab-news') btnId = 'btn-news';
    document.getElementById(btnId).classList.add('active');
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
</script>
</body>
</html>