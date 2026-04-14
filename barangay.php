<?php
// Start session only if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("db_connect.php");

// Fetch barangay data with sequential IDs
$query = "SELECT * FROM barangay ORDER BY brgy_name ASC"; 
$result = $conn->query($query);

// Base URL for Clean Redirects
$base_url = "/PWD/";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay List</title>
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

        /* --- CONTENT WRAPPER --- */
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

        .button-barangay {
            background: #0056b3;
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
            min-width: 160px;
            white-space: nowrap;
            font-size: 15px;
            transition: background 0.3s;
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
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
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
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }

        @media (max-width: 992px) { 
            .top-nav { left: 0; width: 100%; }
        }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-brand-wrapper">
            <div class="nav-text-stack">
                <h1>List of Barangays</h1>
                <p class="nav-sub">Manage local jurisdictions and community leaders.</p>
            </div>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <main class="content-card">
            <div class="barangay-header">
                <button class="button-barangay" id="openAddModal">
                    <i class="fas fa-plus"></i> Add Barangay
                </button>
            </div>

            <table width="100%" border="0" cellspacing="0" cellpadding="10">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th align="left">ID</th>
                        <th align="left">Barangay Name</th>
                        <th align="left">Barangay Captain</th>
                        <th align="left">Contact</th>
                        <th align="left">Actions</th>
                    </tr>
                </thead>
                <tbody id="barangayTableBody">
                    <?php $count = 1; while ($row = $result->fetch_assoc()) { ?>
                    <tr id="barangay-<?php echo $row['id']; ?>" style="border-bottom: 1px solid #eee;">
                        <td class="barangay-number"><?php echo $count++; ?></td>
                        <td><?php echo htmlspecialchars($row['brgy_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['brgy_captain']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact']); ?></td>
                        <td>
                            <div class="buttons">
                                <button onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['brgy_name']); ?>', '<?php echo addslashes($row['brgy_captain']); ?>', '<?php echo $row['contact']; ?>')" class="edit-btn" style="cursor:pointer; background:#27ae60; color:white; border:none; padding:5px 10px; border-radius:4px; margin-right:5px;">Edit</button>
                                <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="delete-btn" style="cursor:pointer; background:#e74c3c; color:white; border:none; padding:5px 10px; border-radius:4px;">Delete</button>
                            </div>
                        </td>
                    </tr>     
                    <?php } ?>
                </tbody>
            </table>
        </main>
    </div>

    <div id="addBarangayModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add New Barangay</h2>
            <form id="addBarangayForm">
                <div class="form-group">
                    <label>Barangay Name:</label>
                    <input type="text" id="brgy_name" name="brgy_name" required>
                </div>
                <div class="form-group">
                    <label>Barangay Captain:</label>
                    <input type="text" id="brgy_captain" name="brgy_captain" required>
                </div>
                <div class="form-group">
                    <label>Contact:</label>
                    <input type="text" id="contact" name="contact" required maxlength="11" pattern="[0-9]{11}" title="Contact number must be 11 digits.">
                </div>
                <button type="submit" class="btn-action" style="background:#0056b3; color:white; border:none; padding:10px; width:100%; border-radius:5px; cursor:pointer; font-weight:bold;">Save Barangay</button>
            </form>
        </div>
    </div>

    <div id="editBarangayModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Barangay</h2>
            <form id="editBarangayForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label>Barangay Name:</label>
                    <input type="text" id="edit_brgy_name" name="brgy_name" required>
                </div>
                <div class="form-group">
                    <label>Barangay Captain:</label>
                    <input type="text" id="edit_brgy_captain" name="brgy_captain" required>
                </div>
                <div class="form-group">
                    <label>Contact:</label>
                    <input type="text" id="edit_contact" name="contact" required maxlength="11" pattern="[0-9]{11}" title="Contact number must be exactly 11 digits.">
                </div>
                <button type="submit" class="btn-action" style="background:#27ae60; color:white; border:none; padding:10px; width:100%; border-radius:5px; cursor:pointer; font-weight:bold;">Update Barangay</button>
            </form>
        </div>
    </div>

    <script>
        // Use the Clean URL slug for reloads
        const cleanURL = "<?= $base_url ?>barangay";

        function openEditModal(id, name, captain, contact) {
            $("#edit_id").val(id);
            $("#edit_brgy_name").val(name);
            $("#edit_brgy_captain").val(captain);
            $("#edit_contact").val(contact);
            $("#editBarangayModal").show();
        }

        function closeEditModal() {
            $("#editBarangayModal").hide();
        }

        function closeAddModal() {
            $("#addBarangayModal").fadeOut();
        }

        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this barangay?")) {
                $.post("delete_barangay.php", { delete_id: id }, function(response) {
                    if (response.trim() === "success") {
                        $("#barangay-" + id).fadeOut(300, function(){
                            $(this).remove();
                            updateNumbers();
                        });
                    } else {
                        alert("Error deleting barangay!");
                    }
                });
            }
        }

        function updateNumbers() {
            $(".barangay-number").each(function(index) {
                $(this).text(index + 1);
            });
        }

        $(document).ready(function() {
            $("#openAddModal").click(function() {
                $("#addBarangayModal").fadeIn();
            });

            $("#addBarangayForm").submit(function(event) {
                event.preventDefault();
                $.post("add_barangay.php", $(this).serialize(), function(response) {
                    if (response.trim() === "success") {
                        alert("Barangay added successfully!");
                        window.location.href = cleanURL; 
                    } 
                    else if (response.trim() === "duplicate") {
                        alert("Barangay already exists!");
                    } 
                    else {
                        alert("Error adding barangay!");
                    }
                });
            });

            $("#editBarangayForm").submit(function(event) {
                event.preventDefault();
                $.post("edit_barangay.php", $(this).serialize(), function(response) {
                    if (response.trim() === "success") {
                        alert("Barangay updated successfully!");
                        window.location.href = cleanURL;
                    } else {
                        alert("Error updating barangay!");
                    }
                });
            });
        });

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>