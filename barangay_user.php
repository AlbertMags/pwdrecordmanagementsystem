<?php 
require 'db_connect.php';

// Start session only if it's not already started to avoid the Notice error
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL for Clean Redirects - Matches your .htaccess RewriteBase
$base_url = "/PWD/";

// Handle ADD request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['full_name'], $_POST['email'], $_POST['password'], $_POST['barangay_id']) && !isset($_POST['user_id'])) {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $barangay_id = $_POST['barangay_id'];
    $role = "barangay_admin"; 

    // Check if email exists
    $emailStmt = $conn->prepare("SELECT user_id FROM myusers WHERE email = ?");
    $emailStmt->bind_param("s", $email);
    $emailStmt->execute();
    $emailStmt->store_result();
    if ($emailStmt->num_rows > 0) {
        echo "Error: Email already exists.";
        exit;
    }

    // Check if barangay already has a user
    $barangayStmt = $conn->prepare("SELECT user_id FROM myusers WHERE barangay_id = ?");
    $barangayStmt->bind_param("i", $barangay_id);
    $barangayStmt->execute();
    $barangayStmt->store_result();
    if ($barangayStmt->num_rows > 0) {
        echo "Error: Barangay already has a user.";
        exit;
    }

    // Insert user
    $insertStmt = $conn->prepare("INSERT INTO myusers (full_name, email, password, barangay_id, role) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("sssis", $name, $email, $password, $barangay_id, $role);

    if ($insertStmt->execute()) {
        echo "success";
    } else {
        echo "Error adding user.";
    }
    exit;
}

// Fetch barangay users
$query = "SELECT u.user_id, u.full_name, u.email, b.brgy_name, u.barangay_id
          FROM myusers u 
          JOIN barangay b ON u.barangay_id = b.id 
          ORDER BY u.user_id ASC";
$result = $conn->query($query);

