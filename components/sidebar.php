<?php
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$employee_pages = ['add_employee', 'manage_employee', 'employee_edit'];
$delivery_pages = ['delivery_settings', 'delivery_earnings'];
$ridesharing_pages = ['ride_sharing_settings', 'ride_sharing_earnings'];
$attendance_pages = ['attendance_shifts', 'attendance_holidays', 'attendance_mark', 'attendance_report'];
$is_employee_section = in_array($current_page, $employee_pages, true);
$is_delivery_section = in_array($current_page, $delivery_pages, true);
$is_ridesharing_section = in_array($current_page, $ridesharing_pages, true);
$is_attendance_section = in_array($current_page, $attendance_pages, true);
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

        <!-- Employee Management -->
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
            </ul>
        </li>

        <!-- Delivery -->
        <li class="has-sub root-level<?= $is_delivery_section ? ' opened active' : '' ?>">
            <a href="#">
                <i class="entypo-basket"></i>
                <span class="title">Delivery</span>
            </a>
            <ul<?= $is_delivery_section ? ' class="visible"' : '' ?>>
                <li class="<?= $current_page === 'delivery_settings' ? 'active' : '' ?>">
                    <a href="delivery_settings">
                        <span class="title">Delivery rate setting</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'delivery_earnings' ? 'active' : '' ?>">
                    <a href="delivery_earnings">
                        <span class="title">Delivery Earnings</span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Ridesharing -->
        <li class="has-sub root-level<?= $is_ridesharing_section ? ' opened active' : '' ?>">
            <a href="#">
                <i class="entypo-direction"></i>
                <span class="title">Ridesharing</span>
            </a>
            <ul<?= $is_ridesharing_section ? ' class="visible"' : '' ?>>
                <li class="<?= $current_page === 'ride_sharing_settings' ? 'active' : '' ?>">
                    <a href="ride_sharing_settings">
                        <span class="title">Ridesharing rate setting</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'ride_sharing_earnings' ? 'active' : '' ?>">
                    <a href="ride_sharing_earnings">
                        <span class="title">Ride Sharing Earnings</span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Attendance -->
        <li class="has-sub root-level<?= $is_attendance_section ? ' opened active' : '' ?>">
            <a href="#">
                <i class="entypo-calendar"></i>
                <span class="title">Attendance</span>
            </a>
            <ul<?= $is_attendance_section ? ' class="visible"' : '' ?>>
                <li class="<?= $current_page === 'attendance_shifts' ? 'active' : '' ?>">
                    <a href="attendance_shifts">
                        <span class="title">Attendance Shifts</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'attendance_holidays' ? 'active' : '' ?>">
                    <a href="attendance_holidays">
                        <span class="title">Attendance Holidays</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'attendance_mark' ? 'active' : '' ?>">
                    <a href="attendance_mark">
                        <span class="title">Mark Attendance</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'attendance_report' ? 'active' : '' ?>">
                    <a href="attendance_report">
                        <span class="title">Attendance Report</span>
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
