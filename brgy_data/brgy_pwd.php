<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../db_connect.php");

// 1. Get current Barangay ID from session
if (!isset($_SESSION['barangay_id'])) {
    echo "Access Denied. Please log in as a Barangay User.";
    exit();
}
$brgy_id = $_SESSION['barangay_id'];

// Catch the status from the URL
$url_status = isset($_GET['status']) ? $_GET['status'] : 'Pending';

// Fetch PWDs ONLY for this barangay AND NOT yet Accepted
// Alphabetical sorting by Last Name then First Name
$query = "SELECT pwd.*, disability_type.disability_name AS disability_name,
        TIMESTAMPDIFF(YEAR, pwd.birth_date, CURDATE()) AS age 
        FROM pwd 
        JOIN disability_type ON pwd.disability_type = disability_type.id
        WHERE pwd.barangay_id = ? AND pwd.status != 'Accepted'
        ORDER BY pwd.last_name ASC, pwd.first_name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $brgy_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch Brgy Name
$brgy_stmt = $conn->prepare("SELECT brgy_name FROM barangay WHERE id = ?");
$brgy_stmt->bind_param("i", $brgy_id);
$brgy_stmt->execute();
$brgy_row = $brgy_stmt->get_result()->fetch_assoc();
$display_brgy_name = $brgy_row['brgy_name'] ?? "Unknown";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay PWD Applications</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* --- INTEGRATED MAIN SYSTEM STYLING --- */
        body, html { 
            background-color: #e9ecef; 
            margin: 0; padding: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

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
        /* --- DASHBOARD WRAPPER --- */
        .dashboard-wrapper { padding: 100px 25px 25px 25px; width: 100%; }
        .content-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }

      :root { --doh-border: 1px solid #000; }
        .clickable-name { color: #070707; text-decoration: none; font-weight: bold; cursor: pointer; }
        .clickable-name:hover { text-decoration: underline; color: #0056b3; }
        .edit-btn { background-color: #07a0e2; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; color: white; margin-right: 5px; }
        .delete-btn { background-color: #dc3545; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; color: white; }
        .status-lock { color: #888; font-style: italic; font-size: 0.85em; }
 table td:nth-child(2), 
        table td:nth-child(4) {
            text-align: left !important;
            padding-left: 15px;
        }
        .pwd-modal-backdrop { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.6); 
        }
        .pwd-form-container {
            background: #fff; 
            margin: 2vh auto; 
            width: 95%; 
            max-width: 1100px; 
            height: 94vh; 
            padding: 0; 
            border-radius: 8px; 
            position: relative; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            overflow-y: auto; 
            box-sizing: border-box;
        }
 
            .barangay-header {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        .button-barangay {
            background: #0056b3; 
            color: white; 
            border: none; 
            padding: 12px 20px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 160px;
            white-space: nowrap;
            font-size: 15px;
            transition: background 0.3s;
        }

        .close-modal-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            color: #333;
            cursor: pointer;
            z-index: 2000;
            line-height: 1;
        }
        .close-modal-btn:hover { color: #000000; }
        .doh-form-wrapper { padding: 20px; }
        .doh-header-container { text-align: center; position: relative; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .doh-logo-left { position: absolute; left: 0; top: 0; width: 80px; }
        .header-text h2 { margin: 5px 0; text-transform: uppercase; font-size: 20px; }
        .header-text p { margin: 2px 0; font-size: 13px; }

        .form-row { display: flex; gap: 15px; margin-bottom: 12px; align-items: flex-end; }
        .form-row div { flex: 1; }
        .form-row label { display: block; font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #333; }
        
        .form-row input, .form-row select, .name-group input { 
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; 
            font-size: 13px; box-sizing: border-box; background-color: #f9f9f9;
        }
        .doh-form-wrapper h3 {
            background: #f0f0f0; padding: 8px; font-size: 13px; 
            border-left: 4px solid #1a5c20; margin: 20px 0 10px 0; text-transform: uppercase;
            border-radius: 0 4px 4px 0;
        }
        .form-content-wrapper { display: flex; gap: 20px; align-items: flex-start; }
        .form-fields-side { flex: 1; }
        .photo-sidebar { width: 130px; display: flex; flex-direction: column; align-items: center; }
        .photo-box {
            width: 120px; height: 120px; border: 2px dashed #ccc; 
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: #fff; font-size: 10px; text-align: center; margin-bottom: 8px; border-radius: 4px;
        }
        .disability-split-container { display: flex; border: 1px solid #ddd; border-radius: 8px; margin-top: 10px; background: #fff; }
        .split-left { flex: 1.5; border-right: 1px solid #ddd; padding: 15px; }
        .split-right { flex: 1; padding: 15px; }
        .disability-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .disability-item { font-size: 11px; display: flex; align-items: center; gap: 8px; padding: 5px; }
        .name-group { display: flex; gap: 8px; }
        .sub-cause-item { font-size: 11px; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .parent-label { font-weight: bold; font-size: 11px; grid-column: span 2; margin-top: 5px; color: #1a5c20; border-bottom: 1px solid #eee; }

        #viewFrame { width: 100%; height: 1600px; border: none; }
    
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-brand-wrapper">
            
            <div class="nav-text-stack">
                <h1>PWD Applications</h1>
                <p class="nav-sub">Manage and Review Records for Barangay <?php echo htmlspecialchars($display_brgy_name); ?></p>
            </div>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <main class="content-card">
            
            <div class="barangay-header" style="margin-bottom: 20px;">
                <button class="button-barangay" id="openAddModal">
                    <i class="fas fa-plus"></i> Add PWD 
                </button>
            </div>

            <div class="filters" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; border: 1px solid #eee;">
             
            
            <div style="display: flex; align-items: center; gap: 8px;">
                    <label style="font-weight: 600; font-size: 14px;">Search:</label>
                    <input type="text" id="nameSearch" placeholder="Type a name..." style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 200px;">
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <label style="font-weight: 600; font-size: 14px;">Disability:</label>
                    <select id="disabilityFilter" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="">All Types</option>
                        <?php
                        $main_filter = $conn->query("SELECT * FROM disability_type WHERE parent_id IS NULL ORDER BY disability_name ASC");
                        while ($mf = $main_filter->fetch_assoc()) {
                            $sub_filter = $conn->query("SELECT * FROM disability_type WHERE parent_id = {$mf['id']} ORDER BY disability_name ASC");
                            if ($sub_filter->num_rows > 0) {
                                echo "<option value='' disabled style='font-weight:bold; color:black;'>-- " . htmlspecialchars($mf['disability_name']) . " --</option>";
                                while ($sf = $sub_filter->fetch_assoc()) {
                                    echo "<option value='{$sf['id']}'>&nbsp;&nbsp;" . htmlspecialchars($sf['disability_name']) . "</option>";
                                }
                            } else {
                                echo "<option value='{$mf['id']}'>" . htmlspecialchars($mf['disability_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <label style="font-weight: 600; font-size: 14px;">Filter by Status:</label>
                    <select id="statusFilter" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="Pending" <?php echo ($url_status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Screening" <?php echo ($url_status == 'Screening') ? 'selected' : ''; ?>>Screening</option>
                        <option value="For Approval" <?php echo ($url_status == 'For Approval') ? 'selected' : ''; ?>>For Approval</option>
                    </select>
                </div>
            </div>


            
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Age</th>
            <th>Disability</th>
            <th>Address</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="pwdTableBody">
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr data-disability="<?php echo $row['disability_type']; ?>" data-status="<?php echo htmlspecialchars($row['status']); ?>">
            <td class="row-number"></td>
            <td>
                <span class="clickable-name" onclick="openViewModal(<?= $row['id']; ?>)">
                    <?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?>
                </span>
            </td>
            <td><?php echo $row['age']; ?></td>
            <td><?php echo htmlspecialchars($row['disability_name']); ?></td>
            <td><?php echo htmlspecialchars($row['address'] ?: 'N/A'); ?></td>
            <td class="status-cell"><?php echo htmlspecialchars($row['status']); ?></td>
            <td>
                <?php if ($row['status'] === 'Pending'): ?>
                    <button class="edit-btn" data-id="<?php echo $row['id']; ?>">Edit</button>
                    <button class="delete-btn" data-id="<?php echo $row['id']; ?>">Delete</button>
                <?php else: ?>
                    <span class="status-lock">🔒 Changes Locked</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<div class="pwd-modal-backdrop" id="addPWDModal">
    <div class="pwd-form-container">
        <span class="close-modal-btn" onclick="closeAddModal()">&times;</span>
        
        <div class="doh-form-wrapper">
            <div class="doh-header-container">
                <img src="../uploads/doh.png" class="doh-logo-left" alt="DOH Logo">
                <div class="header-text">
                    <p style="font-weight:bold;font-size:15px;">DEPARTMENT OF HEALTH</p>
                    <p>Philippine Registry For Persons with Disabilities Version 4.0</p>
                    <h2 id="modalTitle">Application Form</h2>
                </div>
            </div>
            <form id="pwdRegistrationForm" enctype="multipart/form-data">
                <input type="hidden" name="pwd_id" id="pwd_id">
                <input type="hidden" name="barangay_id" value="<?= $brgy_id ?>">

                <div class="form-content-wrapper">
                    <div class="form-fields-side">
                        <div class="form-row">
                            <div style="flex:1.5"><label>1. New or Renewal:</label>
                                <select name="new_applicant_or_renewal" id="new_applicant_or_renewal" required>
                                    <option value="New">New Applicant</option>
                                    <option value="Renewal">Renewal</option>
                                </select>
                            </div>
                            <div style="flex:2"><label>2. PWD Number:</label>
                                <input type="text" name="pwd_number" id="pwd_number" placeholder="RR-PPMM-BBB-NNNNNNN">
                            </div>
                            <div style="flex:1.5"><label>3. Date Applied:</label>
                                <input type="date" name="date_applied" id="date_applied" required>
                            </div>
                        </div>
                        <h3>4. PERSONAL INFORMATION</h3>
                        <div class="form-row">
                            <div><label>LAST NAME:</label><input type="text" name="last_name" id="last_name" required></div>
                            <div><label>FIRST NAME:</label><input type="text" name="first_name" id="first_name" required></div>
                            <div><label>MIDDLE NAME:</label><input type="text" name="middle_name" id="middle_name"></div>
                            <div style="flex:0.4;"><label>SUFFIX:</label><input type="text" name="suffix" id="suffix"></div>
                        </div>
                    </div>
                    <div class="photo-sidebar">
                        <div class="photo-box" id="profile_preview"><p>Photo</p></div>
                        <input type="file" name="profile_picture" id="profile_picture" style="font-size: 10px; width: 120px;">
                    </div>
                </div>
                <div class="form-row">
                    <div><label>5. DATE OF BIRTH:</label><input type="date" name="birth_date" id="birth_date" required></div>
                    <div><label>6. SEX:</label>
                        <select name="gender" id="gender" required><option value="Male">Male</option><option value="Female">Female</option></select>
                    </div>
                    <div><label>7. CIVIL STATUS:</label>
                        <select name="civil_status" id="civil_status" required>
                            <option value="Single">Single</option><option value="Married">Married</option><option value="Widowed">Widowed</option><option value="Separated">Separated</option><option value="Cohabitation">Cohabitation</option>
                        </select>
                    </div>
                </div>
                <div class="disability-split-container">
                    <div class="split-left">
                        <label>8. TYPE OF DISABILITY:</label>
                        <div class="disability-grid">
                            <?php 
                            $modal_m = $conn->query("SELECT * FROM disability_type WHERE parent_id IS NULL ORDER BY disability_name ASC");
                            while ($m = $modal_m->fetch_assoc()) { 
                                $modal_s = $conn->query("SELECT * FROM disability_type WHERE parent_id = {$m['id']} ORDER BY disability_name ASC"); 
                                if ($modal_s->num_rows > 0) {
                                    echo "<div class='parent-label'>".htmlspecialchars($m['disability_name'])."</div>";
                                    while ($s = $modal_s->fetch_assoc()) { 
                                        echo "<div class='disability-item'>
                                                <input type='radio' name='disability_type' value='{$s['id']}' required> " 
                                                . htmlspecialchars($s['disability_name']) . 
                                             "</div>";
                                    }
                                } else {
                                    echo "<div class='disability-item'>
                                            <input type='radio' name='disability_type' value='{$m['id']}' required> " 
                                            . htmlspecialchars($m['disability_name']) . 
                                         "</div>";
                                }
                            } ?>
                        </div>
                    </div>
                    <div class="split-right">
                        <label>9. CAUSE OF DISABILITY:</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px;">
                            <div>
                                <p style="font-size: 10px; font-weight: bold; border-bottom: 1px solid #000; margin: 0 0 5px 0;">Congenital/Inborn</p>
                                <div class="sub-cause-list">
                                    <label class="sub-cause-item"><input type="radio" name="disability_cause" value="Autism"> Autism</label>
                                    <label class="sub-cause-item"><input type="radio" name="disability_cause" value="ADHD"> ADHD</label>
                                    <label class="sub-cause-item"><input type="radio" name="disability_cause" value="Cerebral Palsy (Congenital)"> Cerebral Palsy</label>
                                    <label class="sub-cause-item"><input type="radio" name="disability_cause" value="Down Syndrome"> Down Syndrome</label>
                                </div>
                            </div>
                            <div>
                                <p style="font-size: 10px; font-weight: bold; border-bottom: 1px solid #000; margin: 0 0 5px 0;">Acquired</p>
                                <div class="sub-cause-list">
                                    <label class="sub-cause-item"><input type="radio" name="disability_cause" value="Chronic Illness"> Chronic Illness</label>
                                    <label class="sub-cause-item"><input type="radio" name="disability_cause" value="Cerebral Palsy (Acquired)"> Cerebral Palsy</label>
                                    <label class="sub-cause-item"><input type="radio" name="disability_cause" value="Injury"> Injury</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <h3>10. RESIDENCE ADDRESS</h3>
                <div class="form-row">
                    <div style="flex: 2;"><label>HOUSE NO. AND STREET :</label><input type="text" name="address" id="address" required></div>
                    <div><label>Barangay:</label>
                        <input type="text" value="<?= htmlspecialchars($display_brgy_name) ?>" readonly style="background:#f4f4f4;">
                    </div>
                </div>
                <div class="form-row">
                    <div><label>MUNICIPALITY:</label><input type="text" name="municipality" id="municipality" value="EB Magalona" readonly></div>
                    <div><label>PROVINCE:</label><input type="text" name="province" id="province" value="Negros Occidental" readonly></div>
                    <div><label>REGION:</label><input type="text" name="region" id="region" value="Region VI" readonly></div>
                </div>
                <h3>11. CONTACT DETAILS</h3>
                <div class="form-row">
                    <div><label>MOBILE NO:</label><input type="text" name="contact_number" id="contact_number" required></div>
                    <div><label>E-MAIL ADDRESS:</label><input type="email" name="email" id="email"></div>
                </div>
                <h3>12-14. EDUCATION & EMPLOYMENT</h3>
                <div class="form-row">
                    <div><label>EDUCATION:</label>
                        <select name="education" id="education">
                            <option value="None">None</option><option value="Kindergarten">Kindergarten</option><option value="Elementary">Elementary</option><option value="Junior High School">Junior High School</option><option value="Senior High School">Senior High School</option><option value="College">College</option><option value="Vocational">Vocational</option><option value="Post Graduate">Post Graduate</option>
                        </select>
                    </div>
                    <div><label>EMPLOYMENT STATUS:</label>
                        <select name="employment_status" id="employment_status">
                            <option value="Employed">Employed</option><option value="Unemployed">Unemployed</option><option value="Self-employed">Self-employed</option>
                        </select>
                    </div>
                    <div><label>OCCUPATION:</label><input type="text" name="occupation" id="occupation"></div>
                </div>

                <h3>15. Organization Information</h3>
                <div class="form-row">
                    <div style="flex:1.5;"><label>Organization Name:</label><input type="text" name="organization_name" id="organization_name"></div>
                    <div><label>Contact Person:</label><input type="text" name="organization_contact_person" id="organization_contact_person"></div>
                    <div><label>Tel. Nos.:</label><input type="text" name="organization_contact_number" id="organization_contact_number"></div>
                </div>

                <h3>16. Government IDs</h3>
                <div class="form-row">
                    <div><label>SSS No:</label><input type="text" name="sss_no" id="sss_no"></div>
                    <div><label>GSIS No:</label><input type="text" name="gsis_no" id="gsis_no"></div>
                    <div><label>PAG-IBIG No:</label><input type="text" name="pagibig_no" id="pagibig_no"></div>
                    <div><label>PSN No:</label><input type="text" name="psn_no" id="psn_no"></div>
                    <div><label>PhilHealth No:</label><input type="text" name="philhealth_no" id="philhealth_no"></div>
                </div>

                <h3>17. FAMILY BACKGROUND</h3>
                <div class="form-row">
                    <div style="flex:1;"><label>FATHER'S NAME (L, F, M):</label>
                        <div class="name-group">
                            <input type="text" name="father_lastname" id="father_lastname" placeholder="Last"><input type="text" name="father_firstname" id="father_firstname" placeholder="First"><input type="text" name="father_middlename" id="father_middlename" placeholder="Middle">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div style="flex:1;"><label>MOTHER'S NAME (L, F, M):</label>
                        <div class="name-group">
                            <input type="text" name="mother_lastname" id="mother_lastname" placeholder="Last"><input type="text" name="mother_firstname" id="mother_firstname" placeholder="First"><input type="text" name="mother_middlename" id="mother_middlename" placeholder="Middle">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div style="flex:1;"><label>GUARDIAN'S NAME (L, F, M):</label>
                        <div class="name-group">
                            <input type="text" name="guardian_lastname" id="guardian_lastname" placeholder="Last"><input type="text" name="guardian_firstname" id="guardian_firstname" placeholder="First"><input type="text" name="guardian_middlename" id="guardian_middlename" placeholder="Middle">
                        </div>
                    </div>
                </div> 
                <div style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-top:10px; background: #fcfcfc;">
                    <label style="font-size:11px; font-weight:bold; display:block; margin-bottom:10px;">18. ACCOMPLISHED BY:</label>
                    <div class="form-row" style="align-items: center; margin-bottom: 5px;">
                        <div style="flex:0.3; display:flex; align-items:center; gap:5px;">
                            <input type="radio" name="accomplished_by_type" value="Applicant" id="acc_app" checked>
                            <label for="acc_app" style="margin:0;">Applicant</label>
                        </div>
                        <div style="flex:0.3; display:flex; align-items:center; gap:5px;">
                            <input type="radio" name="accomplished_by_type" value="Guardian" id="acc_guard">
                            <label for="acc_guard" style="margin:0;">Guardian</label>
                        </div>
                        <div style="flex:0.3; display:flex; align-items:center; gap:5px;">
                            <input type="radio" name="accomplished_by_type" value="Representative" id="acc_rep">
                            <label for="acc_rep" style="margin:0;">Representative</label>
                        </div>
                    </div>
                    <div class="name-group">
                        <input type="text" name="acc_lastname" id="acc_lastname" placeholder="Last Name">
                        <input type="text" name="acc_firstname" id="acc_firstname" placeholder="First Name">
                        <input type="text" name="acc_middlename" id="acc_middlename" placeholder="Middle Name">
                    </div>
                </div>
                <br>
                <button type="submit" id="submitBtn" class="button-barangay" style="width:100%; padding:15px; font-size:16px; border-radius: 8px;">SUBMIT REGISTRATION</button>
            </form>
        </div>
    </div>
</div>
<div class="pwd-modal-backdrop" id="viewModal">
    <div class="pwd-form-container">
        <span class="close-modal-btn" onclick="closeViewModal()">&times;</span>
        <iframe id="viewFrame" src=""></iframe>
    </div>
</div>

<script>
// --- GLOBAL MODAL FUNCTIONS ---
function openViewModal(pwdId) {
    $("#viewFrame").attr("src", "../view_pwd.php?id=" + pwdId);
    $("#viewModal").fadeIn();
    $("body").css("overflow", "hidden");
}

function closeViewModal() {
    $("#viewModal").fadeOut();
    $("#viewFrame").attr("src", "");
    $("body").css("overflow", "auto");
}

function closeAddModal() {
    $("#addPWDModal").fadeOut();
    $("body").css("overflow", "auto");
}

$(document).ready(function () {
    // --- 1. PHOTO PREVIEW ---
    $("#profile_picture").change(function() {
        const file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(event) {
                $("#profile_preview").html('<img src="' + event.target.result + '" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">');
            };
            reader.readAsDataURL(file);
        }
    });

    // --- 2. FILTERING ---
    function applyFilters() {
        let status = $("#statusFilter").val();
        let disability = $("#disabilityFilter").val();
        let search = $("#nameSearch").val().toLowerCase();

        $("#pwdTableBody tr").each(function () {
            let rowStatus = $(this).attr("data-status");
            let rowDisability = $(this).attr("data-disability");
            let rowText = $(this).text().toLowerCase();

            let matchStatus = (status === "" || rowStatus === status);
            let matchDisability = (disability === "" || rowDisability === disability);
            let matchSearch = (rowText.indexOf(search) > -1);

            $(this).toggle(matchStatus && matchDisability && matchSearch);
        });
        renumber();
    }
    function renumber() { 
        $("#pwdTableBody tr:visible").each(function (i) { 
            $(this).find(".row-number").text(i + 1); 
        }); 
    }
    $("#statusFilter, #disabilityFilter").on("change", applyFilters);
    $("#nameSearch").on("keyup", applyFilters);
    applyFilters(); 

    // --- 3. ADD NEW RECORD ---
    $("#openAddModal").click(() => { 
        $("#pwdRegistrationForm")[0].reset(); 
        $("#pwd_id").val(""); 
        $("#profile_preview").html("<p>Photo</p>");
        $("#modalTitle").text("Application Form (New)");
        $("#addPWDModal").fadeIn(); 
        $("body").css("overflow", "hidden");
    });

    // --- 4. SUBMIT FORM ---
    $("#pwdRegistrationForm").submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let action = $("#pwd_id").val() === "" ? "brgy_add_pwd.php" : "brgy_update_pwd.php";
        
        $.ajax({
            url: action,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                alert("Operation successful!");
                location.reload();
            },
            error: function() {
                alert("Error saving record.");
            }
        });
    });
    // --- 5. EDIT ACTION ---
    $(document).on("click", ".edit-btn", function() {
        let id = $(this).data("id");
        $.ajax({
            url: "brgy_get_pwd_details.php", 
            type: "POST",
            data: {id: id},
            dataType: "json",
            success: function(res) {
                if(res.status === "success") {
                    let d = res.data;
                    $("#pwd_id").val(d.id);
                    $("#modalTitle").text("Edit Application Form");

                    // Photo
                    if(d.photo && d.photo !== "") {
                        $("#profile_preview").html('<img src="../uploads/profile_pics/' + d.photo + '" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">');
                    } else {
                        $("#profile_preview").html("<p>No Photo</p>");
                    }

                    // Basic Info
                    $("#new_applicant_or_renewal").val(d.new_applicant_or_renewal || 'New');
                    $("#pwd_number").val(d.pwd_number || '');
                    $("#date_applied").val(d.date_applied || '');
                    $("#last_name").val(d.last_name || '');
                    $("#first_name").val(d.first_name || '');
                    $("#middle_name").val(d.middle_name || '');
                    $("#suffix").val(d.suffix || '');
                    $("#birth_date").val(d.birth_date || '');
                    $("#gender").val(d.gender || 'Male');
                    $("#civil_status").val(d.civil_status || 'Single');
                    
                    // Disability Logic
                    $("input[name='disability_type']").prop("checked", false);
                    if(d.disability_type) {
                        $(`input[name='disability_type'][value='${d.disability_type}']`).prop("checked", true);
                    }
                    $("input[name='disability_cause']").prop("checked", false);
                    if(d.disability_cause) {
                        $(`input[name='disability_cause'][value='${d.disability_cause}']`).prop("checked", true);
                    }
                    // Address & Contact
                    $("#address").val(d.address || '');
                    $("#contact_number").val(d.contact_number || '');
                    $("#email").val(d.email || '');

                    // Education & Employment
                    $("#education").val(d.education || 'None');
                    $("#employment_status").val(d.employment_status || 'Unemployed');
                    $("#occupation").val(d.occupation || '');

                    // Organization
                    $("#organization_name").val(d.organization_name || '');
                    $("#organization_contact_person").val(d.organization_contact_person || '');
                    $("#organization_contact_number").val(d.organization_contact_number || '');

                    // Government IDs
                    $("#sss_no").val(d.sss_no || '');
                    $("#gsis_no").val(d.gsis_no || '');
                    $("#pagibig_no").val(d.pagibig_no || '');
                    $("#psn_no").val(d.psn_no || '');
                    $("#philhealth_no").val(d.philhealth_no || '');

                    // Family Background
                    $("#father_lastname").val(d.father_lastname || '');
                    $("#father_firstname").val(d.father_firstname || '');
                    $("#father_middlename").val(d.father_middlename || '');
                    $("#mother_lastname").val(d.mother_lastname || '');
                    $("#mother_firstname").val(d.mother_firstname || '');
                    $("#mother_middlename").val(d.mother_middlename || '');
                    $("#guardian_lastname").val(d.guardian_lastname || '');
                    $("#guardian_firstname").val(d.guardian_firstname || '');
                    $("#guardian_middlename").val(d.guardian_middlename || '');

                    // Accomplished By
                    $("input[name='accomplished_by_type']").prop("checked", false);
                    if(d.accomplished_by_type){
                        $(`input[name='accomplished_by_type'][value='${d.accomplished_by_type}']`).prop("checked", true);
                    }
                    $("#acc_lastname").val(d.acc_lastname || '');
                    $("#acc_firstname").val(d.acc_firstname || '');
                    $("#acc_middlename").val(d.acc_middlename || '');

                    $("#addPWDModal").fadeIn();
                    $("body").css("overflow", "hidden");
                }
            }
        });
    });

    // --- 6. DELETE ACTION ---
    $(document).on("click", ".delete-btn", function () {
        let id = $(this).data("id");
        if(confirm("Permanently remove this PWD record?")) {
            $.post("delete_pwd.php", {delete_id:id}, (res) => { 
                location.reload(); 
            }); 
        }
    });
});
</script>
</body>
</html>
    