// Fetch barangays for dropdown
$barangayQuery = "SELECT * FROM barangay ORDER BY brgy_name ASC";
$barangayResult = $conn->query($barangayQuery);
$barangayOptions = [];
while ($b = $barangayResult->fetch_assoc()) {
    $barangayOptions[] = $b;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Barangay Users</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* --- INTEGRATED NAVBAR STYLING --- */
        * { box-sizing: border-box; }
        
        body, html { 
            background-color: #e9ecef; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; padding: 0; width: 100%; height: 100%;
        }

        .top-nav {
            background: #fff; 
            display: flex; 
            justify-content: flex-start; 
            align-items: center;
            padding: 40px 40px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            position: fixed; 
            top: 0; 
            left: 250px; 
            width: calc(100% - 250px); 
            z-index: 1000;
            height: 70px;
        }

        .nav-brand-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-text-stack {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .top-nav h1 { 
            margin: 0; 
            color: #1a3a5f; 
            font-size: 22px; 
            font-weight: 700; 
            line-height: 1.2;
        }

        .nav-sub { 
            font-size: 16px; 
            color: #4b4848; 
            font-weight: normal; 
            margin: 0;
            line-height: 1.2;
        }

        .dashboard-wrapper { 
            padding: 100px 25px 25px 25px; 
            width: 100%; 
        }

        .content-card {
            background: #fff; border-radius: 12px; padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%;
        }

        .barangay-header {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        .button-barangay_user {
            background:#0056b3; 
            color: white; 
            border: none; 
            padding: 12px 20px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 180px;
            white-space: nowrap;
            font-size: 15px;
            transition: background 0.3s;
        }

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .toggle-eye {
            position: absolute;
            right: 10px;
            cursor: pointer;
            color: #7f8c8d;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 25px;
            border-radius: 8px;
            width: 450px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
        }

        @media (max-width: 992px) { 
            .top-nav { left: 0; width: 100%; }
        }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-brand-wrapper">
            <div class="nav-text-stack">
                <h1>List of Barangay User</h1>
                <p class="nav-sub">Assign and manage administrators for specific local jurisdictions.</p>
            </div>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <main class="content-card">
            <div class="barangay-header">
                <button class="button-barangay_user" id="openAddModal">
                    <i class="fas fa-user-plus"></i> Add Barangay User
                </button>
            </div>
            
            <table width="100%" border="0" cellspacing="0" cellpadding="10">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th align="left">#</th>
                        <th align="left">Full Name</th>
                        <th align="left">Barangay</th>
                        <th align="left">Email</th>
                        <th align="left">Actions</th>
                    </tr>
                </thead>
                <tbody id="barangayUserTableBody">
                    <?php $count = 1; while ($row = $result->fetch_assoc()) { ?>
                        <tr id="user-<?php echo $row['user_id']; ?>" style="border-bottom: 1px solid #eee;">
                            <td class="user-number"><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['brgy_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <div class="buttons">
                                    <button class="edit-btn" 
                                        style="cursor:pointer; background:#27ae60; color:white; border:none; padding:5px 10px; border-radius:4px; margin-right:5px;"
                                        data-id="<?php echo $row['user_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                        data-barangay="<?php echo $row['barangay_id']; ?>"
                                    >Edit</button>
                                    <button class="delete-btn" 
                                        style="cursor:pointer; background:#e74c3c; color:white; border:none; padding:5px 10px; border-radius:4px;"
                                        data-id="<?php echo $row['user_id']; ?>">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </main>
    </div>

    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddUserModal()">&times;</span>
            <h2>Add Barangay User</h2>
            <form id="addUserForm" autocomplete="off">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="add_password" autocomplete="new-password" required>
                        <span id="togglePassword" class="toggle-eye">👁</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Barangay:</label>
                    <select name="barangay_id" required>
                        <option value="">-- Select Barangay --</option>
                        <?php foreach ($barangayOptions as $barangay) { ?>
                            <option value="<?php echo $barangay['id']; ?>">
                                <?php echo htmlspecialchars($barangay['brgy_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <button type="submit" class="add-user-btn" style="background:#0056b3; color:white; border:none; padding:12px; width:100%; border-radius:5px; cursor:pointer; font-weight:bold; margin-top:10px;">Save User</button>
            </form>
        </div>
    </div>

    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditUserModal()">&times;</span>
            <h2>Edit Barangay User</h2>
            <form id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" id="edit_full_name" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="edit_password" autocomplete="new-password" placeholder="Leave blank to keep current">
                        <span id="toggleEditPassword" class="toggle-eye">👁</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Barangay:</label>
                    <select name="barangay_id" id="edit_barangay_id" required>
                        <?php foreach ($barangayOptions as $barangay) { ?>
                            <option value="<?php echo $barangay['id']; ?>">
                                <?php echo htmlspecialchars($barangay['brgy_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <button type="submit" class="add-user-btn" style="background:#27ae60; color:white; border:none; padding:12px; width:100%; border-radius:5px; cursor:pointer; font-weight:bold; margin-top:10px;">Update User</button>
            </form>
        </div>
    </div>

    <script>
    // Ensure cleanURL exactly matches your .htaccess RewriteBase + slug
    const cleanURL = "<?= $base_url ?>barangay_user";

    function closeAddUserModal() { $("#addUserModal").fadeOut(); }
    function closeEditUserModal() { $("#editUserModal").fadeOut(); }

    $(document).ready(function() {
        $("#openAddModal").click(function() {
            $("#addUserForm")[0].reset();
            $("#addUserModal").fadeIn();
        });

        $("#addUserForm").submit(function(event) {
            event.preventDefault();
            // Post specifically to the filename, not the slug
            $.post("barangay_user.php", $(this).serialize(), function(response) {
                if (response.trim() === "success") {
                    alert("Barangay user added successfully!");
                    window.location.href = cleanURL; // Redirect to clean URL
                } else {
                    alert(response);
                }
            });
        });

        $(".edit-btn").click(function() {
            $("#edit_user_id").val($(this).data("id"));
            $("#edit_full_name").val($(this).data("name"));
            $("#edit_email").val($(this).data("email"));
            $("#edit_barangay_id").val($(this).data("barangay"));
            $("#edit_password").val("");
            $("#editUserModal").fadeIn();
        });

        $("#editUserForm").submit(function(event) {
            event.preventDefault();
            $.post("edit_barangay_user.php", $(this).serialize(), function(response) {
                if (response.trim() === "success") {
                    alert("Barangay user updated successfully!");
                    window.location.href = cleanURL; 
                } else {
                    alert(response);
                }
            });
        });

        $(".delete-btn").click(function() {
            var userId = $(this).data("id");
            if (confirm("Are you sure you want to delete this user?")) {
                $.post("delete_barangay_user.php", { delete_id: userId }, function(response) {
                    if (response.trim() === "success") {
                        $("#user-" + userId).fadeOut(300, function() {
                            $(this).remove();
                            updateNumbers();
                        });
                    } else {
                        alert("Error deleting user!");
                    }
                });
            }
        });

        $("#togglePassword, #toggleEditPassword").click(function() {
            const field = $(this).siblings("input");
            const type = field.attr("type") === "password" ? "text" : "password";
            field.attr("type", type);
            $(this).text(type === "password" ? "👁" : "👁‍🗨");
        });

        function updateNumbers() {
            $(".user-number").each(function(index) {
                $(this).text(index + 1);
            });
        }
    });

    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            $(".modal").fadeOut();
        }
    }
    </script>
</body>
</html>