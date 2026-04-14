    <?php
    require 'db_connect.php';

    // Check if barangay already has a user or email exists
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['full_name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $barangay_id = $_POST['barangay_id'];

        $checkStmt = $conn->prepare("SELECT id FROM myusers WHERE barangay_id = ? OR email = ?");
        $checkStmt->bind_param("is", $barangay_id, $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            echo "<script>alert('Error: Barangay already has a user or Email already exists!'); window.history.back();</script>";
            exit;
        } else {
            // Redirect to add_barangay_user.php to insert data
            $_POST['add_user'] = true; 
            include 'add_barangay_user.php';
        }
    


    }
    ?>