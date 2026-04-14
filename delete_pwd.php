   <?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    $stmt = $conn->prepare("DELETE FROM pwd WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error deleting user!";
    }

 
}
?>
