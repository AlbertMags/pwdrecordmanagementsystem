<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("db_connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pwd_ids']) && !empty($_POST['program_name'])) {
    
    // Determine the program name: Use the dropdown value OR the manual text input if 'Other' was selected
    $program_name = ($_POST['program_name'] === 'Other') 
                    ? mysqli_real_escape_string($conn, $_POST['other_program_name']) 
                    : mysqli_real_escape_string($conn, $_POST['program_name']);

    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $pwd_ids = $_POST['pwd_ids']; 

    $success_count = 0;

    // Prepare statement once for better performance
    $stmt = $conn->prepare("INSERT INTO distribution_logs (pwd_id, barangay_id, program_name, remarks) VALUES (?, ?, ?, ?)");

    foreach ($pwd_ids as $id) {
        // 1. Get the current barangay_id for this PWD
        $get_brgy = $conn->query("SELECT barangay_id FROM pwd WHERE id = '$id' LIMIT 1");
        if ($brgy_data = $get_brgy->fetch_assoc()) {
            $b_id = $brgy_data['barangay_id'];

            // 2. Bind and execute the prepared statement
            $stmt->bind_param("iiss", $id, $b_id, $program_name, $remarks);
            
            if ($stmt->execute()) {
                $success_count++;
            }
        }
    }

    $stmt->close();
    $conn->close();

    if ($success_count > 0) {
        echo "<script>
                alert('Successfully encoded $success_count records for " . addslashes($program_name) . "!');
                window.location.href = 'index.php?page=program_distribution';
              </script>";
    } else {
        echo "<script>
                alert('Error: No records were saved. Please check your database table structure.');
                window.history.back();
              </script>";
    }

} else {
    echo "<script>
            alert('Please select a Program and at least one PWD.');
            window.history.back();
          </script>";
}
?>