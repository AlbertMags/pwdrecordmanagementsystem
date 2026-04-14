<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../db_connect.php");

if (!isset($_SESSION['related_pwd_id'])) {
    echo "Access Denied. Please log in.";
    exit();
}

// Fetch the specific user's name from myusers table
$pwd_id = $_SESSION['related_pwd_id'];
$user_query = $conn->query("SELECT full_name FROM myusers WHERE related_pwd_id = '$pwd_id' LIMIT 1");
$user_data = $user_query->fetch_assoc();
$display_name = ($user_data) ? $user_data['full_name'] : "Member";

// Fetch Data
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
$services_query = $conn->query("SELECT * FROM services WHERE status = 'Active' ORDER BY created_at DESC");
$news_query = $conn->query("SELECT * FROM news ORDER BY event_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --ann-blue: #3498db;
            --serv-green: #2ecc71;
            --news-orange: #e67e22;
            --text-dark: #1a3a5f;
            --sub-text: #7f8c8d;
        }

        body { 
            background-color: #f8f9fa; 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            overflow-x: hidden; 
        }

        /* --- TOP NAVIGATION (Original Widths) --- */
        .top-nav {
            background: #fff; 
            display: flex; 
            align-items: center;
            padding: 0 40px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            position: fixed; 
            top: 0; 
            left: 250px; 
            width: calc(100% - 250px); 
            z-index: 1000;
            height: 70px;
            box-sizing: border-box;
        }

        .nav-brand-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

       
        .nav-text-stack h1 { margin: 0; color: var(--text-dark); font-size: 20px; font-weight: 700; }
         
        .nav-sub { 
            font-size: 16px; 
            color: #4b4848; 
            font-weight: normal; 
            margin: 0;
            line-height: 1.2;
        }
        /* --- MAXIMIZED CONTAINER (Original Widths) --- */
        .dashboard-container { 
            padding: 100px 2% 40px 2%; 
            width: 100%; 
            box-sizing: border-box;
        }

        .dashboard-section { margin-bottom: 60px; text-align: center; width: 100%; }

        .section-header { 
            font-size: 1.8rem; font-weight: 800; color: var(--text-dark); 
            margin-bottom: 30px; display: flex; align-items: center; justify-content: center; gap: 12px; 
        }

        .card-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px; 
            margin-bottom: 30px;
        }

        /* --- CARD STYLES --- */
        .ann-card, .serv-card, .news-card {
            background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex; flex-direction: column; transition: 0.3s;
        }
        .ann-card { padding: 25px; text-align: left; border-bottom: 6px solid var(--ann-blue); min-height: 280px; }
        .serv-card { padding: 30px 20px; text-align: center; border-bottom: 6px solid var(--serv-green); }
        .news-card { padding: 0; overflow: hidden; text-align: left; border-bottom: 6px solid var(--news-orange); }

        .card-img-top { width: 100%; height: 180px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; }
        .news-card .card-img-top { border-radius: 0; margin-bottom: 0; } 
        
        .news-content { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }

        .db-date { font-size: 0.85rem; color: var(--sub-text); font-weight: 600; margin-bottom: 8px; display: block; }
        .db-title { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 10px; }
        .db-desc { font-size: 0.9rem; color: #555; line-height: 1.5; margin-bottom: 15px; }
        
        .db-link { 
            font-weight: 700; font-size: 0.9rem; text-decoration: none; 
            margin-top: auto; cursor: pointer; display: inline-block; color: inherit;
        }

        .btn-pill {
            padding: 12px 40px; border-radius: 50px; border: none;
            color: white; font-weight: 700; font-size: 1rem; cursor: pointer;
            transition: 0.3s ease; display: inline-block;
        }
        .bg-ann { background-color: var(--ann-blue); }
        .bg-serv { background-color: var(--serv-green); }
        .bg-news { background-color: var(--news-orange); }

        /* --- MODAL STYLES --- */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 2000; justify-content: center; align-items: center;
        }
        .modal-card {
            background: #fff; width: 90%; max-width: 800px; max-height: 85vh;
            border-radius: 15px; position: relative; overflow-y: auto; padding: 40px;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .close-modal { position: absolute; top: 20px; right: 25px; font-size: 30px; cursor: pointer; color: #999; }
        .modal-body img { width: 100%; max-height: 450px; object-fit: contain; border-radius: 10px; margin-bottom: 20px; background: #f9f9f9; }
        .modal-body h2 { color: var(--text-dark); margin-top: 0; font-size: 2rem; }
        
        /* Modal Info Grid Style */
        .modal-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .modal-info-item { font-size: 0.95rem; color: #555; }
        .modal-info-item i { color: var(--serv-green); margin-right: 8px; width: 20px; }

        .hidden-item { display: none; }

        /* --- RESPONSIVE FIXES --- */
        @media (max-width: 1024px) {
            .top-nav { left: 0; width: 100%; }
            .card-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 600px) {
            .card-grid { grid-template-columns: 1fr; }
            .top-nav { padding: 0 15px; }
            .nav-text-stack h1 { font-size: 16px; }
            .nav-sub { display: none; }
            .modal-info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div id="infoModal" class="modal-overlay">
    <div class="modal-card">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div id="modalContent" class="modal-body"></div>
    </div>
</div>

<header class="top-nav">
    <div class="nav-brand-wrapper">
       
        <div class="nav-text-stack">
            <h1>Member Dashboard Hub</h1>
            <p class="nav-sub">Welcome, <?= htmlspecialchars($display_name) ?> | PWD Community Portal Information Center</p>
        </div>
    </div>
</header>

<div class="dashboard-container">

    <section class="dashboard-section" id="sec-ann">
        <h2 class="section-header"><i class="fas fa-bullhorn" style="color: var(--ann-blue);"></i> Latest Announcements</h2>
        <div class="card-grid">
            <?php 
            $count = 0;
            while($a = $announcements->fetch_assoc()): 
                $count++;
                $hideClass = ($count > 4) ? 'hidden-item' : '';
                $imgSrc = !empty($a['image']) ? '../uploads/announcements/'.$a['image'] : '';
            ?>
            <div class="ann-card <?= $hideClass ?>">
                <span class="db-date"><?= date('F d, Y', strtotime($a['created_at'])) ?></span>
                <h3 class="db-title"><?= htmlspecialchars($a['title']) ?></h3>
                <?php if($imgSrc): ?>
                    <img src="<?= $imgSrc ?>" class="card-img-top">
                <?php endif; ?>
                <p class="db-desc"><?= mb_strimwidth(strip_tags($a['message']), 0, 140, "...") ?></p>
                <a class="db-link" style="color: var(--ann-blue);" 
                    onclick="openModal('<?= addslashes($a['title']) ?>', '<?= addslashes($a['message']) ?>', '<?= $imgSrc ?>', '<?= date('F d, Y', strtotime($a['created_at'])) ?>')">
                    Click to read more
                </a>
            </div>
            <?php endwhile; ?>
        </div>
        <?php if($count > 4): ?>
            <button class="btn-pill bg-ann" onclick="toggleDashboard('sec-ann')">See All Announcements +</button>
        <?php endif; ?>
    </section>

    <section class="dashboard-section" id="sec-serv">
        <h2 class="section-header"><i class="fas fa-hand-holding-heart" style="color: var(--serv-green);"></i> Available Services</h2>
        <div class="card-grid">
            <?php 
            $count = 0;
            while($s = $services_query->fetch_assoc()): 
                $count++;
                $hideClass = ($count > 4) ? 'hidden-item' : '';
            ?>
            <div class="serv-card <?= $hideClass ?>">
               <h3 class="db-title"><?= htmlspecialchars($s['title']) ?></h3>
                <p class="db-desc"><?= mb_strimwidth($s['description'], 0, 110, "...") ?></p>
                <a class="db-link" style="color: var(--serv-green);"
                   onclick="openModal('<?= addslashes($s['title']) ?>', '<?= addslashes($s['description']) ?>', '', 'Active Service', '<?= addslashes($s['provider']) ?>', '<?= addslashes($s['location']) ?>', '<?= addslashes($s['contact']) ?>', '<?= addslashes($s['schedule']) ?>')">
                    View Details <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
        <?php if($count > 4): ?>
            <button class="btn-pill bg-serv" onclick="toggleDashboard('sec-serv')">See All Services +</button>
        <?php endif; ?>
    </section>

    <section class="dashboard-section" id="sec-news">
        <h2 class="section-header"><i class="fas fa-newspaper" style="color: var(--news-orange);"></i> News & Activities</h2>
        <div class="card-grid">
            <?php 
            $count = 0;
            while($n = $news_query->fetch_assoc()): 
                $count++;
                $hideClass = ($count > 4) ? 'hidden-item' : '';
                $imgSrcNews = !empty($n['image']) ? '../uploads/news/'.$n['image'] : '';
            ?>
            <div class="news-card <?= $hideClass ?>">
                <?php if($imgSrcNews): ?>
                    <img src="<?= $imgSrcNews ?>" class="card-img-top">
                <?php else: ?>
                    <div class="card-img-top" style="background:#f1f3f5; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-image fa-3x" style="color:#ccc;"></i>
                    </div>
                <?php endif; ?>
                <div class="news-content">
                    <span class="db-date" style="color: var(--news-orange);"><?= date('M d, Y', strtotime($n['event_date'])) ?></span>
                    <h3 class="db-title"><?= htmlspecialchars($n['title']) ?></h3>
                    <p class="db-desc"><?= mb_strimwidth(strip_tags($n['content']), 0, 110, "...") ?></p>
                    <a class="db-link" style="color: var(--news-orange);"
                       onclick="openModal('<?= addslashes($n['title']) ?>', '<?= addslashes($n['content']) ?>', '<?= $imgSrcNews ?>', '<?= date('M d, Y', strtotime($n['event_date'])) ?>')">
                        Read Story <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php if($count > 4): ?>
            <button class="btn-pill bg-news" onclick="toggleDashboard('sec-news')">See All Activities +</button>
        <?php endif; ?>
    </section>

</div>

<script>
function toggleDashboard(sectionId) {
    const section = document.getElementById(sectionId);
    const hiddenItems = section.querySelectorAll('.hidden-item');
    const btn = section.querySelector('.btn-pill');
    
    if (btn.innerText.includes('+')) {
        hiddenItems.forEach(item => {
            $(item).removeClass('hidden-item').addClass('was-hidden').hide().fadeIn(500);
        });
        btn.innerHTML = btn.innerText.replace('See All', 'Hide').replace('+', '-');
    } else {
        const itemsToHide = section.querySelectorAll('.was-hidden');
        itemsToHide.forEach(item => {
            $(item).fadeOut(400, function() {
                $(this).addClass('hidden-item').removeClass('was-hidden');
            });
        });
        btn.innerHTML = btn.innerText.replace('Hide', 'See All').replace('-', '+');
        $('html, body').animate({ scrollTop: $(section).offset().top - 120 }, 600);
    }
}

function openModal(title, text, imgSrc, date, provider = '', location = '', contact = '', schedule = '') {
    let imgHtml = imgSrc ? `<img src="${imgSrc}" onerror="this.style.display='none'">` : '';
    
    // Build detail grid if it's a service
    let detailsHtml = '';
    if(provider || location || contact || schedule) {
        detailsHtml = `
            <div style="font-weight: 600; color: #555; margin: 10px 0;">Provider: ${provider || 'N/A'}</div>
            <div class="modal-info-grid">
                <div class="modal-info-item"><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> ${location || 'Not Specified'}</div>
                <div class="modal-info-item"><i class="fas fa-phone"></i> <strong>Contact:</strong> ${contact || 'No Contact Provided'}</div>
                <div class="modal-info-item"><i class="fas fa-clock"></i> <strong>Schedule:</strong> ${schedule || 'Contact for Details'}</div>
            </div>
        `;
    }

    document.getElementById('modalContent').innerHTML = `
        ${imgHtml}
        <div style="margin-top: 10px;">
            <span class="db-date">${date}</span>
            <h2>${title}</h2>
            <div style="line-height: 1.8; color: #444; font-size: 1.1rem; text-align: justify;">${text.replace(/\n/g, '<br>')}</div>
            ${detailsHtml}
        </div>
    `;
    document.getElementById('infoModal').style.display = 'flex';
    document.body.style.overflow = 'hidden'; 
}

function closeModal() {
    document.getElementById('infoModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('infoModal')) {
        closeModal();
    }
}
</script>

</body>
</html>