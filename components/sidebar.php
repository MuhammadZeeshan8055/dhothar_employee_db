<?php
/**
 * Sidebar menu
 * -------------
 * $current_page = name of this PHP file without .php
 * Example: manage_employee.php → "manage_employee"
 */

$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');

// Which pages belong to each menu group
$employee_pages = array('add_employee', 'manage_employee', 'employee_edit');
$companies_pages = array('manage_companies', 'manage_vehicle_companies');
$delivery_pages = array('delivery_settings', 'delivery_earnings');
$ridesharing_pages = array('ride_sharing_settings', 'ride_sharing_earnings');
$attendance_pages = array('attendance_shifts', 'attendance_holidays', 'attendance_mark', 'attendance_report');

// Is the current page inside this group?
$show_employee_open = in_array($current_page, $employee_pages, true);
$show_companies_open = in_array($current_page, $companies_pages, true);
$show_delivery_open = in_array($current_page, $delivery_pages, true);
$show_ridesharing_open = in_array($current_page, $ridesharing_pages, true);
$show_attendance_open = in_array($current_page, $attendance_pages, true);
$is_dashboard = ($current_page === 'index');
$is_rate_manager = isRateManager();

// Small helpers so the HTML below stays easy to read
if (!function_exists('sidebar_parent_class')) {
    function sidebar_parent_class($is_open)
    {
        if ($is_open) {
            return 'has-sub root-level opened active';
        }
        return 'has-sub root-level';
    }
}

if (!function_exists('sidebar_item_class')) {
    function sidebar_item_class($current_page, $page_name)
    {
        if ($current_page === $page_name) {
            return 'active';
        }
        return '';
    }
}

if (!function_exists('sidebar_ul_class')) {
    function sidebar_ul_class($is_open)
    {
        if ($is_open) {
            return ' class="visible"';
        }
        return '';
    }
}

// Manage Employees is also active while editing an employee
$manage_employee_active = '';
if ($current_page === 'manage_employee' || $current_page === 'employee_edit') {
    $manage_employee_active = 'active';
}

// Logo goes to dashboard, or rate settings for Rate Manager
$logo_link = 'index';
if ($is_rate_manager) {
    $logo_link = 'delivery_settings';
}
?>
<script src="<?= $base_url ?>assets/js/dhothar-theme.js" defer></script>
<script src="<?= $base_url ?>assets/js/sidebar-nav.js" defer></script>

<div class="sidebar-menu-inner">
    <header class="logo-env">
        <div class="logo">
            <a href="<?= htmlspecialchars($logo_link) ?>">
                <img src="dhothar_logo.png" width="120" height="50" alt="Dhothar International" />
            </a>
        </div>
        <div class="sidebar-collapse">
            <!-- <a href="#" class="sidebar-collapse-icon"><i class="entypo-menu"></i></a> -->
        </div>
        <div class="sidebar-mobile-menu visible-xs">
            <!-- <a href="#" class="with-animation"><i class="entypo-menu"></i></a> -->
        </div>
    </header>

    <ul id="main-menu" class="main-menu">

        <?php if ($is_rate_manager): ?>

            <li class="<?= sidebar_item_class($current_page, 'delivery_settings') ?>">
                <a href="delivery_settings">
                    <i class="entypo-cog"></i>
                    <span class="title">Delivery Rate Settings</span>
                </a>
            </li>

        <?php else: ?>

            <!-- Dashboard -->
            <li class="<?= $is_dashboard ? 'active' : '' ?>">
                <a href="index">
                    <i class="entypo-gauge"></i>
                    <span class="title">Dashboard</span>
                </a>
            </li>

            <!-- Employees -->
            <li class="<?= sidebar_parent_class($show_employee_open) ?>">
                <a href="manage_employee">
                    <i class="entypo-users"></i>
                    <span class="title">Employees</span>
                </a>
                <ul<?= sidebar_ul_class($show_employee_open) ?>>
                    <li class="<?= sidebar_item_class($current_page, 'add_employee') ?>">
                        <a href="add_employee"><span class="title">Add Employee</span></a>
                    </li>
                    <li class="<?= $manage_employee_active ?>">
                        <a href="manage_employee"><span class="title">Manage Employees</span></a>
                    </li>
                </ul>
            </li>

            <!-- Companies -->
            <li class="<?= sidebar_parent_class($show_companies_open) ?>">
                <a href="manage_companies">
                    <i class="entypo-briefcase"></i>
                    <span class="title">Companies</span>
                </a>
                <ul<?= sidebar_ul_class($show_companies_open) ?>>
                    <li class="<?= sidebar_item_class($current_page, 'manage_companies') ?>">
                        <a href="manage_companies"><span class="title">Employee Companies</span></a>
                    </li>
                    <li class="<?= sidebar_item_class($current_page, 'manage_vehicle_companies') ?>">
                        <a href="manage_vehicle_companies"><span class="title">Vehicle Companies</span></a>
                    </li>
                </ul>
            </li>

            <!-- Delivery -->
            <li class="<?= sidebar_parent_class($show_delivery_open) ?>">
                <a href="delivery_earnings">
                    <i class="entypo-basket"></i>
                    <span class="title">Delivery</span>
                </a>
                <ul<?= sidebar_ul_class($show_delivery_open) ?>>
                    <li class="<?= sidebar_item_class($current_page, 'delivery_settings') ?>">
                        <a href="delivery_settings"><span class="title">Rate Settings</span></a>
                    </li>
                    <li class="<?= sidebar_item_class($current_page, 'delivery_earnings') ?>">
                        <a href="delivery_earnings"><span class="title">Delivery Earnings</span></a>
                    </li>
                </ul>
            </li>

            <!-- Ridesharing -->
            <li class="<?= sidebar_parent_class($show_ridesharing_open) ?>">
                <a href="ride_sharing_earnings">
                    <i class="entypo-direction"></i>
                    <span class="title">Ridesharing</span>
                </a>
                <ul<?= sidebar_ul_class($show_ridesharing_open) ?>>
                    <li class="<?= sidebar_item_class($current_page, 'ride_sharing_settings') ?>">
                        <a href="ride_sharing_settings"><span class="title">Rate Settings</span></a>
                    </li>
                    <li class="<?= sidebar_item_class($current_page, 'ride_sharing_earnings') ?>">
                        <a href="ride_sharing_earnings"><span class="title">Ride Sharing Earnings</span></a>
                    </li>
                </ul>
            </li>

            <!-- Attendance -->
            <li class="<?= sidebar_parent_class($show_attendance_open) ?>">
                <a href="attendance_mark">
                    <i class="entypo-calendar"></i>
                    <span class="title">Attendance</span>
                </a>
                <ul<?= sidebar_ul_class($show_attendance_open) ?>>
                    <li class="<?= sidebar_item_class($current_page, 'attendance_mark') ?>">
                        <a href="attendance_mark"><span class="title">Mark Attendance</span></a>
                    </li>
                    <li class="<?= sidebar_item_class($current_page, 'attendance_report') ?>">
                        <a href="attendance_report"><span class="title">Attendance Report</span></a>
                    </li>
                    <li class="<?= sidebar_item_class($current_page, 'attendance_shifts') ?>">
                        <a href="attendance_shifts"><span class="title">Shifts</span></a>
                    </li>
                    <li class="<?= sidebar_item_class($current_page, 'attendance_holidays') ?>">
                        <a href="attendance_holidays"><span class="title">Holidays</span></a>
                    </li>
                </ul>
            </li>

        <?php endif; ?>

    </ul>
</div>
