<?php
// Include database connection since this is now loaded in an iframe
include("../db_connect.php"); 

if (!isset($_GET['id'])) {
    echo "No applicant ID provided.";
    exit;
}

$id = $_GET['id'];

// Fetch full details including joins for the PWD record
$query = "SELECT p.*, d.disability_name, b.brgy_name 
          FROM pwd p 
          LEFT JOIN disability_type d ON p.disability_type = d.id 
          LEFT JOIN barangay b ON p.barangay_id = b.id 
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo "Record not found.";
    exit;
}

// FIXED HELPER FUNCTION
function is_checked($value, $saved_string) {
    if (empty($saved_string) || $saved_string == 'None') return "";
    return (stripos($saved_string, trim($value)) !== false) ? "checked='checked'" : "";
}
?>

<style>
    /* --- SCREEN VIEW STYLING --- */
    .view-outer-wrapper { 
        background: #f4f4f4; 
        padding: 20px; 
        min-height: 100vh; 
    }
    
    .btn-print { 
        background: #1a5c20; 
        color: white; 
        padding: 12px 25px; 
        border: none; 
        border-radius: 4px; 
        cursor: pointer; 
        margin-bottom: 20px; 
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-print:hover { background: #144518; }

    /* Hide print button if loaded in the Admin Modal */
    body.in-modal .btn-print { display: none; }

    .doh-sheet { 
        background: white; 
        width: 8.5in; 
        min-height: 11in; 
        margin: 0 auto; 
        padding: 40px; 
        box-shadow: 0 0 15px rgba(0,0,0,0.2); 
        font-family: Arial, sans-serif; 
        color: #000;
        position: relative;
    }

    /* --- PRINT STYLING --- */
    @media print {
        html, body { 
            height: 11in; 
            overflow: hidden !important; 
            margin: 0 !important; 
            padding: 0 !important;
        }
        body * { visibility: hidden; }
        .btn-print { display: none !important; }
        .view-outer-wrapper, .doh-sheet, .doh-sheet * { 
            visibility: visible !important; 
        }
        .view-outer-wrapper { padding: 0 !important; margin: 0 !important; background: white !important; }
        .doh-sheet { 
            position: absolute; 
            left: 0; 
            top: 0; 
            width: 8.5in; 
            height: 11in;
            box-shadow: none !important; 
            border: none !important; 
            padding: 0.3in !important;
        }
        @page { size: auto; margin: 0; }
    }

    .doh-header { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
    .doh-logo { width: 90px; margin-right: 25px; }
    .header-info { flex: 1; text-align: center; }
    .section-title { font-weight: bold; font-size: 18px; text-align: center; margin: 15px 0; text-transform: uppercase; border: 1px solid #000; padding: 5px; }
    .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    .data-label { font-weight: bold; text-decoration: underline; font-style: italic; display: block; margin: 12px 0 5px 0; font-size: 14px; }
    .check-row { display: flex; align-items: center; font-size: 12px; margin-bottom: 4px; }
    .check-row input { margin-right: 8px; transform: scale(1.1); pointer-events: none; }
    .footer-section { border-top: 2px solid #000; margin-top: 25px; padding-top: 15px; }
    .remark-box { border: 1px solid #000; padding: 10px; min-height: 80px; margin: 10px 0; font-size: 13px; line-height: 1.4; }
    .sig-area { margin-top: 30px; text-align: center; }
    .sig-line-val { font-weight: bold; text-transform: uppercase; font-size: 15px; border-bottom: 1px solid #000; display: inline-block; width: 300px; padding-bottom: 2px; }
</style>

<div class="view-outer-wrapper">
    <button class="btn-print" onclick="window.print()">
        PRINT OFFICIAL ASSESSMENT
    </button>

    <div class="doh-sheet">
        <div class="doh-header">
            <img src="../uploads/doh.png" class="doh-logo" alt="Logo">
            <div class="header-info">
                <p style="margin:0; font-size: 16px;">Republic of the Philippines</p>
                <p style="margin:0; font-size: 18px; font-weight:bold;">DEPARTMENT OF HEALTH</p>
                <p style="margin:0; font-size: 14px;">Philippine Registry For Persons with Disabilities Ver 4.0</p>
            </div>
        </div>

        <div class="section-title">Functional Assessment Record</div>

        <div style="margin-bottom: 20px; font-size: 15px;">
            <p><strong>Applicant Name:</strong> <?php echo htmlspecialchars($data['last_name'] . ", " . $data['first_name'] . " " . $data['middle_name']); ?></p>
            <p><strong>Assessed Disability Type:</strong> <?php echo htmlspecialchars($data['functional_assessments'] != 'None' ? "Physical/Mobility Impairment" : $data['disability_name']); ?></p>
        </div>

        <div class="data-grid">
            <div>
                <span class="data-label">Musculoskeletal & Mobility</span>
                <?php 
                $full_musculo = ["001 Weak, paralyzed left leg", "002 Weak, paralyzed right leg", "003 Underdeveloped left leg", "004 Underdeveloped right leg", "005 Underdeveloped both legs", "006 Missing left leg", "007 Missing right leg", "008 Missing both legs", "009 Missing left foot", "010 Missing right foot", "011 Missing both feet", "012 Missing, paralyzed left arm", "013 Missing, paralyzed right arm", "014 Missing, paralyzed both arm", "015 Underdeveloped left arm", "016 Underdeveloped right arm", "017 Underdeveloped both arm", "018 Missing left arm", "019 Missing right arm", "020 Missing both arms", "021 Missing left hand", "022 Missing right hand", "023 Missing both hands", "024 Polio"];
                foreach($full_musculo as $item) {
                    $checked = is_checked($item, $data['functional_assessments']);
                    echo "<div class='check-row'><input type='checkbox' $checked> $item</div>";
                }
                ?>

                <span class="data-label">Motor Disability</span>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Cerebral Palsy", $data['motor_disability']); ?>> 001 Cerebral Palsy</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Stroke", $data['motor_disability']); ?>> 002 Stroke</div>

              </div>

            <div>
                <span class="data-label">Visual Impairment</span>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Total visual left", $data['visual_impairment']); ?>> 001 Total, left</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Total visual right", $data['visual_impairment']); ?>> 002 Total, right</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Total visual both", $data['visual_impairment']); ?>> 003 Total, both</div>

                <span class="data-label">Hearing Impairment</span>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Total hearing both", $data['hearing_impairment']); ?>> 003 Total hearing, both</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Partial hearing both", $data['hearing_impairment']); ?>> 006 Partial hearing, both</div>

                <span class="data-label">Speech & Communication</span>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Total speech", $data['speech_impairment']); ?>> 001 Total impairment</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Partial speech impairment (Unclear)", $data['speech_impairment']); ?>> 002 Partial (Unclear)</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Partial speech impairment (Irrelevant)", $data['speech_impairment']); ?>> 003 Partial (Irrelevant)</div>

                <span class="data-label">Mental Impairment</span>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Mentally Ill", $data['mental_impairment']); ?>> 001 Mentally Ill</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Mentally Retarded", $data['mental_impairment']); ?>> 002 Mentally Retarded</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Autistic", $data['mental_impairment']); ?>> 003 Autistic</div>

                   <span class="data-label">Deformities</span>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Hunchback", $data['deformity_details']); ?>> 001 Hunchback</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Cleft palate", $data['deformity_details']); ?>> 002 Cleft palate</div>
           
            
                <span class="data-label">Etiology</span>
                <div class='check-row'><input type='checkbox' <?php echo ($data['assessment_etiology'] == 'Inborn') ? "checked" : ""; ?>> 001 Inborn</div>
                <div class='check-row'><input type='checkbox' <?php echo ($data['assessment_etiology'] == 'Acquired') ? "checked" : ""; ?>> 002 Acquired: <strong><?php echo htmlspecialchars($data['etiology_details']); ?></strong></div>

                <span class="data-label">Assistive Devices Needed</span>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Wheelchair", $data['assistive_devices']); ?>> 001 Wheelchair</div>
                <div class='check-row'><input type='checkbox' <?php echo is_checked("Quad cane", $data['assistive_devices']); ?>> 003 Quad Cane</div>
                <div class='check-row'>Other: <strong><?php echo htmlspecialchars($data['assistive_devices_other']); ?></strong></div>
            </div>
        </div>

        <div class="footer-section">
            <strong>FINAL DIAGNOSIS AND CLINICAL IMPRESSION:</strong>
            <div class="remark-box"><?php echo nl2br(htmlspecialchars($data['diagnosis'] ?? 'No remarks provided.')); ?></div>

            <div class="sig-area">
                <span class="sig-line-val"><?php echo htmlspecialchars($data['physician_name'] ?? '___________________________'); ?></span><br>
                <strong style="font-size: 14px;">Examining Health Physician</strong><br>
                <div style="margin-top: 10px; font-size: 14px;">
                    <span>PRC License No: <strong><?php echo htmlspecialchars($data['physician_license'] ?? 'N/A'); ?></strong></span> | 
                    <span>PTR No: <strong><?php echo htmlspecialchars($data['physician_ptr'] ?? 'N/A'); ?></strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if (window.self !== window.top) {
        document.body.classList.add('in-modal');
    }
</script>