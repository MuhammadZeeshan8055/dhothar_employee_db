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


        <!-- Dashboard -->
        <li class="opened">
            <a href="index">
                <i class="entypo-gauge"></i>
                <span class="title">Dashboard</span>
            </a>
        </li>


        <li class="has-sub root-level">
            <a href="">
                <i class="entypo-window"></i>
                <span class="title">Employee Management</span>
            </a>
            <ul class="">
                <li class="">
                    <a href="add_employee">
                        <span class="title">Add New Employee</span>
                    </a>
                </li>

                <li class="">
                    <a href="manage_employee">
                        <span class="title">Manage Employee Details</span>
                    </a>
                </li>

                
            </ul>
        </li>

    </ul>
</div>