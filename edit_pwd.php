<?php
// Force JSON header for consistency with your other scripts
header('Content-Type: application/json');
include 'db_connect.php'; 

try {
    // Check if ID exists (this replaces the long isset check)
    $id = $_POST['id'] ?? $_POST['pwd_id'] ?? null;
    if (!$id) {
        throw new Exception("Missing ID for update.");
    }

    // Collect all fields (matching your add_pwd.php names)
    $barangay_id = $_POST['barangay_id'] ?? 0;
    $date_applied = $_POST['date_applied'] ?? date('Y-m-d');
    $new_applicant_or_renewal = $_POST['new_applicant_or_renewal'] ?? 'New';
    $pwd_number = $_POST['pwd_number'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $suffix = $_POST['suffix'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $civil_status = $_POST['civil_status'] ?? '';
    $disability_type = $_POST['disability_type'] ?? $_POST['disability_id'] ?? 0;
    $disability_cause = $_POST['disability_cause'] ?? ''; 
    $address = $_POST['address'] ?? '';
    
    // Static values
    $municipality = "EB Magalona";
    $province = "Negros Occidental";
    $region = "Region VI";

    // Contact/Work
    $contact_number = $_POST['contact_number'] ?? '';
    $email = $_POST['email'] ?? '';
    $education = $_POST['education'] ?? '';
    $employment_status = $_POST['employment_status'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $organization_name = $_POST['organization_name'] ?? '';
    $organization_contact_person = $_POST['organization_contact_person'] ?? '';
    $organization_contact_number = $_POST['organization_contact_number'] ?? '';
    
    // IDs
    $sss_no = $_POST['sss_no'] ?? '';
    $gsis_no = $_POST['gsis_no'] ?? '';
    $pagibig_no = $_POST['pagibig_no'] ?? '';
    $psn_no = $_POST['psn_no'] ?? '';
    $philhealth_no = $_POST['philhealth_no'] ?? '';
    
    // Relatives
    $father_lastname = $_POST['father_lastname'] ?? '';
    $father_firstname = $_POST['father_firstname'] ?? '';
    $father_middlename = $_POST['father_middlename'] ?? '';
    $mother_lastname = $_POST['mother_lastname'] ?? '';
    $mother_firstname = $_POST['mother_firstname'] ?? '';
    $mother_middlename = $_POST['mother_middlename'] ?? '';
    $guardian_lastname = $_POST['guardian_lastname'] ?? '';
    $guardian_firstname = $_POST['guardian_firstname'] ?? '';
    $guardian_middlename = $_POST['guardian_middlename'] ?? '';
    
    // Accomplished By
    $accomplished_by_type = $_POST['accomplished_by_type'] ?? 'Applicant';
    $acc_lastname = $_POST['acc_lastname'] ?? '';
    $acc_firstname = $_POST['acc_firstname'] ?? '';
    $acc_middlename = $_POST['acc_middlename'] ?? '';

    $sql = "UPDATE pwd SET 
            barangay_id=?, date_applied=?, new_applicant_or_renewal=?, pwd_number=?, 
            last_name=?, first_name=?, middle_name=?, suffix=?, birth_date=?, gender=?, civil_status=?, 
            disability_type=?, disability_cause=?, address=?, 
            municipality=?, province=?, region=?,
            contact_number=?, email=?, education=?, employment_status=?, occupation=?, 
            organization_name=?, organization_contact_person=?, organization_contact_number=?, 
            sss_no=?, gsis_no=?, pagibig_no=?, psn_no=?, philhealth_no=?, 
            father_lastname=?, father_firstname=?, father_middlename=?, 
            mother_lastname=?, mother_firstname=?, mother_middlename=?, 
            guardian_lastname=?, guardian_firstname=?, guardian_middlename=?,
            accomplished_by_type=?, acc_lastname=?, acc_firstname=?, acc_middlename=?
            WHERE id=?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // "issssssssssissssssssssssssssssssssssssssssi" = 42 total parameters
    $types = "issssssssssissssssssssssssssssssssssssssssi";
    
    $stmt->bind_param($types, 
        $barangay_id, $date_applied, $new_applicant_or_renewal, $pwd_number,
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
        $id
    );

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Updated successfully"]);
    } else {
        throw new Exception("Execution failed: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>