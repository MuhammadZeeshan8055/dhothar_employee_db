<div class="sidebar-menu-inner">
    <header class="logo-env">
        <div class="logo">
            <a href="index">
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

        <!-- New Case Audit History -->
        <?php if ($userrole == 'Super Admin'): ?>

            <!-- <li class="">
                <a href="<?=$base_url?>index">
                    <button class="btn btn-primary">SOFTWARE</button>
                </a>
            </li> -->

        <?php endif; ?>

        <!-- Attendance Menu -->
        <!-- <li class="has-sub root-level">
            <a href="attendance_dashboard">
                <i class="entypo-window"></i>
                <span class="title">Attendance</span>
            </a>
            <ul class="">
                <li class="">
                    <a href="attendance_dashboard">
                        <span class="title">Mark Attendance</span>
                    </a>
                </li>

                <li class="">
                    <a href="attendance_report">
                        <span class="title">Attendance Report</span>
                    </a>
                </li>

                <li class="">
                    <a href="salary_report">
                        <span class="title">Salary Report</span>
                    </a>
                </li>

                <li class="">
                    <a href="salary_slip">
                        <span class="title">Salary Slip</span>
                    </a>
                </li>

                <li class="">
                    <a href="leaves">
                        <span class="title">Leave</span>
                    </a>
                </li>

                <li class="">
                    <a href="leave_requests">
                        <span class="title">Leave Requests
                            (2) </span>
                    </a>
                </li>

                <li class="">
                    <a href="attendance_marking_issue">
                        <span class="title">Public Holiday/Other</span>
                    </a>
                </li>

                <li class="">
                    <a href="settings">
                        <span class="title">Settings</span>
                    </a>
                </li>
            </ul>
        </li> -->

        <!-- Dashboard -->
        <li class="opened">
            <a href="index">
                <i class="entypo-gauge"></i>
                <span class="title">Dashboard</span>
            </a>
        </li>

    </ul>
</div>