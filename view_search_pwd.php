<?php
include("db_connect.php");

// FIXED: Updated base URL to match your new folder location
$base_url = "/PWD/";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid request!'); window.history.back();</script>";
    exit;
}

$pwd_id = intval($_GET['id']);

// Fetch full details including joins for the PWD record
$query = "SELECT pwd.*, barangay.brgy_name, disability_type.disability_name 
          FROM pwd 
          JOIN barangay ON pwd.barangay_id = barangay.id 
          JOIN disability_type ON pwd.disability_type = disability_type.id 
          WHERE pwd.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pwd_id);
$stmt->execute();
$result = $stmt->get_result();
$pwd = $result->fetch_assoc();

if (!$pwd) {
    echo "<script>alert('PWD not found!'); window.history.back();</script>";
    exit;
}

$has_form2 = (!empty($pwd['physician_name']) || !empty($pwd['diagnosis']));

if (!function_exists('format_val')) {
    function format_val($val) {
        if (empty($val) || $val == 'None') return '<span style="color:#888; font-style:italic;">None recorded</span>';
        return nl2br(htmlspecialchars($val));
    }
}
?>

<style>
    /* Scope everything to a wrapper so it doesn't break your sidebar layout */
    .view-profile-page-wrapper {
        padding: 20px;
        width: 100%;
        box-sizing: border-box;
        background-color: #f4f7f6;
    }

    .doh-card { 
        max-width: 950px; 
        width: 100%;
        margin: 0 auto; 
        background: #fff; 
        padding: 40px; 
        border-radius: 8px; 
        box-shadow: 0 0 20px rgba(0,0,0,0.1); 
        border: 1px solid #ccc;
        font-family: 'Arial', sans-serif;
        color: #333;
    }
    
    .doh-header { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    .doh-logo { width: 80px; height: auto; margin-right: 20px; }
    .header-text { flex-grow: 1; text-align: center; padding-right: 8%;}
    .header-text p { margin: 2px 0; font-size: 14px; color: #000; }
    .header-text h2 { margin: 5px 0; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; color: #000; }

    .main-row { display: flex; gap: 20px; margin-bottom: 20px; }
    .form-content { flex: 1; }
    .photo-sidebar { width: 160px; text-align: center; }
    .photo-box { width: 150px; height: 150px; border: 2px solid #000; display: flex; align-items: center; justify-content: center; background: #fafafa; overflow: hidden; margin-bottom: 5px; }
    .photo-box img { width: 100%; height: 100%; object-fit: cover; }

    .doh-card h3 { background: #eee; padding: 8px; font-size: 14px; border: 1px solid #ddd; margin: 20px 0 10px 0; text-transform: uppercase; color: #333; font-weight: bold; text-align: left; }
    .form-row { display: flex; gap: 15px; margin-bottom: 12px; border-bottom: 1px solid #f1f1f1; padding-bottom: 8px; justify-content: flex-start; }
    .field { flex: 1; text-align: left; }
    .label { font-size: 11px; font-weight: bold; color: #666; display: block; text-transform: uppercase; margin-bottom: 3px; }
    .value { font-size: 14px; font-weight: 600; color: #000; }

    .disability-container { display: flex; gap: 20px; margin-top: 10px; }
    .disability-box { flex: 1; border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
    .selected-item { background: #e8f4fd; border: 1px solid #2196f3; padding: 5px 10px; border-radius: 4px; display: inline-block; margin-top: 5px; font-weight: bold; }

    .form2-section { margin-top: 50px; border-top: 4px double #000; padding-top: 30px; }
    .assessment-title { text-align: center; border: 2px solid #000; padding: 10px; font-weight: bold; margin-bottom: 20px; background: #f9f9f9; text-transform: uppercase; color: #000; }
    .assessment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    .diag-area { border: 1px solid #000; padding: 15px; min-height: 80px; margin-top: 5px; font-weight: bold; line-height: 1.5; color: #000; text-align: left; }

    .physician-container { margin-top: 40px; display: flex; flex-direction: column; align-items: flex-start; }
    .physician-name-line { border-bottom: 1px solid #000; min-width: 300px; display: inline-block; margin-bottom: 2px; }

    .button-group { margin-top: 40px; text-align: center; padding-bottom: 40px; }
    .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; margin: 0 5px; font-size: 14px; }
    .btn-back { background: #6c757d; color: white; border: none; }
    .btn-print { background: #28a745; color: white; border: none; }

    @media print {
        @page { size: auto; margin: 0mm; }
        html, body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
        .sidebar, .nav, .navbar, .top-bar, .left-sidebar, .main-header, footer, .button-group { display: none !important; }
        .view-profile-page-wrapper { position: absolute; left: 0; top: 0; width: 100% !important; margin: 0 !important; padding: 1.5cm !important; background: white !important; }
        .doh-card { border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .form2-section { page-break-before: always; border-top: none; padding-top: 1cm; }
    }
</style>

<div class="view-profile-page-wrapper">
    <div class="doh-card">
        <div class="doh-header">
            <img src="<?= $base_url ?>uploads/doh.png" class="doh-logo" alt="DOH Logo">
            <div class="header-text">
                <p style="font-weight:bold;">DEPARTMENT OF HEALTH</p>
                <p>Philippine Registry For Persons with Disabilities Version 4.0</p>
                <h2>Application Form 1</h2>
            </div>
        </div>

        <div class="main-row">
            <div class="form-content">
                <div class="form-row">
                    <div class="field" style="flex: 1;"><span class="label">1. New or Renewal</span><div class="value"><?php echo htmlspecialchars($pwd['new_applicant_or_renewal']); ?></div></div>
                    <div class="field" style="flex: 2;"><span class="label">2. PWD Number</span><div class="value"><?php echo htmlspecialchars($pwd['pwd_number']) ?: 'PENDING'; ?></div></div>
                    <div class="field" style="flex: 1.5;"><span class="label">3. Date Applied</span><div class="value"><?php echo date("M d, Y", strtotime($pwd['date_applied'])); ?></div></div>
                </div>

                <h3>4. Personal Information</h3>
                <div class="form-row">
                    <div class="field"><span class="label">Last Name</span><div class="value"><?php echo htmlspecialchars($pwd['last_name']); ?></div></div>
                    <div class="field"><span class="label">First Name</span><div class="value"><?php echo htmlspecialchars($pwd['first_name']); ?></div></div>
                    <div class="field"><span class="label">Middle Name</span><div class="value"><?php echo htmlspecialchars($pwd['middle_name']); ?></div></div>
                    <div class="field" style="flex:0.4;"><span class="label">Suffix</span><div class="value"><?php echo htmlspecialchars($pwd['suffix']) ?: 'N/A'; ?></div></div>
                </div>
            </div>

            <div class="photo-sidebar">
                <div class="photo-box">
                    <?php 
                        $photo_filename = basename($pwd['photo']);
                        $image_path = $base_url . "uploads/profile_pics/" . $photo_filename;
                        if (empty($pwd['photo'])) { $image_path = $base_url . "uploads/profile_pics/default.png"; }
                    ?>
                    <img src="<?php echo $image_path; ?>?t=<?php echo time(); ?>" alt="Profile Photo">
                </div>
                <span class="label">Photo</span>
            </div>
        </div>

        <div class="form-row">
            <div class="field"><span class="label">5. Date of Birth</span><div class="value"><?php echo date("M d, Y", strtotime($pwd['birth_date'])); ?></div></div>
            <div class="field"><span class="label">6. Sex</span><div class="value"><?php echo htmlspecialchars($pwd['gender']); ?></div></div>
            <div class="field"><span class="label">7. Civil Status</span><div class="value"><?php echo htmlspecialchars($pwd['civil_status']); ?></div></div>
        </div>

        <div class="disability-container">
            <div class="disability-box">
                <span class="label">8. Type of Disability</span>
                <div class="selected-item"><?php echo htmlspecialchars($pwd['disability_name']); ?></div>
            </div>
            <div class="disability-box">
                <span class="label">9. Cause of Disability</span>
                <div class="value" style="margin-top:5px;"><?php echo htmlspecialchars($pwd['disability_cause']); ?></div>
            </div>
        </div>

        <h3>10. Residence Address</h3>
        <div class="form-row">
            <div class="field" style="flex: 2;"><span class="label">House No. and Street</span><div class="value"><?php echo htmlspecialchars($pwd['address']); ?></div></div>
            <div class="field"><span class="label">Barangay</span><div class="value"><?php echo htmlspecialchars($pwd['brgy_name']); ?></div></div>
        </div>
        <div class="form-row">
            <div class="field"><span class="label">Municipality</span><div class="value">EB Magalona</div></div>
            <div class="field"><span class="label">Province</span><div class="value">Negros Occidental</div></div>
            <div class="field"><span class="label">Region</span><div class="value">Region VI</div></div>
        </div>

        <h3>11. Contact Details</h3>
        <div class="form-row">
            <div class="field"><span class="label">Mobile No.</span><div class="value"><?php echo htmlspecialchars($pwd['contact_number']); ?></div></div>
            <div class="field"><span class="label">E-mail Address</span><div class="value"><?php echo htmlspecialchars($pwd['email']) ?: 'N/A'; ?></div></div>
        </div>

        <h3>12-14. Education & Employment</h3>
        <div class="form-row">
            <div class="field"><span class="label">Education</span><div class="value"><?php echo htmlspecialchars($pwd['education']); ?></div></div>
            <div class="field"><span class="label">Employment Status</span><div class="value"><?php echo htmlspecialchars($pwd['employment_status']); ?></div></div>
            <div class="field"><span class="label">Occupation</span><div class="value"><?php echo htmlspecialchars($pwd['occupation']) ?: 'NONE'; ?></div></div>
        </div>

        <h3>15. Organization Information</h3>
        <div class="form-row">
            <div class="field"><span class="label">Organization Name</span><div class="value"><?php echo htmlspecialchars($pwd['organization_name']) ?: 'N/A'; ?></div></div>
            <div class="field"><span class="label">Contact Person</span><div class="value"><?php echo htmlspecialchars($pwd['organization_contact_person']) ?: 'N/A'; ?></div></div>
            <div class="field"><span class="label">Tel Nos.</span><div class="value"><?php echo htmlspecialchars($pwd['organization_contact_number']) ?: 'N/A'; ?></div></div>
        </div>

        <h3>16. Government IDs</h3>
        <div class="form-row" style="flex-wrap: wrap;">
            <div class="field"><span class="label">SSS</span><div class="value"><?php echo htmlspecialchars($pwd['sss_no']) ?: 'N/A'; ?></div></div>
            <div class="field"><span class="label">GSIS</span><div class="value"><?php echo htmlspecialchars($pwd['gsis_no']) ?: 'N/A'; ?></div></div>
            <div class="field"><span class="label">PAG-IBIG</span><div class="value"><?php echo htmlspecialchars($pwd['pagibig_no']) ?: 'N/A'; ?></div></div>
            <div class="field"><span class="label">PSN (PhilSys)</span><div class="value"><?php echo htmlspecialchars($pwd['psn_no']) ?: 'N/A'; ?></div></div>
            <div class="field"><span class="label">PhilHealth</span><div class="value"><?php echo htmlspecialchars($pwd['philhealth_no']) ?: 'N/A'; ?></div></div>
        </div>

        <h3>17. Family Background</h3>
        <div class="form-row">
            <div class="field"><span class="label">Father's Name</span><div class="value"><?php echo htmlspecialchars($pwd['father_firstname'].' '.$pwd['father_middlename'].' '.$pwd['father_lastname']); ?></div></div>
        </div>
        <div class="form-row">
            <div class="field"><span class="label">Mother's Name</span><div class="value"><?php echo htmlspecialchars($pwd['mother_firstname'].' '.$pwd['mother_middlename'].' '.$pwd['mother_lastname']); ?></div></div>
        </div>
        <div class="form-row">
            <div class="field"><span class="label">Guardian's Name</span><div class="value"><?php echo htmlspecialchars($pwd['guardian_firstname'].' '.$pwd['guardian_middlename'].' '.$pwd['guardian_lastname']); ?></div></div>
        </div>

        <h3>18. Accomplished By</h3>
        <div class="form-row">
            <div class="field"><span class="label">Type</span><div class="value"><?php echo htmlspecialchars($pwd['accomplished_by_type']); ?></div></div>
            <div class="field"><span class="label">Name</span><div class="value"><?php echo htmlspecialchars($pwd['acc_firstname'].' '.$pwd['acc_middlename'].' '.$pwd['acc_lastname']); ?></div></div>
        </div>

        <?php if ($has_form2): ?>
        <div class="form2-section">
            <div class="assessment-title">FORM 2: FUNCTIONAL ASSESSMENT RECORD</div>
            <div class="assessment-grid">
                <div class="col">
                    <div class="field"><span class="label">1. Musculoskeletal & Mobility</span><div class="value"><?php echo format_val($pwd['functional_assessments']); ?></div></div>
                    <div class="field" style="margin-top:15px;"><span class="label">2. Motor Disability</span><div class="value"><?php echo format_val($pwd['motor_disability']); ?></div></div>
                    <div class="field" style="margin-top:15px;"><span class="label">3. Visual Impairment</span><div class="value"><?php echo format_val($pwd['visual_impairment']); ?></div></div>
                    <div class="field" style="margin-top:15px;"><span class="label">4. Hearing Impairment</span><div class="value"><?php echo format_val($pwd['hearing_impairment']); ?></div></div>
                </div>
                <div class="col">
                    <div class="field"><span class="label">5. Speech & Communication</span><div class="value"><?php echo format_val($pwd['speech_impairment']); ?></div></div>
                    <div class="field" style="margin-top:15px;"><span class="label">6. Mental Impairment</span><div class="value"><?php echo format_val($pwd['mental_impairment']); ?></div></div>
                    <div class="field" style="margin-top:15px;"><span class="label">7. Deformities</span><div class="value"><?php echo format_val($pwd['deformity_details']); ?></div></div>
                    <div class="field" style="margin-top:15px;">
                        <span class="label">8. Etiology & Assistive Devices</span>
                        <div class="value">
                            <strong>Etiology:</strong> <?php echo htmlspecialchars($pwd['assessment_etiology'] ?? 'N/A'); ?> 
                            <?php if(!empty($pwd['etiology_details'])) echo " (".htmlspecialchars($pwd['etiology_details']).")"; ?><br>
                            <strong>Devices:</strong> <?php echo htmlspecialchars($pwd['assistive_devices'] ?: 'None'); ?>
                            <?php if(!empty($pwd['assistive_devices_other'])) echo " (".htmlspecialchars($pwd['assistive_devices_other']).")"; ?>
                        </div>
                    </div>
                </div>
            </div>

            <h3 style="margin-top:25px;">Final Diagnosis and Clinical Impression</h3>
            <div class="diag-area">
                <?php echo nl2br(htmlspecialchars($pwd['diagnosis'])) ?: 'NO DIAGNOSIS PROVIDED'; ?>
            </div>

            <div class="physician-container">
                <div>
                    <div class="value" style="font-size:16px;"><?php echo htmlspecialchars($pwd['physician_name']); ?></div>
                    <span class="physician-name-line"></span><br>
                    <span class="label">Examining Health Physician</span>
                    <div class="label" style="margin-top:5px; color: #000;">
                        PRC License No: <strong><?php echo htmlspecialchars($pwd['physician_license'] ?: 'N/A'); ?></strong> | 
                        PTR No: <strong><?php echo htmlspecialchars($pwd['physician_ptr'] ?: 'N/A'); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="button-group">
            <button onclick="window.history.back()" class="btn btn-back">Back to Search</button>
            <button onclick="window.print()" class="btn btn-print"><?php echo $has_form2 ? 'Print Forms 1 & 2' : 'Print Form 1'; ?></button>
        </div>
    </div>
</div>