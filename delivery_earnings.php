<?php

include('session.php');
include('database.php');

$obj = new Database();

function calc_amount($base, $rate, $type)
{
    $base = (float) $base;
    $rate = (float) $rate;
    if ($type === 'fixed') {
        return round($rate, 2);
    }
    return round(($base * $rate) / 100, 2);
}

if (isset($_POST['save_delivery_earning'])) {

    $employee_id = (int) ($_POST['employee_id'] ?? 0);
    $earning_date = $_POST['earning_date'] ?? date('Y-m-d');
    $earning = (float) ($_POST['earning'] ?? 0);
    $cash_in_hand = (float) ($_POST['cash_in_hand'] ?? 0);
    $app_tax = (float) ($_POST['app_tax'] ?? 0);
    $others = (float) ($_POST['others'] ?? 0);

    if ($employee_id <= 0) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Please select an employee.'
        ];
        header("Location: delivery_earnings");
        exit;
    }

    $obj->select("employee_rate_settings", "*", null, "employee_id = $employee_id");
    $rateRows = $obj->getResult();

    if (empty($rateRows) || !isset($rateRows[0]['id'])) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'No rate settings found for this employee. Set rates first.'
        ];
        header("Location: delivery_earnings");
        exit;
    }

    $rates = $rateRows[0];

    $commission = calc_amount($earning, $rates['commission_rate'], $rates['commission_type']);
    $total_earning = round($earning - $commission - $cash_in_hand - $app_tax, 2);
    $tax = calc_amount($total_earning, $rates['tax_rate'], $rates['tax_type']);
    $sc = calc_amount($total_earning, $rates['sc_rate'], $rates['sc_type']);
    $total_balance = round($total_earning - $tax - $sc - $others, 2);

    $data = array(
        "employee_id" => $employee_id,
        "earning_date" => $earning_date,
        "earning" => number_format($earning, 2, '.', ''),
        "commission" => number_format($commission, 2, '.', ''),
        "cash_in_hand" => number_format($cash_in_hand, 2, '.', ''),
        "app_tax" => number_format($app_tax, 2, '.', ''),
        "total_earning" => number_format($total_earning, 2, '.', ''),
        "tax" => number_format($tax, 2, '.', ''),
        "sc" => number_format($sc, 2, '.', ''),
        "others" => number_format($others, 2, '.', ''),
        "total_balance" => number_format($total_balance, 2, '.', '')
    );

    $save = $obj->insert("delivery_earnings", $data);

    if ($save) {
        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Delivery earning saved successfully!'
        ];
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Failed to save delivery earning. Please try again.'
        ];
    }

    header("Location: delivery_earnings");
    exit;
}

$obj->select("add_employee_details", "*", null, "work_type = 'food_delivery'", "name ASC");
$employees = $obj->getResult();

$obj->select(
    "delivery_earnings",
    "delivery_earnings.*, add_employee_details.name, add_employee_details.company_name",
    "LEFT JOIN add_employee_details ON add_employee_details.id = delivery_earnings.employee_id",
    null,
    "delivery_earnings.id DESC"
);
$earnings = $obj->getResult();

