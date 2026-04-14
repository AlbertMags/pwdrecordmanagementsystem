<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : 'pwd_dashboard';
?>

<div class="sidebar">
    <div class="sidebar-header" style="text-align: center; padding: -10px 15px;">
        <img src="../uploads/mswdo.jpg" alt="Logo" style="width: 60px; height: 60px; border-radius: 12px; margin-bottom: 10px; object-fit: cover;">
        <h3 style="margin: 0; font-size: 16px; line-height: 1.2;">PWD Record <br> Member System</h3>
        <p style="font-size: 10px; margin-top: 10px; color: #ffffff; opacity: 0.9; font-weight: 400; letter-spacing: 0.3px; line-height: 1.2;">
            Municipal Social Welfare Development Office <br> 
            <span style="display: inline-block; margin-top: 1px;">of E.B. Magalona</span>
        </p>
    </div>

    <hr style="margin: 10px 20px; border: 0; border-top: 1px solid rgba(252, 252, 252, 0.2);">
    
    <ul>
        <li class="<?= ($current_page == 'pwd_dashboard') ? 'active' : '' ?>">
            <a href="dashboard">
                <i class="fas fa-chart-line"></i> Information Hub
            </a>
        </li>
        
        <li class="<?= ($current_page == 'manage_account') ? 'active' : '' ?>">
            <a href="manage_account">
                <i class="fas fa-user-shield"></i> Manage Account
            </a>
        </li>
        
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>