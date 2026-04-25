<?php

include('session.php');
include('database.php');

$obj = new Database();

// total registered users  
$totalUsers = $obj->count("add_employee_details");

// expired passports
$expiredPassports = $obj->count(
    "add_employee_details",
    "*",
    "passport_expiry_date < CURDATE()"
);

// expired license
$expiredLicenses = $obj->count(
    "add_employee_details",
    "*",
    "license_expiry < CURDATE()"
);

// new joinies
$newJoinees = $obj->count(
    "add_employee_details",
    "*",
    "MONTH(date_joining) = MONTH(CURDATE()) AND YEAR(date_joining) = YEAR(CURDATE())"
);

?>
<!DOCTYPE html>
<html lang="en">
<!-- Added by HTTrack -->
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<!-- /Added by HTTrack -->

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Dhothar International" />
    <meta name="author" content="Laborator.co" />
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Dhothar International Employee DB | Dashboard</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css" id="style-resource-2">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic"
        id="style-resource-3">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css" id="style-resource-4">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css" id="style-resource-5">

    <script src="<?= $base_url ?>assets/js/jquery-1.11.3.min.js"></script>
</head>

<body class="">
    <div class="page-container">
        <div class="sidebar-menu">

            <?php include('components/sidebar.php'); ?>


        </div>
        <div class="main-content">


            <?php include('components/header.php'); ?>


            <hr />


            <div class="row">
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-red">
                        <div class="icon"><i class="entypo-users"></i></div>
                        <div class="num" data-start="0" data-end="<?= $totalUsers ?>" data-postfix=""
                            data-duration="1500" data-delay="0">
                            <?= $totalUsers ?>
                        </div>
                        <h3>Registered users</h3>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-green">
                        <div class="icon"><i class="entypo-chart-bar"></i></div>
                        <div class="num" data-start="0" data-end="<?= $expiredPassports ?>" data-postfix=""
                            data-duration="1500" data-delay="600"><?= $expiredPassports ?></div>
                        <h3>Expired Passports</h3>
                    </div>
                </div>
                <div class="clear visible-xs"></div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-aqua">
                        <div class="icon"><i class="entypo-mail"></i></div>
                        <div class="num" data-start="0" data-end="<?= $expiredLicenses ?>" data-postfix=""
                            data-duration="1500" data-delay="1200"><?= $expiredLicenses ?></div>
                        <h3>Expired License</h3>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-blue">
                        <div class="icon"><i class="entypo-rss"></i></div>
                        <div class="num" data-start="0" data-end="<?= $newJoinees ?>" data-postfix=""
                            data-duration="1500" data-delay="1800"><?= $newJoinees ?></div>
                        <h3>New Joinies</h3>
                    </div>
                </div>
            </div>

            <hr>
            
            <?php

            $table = "add_employee_details";

            $obj->select(
                "add_employee_details",
                "DATE(date_joining) as join_date, COUNT(*) as total",
                null,
                null,
                "join_date ASC",
                null,
                "DATE(date_joining)"
            );
            $rows = $obj->getResult();

            $labels = [];
            $data = [];

            if (!empty($rows)) {
                foreach ($rows as $r) {
                    $labels[] = $r['join_date'];
                    $data[] = $r['total'];
                }
            }

            /* =========================
               BAR CHART DATA (STATUS)
            ========================= */
            $activeEmployees = $obj->count($table, "*", "status = 1");
            $expiredPassports = $obj->count($table, "*", "passport_expiry_date < CURDATE()");
            $expiredLicenses = $obj->count($table, "*", "license_expiry < CURDATE()");
            $newJoinees = $obj->count(
                $table,
                "*",
                "MONTH(date_joining) = MONTH(CURDATE()) AND YEAR(date_joining) = YEAR(CURDATE())"
            );

            ?>

            <!-- ===================== CHART UI ===================== -->

            <div class="row">

                <!-- LINE CHART -->
                <div class="col-12 col-lg-6 d-flex">
                    <div class="card w-100">
                        <div class="card-body">
                            <h6>Date Wise Employee Joining</h6>
                            <canvas id="myChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- BAR CHART -->
                <div class="col-12 col-lg-6 d-flex">
                    <div class="card w-100">
                        <div class="card-body">
                            <h6>Employee Status Overview</h6>
                            <canvas id="myChart2"></canvas>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ===================== DATA TO JS ===================== -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <?php 
            
            $formatedLabels=[];

            foreach($labels as $label){
             
                $formatedLabels[] = formatDate($label);

            }

            ?>

            <script>
                const labels = <?= json_encode($formatedLabels) ?>;
                const data = <?= json_encode($data) ?>;

                const statusData = {
                    active: <?= $activeEmployees ?>,
                    expiredPassport: <?= $expiredPassports ?>,
                    expiredLicense: <?= $expiredLicenses ?>,
                    newJoinees: <?= $newJoinees ?>
                };

                /* =========================
                   LINE CHART
                ========================= */
                new Chart(document.getElementById('myChart'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Employees Joined',
                            data: data,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13,110,253,0.2)',
                            fill: true,
                            borderWidth: 2,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                labels: {
                                    color: "black"
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: "black" }
                            },
                            y: {
                                ticks: { color: "black" }
                            }
                        }
                    }
                });

                /* =========================
                   BAR CHART
                ========================= */
                new Chart(document.getElementById('myChart2'), {
                    type: 'bar',
                    data: {
                        labels: [
                            "Active Employees",
                            "Expired Passports",
                            "Expired Licenses",
                            "New Joinees"
                        ],
                        datasets: [{
                            label: 'Employee Stats',
                            data: [
                                statusData.active,
                                statusData.expiredPassport,
                                statusData.expiredLicense,
                                statusData.newJoinees
                            ],
                            backgroundColor: [
                                '#28a745',
                                '#dc3545',
                                '#ffc107',
                                '#0dcaf0'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                labels: {
                                    color: "black"
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: "black" }
                            },
                            y: {
                                ticks: { color: "black" }
                            }
                        }
                    }
                });
            </script>


            <br />

            <?php include('components/footer.php'); ?>


        </div>



    </div>

    <script src="<?= $base_url ?>assets/js/datatables/datatables.js" id="script-resource-8"></script>
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/datatables/datatables.css" id="style-resource-1">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-1.2.2.css"
        id="style-resource-1">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/rickshaw/rickshaw.min.css" id="style-resource-2">
    <script src="<?= $base_url ?>assets/js/gsap/TweenMax.min.js" id="script-resource-1"></script>
    <script src="<?= $base_url ?>assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js"
        id="script-resource-2"></script>
    <script src="<?= $base_url ?>assets/js/bootstrap.js" id="script-resource-3"></script>
    <script src="<?= $base_url ?>assets/js/joinable.js" id="script-resource-4"></script>
    <script src="<?= $base_url ?>assets/js/resizeable.js" id="script-resource-5"></script>
    <script src="<?= $base_url ?>assets/js/neon-api.js" id="script-resource-6"></script>
    <script src="<?= $base_url ?>assets/js/cookies.min.js" id="script-resource-7"></script>
    <script src="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-1.2.2.min.js" id="script-resource-8"></script>
    <script src="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-europe-merc-en.js"
        id="script-resource-9"></script>

    <script src="<?= $base_url ?>assets/js/neon-chat.js" id="script-resource-16"></script>
    <script src="<?= $base_url ?>assets/js/neon-custom.js" id="script-resource-17"></script>

</body>

</html>