<?php
include("../db_connect.php");

// Ensure the session is started and brgy_id is available
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$brgy_id = $_SESSION['barangay_id'];

// --- COUNTS FOR TOP BOXES (Filtered by Barangay) ---
$total_pwds = $conn->query("SELECT COUNT(*) total FROM pwd WHERE status='Official' AND barangay_id = '$brgy_id'")->fetch_assoc()['total'];
$pending_pwds = $conn->query("SELECT COUNT(*) total FROM pwd WHERE barangay_id = '$brgy_id' AND (status='Pending' OR status='Screening' OR status='For Approval')")->fetch_assoc()['total'];

// --- GRAPH DATA QUERIES (Filtered by Barangay) ---
$gender_query = "SELECT gender, COUNT(*) AS count FROM pwd WHERE status = 'Official' AND barangay_id = '$brgy_id' GROUP BY gender";
$gender_result = $conn->query($gender_query);
$gender_data = [];
while ($row = $gender_result->fetch_assoc()) { $gender_data[] = $row; }

$disabilityQuery = "SELECT d.disability_name, COUNT(*) AS count FROM pwd p 
                    JOIN disability_type d ON p.disability_type = d.id 
                    WHERE p.status = 'Official' AND p.barangay_id = '$brgy_id' GROUP BY p.disability_type";
$disabilityResult = $conn->query($disabilityQuery);
$disability_data = [];
while ($row = $disabilityResult->fetch_assoc()) { $disability_data[] = $row; }

$age_groups = ["0–17" => 0, "18–30" => 0, "31–45" => 0, "46–60" => 0, "61+" => 0];
$age_res = $conn->query("SELECT birth_date FROM pwd WHERE status = 'Official' AND barangay_id = '$brgy_id'");
$current_year = date('Y');
while ($row = $age_res->fetch_assoc()) {
    $age = $current_year - date('Y', strtotime($row['birth_date']));
    if ($age <= 17) $age_groups["0–17"]++;
    elseif ($age <= 30) $age_groups["18–30"]++;
    elseif ($age <= 45) $age_groups["31–45"]++;
    elseif ($age <= 60) $age_groups["46–60"]++;
    else $age_groups["61+"]++;
}

