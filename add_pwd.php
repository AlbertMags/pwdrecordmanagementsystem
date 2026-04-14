<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include("db_connect.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

try {
    // Collect data from form
    $new_applicant_or_renewal = $_POST['new_applicant_or_renewal'] ?? 'New';
    $pwd_number = $_POST['pwd_number'] ?? null;
    $barangay_id = $_POST['barangay_id'] ?? null;
    $date_applied = $_POST['date_applied'] ?? date('Y-m-d');
    $last_name = $_POST['last_name'] ?? null;
    $first_name = $_POST['first_name'] ?? null;
    $middle_name = $_POST['middle_name'] ?? null;
    $suffix = $_POST['suffix'] ?? null;
    $birth_date = $_POST['birth_date'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $civil_status = $_POST['civil_status'] ?? null;
    $disability_type = $_POST['disability_type'] ?? null;
    $disability_cause = $_POST['disability_cause'] ?? null;
    $address = $_POST['address'] ?? null;
    
    $municipality = "EB Magalona";
    $province = "Negros Occidental";
    $region = "Region VI";
    
    $contact_number = $_POST['contact_number'] ?? null;
    $email = $_POST['email'] ?? null;
    $education = $_POST['education'] ?? null;
    $employment_status = $_POST['employment_status'] ?? null;
    $occupation = $_POST['occupation'] ?? null;

    $organization_name = $_POST['organization_name'] ?? null;
    $organization_contact_person = $_POST['organization_contact_person'] ?? null;
    $organization_contact_number = $_POST['organization_contact_number'] ?? null;

    $sss_no = $_POST['sss_no'] ?? null;
    $gsis_no = $_POST['gsis_no'] ?? null;
    $pagibig_no = $_POST['pagibig_no'] ?? null;
    $psn_no = $_POST['psn_no'] ?? null;
    $philhealth_no = $_POST['philhealth_no'] ?? null;

    $father_lastname = $_POST['father_lastname'] ?? null;
    $father_firstname = $_POST['father_firstname'] ?? null;
    $father_middlename = $_POST['father_middlename'] ?? null;
    $mother_lastname = $_POST['mother_lastname'] ?? null;
    $mother_firstname = $_POST['mother_firstname'] ?? null;
    $mother_middlename = $_POST['mother_middlename'] ?? null;
    $guardian_lastname = $_POST['guardian_lastname'] ?? null;
    $guardian_firstname = $_POST['guardian_firstname'] ?? null;
    $guardian_middlename = $_POST['guardian_middlename'] ?? null;

    $accomplished_by_type = $_POST['accomplished_by_type'] ?? 'Applicant';
    $acc_lastname = $_POST['acc_lastname'] ?? null;
    $acc_firstname = $_POST['acc_firstname'] ?? null;
    $acc_middlename = $_POST['acc_middlename'] ?? null;

    // --- FIXED IMAGE UPLOAD LOGIC ---
    $db_image_name = ""; 
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "uploads/profile_pics/";
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_extension;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $db_image_name = $file_name;
        }
    }

    $sql = "INSERT INTO pwd (
        new_applicant_or_renewal, pwd_number, barangay_id, date_applied,
        last_name, first_name, middle_name, suffix, birth_date, gender, civil_status,
        disability_type, disability_cause, address, 
        municipality, province, region,
        contact_number, email, education, employment_status, occupation,
        organization_name, organization_contact_person, organization_contact_number,
        sss_no, gsis_no, pagibig_no, psn_no, philhealth_no,
        father_lastname, father_firstname, father_middlename,
        mother_lastname, mother_firstname, mother_middlename,
        guardian_lastname, guardian_firstname, guardian_middlename,
        accomplished_by_type, acc_lastname, acc_firstname, acc_middlename,
        photo, status
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending'
    )";

    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param(
        "ssissssssssissssssssssssssssssssssssssssssss",
        $new_applicant_or_renewal, $pwd_number, $barangay_id, $date_applied,
        $last_name, $first_name, $middle_name, $suffix, $birth_date, $gender, $civil_status,
        $disability_type, $disability_cause, $address,
        $municipality, $province, $region,
        $contact_number, $email, $education, $employment_status, $occupation,
        $organization_name, $organization_contact_person, $organization_contact_number,
        $sss_no, $gsis_no, $pagibig_no, $psn_no, $philhealth_no,
        $father_lastname, $father_firstname, $father_middlename,
        $mother_lastname, $mother_firstname, $mother_middlename,
        $guardian_lastname, $guardian_firstname, $guardian_middlename,
        $accomplished_by_type, $acc_lastname, $acc_firstname, $acc_middlename,
        $db_image_name
    );

    if ($stmt->execute()) {
        // --- ADDED NOTIFICATION LOGIC ---
        $full_name = trim($first_name . ' ' . $last_name);
        $notif_msg = "New Application: $full_name has been added to the system.";
        
        $notif_sql = "INSERT INTO notifications (user_type, barangay_id, message, read_by_admin, created_at) VALUES ('all', ?, ?, 0, NOW())";
        $stmt_notif = $conn->prepare($notif_sql);
        $stmt_notif->bind_param("is", $barangay_id, $notif_msg);
        $stmt_notif->execute();
        // --- END NOTIFICATION LOGIC ---

        echo json_encode(["status" => "success", "message" => "Registration successful"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Execution failed: " . $stmt->error]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>