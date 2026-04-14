<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../db_connect.php");

if (!isset($_SESSION['related_pwd_id']) || !isset($_SESSION['user_id'])) {
    echo "Access Denied. Please log in.";
    exit();
}

$session_user_id = $_SESSION['user_id'];
$related_pwd_id = $_SESSION['related_pwd_id']; 
$success_message = '';
$error_message = '';

// 1. Fetch PWD Profile Details & Barangay Stats (Using 'photo' column)
$profile_query = "SELECT p.*, b.brgy_name, d.disability_name,
                  (SELECT COUNT(*) FROM pwd WHERE barangay_id = p.barangay_id) as total_brgy_pwd
                  FROM pwd p 
                  LEFT JOIN barangay b ON p.barangay_id = b.id 
                  LEFT JOIN disability_type d ON p.disability_type = d.id
                  WHERE p.id = ?";
$p_stmt = $conn->prepare($profile_query);
$p_stmt->bind_param("i", $related_pwd_id);
$p_stmt->execute();
$profile = $p_stmt->get_result()->fetch_assoc();

// 2. Fetch User account name from myusers
$user_account_query = $conn->query("SELECT full_name, email FROM myusers WHERE user_id = '$session_user_id' LIMIT 1");
$user_account = $user_account_query->fetch_assoc();

// Logic for Profile Picture using the 'photo' column
$photo_filename = $profile['photo']; 
$profile_pic = !empty($photo_filename) ? "../uploads/profile_pics/" . $photo_filename : "../uploads/profile_pics/default_user.png";