// Updated base path to match your actual folder: localhost/PWD/brgy_data/
$base_url = "/PWD/brgy_data/";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * { box-sizing: border-box; }
        
        body, html { 
            background-color: #e9ecef; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; padding: 0; width: 100%; overflow-x: hidden;
        }

        /* --- UNIFIED NAVBAR STYLING --- */
        .top-nav {
            background: #fff; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 0 40px; 
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

        .nav-right-wrapper {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-search-container {
            width: 200px;
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

        /* --- CONTENT WRAPPER --- */
        .dashboard-wrapper { 
            padding: 100px 25px 25px 25px; 
            width: 100%; 
        }

        .content-card {
            background: #fff; border-radius: 12px; padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%;
        }

        /* --- INFO BOXES --- */
        .info-boxes { display: flex; gap: 20px; margin-bottom: 40px; flex-wrap: wrap; }
        .info-box { 
            flex: 1; color: #fff; padding: 30px; border-radius: 12px; cursor: pointer; 
            display: flex; justify-content: space-between; align-items: center;
            min-width: 320px; transition: transform 0.2s;
        }
        .bg-blue { background: #3498db; }
        .bg-teal { background: #3498db; }
        .info-box:hover { transform: scale(1.02); }
        .info-box .inner h3 { margin: 0; font-size: 18px; font-weight: 600; }
        .info-box .inner .count { font-size: 42px; font-weight: bold; margin-top: 10px; }
        .info-box .icon { font-size: 60px; opacity: 0.3; }

        /* --- GRAPHS SECTION --- */
        .graph-title {
            text-align: left; font-size: 22px; font-weight: bold; margin-bottom: 25px;
            color: #1a3a5f; border-bottom: 2px solid #f1f1f1; padding-bottom: 12px;
        }

        .graph-grid { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 35px; 
        }

        .chart-item { 
            border: 1px solid #f1f1f1; 
            padding: 25px; 
            border-radius: 12px; 
            background: #fafafa; 
        }

        .chart-item.center-item {
            grid-column: 1 / span 2; 
            width: 50%;               
            margin: 0 auto;           
        }

        canvas { max-height: 280px !important; width: 100% !important; }

        @media (max-width: 992px) { 
            .top-nav { left: 0; width: 100%; }
            .graph-grid { grid-template-columns: 1fr; } 
            .chart-item.center-item { grid-column: auto; width: 100%; }
        }

        /* --- NOTIFICATION UI --- */
        .notif-wrapper { position: relative; }
        #notif-btn { background: none; border: none; font-size: 24px; cursor: pointer; color:#07a0e2 ; display: flex; align-items: center;}
        .notif-badge {
            position: absolute; top: -5px; right: -5px; background: #ff0000; color: #fff;
            font-size: 10px; padding: 2px 6px; border-radius: 50%; font-weight: bold;
        }
        .notif-dropdown {
            position: absolute; right: 0; top: 45px; width: 450px; background: #fff;
            border: 1px solid #ddd; border-radius: 10px; display: none; z-index: 1000;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.2); overflow: hidden;
        }
        .notif-header { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; 
        }
        #notif-list { list-style: none; margin: 0; padding: 0; max-height: 450px; overflow-y: auto; }
        #notif-list li { 
            padding: 15px 20px; border-bottom: 1px solid #f1f1f1; 
            font-size: 14px; color: #444; cursor: pointer;
            text-align: left; line-height: 1.5; transition: background 0.2s;
        }
        #notif-list li:hover { background: #f0f7ff; }
        #notif-list li.unread { background-color: #fdf2f2; font-weight: bold; }
        #notif-list li small { display: block; margin-top: 5px; color: #888; }
    </style>
</head>
<body>

    <nav class="top-nav">
        <div class="nav-brand-wrapper">
            <div class="nav-text-stack">
                <h1>Barangay Dashboard Hub</h1>
                <p class="nav-sub">Real-time PWD Statistics Overview</p>
            </div>
        </div>

        <div class="nav-right-wrapper">
            <div class="nav-search-container">
                <form onsubmit="event.preventDefault(); executeBrgySearch();" style="position: relative; margin: 0;">
                    <input type="text" id="brgySearchInput" placeholder="Search name..." class="search-input">
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
                        <h3 style="margin:0;">Notifications</h3>
                        <span id="close-notif" style="cursor:pointer; font-size:24px;">&times;</span>
                    </div>
                    <ul id="notif-list"></ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <main class="content-card">
            
            <div class="info-boxes">
                <div class="info-box bg-blue" onclick="location.href='<?= $base_url ?>brgy_official_list';">
                    <div class="inner">
                        <h3>Total Official PWDs</h3>
                        <div class="count"><?= number_format($total_pwds) ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-wheelchair"></i></div>
                </div>

                <div class="info-box bg-teal" onclick="location.href='<?= $base_url ?>brgy_pending';">
                    <div class="inner">
                        <h3>In Progress / For Approval</h3>
                        <div class="count"><?= number_format($pending_pwds) ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>

            <div class="graph-title">Barangay Graphical Report</div>
            
            <div class="graph-grid">
                <div class="chart-item">
                    <h4 style="text-align:center;">Gender Distribution</h4>
                    <canvas id="genderChart"></canvas>
                </div>
                <div class="chart-item">
                    <h4 style="text-align:center;">Disability Type</h4>
                    <canvas id="disabilityChart"></canvas>
                </div>
                
                <div class="chart-item center-item">
                    <h4 style="text-align:center;">Age Distribution</h4>
                    <canvas id="ageChart"></canvas>
                </div>
            </div>
        </main>
    </div>

<script>
// --- CLEAN URL SEARCH LOGIC FIXED ---
function executeBrgySearch() {
    const query = document.getElementById('brgySearchInput').value.trim();
    if (query !== "") {
        // Absolute path prevents doubling the /search/ segment
        window.location.href = "/PWD/brgy_data/search/" + encodeURIComponent(query);
    }
}

// --- NOTIFICATION LOGIC ---
function loadNotifications(){
    $.getJSON("brgy_notification.php", function(data){
        let unreadCount = 0; 
        $("#notif-list").empty();
        if(data && data.length){
            data.forEach(n => {
                let statusClass = (n.status === 'unread') ? 'unread' : '';
                if(n.status === 'unread') unreadCount++;
                $("#notif-list").append(`
                    <li class="${statusClass}" onclick="handleNotifClick(${n.id}, '${n.redirect_link}')">
                        ${n.message}
                        <small>${n.created_at}</small>
                    </li>
                `);
            });
        } else {
            $("#notif-list").append("<li style='text-align:center; padding:15px;'>No notifications</li>");
        }
        unreadCount ? $("#notif-count").text(unreadCount).show() : $("#notif-count").hide();
    });
}

function handleNotifClick(id, link) {
    $.post("brgy_notification.php", {id: id}, function() { window.location.href = link; });
}

$("#notif-btn").click(function(e){ e.stopPropagation(); $("#notif-dropdown").fadeToggle(200); });
$("#close-notif").click(function(){ $("#notif-dropdown").fadeOut(200); });
$(document).click(function(){ $("#notif-dropdown").fadeOut(200); });

loadNotifications();
setInterval(loadNotifications, 10000); 

// --- CHARTS ---
const commonOptions = { 
    responsive: true, maintainAspectRatio: false, 
    plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } } } 
};

new Chart(document.getElementById('genderChart'), { 
    type: 'pie', 
    data: { 
        labels: <?= json_encode(array_column($gender_data, 'gender')) ?>, 
        datasets: [{ data: <?= json_encode(array_column($gender_data, 'count')) ?>, backgroundColor: ['#36A2EB', '#FF6384'] }] 
    }, 
    options: commonOptions 
});

new Chart(document.getElementById('disabilityChart'), { 
    type: 'bar', 
    data: { 
        labels: <?= json_encode(array_column($disability_data, 'disability_name')) ?>, 
        datasets: [{ label: 'Count', data: <?= json_encode(array_column($disability_data, 'count')) ?>, backgroundColor: '#f45c0a' }] 
    }, 
    options: { ...commonOptions, indexAxis: 'y', plugins: { legend: { display: false } } } 
});

new Chart(document.getElementById('ageChart'), { 
    type: 'bar', 
    data: { 
        labels: <?= json_encode(array_keys($age_groups)) ?>, 
        datasets: [{ label: 'Count', data: <?= json_encode(array_values($age_groups)) ?>, backgroundColor: '#05f545' }] 
    }, 
    options: { ...commonOptions, plugins: { legend: { display: false } } } 
});
</script>

</body>
</html>