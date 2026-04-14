<?php
include("db_connect.php");

// --- COUNTS FOR TOP BOXES ---
$total_barangays = $conn->query("SELECT COUNT(*) total FROM barangay")->fetch_assoc()['total'];
$total_barangay_users = $conn->query("SELECT COUNT(*) total FROM myusers WHERE role='barangay_admin'")->fetch_assoc()['total'];
$total_pwds = $conn->query("SELECT COUNT(*) total FROM pwd WHERE status='Official'")->fetch_assoc()['total'];

// --- GRAPH DATA QUERIES ---
$gender_query = "SELECT gender, COUNT(*) AS count FROM pwd WHERE status = 'Official' GROUP BY gender";
$gender_result = $conn->query($gender_query);
$gender_data = [];
while ($row = $gender_result->fetch_assoc()) { $gender_data[] = $row; }

$disabilityQuery = "SELECT d.disability_name, COUNT(*) AS count FROM pwd p 
                    JOIN disability_type d ON p.disability_type = d.id 
                    WHERE p.status = 'Official' GROUP BY p.disability_type";
$disabilityResult = $conn->query($disabilityQuery);
$disability_data = [];
while ($row = $disabilityResult->fetch_assoc()) { $disability_data[] = $row; }

$barangayQuery = "SELECT b.brgy_name AS barangay, COUNT(*) AS count FROM pwd p 
                  JOIN barangay b ON p.barangay_id = b.id 
                  WHERE p.status = 'Official' GROUP BY b.id ORDER BY b.brgy_name ASC";
$barangayResult = $conn->query($barangayQuery);
$barangay_data = [];
while ($row = $barangayResult->fetch_assoc()) { $barangay_data[] = $row; }

$age_groups = ["0–17" => 0, "18–30" => 0, "31–45" => 0, "46–60" => 0, "61+" => 0];
$age_res = $conn->query("SELECT birth_date FROM pwd WHERE status = 'Official'");
$current_year = date('Y');
while ($row = $age_res->fetch_assoc()) {
    $age = $current_year - date('Y', strtotime($row['birth_date']));
    if ($age <= 17) $age_groups["0–17"]++;
    elseif ($age <= 30) $age_groups["18–30"]++;
    elseif ($age <= 45) $age_groups["31–45"]++;
    elseif ($age <= 60) $age_groups["46–60"]++;
    else $age_groups["61+"]++;
}

