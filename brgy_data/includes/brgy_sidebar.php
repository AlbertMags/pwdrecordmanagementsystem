<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : '';
// Updated list to match your Barangay page names
$manage_pages = ['brgy_support_center', 'brgy_pwd', 'brgy_official_list', 'brgy_monthly_report'];
$is_manage_active = in_array($current_page, $manage_pages);
?>

<script>
    (function() {
        const manageState = localStorage.getItem("manageMenuOpen");
        // Immediate override to prevent flicker
        if (manageState === "true") {
            document.write('<style>#manageSubmenu { display: block !important; }</style>');
        } else if (manageState === "false") {
            document.write('<style>#manageSubmenu { display: none !important; }</style>');
        }
    })();
</script>

<div class="sidebar">
    <div class="sidebar-header" style="text-align: center; padding: 15px 15px;">
      <img src="/PWD/uploads/mswdo.jpg" alt="Logo" style="width: 60px; height: 60px; border-radius: 12px; margin-bottom: 10px; object-fit: cover;">
        <h3 style="margin: 0; font-size: 16px; line-height: 1.2;">PWD Record <br> Barangay System</h3>
        <p style="font-size: 10px; margin-top: 10px; color: #ffffff; opacity: 0.9; font-weight: 400; letter-spacing: 0.3px; line-height: 1.2;">
            Municipal Social Welfare Development Office <br> 
            <span style="display: inline-block; margin-top: 1px;">of E.B. Magalona</span>
        </p>
    </div>

    <hr style="margin: 10px 20px; border: 0; border-top: 1px solid rgba(252, 252, 252, 0.2);">
    
    <ul>
        <li class="<?= ($current_page == 'brgy_dashboard' || $current_page == '') ? 'active' : '' ?>">
            <a href="/PWD/brgy_data/brgy_dashboard">
                <i class="fas fa-chart-line"></i> Information Hub
            </a>
        </li>
        
        <li class="<?= $is_manage_active ? 'parent-active' : '' ?>">
            <a href="javascript:void(0)" class="manage-toggle" id="manageBtn">
                <i class="fas fa-tasks"></i> 
                <span>Manage</span> 
                <span id="manageArrow" style="font-size: 10px; float: right; margin-top: 5px;"><?= $is_manage_active ? '▼' : '▶' ?></span>
            </a>
            <ul class="submenu" id="manageSubmenu" style="display: <?= $is_manage_active ? 'block' : 'none' ?>;">
                <li class="<?= ($current_page == 'brgy_support_center') ? 'active' : '' ?>">
                    <a href="/PWD/brgy_data/brgy_support_center"><i class="fas fa-hand-holding-heart"></i> Support Center</a>
                </li>
                <li class="<?= ($current_page == 'brgy_pwd') ? 'active' : '' ?>">
                    <a href="/PWD/brgy_data/brgy_pwd"><i class="fas fa-file-medical"></i> Review Applications</a>
                </li>
                <li class="<?= ($current_page == 'brgy_official_list') ? 'active' : '' ?>">
                    <a href="/PWD/brgy_data/brgy_official_list"><i class="fas fa-address-book"></i> Official List</a>
                </li>
                <li class="<?= ($current_page == 'brgy_monthly_report') ? 'active' : '' ?>">
                    <a href="/PWD/brgy_data/brgy_monthly_report"><i class="fas fa-file-invoice"></i> Barangay Reports</a>
                </li>
            </ul>
        </li>
        
        <li>
            <a href="/PWD/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>