?>
<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Dhothar International" />
    <meta name="author" content="Laborator.co" />
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Dhothar International Employee DB | Delivery Earnings</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css" id="style-resource-2">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic"
        id="style-resource-3">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css" id="style-resource-4">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css" id="style-resource-5">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2-bootstrap.css">

    <script src="<?= $base_url ?>assets/js/jquery-1.11.3.min.js"></script>

    <style>
        #earningForm .control-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #555;
        }

        #earningForm .form-control {
            height: 38px;
            box-shadow: none;
        }

        #earningForm .row {
            margin-bottom: 4px;
        }

        #earningForm hr {
            margin: 18px 0;
            border-top: 1px solid #eee;
        }

        .calc-readonly {
            background: #f7f7f7;
            font-weight: 600;
            color: #444;
        }

        .total-highlight {
            background: #f0faf2 !important;
            border-color: #cfe8d4;
            font-weight: 700;
            color: #2e7d32;
        }

        .rate-info {
            margin-top: 6px;
            font-size: 12px;
            color: #999;
        }

        /* Flat Select2 — kill default grey gradient */
        #earningForm .select2-container,
        #s2id_employee_id {
            width: 100% !important;
            display: block !important;
        }

        #earningForm .select2-container .select2-choice,
        #s2id_employee_id .select2-choice,
        #earningForm .select2-container.select2-drop-above .select2-choice,
        #s2id_employee_id.select2-drop-above .select2-choice,
        #earningForm .select2-container.select2-container-active .select2-choice,
        #s2id_employee_id.select2-container-active .select2-choice,
        #earningForm .select2-container.select2-dropdown-open .select2-choice,
        #s2id_employee_id.select2-dropdown-open .select2-choice {
            height: 38px !important;
            line-height: 36px !important;
            padding-left: 12px !important;
            border: 1px solid #ccc !important;
            border-radius: 3px !important;
            background: #fff !important;
            background-color: #fff !important;
            background-image: none !important;
            -webkit-box-shadow: none !important;
            -moz-box-shadow: none !important;
            box-shadow: none !important;
            filter: none !important;
        }

        #earningForm .select2-container .select2-choice .select2-chosen,
        #s2id_employee_id .select2-choice .select2-chosen {
            line-height: 36px !important;
            color: #555 !important;
            margin-right: 40px !important;
        }

        #earningForm .select2-container .select2-choice .select2-arrow,
        #s2id_employee_id .select2-choice .select2-arrow {
            width: 28px !important;
            border-left: none !important;
            background: #fff !important;
            background-image: none !important;
            filter: none !important;
        }

        #earningForm .select2-container .select2-choice .select2-arrow b,
        #s2id_employee_id .select2-choice .select2-arrow b {
            background-color: transparent !important;
            background-image: url('<?= $base_url ?>assets/js/select2/select2.png') !important;
            background-repeat: no-repeat !important;
            background-position: 2px 5px !important;
        }

        #earningForm .select2-container.select2-allowclear .select2-choice .select2-chosen,
        #s2id_employee_id.select2-allowclear .select2-choice .select2-chosen {
            margin-right: 52px !important;
        }

        .select2-drop {
            border-color: #ccc !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08) !important;
        }

        .select2-drop-active {
            border-color: #66afe9 !important;
        }

        .select2-search input {
            background: #fff !important;
            background-image: none !important;
            border: 1px solid #ccc !important;
            box-shadow: none !important;
            filter: none !important;
        }

        #earningForm .btn-success {
            min-width: 160px;
            padding: 8px 20px;
        }
    </style>
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
                <div class="col-md-12">
                    <div class="panel panel-primary" data-collapsed="0">
                        <div class="panel-heading">
                            <div class="panel-title">
                                Delivery Earning Entry
                            </div>
                        </div>
                        <div class="panel-body">
                            <form action="delivery_earnings" method="post" id="earningForm">
                                <div class="row">
                                    <div class="col-md-1"></div>
                                    <div class="col-md-10">

                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="control-label">Name - Company name</label>
                                                <select name="employee_id" id="employee_id" class="form-control select2"
                                                    data-allow-clear="true" data-placeholder="Search employee..."
                                                    required>
                                                    <option value=""></option>
                                                    <?php
                                                    if (!empty($employees)) {
                                                        foreach ($employees as $emp) {
                                                            $emp_id = htmlspecialchars($emp['id'] ?? '');
                                                            $empName = htmlspecialchars($emp['name'] ?? '');
                                                            $companyName = htmlspecialchars($emp['company_name'] ?? '');
                                                            echo '<option value="' . $emp_id . '">' . $empName . ' - ' . $companyName . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                                <div class="rate-info" id="rateInfo">Select employee to load rate settings.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <!-- <label class="control-label">Date</label> -->
                                                <input type="hidden" name="earning_date" id="earning_date"
                                                    class="form-control" value="<?= date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="control-label">Earning</label>
                                                <input type="number" step="0.01" min="0" name="earning" id="earning"
                                                    class="form-control calc-input" placeholder="0.00" value="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Commission <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="commission" id="commission"
                                                    class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Cash in Hand</label>
                                                <input type="number" step="0.01" min="0" name="cash_in_hand"
                                                    id="cash_in_hand" class="form-control calc-input" placeholder="0.00"
                                                    value="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">App Tax</label>
                                                <input type="number" step="0.01" min="0" name="app_tax" id="app_tax"
                                                    class="form-control calc-input" placeholder="0.00" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Total Earning <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="total_earning" id="total_earning"
                                                    class="form-control calc-readonly total-highlight" readonly
                                                    value="0.00">
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="control-label">Tax <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="tax" id="tax"
                                                    class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">SC <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="sc" id="sc"
                                                    class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Others</label>
                                                <input type="number" step="0.01" min="0" name="others" id="others"
                                                    class="form-control calc-input" placeholder="0.00" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Total Balance <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="total_balance" id="total_balance"
                                                    class="form-control calc-readonly total-highlight" readonly
                                                    value="0.00">
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="text-center">
                                            <button type="submit" name="save_delivery_earning" class="btn btn-success">
                                                Submit Details
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <hr />

            <h3>Delivery Earnings</h3>
            <br />
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var $table4 = jQuery("#table-4");
                    $table4.DataTable({
                        dom: 'Bfrtip',
                        buttons: [
                            {
                                extend: 'copyHtml5',
                                exportOptions: { columns: ':not(:last-child)' }
                            },
                            {
                                extend: 'excelHtml5',
                                exportOptions: { columns: ':not(:last-child)' }
                            },
                            {
                                extend: 'csvHtml5',
                                exportOptions: { columns: ':not(:last-child)' }
                            },
                            {
                                extend: 'pdfHtml5',
                                exportOptions: { columns: ':not(:last-child)' }
                            }
                        ],
                        order: [[0, 'desc']]
                    });
                });
            </script>
            <table class="table table-bordered datatable table-3" id="table-4">
                <thead>
                    <tr>
                        <th>S.no</th>
                        <th>Employee</th>
                        <th>Company</th>
                        <th>Earning</th>
                        <th>Commission</th>
                        <th>Cash in Hand</th>
                        <th>App Tax</th>
                        <th>Total Earning</th>
                        <th>Tax</th>
                        <th>SC Rent</th>
                        <th>Others</th>
                        <th>Total Balance</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($earnings) && isset($earnings[0]['id'])) {
                        $sno = 1;
                        foreach ($earnings as $row) {
                            ?>
                            <tr>
                                <td><?= $sno++; ?></td>
                                <td><?= $row['name']; ?></td>
                                <td><?= $row['company_name']; ?></td>
                                <td><?= number_format((float) $row['earning'], 2); ?></td>
                                <td><?= number_format((float) $row['commission'], 2); ?></td>
                                <td><?= number_format((float) $row['cash_in_hand'], 2); ?></td>
                                <td><?= number_format((float) $row['app_tax'], 2); ?></td>
                                <td><?= number_format((float) $row['total_earning'], 2); ?></td>
                                <td><?= number_format((float) $row['tax'], 2); ?></td>
                                <td><?= number_format((float) $row['sc'], 2); ?></td>
                                <td><?= number_format((float) $row['others'], 2); ?></td>
                                <td><strong><?= number_format((float) $row['total_balance'], 2); ?></strong></td>
                                <td><?= htmlspecialchars(formatDate($row['earning_date'] ?? '')); ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm delete-earning-btn"
                                        data-id="<?= (int) $row['id']; ?>">
                                        <span class="entypo-trash"></span> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            <br />

            <?php include('components/footer.php'); ?>

        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <?php if (isset($_SESSION['toast'])) { ?>
        <script>
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "3000"
            };
            toastr["<?= $_SESSION['toast']['type'] ?>"]("<?= $_SESSION['toast']['message'] ?>");
        </script>
        <?php unset($_SESSION['toast']);
    } ?>

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
    <script src="<?= $base_url ?>assets/js/select2/select2.min.js"></script>
    <script src="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-1.2.2.min.js" id="script-resource-8"></script>
    <script src="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-europe-merc-en.js"
        id="script-resource-9"></script>
    <script src="<?= $base_url ?>assets/js/neon-chat.js" id="script-resource-16"></script>
    <script src="<?= $base_url ?>assets/js/neon-custom.js" id="script-resource-17"></script>

    <script>
        (function ($) {
            var currentRates = null;

            function toNum(val) {
                var n = parseFloat(val);
                return isNaN(n) ? 0 : n;
            }

            function money(n) {
                return (Math.round(n * 100) / 100).toFixed(2);
            }

            function calcAmount(base, rate, type) {
                base = toNum(base);
                rate = toNum(rate);
                if (type === 'fixed') {
                    return rate;
                }
                return (base * rate) / 100;
            }

            function formatRateLabel(rate, type) {
                if (type === 'fixed') {
                    return 'Fixed ' + money(rate);
                }
                return rate + '%';
            }

            function recalculate() {
                var earning = toNum($('#earning').val());
                var cashInHand = toNum($('#cash_in_hand').val());
                var appTax = toNum($('#app_tax').val());
                var others = toNum($('#others').val());

                var commission = 0;
                var tax = 0;
                var sc = 0;

                if (currentRates) {
                    commission = calcAmount(earning, currentRates.commission_rate, currentRates.commission_type);
                }

                var totalEarning = earning - commission - cashInHand - appTax;

                if (currentRates) {
                    tax = calcAmount(totalEarning, currentRates.tax_rate, currentRates.tax_type);
                    sc = calcAmount(totalEarning, currentRates.sc_rate, currentRates.sc_type);
                }

                var totalBalance = totalEarning - tax - sc - others;

                $('#commission').val(money(commission));
                $('#total_earning').val(money(totalEarning));
                $('#tax').val(money(tax));
                $('#sc').val(money(sc));
                $('#total_balance').val(money(totalBalance));
            }

            function loadRates(employeeId) {
                currentRates = null;
                $('#rateInfo').text('Loading rate settings...');

                if (!employeeId) {
                    $('#rateInfo').text('Select employee to load rate settings.');
                    recalculate();
                    return;
                }

                $.ajax({
                    url: 'ajax/get_employee_rates',
                    type: 'GET',
                    dataType: 'json',
                    data: { employee_id: employeeId },
                    success: function (res) {
                        if (res.success) {
                            currentRates = res.data;
                            $('#rateInfo').html(
                                'Rates — Commission: <strong>' + formatRateLabel(currentRates.commission_rate, currentRates.commission_type) +
                                '</strong> | Tax: <strong>' + formatRateLabel(currentRates.tax_rate, currentRates.tax_type) +
                                '</strong> | SC: <strong>' + formatRateLabel(currentRates.sc_rate, currentRates.sc_type) + '</strong>'
                            );
                        } else {
                            currentRates = null;
                            $('#rateInfo').text(res.message || 'No rate settings found.');
                            toastr.warning(res.message || 'No rate settings found for this employee.');
                        }
                        recalculate();
                    },
                    error: function () {
                        currentRates = null;
                        $('#rateInfo').text('Failed to load rate settings.');
                        toastr.error('Failed to load rate settings.');
                        recalculate();
                    }
                });
            }

            $(document).ready(function () {
                var $employee = $('#employee_id');

                if ($.fn.select2) {
                    if ($employee.data('select2')) {
                        $employee.select2('destroy');
                    }
                    $employee.select2({
                        allowClear: true,
                        placeholder: 'Search employee...',
                        width: '100%'
                    });
                }

                $employee.on('change', function () {
                    loadRates($(this).val());
                });

                $('.calc-input').on('input change', function () {
                    recalculate();
                });

                $(document).on('click', '.delete-earning-btn', function () {
                    var id = $(this).data('id');
                    var row = $(this).closest('tr');

                    if (!confirm('Are you sure you want to delete this earning record?')) {
                        return;
                    }

                    $.ajax({
                        url: 'ajax/delete_delivery_earning',
                        type: 'POST',
                        data: { id: id },
                        success: function (response) {
                            if (response == 'success') {
                                row.fadeOut(400, function () {
                                    $(this).remove();
                                });
                                toastr.success('Earning record deleted successfully!');
                            } else {
                                toastr.error('Delete failed!');
                            }
                        }
                    });
                });
            });
        })(jQuery);
    </script>
</body>

</html>
