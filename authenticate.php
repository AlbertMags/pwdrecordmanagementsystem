<?php
session_start();
$conn = new mysqli("localhost", "root", "", "PWDRECORDS");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use Prepared Statements for security
    $stmt = $conn->prepare("SELECT * FROM Myusers WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['username'] = $username;
        $_SESSION['barangay_id'] = $user['barangay_id']; // IMPORTANT: Store the ID here!
        header("Location: index.php");
    } else {
        echo "<script>alert('Invalid login.'); window.location.href='login.php';</script>";
    }
}

$conn->close();
?>