// FIXED: Updated Base URL for clean links (Removed /backup/)
$base_url = "/PWD/";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Information Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* --- CORE STYLING --- */
        * { box-sizing: border-box; }
        
        body, html { 
            background-color: #e9ecef; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; padding: 0; width: 100%; height: 100%;
        }

         /* --- UNIFIED NAVBAR STYLING --- */
        .top-nav {
            background: #fff; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 40px 40px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            position: fixed; 
            top: 0; 
            left: 250px; 
            width: calc(100% - 250px); 
            z-index: 1000;
            height: 70px;
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

        .nav-right-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-search-container {
            width: 100%;
            max-width: 300px;
        }

        .search-input {
            width: 100%; 
            padding: 8px 40px 8px 15px; 
            border-radius: 20px; 
            border: 1px solid #ddd; 
            font-size: 13px; 
            outline: none; 
            background: #f8f9fa;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .search-input:focus {
            border-color: #3498db; 
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
            background: #fff;
        }
        
        .dashboard-wrapper { 
            padding: 90px 25px 25px 25px; 
            width: 100%; 
        }

        .content-card {
            background: #fff; border-radius: 12px; padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%;
        }

        /* --- NOTIFICATION UI --- */
        .notif-wrapper { position: relative; }
        #notif-btn { 
            background: none; border: none; font-size: 24px; 
            cursor: pointer; color: #07a0e2 ; display: flex; align-items: center;
        }
        .notif-badge {
            position: absolute; top: -2px; right: -2px;
            background: #ff0000; color: #ffffff; font-size: 11px;
            padding: 3px 7px; border-radius: 50%; font-weight: bold;
            display: none; 
        }
        
        .notif-dropdown {
            position: absolute; right: 0; top: 55px; width: 450px; 
            background: #fff; border: 1px solid #ddd; border-radius: 10px;
            display: none; z-index: 2000; box-shadow: 0px 10px 25px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .notif-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #eee;
        }
        .notif-header h3 { margin: 0; font-size: 16px; color: #333; }
        .close-notif { font-size: 24px; cursor: pointer; color: #999; line-height: 1; }

        #notif-list { list-style: none; margin: 0; padding: 0; max-height: 450px; overflow-y: auto; }
        #notif-list li {
            padding: 15px 20px; border-bottom: 1px solid #f1f1f1;
            font-size: 14px; color: #444; cursor: pointer;
            transition: background 0.2s; text-align: left; line-height: 1.4;
        }
        #notif-list li:hover { background: #f0f7ff; }
        #notif-list li.unread { background-color: #fdf2f2; font-weight: bold; }

        /* --- INFO BOXES --- */
        .info-boxes { 
            display: flex; 
            gap: 20px; 
            margin-bottom: 40px; 
            justify-content: space-between; 
        }
        .info-box { 
            flex: 1; 
            padding: 25px; 
            border-radius: 12px; 
            cursor: pointer; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            min-width: 200px; 
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .info-box:hover { transform: scale(1.03); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .info-box .inner h3 { margin: 0; font-size: 16px; font-weight: 600; text-align: left; }
        .info-box .inner .count { font-size: 36px; font-weight: bold; margin-top: 5px; text-align: left; }
        .info-box .icon { font-size: 50px; opacity: 0.3; }

        /* --- GRAPHS --- */
        .graph-title {
            text-align: left; font-size: 22px; font-weight: bold; 
            margin-bottom: 25px; color: #1a3a5f; border-bottom: 2px solid #f1f1f1; 
            padding-bottom: 12px;
        }
        .graph-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 35px; }
        .chart-item { border: 1px solid #f1f1f1; padding: 25px; border-radius: 12px; background: #fafafa; text-align: left; }
        .chart-item h4 { margin-top: 0; margin-bottom: 15px; }
        canvas { max-height: 300px !important; width: 100% !important; }

        @media (max-width: 992px) { 
            .top-nav { left: 0; width: 100%; padding: 0 20px; }
            .dashboard-wrapper { padding-left: 25px; }
            .info-boxes { flex-direction: column; }
            .graph-grid { grid-template-columns: 1fr; } 
            .nav-search-container { max-width: 200px; }
        }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-brand-wrapper">
            <div class="nav-text-stack">
                <h1>Information Hub</h1>
                <p class="nav-sub">Monitor and manage PWD information </p>
            </div>
        </div>

        <div class="nav-right-actions">
            <div class="nav-search-container">
                <form onsubmit="event.preventDefault(); executeSearch();" style="position: relative; margin: 0;">
                    <input type="text" id="topNavSearch" placeholder="Search name..." class="search-input">
                    <button type="submit" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #666;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <div class="notif-wrapper">
                <button id="notif-btn">
                    <i class="fas fa-bell"></i>
                    <span id="notif-count" class="notif-badge">0</span>
                </button>
                <div class="notif-dropdown" id="notif-dropdown">
                    <div class="notif-header">
                        <h3 style="margin:0;">Notifications (Main Admin)</h3>
                        <span class="close-notif" id="close-notif" style="cursor:pointer; font-size:24px;">&times;</span>
                    </div>
                    <ul id="notif-list"></ul>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <main class="content-card">
            <div class="info-boxes">
                <div class="info-box" onclick="location.href='<?= $base_url ?>barangay';">
                    <div class="inner">
                        <h3>Total Barangay</h3>
                        <div class="count"><?= number_format($total_barangays) ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-home"></i></div>
                </div>

                <div class="info-box" onclick="location.href='<?= $base_url ?>barangay_user';">
                    <div class="inner">
                        <h3>Total Barangay Users</h3>
                        <div class="count"><?= number_format($total_barangay_users) ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-user-shield"></i></div>
                </div>

                <div class="info-box" onclick="location.href='<?= $base_url ?>official_list';">
                    <div class="inner">
                        <h3>Total PWDs</h3>
                        <div class="count"><?= number_format($total_pwds) ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-wheelchair"></i></div>
                </div>
            </div>

            <div class="graph-title">Graphical Reports</div>
            <div class="graph-grid">
                <div class="chart-item"><h4>Gender Distribution</h4><canvas id="genderChart"></canvas></div>
                <div class="chart-item"><h4>Disability Type</h4><canvas id="disabilityChart"></canvas></div>
                <div class="chart-item"><h4>Barangay Distribution</h4><canvas id="barangayChart"></canvas></div>
                <div class="chart-item"><h4>Age Distribution</h4><canvas id="ageChart"></canvas></div>
            </div>
        </main>
    </div>

<script>
// --- CLEAN URL SEARCH LOGIC ---
function executeSearch() {
    const query = document.getElementById('topNavSearch').value.trim();
    if (query !== "") {
        // Updated to use the corrected base_url
        window.location.href = "<?= $base_url ?>search/" + encodeURIComponent(query);
    }
}

// --- CHARTS ---
const commonOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } } } };
new Chart(document.getElementById('genderChart'), { type: 'pie', data: { labels: <?= json_encode(array_column($gender_data, 'gender')) ?>, datasets: [{ data: <?= json_encode(array_column($gender_data, 'count')) ?>, backgroundColor: ['#36A2EB', '#FF6384'] }] }, options: commonOptions });
new Chart(document.getElementById('disabilityChart'), { type: 'bar', data: { labels: <?= json_encode(array_column($disability_data, 'disability_name')) ?>, datasets: [{ label: 'Count', data: <?= json_encode(array_column($disability_data, 'count')) ?>, backgroundColor: '#f45c0a' }] }, options: { ...commonOptions, indexAxis: 'y', plugins: { legend: { display: false } } } });
new Chart(document.getElementById('barangayChart'), { type: 'bar', data: { labels: <?= json_encode(array_column($barangay_data, 'barangay')) ?>, datasets: [{ label: 'Count', data: <?= json_encode(array_column($barangay_data, 'count')) ?>, backgroundColor: '#17a2b8' }] }, options: { ...commonOptions, plugins: { legend: { display: false } } } });
new Chart(document.getElementById('ageChart'), { type: 'bar', data: { labels: <?= json_encode(array_keys($age_groups)) ?>, datasets: [{ label: 'Count', data: <?= json_encode(array_values($age_groups)) ?>, backgroundColor: '#05f545' }] }, options: { ...commonOptions, plugins: { legend: { display: false } } } });