// 3. Handle Login Credential Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $new_email = trim($_POST['email']); 
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Update Email
        $stmt = $conn->prepare("UPDATE myusers SET email = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_email, $session_user_id);
        $stmt->execute();

        // Update Password if provided
        if (!empty($new_password)) {
            $check_stmt = $conn->prepare("SELECT password FROM myusers WHERE user_id = ?");
            $check_stmt->bind_param("i", $session_user_id);
            $check_stmt->execute();
            $user_res = $check_stmt->get_result()->fetch_assoc();

            if (!password_verify($current_password, $user_res['password'])) {
                throw new Exception('Current password incorrect.');
            }
            if ($new_password !== $confirm_password) {
                throw new Exception('New passwords do not match.');
            }
            
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $pw_stmt = $conn->prepare("UPDATE myusers SET password = ? WHERE user_id = ?");
            $pw_stmt->bind_param("si", $hashed, $session_user_id);
            $pw_stmt->execute();
        }
        $success_message = 'Account updated successfully!';
        $user_account['email'] = $new_email;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .manage-container { max-width: 1100px; margin: 20px auto; padding: 20px; font-family: 'Segoe UI', sans-serif; }
    
    /* Top Stats Grid */
    .stats-row { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 25px; 
        margin-bottom: 25px; 
    }

    /* Profile Header Card */
    .profile-card-top { 
        background:  #07a0e2; 
        color: white !important; 
        padding: 30px; 
        border-radius: 15px; 
        text-align: center; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .profile-img-container {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid rgba(255,255,255,0.4);
        margin-bottom: 15px;
        background: #f8f9fa;
    }

    .profile-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-card-top h2 { margin: 5px 0; font-size: 1.8rem; letter-spacing: 1px; color: #ffffff !important; }
    .profile-card-top p { margin: 0; color: #ffffff !important; opacity: 0.9; font-size: 0.95rem; }

    /* Barangay Count Card */
    .count-card {
        background:  #07a0e2;
        color: white !important;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
    }
    .count-card h3 { margin: 0; font-size: 1.3rem; font-weight: 400; color: #ffffff !important; }
    .count-card .big-number { font-size: 4rem; font-weight: 700; margin-top: 10px; color: #ffffff !important; }

    /* Tables & Forms */
    .sections-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    .content-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); display: flex; flex-direction: column; }
    
    .section-title { 
        font-size: 1.1rem; 
        font-weight: bold; 
        margin-bottom: 20px; 
        color: #1a3a5f; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }
    
    .profile-table { width: 100%; border-collapse: collapse; }
    .profile-table th { 
        text-align: left; 
        padding: 15px 10px; 
        background:  #07a0e2; 
        color: #ffffff; 
        width: 40%; 
        border: 1px solid #eee;
        font-size: 0.9rem;
    }
    .profile-table td { 
        padding: 15px; 
        font-weight: 600; 
        color: #2d3748; 
        border: 1px solid #eee;
        text-align: center;
    }

    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 0.9rem; }
    .form-group input { 
        width: 100%; 
        padding: 10px; 
        border: 1px solid #cbd5e0; 
        border-radius: 5px; 
        box-sizing: border-box;
        background: #fcfcfc;
    }

    .alert { padding: 12px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    .btn-save { 
        background: #0056b3; 
        color: white; 
        padding: 12px 20px; 
        border: none; 
        border-radius: 5px; 
        font-weight: bold; 
        cursor: pointer; 
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: 0.3s ease;
    }
    .btn-save:hover { background: #03478f; }

    .btn-view-more {
        margin-top: auto;
        background: #0056b3; 
        color: white;
        padding: 12px;
        border: none;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        display: block;
        transition: background 0.3s;
    }
    .btn-view-more:hover { background: #03478f; }

    /* UPDATED MODAL STYLING (Fade-in Version) */
    .pwd-modal-backdrop {
        display: none; 
        position: fixed; 
        inset: 0; 
        background: rgba(0,0,0,0.7);
        z-index: 9999; 
        overflow: hidden;
        justify-content: center;
        align-items: center;
    }
    .pwd-modal-content {
        position: relative; 
        background: #fff; 
        width: 95%; 
        max-width: 1100px;
        margin: 2vh auto; 
        border-radius: 8px; 
        height: 96vh; 
        overflow: hidden;
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
    }
    .close-view {
        position: absolute; 
        right: 25px; 
        top: 15px; 
        font-size: 40px;
        font-weight: bold; 
        cursor: pointer; 
        color: #333; 
        z-index: 10001;
    }
    .no-scroll { overflow: hidden !important; }

    @media (max-width: 900px) { 
        .stats-row, .sections-grid { grid-template-columns: 1fr; } 
    }
</style>

<div class="manage-container">
    <div class="stats-row">
        <div class="profile-card-top">
            <div class="profile-img-container">
                <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture">
            </div>
            <h2><?= htmlspecialchars($user_account['full_name']) ?></h2>
            <p>Member Profile & Security Settings</p>
        </div>

        <div class="count-card">
            <h3>PWDs in <?= htmlspecialchars($profile['brgy_name']) ?></h3>
            <div class="big-number"><?= number_format($profile['total_brgy_pwd']) ?></div>
        </div>
    </div>

    <div class="sections-grid">
        <div class="content-card">
            <div class="section-title"><i class="fas fa-address-card"></i> Personal Information</div>
            <table class="profile-table">
                <tr>
                    <th>Full Name</th>
                    <td><?= htmlspecialchars($profile['first_name'] . " " . $profile['middle_name'] . " " . $profile['last_name']) ?></td>
                </tr>
                <tr>
                    <th>Disability Type</th>
                    <td><?= htmlspecialchars($profile['disability_name']) ?></td>
                </tr>
                <tr>
                    <th>Contact Number</th>
                    <td><?= htmlspecialchars($profile['contact_number'] ?: 'None') ?></td>
                </tr>
                <tr>
                    <th>Resident Barangay</th>
                    <td><?= htmlspecialchars($profile['brgy_name']) ?></td>
                </tr>
            </table>
            
            <p style="font-size: 0.8rem; color: #7f8c8d; margin-top: 15px; margin-bottom: 20px; line-height: 1.4;">
                <i class="fas fa-info-circle"></i> To change personal data, please contact your Barangay Health Office.
            </p>

            <button type="button" class="btn-view-more open-view-btn">
                <i class="fas fa-eye"></i> View Full Form Details
            </button>
        </div>

        <div class="content-card">
            <div class="section-title"><i class="fas fa-lock"></i> Login Credentials</div>
            
            <?php if ($success_message): ?><div class="alert alert-success"><?= $success_message ?></div><?php endif; ?>
            <?php if ($error_message): ?><div class="alert alert-error"><?= $error_message ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user_account['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Leave blank to keep current">
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password">
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Account Updates
                </button>
            </form>
        </div>
    </div>
</div>

<div class="pwd-modal-backdrop">
    <div class="pwd-modal-content">
        <span class="close-view">&times;</span>
        <iframe id="viewFrame" src="" style="width: 100%; height: 100%; border: none; display: block;"></iframe>
    </div>
</div>

<script>
$(document).ready(function () {
    // Open Modal with Fade and Path correction
    $(".open-view-btn").click(function() {
        var viewUrl = "../view_pwd.php?id=<?= $related_pwd_id ?>";
        $("#viewFrame").attr("src", viewUrl);
        $(".pwd-modal-backdrop").css("display", "flex").hide().fadeIn();
        $("body").addClass("no-scroll");
    });

    // Close Modal Logic
    function closeModal() {
        $(".pwd-modal-backdrop").fadeOut(function() {
            $(this).hide();
            $("#viewFrame").attr("src", "");
            $("body").removeClass("no-scroll");
        });
    }

    $(".close-view").click(function() {
        closeModal();
    });

    $(document).keyup(function(e) {
        if (e.key === "Escape") closeModal();
    });

    $(".pwd-modal-backdrop").click(function(event) {
        if ($(event.target).is(".pwd-modal-backdrop")) {
            closeModal();
        }
    });
});
</script>