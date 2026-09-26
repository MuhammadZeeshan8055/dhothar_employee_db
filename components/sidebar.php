<?php
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$employee_pages = ['add_employee', 'manage_employee', 'employee_edit', 'delivery_settings', 'delivery_earnings'];
$is_employee_section = in_array($current_page, $employee_pages, true);
$is_dashboard = ($current_page === 'index');
$is_rate_manager = isRateManager();
?>
<div class="sidebar-menu-inner">
    <header class="logo-env">
        <div class="logo">
            <a href="<?= $is_rate_manager ? 'delivery_settings' : 'index' ?>">
                <img src="dhothar_logo.png" width="120px" height="50px" alt />
            </a>
        </div>
        <div class="sidebar-collapse">
            <a href="#" class="sidebar-collapse-icon">
                <i class="entypo-menu"></i>
            </a>
        </div>
        <div class="sidebar-mobile-menu visible-xs">
            <a href="#" class="with-animation">
                <i class="entypo-menu"></i>
            </a>
        </div>
    </header>

    <ul id="main-menu" class="main-menu">

        <?php if (!$is_rate_manager): ?>

        <!-- Dashboard -->
        <li class="<?= $is_dashboard ? 'active' : '' ?>">
            <a href="index">
                <i class="entypo-gauge"></i>
                <span class="title">Dashboard</span>
            </a>
        </li>

        <li class="has-sub root-level<?= $is_employee_section ? ' opened active' : '' ?>">
            <a href="#">
                <i class="entypo-window"></i>
                <span class="title">Employee Management</span>
            </a>
            <ul<?= $is_employee_section ? ' class="visible"' : '' ?>>
                <li class="<?= $current_page === 'add_employee' ? 'active' : '' ?>">
                    <a href="add_employee">
                        <span class="title">Add New Employee</span>
                    </a>
                </li>

                <li class="<?= $current_page === 'manage_employee' || $current_page === 'employee_edit' ? 'active' : '' ?>">
                    <a href="manage_employee">
                        <span class="title">Manage Employee Details</span>
                    </a>
                </li>

                <li class="<?= $current_page === 'delivery_settings' ? 'active' : '' ?>">
                    <a href="delivery_settings">
                        <span class="title">Employee Rate Settings</span>
                    </a>
                </li>

                <li class="<?= $current_page === 'delivery_earnings' ? 'active' : '' ?>">
                    <a href="delivery_earnings">
                        <span class="title">Delivery Earnings</span>
                    </a>
                </li>
            </ul>
        </li>

        <?php else: ?>

        <li class="<?= $current_page === 'delivery_settings' ? 'active' : '' ?>">
            <a href="delivery_settings">
                <i class="entypo-cog"></i>
                <span class="title">Employee Rate Settings</span>
            </a>
        </li>

        <?php endif; ?>

    </ul>
</div>