// --- NOTIFICATIONS ---
function handleNotificationClick(id, link) {
    $.post("notification.php", { mark_read_id: id }, function(response) {
        window.location.href = link;
    }, 'json');
}

$("#notif-btn").click(function(e){ e.stopPropagation(); $("#notif-dropdown").fadeToggle(200); });
$("#close-notif").click(function(){ $("#notif-dropdown").fadeOut(200); });
$(document).click(function(){ $("#notif-dropdown").fadeOut(200); });
$("#notif-dropdown").click(function(e){ e.stopPropagation(); });

function loadNotifications(){
    $.ajax({
        url: "notification.php",
        method: "GET",
        dataType: "json",
        cache: false,
        success: function(data){
            let unreadCount = 0; 
            $("#notif-list").empty();
            if(data && data.length){
                data.forEach(n => {
                    if(parseInt(n.read_by_admin) === 0) { unreadCount++; var statusClass = 'unread'; } else { var statusClass = ''; }
                    let displayName = (n.brgy_name && n.brgy_name !== 'System') ? `<strong>${n.brgy_name}:</strong> ` : "";
                    $("#notif-list").append(`<li class="${statusClass}" onclick="handleNotificationClick(${n.id}, '${n.redirect_link}')">${displayName}${n.message}<br><small style="color:#999;">${n.time_ago || n.created_at}</small></li>`);
                });
            } else { $("#notif-list").append("<li style='text-align:center; padding: 20px;'>No notifications</li>"); }
            if(unreadCount > 0) { $("#notif-count").text(unreadCount).css('display', 'inline-block'); } else { $("#notif-count").hide(); }
        },
        error: function(err) { console.error("Notification load failed", err); }
    });
}
loadNotifications();
setInterval(loadNotifications, 10000); 
</script>
</body>
</html>