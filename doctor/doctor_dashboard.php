<?php
// Since we are included in index.php, $conn and session are already available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$dr_id = $_SESSION['user_id'];

// 1. Count people waiting in the queue
$screening_count = $conn->query("SELECT COUNT(*) total FROM pwd WHERE status = 'Screening'")->fetch_assoc()['total'];

// 2. Count TOTAL records validated by this specific doctor
$stmt = $conn->prepare("SELECT COUNT(*) total FROM pwd WHERE validated_by_id = ?");
$stmt->bind_param("i", $dr_id);
$stmt->execute();
$total_done = $stmt->get_result()->fetch_assoc()['total'];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
    /* --- SHARED NAVIGATION STYLING --- */
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
        height: 60px;
        box-sizing: border-box;
    }

    .nav-brand-wrapper {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .top-nav h1 { 
        margin: 0; 
        color: #1a3a5f; 
        font-size: 20px; 
        font-weight: 700; 
    }

    .nav-sub { 
        font-size: 13px; 
        color: #6c757d; 
        font-weight: normal; 
        margin: 0;
        line-height: 1.2;
    }

    /* --- DASHBOARD CONTAINER ADJUSTMENT --- */
    .dashboard-container { 
        padding: 80px 40px 20px 40px; 
        background: #f8f9fa; 
        min-height: 100vh; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        width: 100%;
        box-sizing: border-box;
    }

    .dashboard-content-wrapper {
        max-width: 100%; 
        margin: 0 auto;
    }
    
    .report-btn { 
        background: #0056b3; 
        color: white !important; 
        padding: 8px 16px; 
        border: none; 
        border-radius: 6px; 
        font-weight: 600; 
        font-size: 14px;
        cursor: pointer !important; 
        transition: 0.3s;
        display: flex; 
        align-items: center; 
        gap: 8px;
    }

    .report-btn:hover {
        background: #004494;
    }
    
    /* --- COMPACT STAT CARDS --- */
    .stat-card {
        flex: 1; 
        border-left: 6px solid #ffc107; 
        padding: 20px 30px; 
        border-radius: 10px; 
        background: #fff; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: transform 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .stat-card:hover {
        transform: translateY(-3px);
    }

    .stat-card h2 {
        font-size: 2.8rem; 
        margin: 5px 0; 
        font-weight: 800;
    }

    .stat-card p {
        margin-bottom: 0;
    }

    /* --- OVERVIEW SECTION --- */
    .overview-section {
        background: #fff; 
        padding: 25px 40px; 
        border-radius: 12px; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
        width: 100%; 
        box-sizing: border-box;
    }

    /* --- MODAL --- */
    .custom-modal-overlay { 
        display: none; position: fixed; z-index: 10001; left: 0; top: 0; 
        width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); 
    }
    .custom-modal-content { 
        background-color: #fff; 
        margin: 2% auto; 
        border-radius: 12px; 
        width: 95%; 
        height: 90vh; 
        overflow: hidden; 
    }
    .modal-header-custom { 
        background: #17a2b8; color: white; 
        display: flex; justify-content: space-between; align-items: center; 
        padding: 12px 25px; 
    }
    .close-btn { background: transparent; border: none; color: white; font-size: 24px; cursor: pointer; }
</style>

<header class="top-nav">
    <div class="nav-brand-wrapper">
        <div class="nav-text-stack">
            <h1>Medical Officer Hub</h1>
            <p class="nav-sub">Dr. <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Officer'); ?> | Municipality Medical Portal</p>
        </div>
    </div>

    <button type="button" class="report-btn" onclick="openReportModal()">
        <i class="fas fa-file-medical-alt"></i> Monthly Report
    </button>
</header>

<div class="dashboard-container">
    <div class="dashboard-content-wrapper">
        
        <div style="display: flex; gap: 25px; margin-bottom: 25px;">
            <div class="stat-card" style="border-left-color: #07a0e2;">
                <p style="color: #6c757d; font-weight: 700; font-size: 0.85rem; text-transform: uppercase;">Waiting for Screening</p>
                <h2 style="color: #07a0e2;"><?php echo $screening_count; ?></h2>
                <a href="medical_screening" style="color: #07a0e2; text-decoration: none; font-weight: 700; font-size: 0.9rem;">Go to List →</a>
            </div>

            <div class="stat-card" style="border-left-color: #28a745;">
                <p style="color: #6c757d; font-weight: 700; font-size: 0.85rem; text-transform: uppercase;">Total Validated Records</p>
                <h2 style="color: #28a745;"><?php echo $total_done; ?></h2>
                <a href="validated_list" style="color: #28a745; text-decoration: none; font-weight: 700; font-size: 0.9rem;">View History →</a>
            </div>
        </div>

        <div class="overview-section">
            <div style="margin-bottom: 20px; border-bottom: 1px solid #f1f3f5; padding-bottom: 10px;">
                <h3 style="margin: 0; color: #1a3a5f; font-size: 1.3rem; font-weight: 700;">Municipality Disability Overview</h3>
            </div>
            
            <div class="charts-container" style="width: 100%;">
                <?php 
                    if (file_exists("../graphical_reports.php")) { 
                        include("../graphical_reports.php"); 
                    } 
                ?>
            </div>
        </div>

    </div>
</div>

<div id="reportModal" class="custom-modal-overlay">
  <div class="custom-modal-content">
    <div class="modal-header-custom">
      <h2 style="margin: 0; font-size: 1.1rem; color: #fff !important;"><i class="fas fa-chart-pie"></i> Monthly Statistical Report</h2>
      <button type="button" class="close-btn" onclick="closeReportModal()">&times;</button>
    </div>
    <div style="height: calc(100% - 50px); width: 100%;">
      <iframe src="doctor_monthly.php" style="width: 100%; height: 100%; border: none;"></iframe>
    </div>
  </div>
</div>

<script>
function openReportModal() {
    document.getElementById("reportModal").style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeReportModal() {
    document.getElementById("reportModal").style.display = "none";
    document.body.style.overflow = "auto";
}

window.onclick = function(event) {
    var modal = document.getElementById("reportModal");
    if (event.target == modal) { closeReportModal(); }
}
</script>