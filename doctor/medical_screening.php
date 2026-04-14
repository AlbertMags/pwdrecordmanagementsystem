<?php
// Fetch applicants in 'Screening' status
$query = "SELECT p.*, d.disability_name, b.brgy_name 
          FROM pwd p 
          JOIN disability_type d ON p.disability_type = d.id 
          JOIN barangay b ON p.barangay_id = b.id 
          WHERE p.status = 'Screening'
          ORDER BY p.last_name ASC, p.first_name ASC";
$result = $conn->query($query);
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
/* --- INTEGRATED NAVBAR STYLING --- */
.top-nav {
    background: #fff; 
    display: flex; 
    justify-content: flex-start; 
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
        .clickable-name:hover { 
        color: #0056b3 !important; 
        text-decoration: underline !important; 
    }

/* --- DASHBOARD WRAPPER --- */
.dashboard-wrapper { 
    padding: 100px 40px 40px 40px; 
    width: 100%; 
}
.content-card {
    background: #fff; border-radius: 12px; padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%;
}

/* --- MODAL STYLES --- */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); overflow-y: auto; }
.modal-content { background: #f4f4f4; margin: 2% auto; padding: 0; border: 1px solid #888; width: 80%; max-width: 1000px; border-radius: 8px; }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; padding: 10px 20px; cursor: pointer; }
.close:hover { color: black; }

/* STICK TO LEFT ALIGNMENT ONLY */
.main-table th, .main-table td {
    text-align: left !important;
}
</style>

<header class="top-nav">
    <div class="nav-brand-wrapper">
       
        <div class="nav-text-stack">
            <h1>Medical Screening Queue</h1>
            <p class="nav-sub">Verified records for registered PWDs.</p>
        </div>
    </div>
</header>

<div class="dashboard-wrapper">
    <main class="content-card">
        <table class="main-table" style="width:100%; background:white; border-collapse: collapse;">
                <thead>
                <tr style="text-align: left; border-bottom: 2px solid #eee;">
                    <th style="padding: 10px;">Name</th>
                    <th style="padding: 10px;">Disability</th>
                    <th style="padding: 10px;">Barangay</th>
                    <th style="padding: 10px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                       <td style="padding: 10px; text-align: left;">
    <a href="javascript:void(0)" 
       onclick="openViewModal(<?= $row['id'] ?>)" 
       class="clickable-name" 
       style="color: #000000; text-decoration: none; font-weight: bold;">
        <?= htmlspecialchars($row['last_name'] . ", " . $row['first_name']) ?>
    </a>
</td>
                        <td style="padding: 10px; text-align: left;"><?= $row['disability_name'] ?></td>
                        <td style="padding: 10px; text-align: left;"><?= $row['brgy_name'] ?></td>
                        <td style="padding: 10px; text-align: left;">
                            <button onclick="openAssessmentModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?>')" 
                                    style="background:#0056b3; color:white; border:none; padding:8px 15px; cursor:pointer; border-radius:4px;">
                                Examine
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<div id="viewModal" class="modal">
    <div class="modal-content" style="width: 90%; max-width: 1100px; height: 90vh;">
        <span class="close" onclick="closeViewModal()">&times;</span>
        <iframe id="viewFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
    </div>
</div>

<div id="assessmentModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modalBody">
            <?php include('examine_pwd.php'); ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // This bridge detects when the iframe content loads
    $('#viewFrame').on('load', function() {
        var iframe = $(this).contents();
        // Finds the "Back to List" button inside view_pwd.php and hooks it to closeViewModal
        iframe.find('button:contains("Back"), a:contains("Back")').on('click', function(e) {
            e.preventDefault();
            closeViewModal();
        });
    });
});

function openViewModal(id) {
    document.getElementById('viewFrame').src = "../view_pwd.php?id=" + id;
    document.getElementById('viewModal').style.display = "block";
}
function closeViewModal() {
    document.getElementById('viewModal').style.display = "none";
    document.getElementById('viewFrame').src = "";
}

function openAssessmentModal(id, name) {
    document.getElementById('pwd_id_input').value = id;
    document.getElementById('applicant_name_display').innerText = name;
    document.getElementById('assessmentModal').style.display = "block";
    
    // Also hook the Examine back button
    setTimeout(() => {
        $('#modalBody button:contains("Back"), #modalBody a:contains("Back")').on('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }, 100);
}
function closeModal() {
    document.getElementById('assessmentModal').style.display = "none";
}
</script>