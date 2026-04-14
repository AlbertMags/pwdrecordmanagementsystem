<?php
    // Since this is included in index.php, $conn and session are already available.
    $dr_id = $_SESSION['user_id'];

    $query = "SELECT p.*, d.disability_name, b.brgy_name 
            FROM pwd p 
            JOIN disability_type d ON p.disability_type = d.id 
            JOIN barangay b ON p.barangay_id = b.id 
            WHERE p.validated_by_id = ? 
            ORDER BY 
                CASE WHEN p.status = 'For Approval' THEN 1 ELSE 2 END, 
                p.last_name ASC, 
                p.first_name ASC"; 

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $dr_id);
    $stmt->execute();
    $result = $stmt->get_result();
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
        text-align: left;
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

    /* --- DASHBOARD WRAPPER --- */
    .dashboard-wrapper { 
        padding: 100px 40px 40px 40px; 
        width: 100%; 
    }
    .content-card {
        background: #fff; 
        border-radius: 12px; 
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
        width: 100%;
    }

    .main-table {
        width: 100%; 
        border-collapse: collapse;
    }

    .main-table th, .main-table td {
        text-align: left !important;
        padding: 15px 10px;
        border-bottom: 1px solid #eee;
    }

    /* --- MODAL STYLES --- */
    .modal { 
        display: none; 
        position: fixed; 
        z-index: 2000; 
        left: 0; 
        top: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(0,0,0,0.6); 
        overflow-y: auto; 
    }
    .modal-content { 
        background: #fff; 
        margin: 2% auto; 
        padding: 0; 
        width: 90%; 
        max-width: 1100px; 
        border-radius: 8px; 
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 80vh;
    }
    
    /* Moved X button to be more visible */
    .close-modal { 
        color: #333; 
        position: absolute;
        right: 45px; 
        top: 20px; 
        font-size: 35px; 
        font-weight: bold; 
        cursor: pointer; 
        z-index: 2100;
    }
    .close-modal:hover { color: #ff0000; }

    /* Modal Footer - UPDATED TO CENTER BUTTONS */
    .modal-footer {
        padding: 20px 40px;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        display: flex;
        justify-content: center; /* Centered the buttons */
        gap: 15px;
        border-radius: 0 0 8px 8px;
    }
    .btn-modal {
        padding: 12px 25px; /* Increased padding for better visibility */
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        border: none;
        font-size: 14px;
    }
    .btn-back { background: #6c757d; color: white; }
    .btn-print { background:  #0056b3; color: white; }
    </style>

    <header class="top-nav">
        <div class="nav-brand-wrapper">
        
            <div class="nav-text-stack">
                <h1>My Validated Records</h1>
                <p class="nav-sub">List of all applicants you have assessed and forwarded for final approval.</p>
            </div>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <main class="content-card">
            <table class="main-table">
                <thead>
                    <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                        <th>Applicant Name</th>
                        <th>Disability</th>
                        <th>Barangay</th>
                        <th>Current Status</th>
                        <th style="text-align: center !important;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span style="font-style:normal;"><?= htmlspecialchars($row['last_name'] . ", " . $row['first_name']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['disability_name']) ?></td>
                                <td><?= htmlspecialchars($row['brgy_name']) ?></td>
                                <td>
                                    <?php 
                                        $status = $row['status'];
                                        $color = ($status == 'Official') ? '#28a745' : (($status == 'For Approval') ? '#17a2b8' : '#ffc107');
                                        echo "<span style='color: $color; font-weight: bold;'>$status</span>";
                                    ?>
                                </td>
                                <td style="text-align: center !important;">
                                    <button onclick="openAssessmentModal(<?= $row['id'] ?>)" 
                                    style="background: #007bff; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; display: inline-block;">
                                        <i class="fas fa-eye"></i> View Assessment
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>

    <div id="assessmentModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            
            <div id="modalBody" style="flex: 1; padding: 20px; overflow-y: auto;">
                <iframe id="assessmentFrame" src="" style="width: 100%; height: 70vh; border: none;"></iframe>
            </div>

            <div class="modal-footer">
                <button class="btn-modal btn-back" onclick="closeModal()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="btn-modal btn-print" onclick="printModalContent()">
                    <i class="fas fa-print"></i> PRINT OFFICIAL ASSESSMENT
                </button>
            </div>
        </div>
    </div>

    <script>
    function openAssessmentModal(id) {
        document.getElementById('assessmentFrame').src = "view_assessment.php?id=" + id;
        document.getElementById('assessmentModal').style.display = "block";
    }

    function closeModal() {
        document.getElementById('assessmentModal').style.display = "none";
        document.getElementById('assessmentFrame').src = "";
    }

    function printModalContent() {
        const frame = document.getElementById('assessmentFrame');
        if (frame.contentWindow) {
            frame.contentWindow.print();
        }
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('assessmentModal')) {
            closeModal();
        }
    }
    </script>