<?php
// 1. UPDATED BASE URL: Removed the /backup/ prefix to match your new folder structure
$base_url = "/PWD/"; 
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove the base_url path from the request to get just the "slug"
$relative_path = str_replace($base_url, '', $request_uri);
$current_page = trim($relative_path, '/');

// 2. ACTIVE LINK LOGIC
if (empty($current_page) || $current_page == 'index.php' || $current_page == 'information_hub') {
    $current_page = 'information_hub';
}

// 3. Keep logic for the manage menu - Added 'program_distribution' to the array
$manage_pages = ['support_center', 'program_distribution', 'barangay', 'barangay_user', 'PWD', 'official_list', 'monthly_report'];
$is_manage_active = in_array($current_page, $manage_pages);
?>

<script>
    (function() {
        // Keeps the menu open if localStorage says so OR if a child is active
        const manageState = localStorage.getItem("manageMenuOpen");
        const isChildActive = <?= json_encode($is_manage_active) ?>;
        
        if (manageState === "true" || isChildActive) {
            document.write('<style>#manageSubmenu { display: block !important; }</style>');
        }
    })();
</script>

<div class="sidebar">
    <div class="sidebar-header" style="text-align: center; padding: 10px 15px;">
        <img src="<?= $base_url ?>uploads/mswdo.jpg" alt="Logo" style="width: 60px; height: 60px; border-radius: 12px; margin-bottom: 10px; object-fit: cover;">
        <h3 style="margin: 0; font-size: 16px; line-height: 1.2;">PWD Record <br> Management System</h3>
    </div>

    <hr style="margin: 10px 20px; border: 0; border-top: 1px solid rgba(252, 252, 252, 0.2);">
    
    <ul>
        <li class="<?= ($current_page == 'information_hub') ? 'active' : '' ?>">
            <a href="<?= $base_url ?>information_hub">
                <i class="fas fa-chart-line"></i> Information Hub
            </a>
        </li>
        
        <li class="<?= $is_manage_active ? 'parent-active' : '' ?>">
            <a href="javascript:void(0)" class="manage-toggle" id="manageBtn">
                <i class="fas fa-tasks"></i> <span>Manage</span> 
                <span id="manageArrow"><?= $is_manage_active ? '▼' : '▶' ?></span>
            </a>
            <ul class="submenu" id="manageSubmenu" style="display: <?= $is_manage_active ? 'block' : 'none' ?>;">
                <li class="<?= ($current_page == 'support_center') ? 'active' : '' ?>">
                    <a href="<?= $base_url ?>support_center"><i class="fas fa-hand-holding-heart"></i> Support Center</a>
                </li>
                <li class="<?= ($current_page == 'program_distribution') ? 'active' : '' ?>">
                    <a href="<?= $base_url ?>program_distribution"><i class="fas fa-boxes"></i> Program Distribution</a>
                </li>
                <li class="<?= ($current_page == 'barangay') ? 'active' : '' ?>">
                    <a href="<?= $base_url ?>barangay"><i class="fas fa-map-marker-alt"></i> Barangays</a>
                </li>
                <li class="<?= ($current_page == 'barangay_user') ? 'active' : '' ?>">
                    <a href="<?= $base_url ?>barangay_user"><i class="fas fa-users-cog"></i> Barangay User</a>
                </li>
                <li class="<?= ($current_page == 'PWD') ? 'active' : '' ?>">
                    <a href="<?= $base_url ?>PWD"><i class="fas fa-file-medical"></i> Review Applications</a>
                </li>
                <li class="<?= ($current_page == 'official_list') ? 'active' : '' ?>">
                    <a href="<?= $base_url ?>official_list"><i class="fas fa-address-book"></i> Official List</a>
                </li>
                <li class="<?= ($current_page == 'monthly_report') ? 'active' : '' ?>">
                    <a href="<?= $base_url ?>monthly_report"><i class="fas fa-file-invoice"></i> Monthly Reports</a>
                </li>
            </ul>
        </li>
        <li><a href="<?= $base_url ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>