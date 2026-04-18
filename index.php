<?php

include('session.php');


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
                        <div class="num" data-start="0" data-end="83" data-postfix="" data-duration="1500"
                            data-delay="0">83</div>
                        <h3>Registered users</h3>
                        <p>so far in our blog, and our website.</p>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-green">
                        <div class="icon"><i class="entypo-chart-bar"></i></div>
                        <div class="num" data-start="0" data-end="135" data-postfix="" data-duration="1500"
                            data-delay="600">135</div>
                        <h3>Daily Visitors</h3>
                        <p>this is the average value.</p>
                    </div>
                </div>
                <div class="clear visible-xs"></div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-aqua">
                        <div class="icon"><i class="entypo-mail"></i></div>
                        <div class="num" data-start="0" data-end="23" data-postfix="" data-duration="1500"
                            data-delay="1200">23</div>
                        <h3>New Messages</h3>
                        <p>messages per day.</p>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="tile-stats tile-blue">
                        <div class="icon"><i class="entypo-rss"></i></div>
                        <div class="num" data-start="0" data-end="52" data-postfix="" data-duration="1500"
                            data-delay="1800">52</div>
                        <h3>Subscribers</h3>
                        <p>on our site right now.</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4 col-xs-6">
                    <a href="">
                        <div class="tile-stats tile-green">
                            <div class="icon"><i class="entypo-users"></i></div>


                            <div class="num" data-start="0" data-end="12" data-postfix data-duration="500"
                                data-delay="0">0</div>
                            <h3>Today’s tickets count</h3>

                        </div>
                    </a>
                </div>
                <div class="col-sm-4 col-xs-6">
                    <a href="">

                        <div class="tile-stats tile-aqua">
                            <div class="icon"><i class="entypo-chart-bar"></i></div>
                            <div class="num" data-start="0" data-end="96000" data-postfix data-duration="500"
                                data-delay="600">0</div>
                            <h3>Today’s revenue</h3>

                        </div>

                    </a>
                </div>
                <div class="clear visible-xs"></div>
                <div class="col-sm-4 col-xs-6">
                    <a href="status_wise_data?status=Applied">

                        <div class="tile-stats tile-blue">
                            <div class="icon"><i class="entypo-mail"></i></div>
                            <div class="num" data-start="0" data-end="5" data-postfix data-duration="500"
                                data-delay="1200">0</div>
                            <h3>Pending tickets</h3>

                        </div>

                    </a>
                </div>

                <div class="col-sm-4 col-xs-6">
                    <a href="status_wise_data?status=Work Permit Issued">

                        <div class="tile-stats tile-green">
                            <div class="icon"><i class="entypo-rss"></i></div>
                            <div class="num" data-start="0" data-end="3" data-postfix data-duration="500"
                                data-delay="1800">0</div>
                            <h3>Failed payments</h3>

                        </div>

                    </a>
                </div>

                <div class="col-sm-4 col-xs-6">
                    <a href="status_wise_data?status=Appointment Applied">

                        <div class="tile-stats tile-aqua">
                            <div class="icon"><i class="entypo-rss"></i></div>
                            <div class="num" style="    font-style: italic;">Dubai To London</div>
                            <h3>Top Route</h3>

                        </div>

                    </a>
                </div>

                <div class="col-sm-4 col-xs-6">
                    <a href="status_wise_data?status=Work Permit Issued">

                        <div class="tile-stats tile-green">
                            <div class="icon"><i class="entypo-rss"></i></div>
                            <div class="num" data-start="0" data-end="3" data-postfix data-duration="500"
                                data-delay="1800">0</div>
                            <h3>Refunds today</h3>

                        </div>

                    </a>
                </div>

            </div>

            <hr>
            <br>
            <br>

            <div class="row">
                <div class="col-12 col-lg-6 col-xl-6 d-flex">
                    <div class="card radius-10 overflow-hidden w-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <form action="index_search" method="post">

                                    <table class="table-1">
                                        <tr>
                                            <td><select class='form-control' name="date" required>
                                                    <option value=''>Select</option>
                                                    <option value="2026-02-14/2026-02-14">Today</option>
                                                    <option value="2026-02-13/2026-02-13">Yesterday</option>
                                                    <option value="2026-02-08/2026-02-14">Last 7 Days</option>
                                                    <option value="2026-02-01/2026-02-28">This Month</option>
                                                    <option value="2026-01-01/2026-01-31">Last Month</option>
                                                    <option value="2026-01-01/2026-12-31">This Year</option>
                                                    <option value="2025-01-01/2025-12-31">Last Year</option>
                                                    <option value="1969-12-31/2026-12-31">All Time</option>
                                                </select>
                                            </td>
                                            <td> <input type="submit" name="date_select" value="search" id=""
                                                    class="form-control">

                                            </td>
                                        </tr>
                                    </table>


                                </form>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 col-xl-6 d-flex">
                    <div class="card radius-10 overflow-hidden w-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">


                                <form action="index_search" method="post">

                                    <table class="table-2">
                                        <tr>
                                            <td> <input type='date' name='date1' class='form-control' value="" />
                                            </td>
                                            <td> <input type='date' name='date2' class='form-control' value="" />

                                            </td>
                                            <td>
                                                <input type="submit" name="date_range" value="search" id=""
                                                    class="form-control">

                                            </td>
                                        </tr>
                                    </table>



                                    <!--<input type='date' name='date1' class='form-control' value=""   />-->
                                    <!--  	<input type='date' name='date2' class='form-control' value=""   />-->
                                    <!--  <input type="submit" name="date_range" value="search" id="" class="form-control">-->




                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>


            <br>
            <br>

            <hr>


            <div class="row">
                <div class="col-12 col-lg-6 col-xl-6 d-flex">
                    <div class="card radius-10 overflow-hidden w-100" style="background:#00c0ef">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h6 class="mb-0">Date Wise Entry Graph</h6>
                                </div>
                                <div class="font-22 ms-auto text-white"><i class="bx bx-dots-horizontal-rounded"></i>
                                </div>
                            </div>

                            <canvas id="myChart"></canvas>
                        </div>
                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                        </script>

                    </div>
                </div>

                <div class="col-12 col-lg-6 col-xl-6 d-flex">
                    <div class="card radius-10 overflow-hidden w-100" style="background:#00c0ef;">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h6 class="mb-0">Status Wise Data</h6>
                                </div>
                                <div class="font-22 ms-auto text-white"><i class="bx bx-dots-horizontal-rounded"></i>
                                </div>
                            </div>
                            <div>

                                <canvas id="myChart2"></canvas>
                            </div>

                        </div>
                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                        <script>  // === include 'setup' then 'config' above ===
                            Chart.defaults.color = "white";
                            //   Chart.defaults.backgroundcolor = "re";

                            const ctx = document.getElementById('myChart');
                            const ctx2 = document.getElementById('myChart2');

                            const myChart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: ["2025-02-01", "2025-02-05", "2025-02-06", "2025-02-11", "2025-02-12", "2025-02-17", "2025-02-19", "2025-02-24", "2025-02-26", "2025-02-27", "2025-02-28", "2026-02-02", "2026-02-05", "2026-02-06", "2026-02-09", "2026-02-10", "2026-02-11", "2026-02-12", "2026-02-13",],
                                    datasets: [{
                                        label: 'Entry by Date',
                                        data: ["4", "4", "1", "1", "1", "8", "1", "11", "4", "2", "2", "8", "1", "3", "1", "2", "1", "4", "1",],
                                        borderColor: [
                                            '#adb5bd'
                                        ],
                                        backgroundColor: [
                                            '#6f42c1',
                                            '#dc3545',
                                            '#28a745',
                                            '#007bff',
                                            '#ffc107',
                                            '#20c997',
                                            '#17a2b8',
                                            '#fd7e14',
                                            '#e83e8c',
                                            '#6610f2'
                                        ],
                                        fill: false,
                                        borderWidth: 2
                                    }]
                                },
                            });

                            const myChart2 = new Chart(ctx2, {
                                type: 'bar',
                                data: {
                                    labels: ["Today’s tickets count", "Today’s revenue", "Pending tickets", "Failed payments", "Refunds today",],
                                    datasets: [{
                                        label: 'Status by Category',
                                        data: ["12", "96", "5", "3", "3",],


                                        backgroundColor: [
                                            'rgba(255, 99, 132, 0.2)',
                                            'rgba(255, 159, 64, 0.2)',
                                            'rgba(255, 205, 86, 0.2)',
                                            'rgba(75, 192, 192, 0.2)',
                                            'rgba(54, 162, 235, 0.2)',
                                            'rgba(153, 102, 255, 0.2)',
                                            'rgba(201, 203, 207, 0.2)'
                                        ],
                                        borderColor: [
                                            'rgb(255, 99, 132)',
                                            'rgb(255, 159, 64)',
                                            'rgb(255, 205, 86)',
                                            'rgb(75, 192, 192)',
                                            'rgb(54, 162, 235)',
                                            'rgb(153, 102, 255)',
                                            'rgb(201, 203, 207)'
                                        ],
                                        borderWidth: 1
                                    }]
                                },
                            });
                        </script>
                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                    </div>
                </div>
            </div><!--End Row-->




            <br />


            <footer class="main">
                &copy; <strong>Dhothar International</strong>
            </footer>
            <script>
                function checkNotification() {
                    // Check current hour (in 24-hour format)
                    const now = new Date();
                    const currentHour = now.getHours(); // returns 0–23

                    // Only run after 6 PM (18:00)
                    if (currentHour >= 18) {
                        $.ajax({
                            url: 'attendance_system/notifications',
                            type: 'GET',
                            success: function (response) {
                                console.log("Received:", response);
                                try {
                                    let data = typeof response === 'string' ? JSON.parse(response) : response;
                                    if (data.status === 'new') {
                                        showSidebarNotification(data.message);
                                    }
                                } catch (e) {
                                    console.error("JSON parse error:", e);
                                    console.log("Raw response:", response);
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error("AJAX Error:", status, error);
                            }
                        });
                    }
                }

                function showSidebarNotification(message) {
                    let notification = $(`
                  <a href="attendance_dashboard" class="notification-popup" target="_blank">
                        ${message}
                  </a>
               `);
                    $('body').append(notification);
                    setTimeout(() => {
                        notification.fadeOut(300, function () { $(this).remove(); });
                    }, 5000);
                }


                // Check every 10 seconds, but only runs if time is after 6 PM
                setInterval(checkNotification, 10000);
            </script>


            <style>
                .notification-popup {
                    position: fixed;
                    top: 75px;
                    right: 25px;
                    max-width: 400px;
                    background: linear-gradient(135deg, #ff4e50, #f44336);
                    color: #fff;
                    font-size: 16px;
                    font-weight: 500;
                    padding: 20px 24px;
                    border-radius: 12px;
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
                    z-index: 9999;
                    animation: slideInRight 0.4s ease-out;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    line-height: 1.4;
                }

                @keyframes slideInRight {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }

                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }

                /* Optional: Add icon styling if you're including icons inside the popup */
                .notification-popup i {
                    font-size: 22px;
                    flex-shrink: 0;
                }
            </style>
        </div>



    </div>

    <script src="<?= $base_url ?>assets/js/datatables/datatables.js" id="script-resource-8"></script>

   

</body>

</html>