<?php
header('Content-Type: application/json');
include("../db_connect.php"); 

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $id = $_POST['pwd_id'] ?? null;
    if (!$id) throw new Exception("ID is missing.");

    // --- 1. SECURITY LOCK CHECK ---
    $check = $conn->prepare("SELECT status FROM pwd WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $current_status = $check->get_result()->fetch_assoc()['status'] ?? '';

    if ($current_status === 'Accepted') {
        throw new Exception("This record is locked and cannot be edited.");
    }

    // --- 2. HANDLE IMAGE UPLOAD ---
    $photo_filename = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "../uploads/profile_pics/"; 
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $ext = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
        $photo_filename = "PWD_" . $id . "_" . time() . "_" . uniqid() . "." . $ext;
        
        if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_dir . $photo_filename)) {
            throw new Exception("Failed to upload the new photo.");
        }
    }

    // --- 3. PREPARE ALL FIELDS ---
    $fields = [
        'new_applicant_or_renewal' => $_POST['new_applicant_or_renewal'] ?? 'New',
        'pwd_number'               => $_POST['pwd_number'] ?? '',
        'barangay_id'              => $_POST['barangay_id'] ?? null, // Added for consistency
        'date_applied'             => $_POST['date_applied'] ?? '',
        'last_name'                => $_POST['last_name'] ?? '',
        'first_name'               => $_POST['first_name'] ?? '',
        'middle_name'              => $_POST['middle_name'] ?? '',
        'suffix'                   => $_POST['suffix'] ?? '',
        'birth_date'               => $_POST['birth_date'] ?? '',
        'gender'                   => $_POST['gender'] ?? '',
        'civil_status'             => $_POST['civil_status'] ?? '',
        'disability_type'          => !empty($_POST['disability_type']) ? intval($_POST['disability_type']) : null,
        'disability_cause'         => $_POST['disability_cause'] ?? '',
        'address'                  => $_POST['address'] ?? '',
        'contact_number'           => $_POST['contact_number'] ?? '',
        'email'                    => $_POST['email'] ?? '',
        'education'                => $_POST['education'] ?? '',
        'employment_status'        => $_POST['employment_status'] ?? '',
        'occupation'               => $_POST['occupation'] ?? '',
        'organization_name'        => $_POST['organization_name'] ?? '',
        'organization_contact_person' => $_POST['organization_contact_person'] ?? '',
        'organization_contact_number' => $_POST['organization_contact_number'] ?? '',
        'sss_no'                   => $_POST['sss_no'] ?? '',
        'gsis_no'                  => $_POST['gsis_no'] ?? '',
        'pagibig_no'               => $_POST['pagibig_no'] ?? '',
        'psn_no'                   => $_POST['psn_no'] ?? '',
        'philhealth_no'            => $_POST['philhealth_no'] ?? '',
        'father_lastname'          => $_POST['father_lastname'] ?? '',
        'father_firstname'         => $_POST['father_firstname'] ?? '',
        'father_middlename'        => $_POST['father_middlename'] ?? '',
        'mother_lastname'          => $_POST['mother_lastname'] ?? '',
        'mother_firstname'         => $_POST['mother_firstname'] ?? '',
        'mother_middlename'        => $_POST['mother_middlename'] ?? '',
        'guardian_lastname'        => $_POST['guardian_lastname'] ?? '',
        'guardian_firstname'       => $_POST['guardian_firstname'] ?? '',
        'guardian_middlename'      => $_POST['guardian_middlename'] ?? '',
        'accomplished_by_type'     => $_POST['accomplished_by_type'] ?? 'Applicant',
        'acc_lastname'             => $_POST['acc_lastname'] ?? '',
        'acc_firstname'            => $_POST['acc_firstname'] ?? '',
        'acc_middlename'           => $_POST['acc_middlename'] ?? ''
    ];

    $setClause = "";
    $types = "";
    $values = [];

    if ($photo_filename) {
        $setClause .= "photo = ?, "; 
        $types .= "s";
        $values[] = $photo_filename;
    }

    foreach ($fields as $column => $value) {
        $setClause .= "$column = ?, ";
        // Treat both disability_type and barangay_id as integers (i)
        $types .= (in_array($column, ['disability_type', 'barangay_id'])) ? "i" : "s";
        $values[] = $value;
    }

    $setClause = rtrim($setClause, ", ");
    $sql = "UPDATE pwd SET $setClause WHERE id = ?";
    $types .= "i";
    $values[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Record updated successfully!"]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>