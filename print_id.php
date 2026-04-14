<?php
include("db_connect.php");

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo "Invalid Request";
    exit;
}

// Fetching all columns from pwd (p.*) to ensure 'photo' is available
$query = "SELECT p.*, b.brgy_name, d.disability_name 
          FROM pwd p 
          JOIN barangay b ON p.barangay_id = b.id 
          JOIN disability_type d ON p.disability_type = d.id 
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo "Record not found.";
    exit;
}

$current_year = date("Y");
$date_issued = date("M d, Y"); 
$valid_until = date("M d, Y", strtotime('+5 years')); 

/**
 * PATH LOGIC FIX 
 * Matches the logic in your view_pwd.php
 */
$image_path = "uploads/profile_pics/" . basename($data['photo']);
if (empty($data['photo']) || !file_exists($image_path)) { 
    $image_path = "uploads/profile_pics/default.png"; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PWD ID - <?php echo htmlspecialchars(strtoupper($data['last_name'])); ?></title>
    <style>
        body { 
            font-family: "Arial", sans-serif; 
            background: #f4f4f4; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            padding: 20px; 
            margin: 0;
        }
        
        .id-container {
            width: 7.5in; 
            height: 2.5in;
            background: white;
            display: flex;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            position: relative;
            color: #000;
            border: 1px solid #ccc;
        }

        .panel {
            width: 50%;
            height: 100%;
            padding: 8px 12px;
            box-sizing: border-box;
            position: relative;
            z-index: 1;
        }

        .left-panel { border-right: 1px dashed #ccc; }
        
        .id-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; }
        .id-header img.flag { height: 28px; }
        
        .circle-logo { 
            height: 38px; 
            width: 38px; 
            border-radius: 50%; 
            border: 1px solid #333; 
            object-fit: cover;
        }

        .header-text { text-align: center; flex-grow: 1; line-height: 1.1; }
        .header-text h1 { font-size: 7pt; margin: 0; }
        .header-text h2 { font-size: 8.5pt; margin: 0; font-weight: bold;  }

        .id-number-section { margin-top: 2px; font-weight: bold; font-size: 11pt; }
        
        .main-info { margin-top: 10px; width: 62%; }

        .info-field { 
            margin-bottom: 5px; 
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        .info-value { 
            font-size: 10pt; 
            font-weight: bold; 
            text-transform: uppercase; 
            width: 100%;
            text-align: center;
            border-bottom: 1.5px solid #000; 
            padding-bottom: 1px;
            min-height: 14pt;
        }
        .info-label { 
            font-size: 7.5pt; 
            font-weight: bold; 
            text-align: center; 
            width: 100%;
            margin-top: 1px;
        }

        .photo-box {
            position: absolute;
            right: 12px;
            top: 58px;
            width: 1.1in;
            height: 1.1in;
            border: 2px solid #000;
            background: #fff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; }

        .legal-footer {
            position: absolute;
            bottom: 5px;
            left: 8px;
            right: 8px;
            font-size: 6pt;
            line-height: 1.1;
            font-style: italic;
            text-align: justify;
            margin: 0;
            font-weight: bold;
        }

        .right-panel { font-size: 8.5pt; padding-left: 15px; overflow: hidden; display: flex; flex-direction: column; }
        
        .watermark-back {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 1.9in;
            opacity: 0.15; 
            z-index: -1; 
            pointer-events: none;
        }

        .row { display: flex; margin-bottom: 4px; border-bottom: 1px solid #eee; align-items: baseline; }
        .row label { width: 85px; font-weight: normal; font-size: 8pt; }
        .row span { font-weight: bold; text-transform: uppercase; }

        .section-header { font-weight: bold; margin-top: 8px; text-transform: uppercase; font-size: 8pt; border-bottom: 1px solid #000; }
        
        .mayor-section {
            text-align: center;
            width: 100%;
            margin-top: auto; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .mayor-name { 
            font-weight: bold; 
            border-bottom: 1.5px solid #000; 
            display: inline-block; 
            text-transform: uppercase; 
            font-size: 10pt; 
            padding: 0 15px; 
            margin-bottom: 2px;
        }
        .mayor-title { font-size: 8pt; font-weight: bold; }

        .print-controls {
            margin-bottom: 20px;
        }

        .print-btn-action {
            background: #28a745; color: white; border: none; padding: 10px 30px; 
            font-size: 16px; cursor: pointer; border-radius: 5px;
            font-weight: bold;
        }

        @media print {
            .print-controls { display: none; }
            body { background: white; padding: 0; }
            .id-container { box-shadow: none; border: 1px solid #000; margin: 0; }
            .left-panel { border-right: 1px solid #000; }
            .watermark-back { -webkit-print-color-adjust: exact; opacity: 0.15 !important; }
        }
    </style>
</head>
<body>

    <div class="print-controls">
        <button class="print-btn-action" onclick="window.print()">Confirm & Print ID</button>
    </div>

    <div class="id-container">
        <div class="panel left-panel">
            <div class="id-header">
                <img src="uploads/flagg.png" class="flag" alt="Flag">
                <div class="header-text">
                    <h1>Republic of the Philippines</h1>
                    <h2>Municipality of EB MAGALONA</h2>
                </div>
                <img src="uploads/logo.jpg" class="circle-logo" alt="Logo">
                <img src="uploads/chair.png" class="circle-logo" alt="PWD" style="margin-left: 3px;">
            </div>

            <div class="id-number-section">
                ID No. <span style="text-decoration: underline;">
                    <?php echo $current_year . "-" . str_pad($data['id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
            </div>

            <div class="main-info">
                <div class="info-field">
                    <span class="info-value"><?php echo htmlspecialchars(strtoupper($data['first_name'] . " " . $data['last_name'])); ?></span>
                    <span class="info-label">Name</span>
                </div>
                <div class="info-field">
                    <span class="info-value" style="font-size: 8pt;"><?php echo htmlspecialchars(strtoupper($data['disability_name'])); ?></span>
                    <span class="info-label">Type of Disability</span>
                </div>
                <div class="info-field">
                    <span class="info-value">&nbsp;</span> 
                    <span class="info-label">Signature</span>
                </div>
            </div>

            <div class="photo-box">
                <img src="./<?php echo $image_path; ?>?t=<?php echo time(); ?>" alt="PWD Photo">
            </div>

            <p class="legal-footer">
                The holder of this card is a person with disability and is entitled to all benefits and privileges in 
                accordance with Republic Acts 9442 and 10754. Non-transferable. Valid for five (5) years. Any 
                violation is punishable by law. VALID ANYWHERE IN THE PHILIPPINES.
            </p>
        </div>

        <div class="panel right-panel">
            <img src="uploads/logo.jpg" class="watermark-back" alt="watermark">

            <div class="row">
                <label>Address:</label>
                <span style="font-size: 7.5pt;">BRGY. <?php echo htmlspecialchars(strtoupper($data['brgy_name'])); ?>, EB MAGALONA</span>
            </div>
            <div class="row">
                <label>Date of Birth:</label>
                <span style="width: 95px;"><?php echo date("m/d/Y", strtotime($data['birth_date'])); ?></span>
                <label style="width: 35px;">Sex:</label>
                <span><?php echo htmlspecialchars(strtoupper($data['gender'] ?? 'N/A')); ?></span>
            </div>
            <div class="row">
                <label>Date Issued:</label>
                <span style="width: 95px;"><?php echo $date_issued; ?></span>
                <label style="width: 65px;">Valid Until:</label>
                <span><?php echo $valid_until; ?></span>
            </div>

            <div class="emergency-box">
                <div class="section-header">In Case of Emergency, Please Notify</div>
                <div class="row">
                    <label>Guardian:</label>
                    <span><?php echo htmlspecialchars(strtoupper($data['guardian_firstname'] ?? '') . ' ' . strtoupper($data['guardian_lastname'] ?? '')); ?></span>
                </div>
                <div class="row">
                    <label>Contact No:</label>
                    <span><?php echo htmlspecialchars($data['contact_number'] ?? ''); ?></span>
                </div>
            </div>

            <div class="tax-section">
                <div class="section-header" style="margin-top: 0; font-size: 7pt; border: none;">For Availing Tax Incentives as Dependent:</div>
                <div class="row"><label>Tax Claimant:</label><span></span></div>
                <div class="row"><label>Tin:</label><span></span></div>
                <div class="row"><label>Contact No:</label><span></span></div>
            </div>

            <div class="mayor-section">
                <span class="mayor-name">Hon. Matthew Luis Malacon</span>
                <span class="mayor-title">Municipal Mayor</span>
            </div>
        </div>
    </div>

</body>
</html>