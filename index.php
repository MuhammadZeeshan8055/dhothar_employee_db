<?php

include('session.php');
include('database.php');

$obj = new Database();

$table = 'add_employee_details';

// Dashboard stats
$totalUsers = $obj->count($table);
$expiredPassports = $obj->count($table, '*', 'passport_expiry_date < CURDATE()');
$expiredLicenses = $obj->count($table, '*', 'license_expiry < CURDATE()');
$newJoinees = $obj->count(
    $table,
    '*',
    'MONTH(date_joining) = MONTH(CURDATE()) AND YEAR(date_joining) = YEAR(CURDATE())'
);
$activeEmployees = $obj->count($table, '*', 'status = 1');
$onLeaveEmployees = $obj->count($table, '*', 'status = 2');
$inactiveEmployees = $obj->count($table, '*', 'status = 0');

$firstName = trim(explode(' ', $currentUser['name'] ?? 'User')[0]);

// Line chart: joinings by date
$obj->select(
    $table,
    'DATE(date_joining) as join_date, COUNT(*) as total',
    null,
    null,
    'join_date ASC',
    null,
    'DATE(date_joining)'
);
$rows = $obj->getResult();
$labels = [];
$data = [];
if (is_array($rows)) {
    foreach ($rows as $r) {
        if (empty($r['join_date'])) {
            continue;
        }
        $labels[] = formatDate($r['join_date']);
        $data[] = (int) ($r['total'] ?? 0);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Dhothar International" />
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Dhothar International Employee DB | Dashboard</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <?php include('components/head_theme.php'); ?>
    <script src="<?= $base_url ?>assets/js/jquery-1.11.3.min.js"></script>
</head>

<body class="page-body dh-dashboard">
    <div class="page-container">
        <div class="sidebar-menu">
            <?php include('components/sidebar.php'); ?>
        </div>

        <div class="main-content">
            <?php include('components/header.php'); ?>

            <div class="dh-dash-welcome dh-animate">
                <div>
                    <span class="dh-brand">Dhothar</span>
                    <h1>Welcome back, <?= htmlspecialchars($firstName) ?>!</h1>
                    <p>Here’s what’s happening with your employees today.</p>
                </div>
            </div>

            <div class="row dh-stat-grid dh-animate dh-animate-delay-1">
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-red">
                        <div class="icon"><i class="entypo-users"></i></div>
                        <div class="num" data-start="0" data-end="<?= (int) $totalUsers ?>" data-postfix=""
                            data-duration="1500" data-delay="0"><?= (int) $totalUsers ?></div>
                        <h3>Registered employees</h3>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-green">
                        <div class="icon"><i class="entypo-chart-bar"></i></div>
                        <div class="num" data-start="0" data-end="<?= (int) $expiredPassports ?>" data-postfix=""
                            data-duration="1500" data-delay="400"><?= (int) $expiredPassports ?></div>
                        <h3>Expired Passports</h3>
                    </div>
                </div>
                <div class="clear visible-xs"></div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-aqua">
                        <div class="icon"><i class="entypo-key"></i></div>
                        <div class="num" data-start="0" data-end="<?= (int) $expiredLicenses ?>" data-postfix=""
                            data-duration="1500" data-delay="800"><?= (int) $expiredLicenses ?></div>
                        <h3>Expired License</h3>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-blue">
                        <div class="icon"><i class="entypo-user-add"></i></div>
                        <div class="num" data-start="0" data-end="<?= (int) $newJoinees ?>" data-postfix=""
                            data-duration="1500" data-delay="1200"><?= (int) $newJoinees ?></div>
                        <h3>New Joinies</h3>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-green">
                        <div class="icon"><i class="entypo-check"></i></div>
                        <div class="num" data-start="0" data-end="<?= (int) $activeEmployees ?>" data-postfix=""
                            data-duration="1500" data-delay="400"><?= (int) $activeEmployees ?></div>
                        <h3>Active</h3>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-aqua">
                        <div class="icon"><i class="entypo-clock"></i></div>
                        <div class="num" data-start="0" data-end="<?= (int) $onLeaveEmployees ?>" data-postfix=""
                            data-duration="1500" data-delay="800"><?= (int) $onLeaveEmployees ?></div>
                        <h3>On Leave</h3>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-red">
                        <div class="icon"><i class="entypo-cancel"></i></div>
                        <div class="num" data-start="0" data-end="<?= (int) $inactiveEmployees ?>" data-postfix=""
                            data-duration="1500" data-delay="1200"><?= (int) $inactiveEmployees ?></div>
                        <h3>Inactive</h3>
                    </div>
                </div>
            </div>

            <div class="row dh-animate dh-animate-delay-3" style="margin-top:8px">
                <div class="col-12 col-lg-6 col-xl-6 d-flex">
                    <div class="card radius-10 overflow-hidden w-100 dh-panel">
                        <div class="card-body">
                            <h6 class="dh-panel-title">Date Wise Employee Joining</h6>
                            <div class="dh-chart-wrap">
                                <canvas id="myChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 col-xl-6 d-flex">
                    <div class="card radius-10 overflow-hidden w-100 dh-panel">
                        <div class="card-body">
                            <h6 class="dh-panel-title">Employee Status Overview</h6>
                            <div class="dh-chart-wrap">
                                <canvas id="myChart2"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include('components/footer.php'); ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- TweenMax is required for Neon sidebar submenus -->
    <script src="<?= $base_url ?>assets/js/gsap/TweenMax.min.js"></script>
    <script src="<?= $base_url ?>assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js"></script>
    <script src="<?= $base_url ?>assets/js/bootstrap.js"></script>
    <script src="<?= $base_url ?>assets/js/joinable.js"></script>
    <script src="<?= $base_url ?>assets/js/resizeable.js"></script>
    <script src="<?= $base_url ?>assets/js/neon-api.js"></script>
    <script src="<?= $base_url ?>assets/js/cookies.min.js"></script>
    <script src="<?= $base_url ?>assets/js/neon-custom.js"></script>
    <script>
    (function () {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        var textColor = isDark ? '#a8bdd0' : '#5a6f82';
        if (window.Chart && Chart.defaults) {
            Chart.defaults.color = textColor;
        }

        var labels = <?= json_encode($labels) ?>;
        var data = <?= json_encode($data) ?>;

        var lineEl = document.getElementById('myChart');
        var barEl = document.getElementById('myChart2');
        if (!lineEl || !barEl) return;

        new Chart(lineEl, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Employees Joined',
                    data: data,
                    borderColor: '#00afeb',
                    backgroundColor: 'rgba(86, 201, 246, 0.25)',
                    pointBackgroundColor: '#d8ba6f',
                    pointBorderColor: '#00afeb',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: true } },
                scales: {
                    x: { grid: { color: 'rgba(0,175,235,0.08)' } },
                    y: { grid: { color: 'rgba(0,175,235,0.08)' }, beginAtZero: true }
                }
            }
        });

        new Chart(barEl, {
            type: 'bar',
            data: {
                labels: ['Active', 'On Leave', 'Inactive', 'Expired Passports', 'New Joinies'],
                datasets: [{
                    label: 'Employee Stats',
                    data: [
                        <?= (int) $activeEmployees ?>,
                        <?= (int) $onLeaveEmployees ?>,
                        <?= (int) $inactiveEmployees ?>,
                        <?= (int) $expiredPassports ?>,
                        <?= (int) $newJoinees ?>
                    ],
                    backgroundColor: [
                        '#00afeb',
                        '#e5d8ab',
                        '#56c9f6',
                        '#d8ba6f',
                        '#00afeb'
                    ],
                    borderRadius: 6,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: 'rgba(0,175,235,0.08)' }, beginAtZero: true }
                }
            }
        });
    })();
    </script>
</body>
</html>
