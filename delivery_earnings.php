<?php
/**
 * Delivery Earnings
 *
 * - Save/update by employee + year + week (no separate edit page)
 * - Auto date range from ISO week
 * - Negative balance from earlier week carries into the next week
 */

include('session.php');
include('database.php');
include('includes/delivery_earnings_helper.php');

$obj = new Database();

$current_year = (int) date('o');   // ISO year
$current_week = (int) date('W');   // ISO week
list($current_start, $current_end) = iso_week_range($current_year, $current_week);

// ---------------------------------------------------------------------------
// SAVE / UPDATE
// ---------------------------------------------------------------------------
if (isset($_POST['save_delivery_earning'])) {

    $employee_id = (int) ($_POST['employee_id'] ?? 0);
    $week_year = (int) ($_POST['week_year'] ?? $current_year);
    $week_number = (int) ($_POST['week_number'] ?? $current_week);
    $earning = (float) ($_POST['earning'] ?? 0);
    $cash_in_hand = (float) ($_POST['cash_in_hand'] ?? 0);
    $app_tax = (float) ($_POST['app_tax'] ?? 0);
    $others = (float) ($_POST['others'] ?? 0);

    if ($employee_id <= 0) {
        earning_toast('error', 'Please select an employee.');
    }

    $max_week = iso_weeks_in_year($week_year);
    if ($week_year < 2000 || $week_year > 2100 || $week_number < 1 || $week_number > $max_week) {
        earning_toast('error', 'Please select a valid year and week.');
    }

    list($week_start, $week_end) = iso_week_range($week_year, $week_number);

    // Rates required
    $obj->select('employee_rate_settings', '*', null, "employee_id = $employee_id");
    $rateRows = $obj->getResult();
    if (empty($rateRows[0]['id'])) {
        earning_toast('error', 'No rate settings found for this employee. Set rates first.');
    }
    $rates = $rateRows[0];

    // Previous week negative (if any)
    $carry = get_prev_carry($obj, $employee_id, $week_year, $week_number);
    $calc = calculate_week_earning($earning, $cash_in_hand, $app_tax, $others, $rates, $carry['prev_carry']);

    $data = [
        'employee_id' => $employee_id,
        'earning_date' => $week_start,
        'week_year' => $week_year,
        'week_number' => $week_number,
        'week_start' => $week_start,
        'week_end' => $week_end,
        'earning' => money_db($earning),
        'commission' => money_db($calc['commission']),
        'cash_in_hand' => money_db($cash_in_hand),
        'app_tax' => money_db($app_tax),
        'total_earning' => money_db($calc['total_earning']),
        'tax' => money_db($calc['tax']),
        'sc' => money_db($calc['sc']),
        'others' => money_db($others),
        'week_balance' => money_db($calc['week_balance']),
        'prev_carry' => money_db($calc['prev_carry']),
        'adjustment_note' => $carry['note'],
        'total_balance' => money_db($calc['total_balance']),
    ];

    // Same employee + year + week → update; otherwise insert
    $existing = find_week_earning($obj, $employee_id, $week_year, $week_number);
    $isUpdate = !empty($existing);

    if ($isUpdate) {
        $save = $obj->update('delivery_earnings', $data, 'id = ' . (int) $existing['id']);
    } else {
        $save = $obj->insert('delivery_earnings', $data);
    }

    if (!$save) {
        earning_toast('error', 'Failed to save delivery earning. Please try again.');
    }

    $cascaded = cascade_following_weeks($obj, $employee_id, $week_year, $week_number);

    $msg = $isUpdate
        ? "Week {$week_number} updated successfully!"
        : 'Delivery earning saved successfully!';

    if ($calc['prev_carry'] < 0) {
        $msg .= ' Previous negative ' . number_format($calc['prev_carry'], 2) . ' adjusted.';
    }
    if ($cascaded > 0) {
        $msg .= " Updated {$cascaded} later week(s) carry.";
    }

    earning_toast('success', $msg);
}

// ---------------------------------------------------------------------------
// PAGE DATA
// ---------------------------------------------------------------------------
// Only food-delivery employees who already have rate settings
$obj->select(
    'add_employee_details',
    'add_employee_details.*',
    'INNER JOIN employee_rate_settings ON employee_rate_settings.employee_id = add_employee_details.id',
    "add_employee_details.work_type = 'food_delivery'",
    'add_employee_details.name ASC'
);
$employees = $obj->getResult();

$filter_year = (isset($_GET['filter_year']) && $_GET['filter_year'] !== '') ? (int) $_GET['filter_year'] : null;
$filter_week = (isset($_GET['filter_week']) && $_GET['filter_week'] !== '') ? (int) $_GET['filter_week'] : null;

$where = [];
if ($filter_year !== null) {
    $where[] = 'delivery_earnings.week_year = ' . $filter_year;
}
if ($filter_week !== null) {
    $where[] = 'delivery_earnings.week_number = ' . $filter_week;
}
$earningsWhere = $where ? implode(' AND ', $where) : null;

$obj->select(
    'delivery_earnings',
    'delivery_earnings.*, add_employee_details.name, add_employee_details.company_name, add_employee_details.type_of_contract, employee_rate_settings.service_providers',
    'LEFT JOIN add_employee_details ON add_employee_details.id = delivery_earnings.employee_id
     LEFT JOIN employee_rate_settings ON employee_rate_settings.employee_id = delivery_earnings.employee_id',
    $earningsWhere,
    'delivery_earnings.week_year ASC, delivery_earnings.week_number ASC, delivery_earnings.id ASC'
);
$earnings = $obj->getResult();

$form_range_label = week_range_label($current_start, $current_end);
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

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2-bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/delivery_earnings.css">
    <style>
        /* Select2 arrow sprite needs app base URL */
        #earningForm .select2-container .select2-choice .select2-arrow b,
        #s2id_employee_id .select2-choice .select2-arrow b {
            background-image: url('<?= $base_url ?>assets/js/select2/select2.png') !important;
        }
    </style>
    <script src="<?= $base_url ?>assets/js/jquery-1.11.3.min.js"></script>
</head>

<body>
    <div class="page-container">
        <div class="sidebar-menu">
            <?php include('components/sidebar.php'); ?>
        </div>
        <div class="main-content">

            <?php include('components/header.php'); ?>
            <hr />

            <!-- ENTRY FORM -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-primary" data-collapsed="0">
                        <div class="panel-heading">
                            <div class="panel-title">Delivery Earning Entry</div>
                        </div>
                        <div class="panel-body">
                            <form action="delivery_earnings" method="post" id="earningForm">
                                <div class="row">
                                    <div class="col-md-1"></div>
                                    <div class="col-md-10">

                                        <div class="row">
                                            <div class="col-md-5">
                                                <label class="control-label">Name - Company name - Type of Contract</label>
                                                <select name="employee_id" id="employee_id" class="form-control select2"
                                                    data-allow-clear="true" data-placeholder="Search employee..." required>
                                                    <option value=""></option>
                                                    <?php foreach ($employees as $emp): ?>
                                                        <option value="<?= (int) $emp['id']; ?>">
                                                            <?= htmlspecialchars(($emp['name'] ?? '') . ' - ' . ($emp['company_name'] ?? '') . ' - ' . ($emp['type_of_contract'] ?? '')); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="rate-info" id="rateInfo">Select employee to load rate settings.</div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Year</label>
                                                <select name="week_year" id="week_year" class="form-control" required>
                                                    <?= year_options_html($current_year, $current_year); ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Week</label>
                                                <select name="week_number" id="week_number" class="form-control" required>
                                                    <?= week_options_html($current_week, $current_year); ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Date Range</label>
                                                <input type="text" id="week_date_range" class="form-control calc-readonly"
                                                    value="<?= htmlspecialchars($form_range_label); ?>" readonly>
                                                <input type="hidden" name="week_start" id="week_start" value="<?= htmlspecialchars($current_start); ?>">
                                                <input type="hidden" name="week_end" id="week_end" value="<?= htmlspecialchars($current_end); ?>">
                                                <input type="hidden" name="earning_date" id="earning_date" value="<?= htmlspecialchars($current_start); ?>">
                                                <input type="hidden" name="earning_id" id="earning_id" value="">
                                            </div>
                                        </div>

                                        <div class="carry-alert" id="carryAlert"></div>
                                        <hr>

                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="control-label">Earning</label>
                                                <input type="number" step="0.01" min="0" name="earning" id="earning"
                                                    class="form-control calc-input" value="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Commission <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="commission" id="commission" class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Cash in Hand</label>
                                                <input type="number" step="0.01" min="0" name="cash_in_hand" id="cash_in_hand"
                                                    class="form-control calc-input" value="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">App Tax</label>
                                                <input type="number" step="0.01" min="0" name="app_tax" id="app_tax"
                                                    class="form-control calc-input" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Total Earning <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="total_earning" id="total_earning"
                                                    class="form-control calc-readonly total-highlight" readonly value="0.00">
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            <div class="col-md-2">
                                                <label class="control-label">Tax <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="tax" id="tax" class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">SC <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="sc" id="sc" class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Others</label>
                                                <input type="number" step="0.01" min="0" name="others" id="others"
                                                    class="form-control calc-input" value="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Week Balance <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="week_balance" id="week_balance" class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Prev Adjust <small class="text-muted">(auto)</small></label>
                                                <input type="text" id="prev_carry_display" class="form-control calc-readonly" readonly value="0.00">
                                                <input type="hidden" name="prev_carry" id="prev_carry" value="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Total Balance <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="total_balance" id="total_balance"
                                                    class="form-control calc-readonly total-highlight" readonly value="0.00">
                                            </div>
                                        </div>

                                        <hr>
                                        <div class="text-center">
                                            <button type="submit" name="save_delivery_earning" id="submitEarningBtn" class="btn btn-success">
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

            <!-- FILTER -->
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-12">
                    <form method="get" action="delivery_earnings" class="form-inline">
                        <label class="control-label" style="margin-right: 8px;">Filter</label>
                        <select name="filter_year" class="form-control" style="margin-right: 8px; min-width: 110px;">
                            <option value="">All Years</option>
                            <?= year_options_html($filter_year, $current_year); ?>
                        </select>
                        <select name="filter_week" class="form-control" style="margin-right: 8px; min-width: 130px;">
                            <?= week_options_html($filter_week, $filter_year ?: $current_year, true); ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm" style="margin-right: 6px;">Apply</button>
                        <a href="delivery_earnings" class="btn btn-default btn-sm">Clear</a>
                    </form>
                </div>
            </div>

            <h3>Delivery Earnings</h3>
            <br />

            <table class="table table-bordered datatable table-3" id="table-4">
                <thead>
                    <tr>
                        <th>S.no</th>
                        <th>Employee</th>
                        <th>Company</th>
                        <th>Service Provider</th>
                        <th>Year</th>
                        <th>Week</th>
                        <th>Date Range</th>
                        <th>Earning</th>
                        <th>Commission</th>
                        <th>Cash in Hand</th>
                        <th>App Tax</th>
                        <th>Total Earning</th>
                        <th>Tax</th>
                        <th>SC Rent</th>
                        <th>Others</th>
                        <th>Week Balance</th>
                        <th>Prev Adjust</th>
                        <th>Total Balance</th>
                        <th>Note</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($earnings) && !empty($earnings[0]['id'])) {
                        $sno = 1;
                        foreach ($earnings as $row) {
                            $rangeLabel = week_range_label(
                                $row['week_start'] ?? $row['earning_date'] ?? '',
                                $row['week_end'] ?? ''
                            );
                            $weekBal = row_week_balance($row);
                            $prevCarry = (float) ($row['prev_carry'] ?? 0);
                            $totalBal = (float) $row['total_balance'];
                            ?>
                            <tr>
                                <td><?= $sno++; ?></td>
                                <td><?= htmlspecialchars($row['name'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($row['company_name'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($row['service_providers'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($row['week_year'] ?? ''); ?></td>
                                <td><?= !empty($row['week_number']) ? 'Week ' . (int) $row['week_number'] : ''; ?></td>
                                <td><?= htmlspecialchars($rangeLabel); ?></td>
                                <td><?= number_format((float) $row['earning'], 2); ?></td>
                                <td><?= number_format((float) $row['commission'], 2); ?></td>
                                <td><?= number_format((float) $row['cash_in_hand'], 2); ?></td>
                                <td><?= number_format((float) $row['app_tax'], 2); ?></td>
                                <td><?= number_format((float) $row['total_earning'], 2); ?></td>
                                <td><?= number_format((float) $row['tax'], 2); ?></td>
                                <td><?= number_format((float) $row['sc'], 2); ?></td>
                                <td><?= number_format((float) $row['others'], 2); ?></td>
                                <td><?= number_format($weekBal, 2); ?></td>
                                <td><?= number_format($prevCarry, 2); ?></td>
                                <td>
                                    <strong<?= $totalBal < 0 ? ' style="color:#c62828;"' : ''; ?>>
                                        <?= number_format($totalBal, 2); ?>
                                    </strong>
                                </td>
                                <td><?= htmlspecialchars($row['adjustment_note'] ?? ''); ?></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm delete-earning-btn"
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

    <?php if (isset($_SESSION['toast'])): ?>
        <script>
            toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: '3000' };
            toastr["<?= $_SESSION['toast']['type']; ?>"]("<?= htmlspecialchars($_SESSION['toast']['message'], ENT_QUOTES); ?>");
        </script>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <script src="<?= $base_url ?>assets/js/datatables/datatables.js" id="script-resource-8"></script>
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/datatables/datatables.css" id="style-resource-1">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-1.2.2.css" id="style-resource-1">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/rickshaw/rickshaw.min.css" id="style-resource-2">
    <script src="<?= $base_url ?>assets/js/gsap/TweenMax.min.js" id="script-resource-1"></script>
    <script src="<?= $base_url ?>assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js" id="script-resource-2"></script>
    <script src="<?= $base_url ?>assets/js/bootstrap.js" id="script-resource-3"></script>
    <script src="<?= $base_url ?>assets/js/joinable.js" id="script-resource-4"></script>
    <script src="<?= $base_url ?>assets/js/resizeable.js" id="script-resource-5"></script>
    <script src="<?= $base_url ?>assets/js/neon-api.js" id="script-resource-6"></script>
    <script src="<?= $base_url ?>assets/js/cookies.min.js" id="script-resource-7"></script>
    <script src="<?= $base_url ?>assets/js/select2/select2.min.js"></script>
    <script src="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-1.2.2.min.js" id="script-resource-8"></script>
    <script src="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-europe-merc-en.js" id="script-resource-9"></script>
    <script src="<?= $base_url ?>assets/js/neon-chat.js" id="script-resource-16"></script>
    <script src="<?= $base_url ?>assets/js/neon-custom.js" id="script-resource-17"></script>
    <script src="<?= $base_url ?>assets/js/delivery_earnings.js"></script>

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
                order: [[0, 'asc']]
            });
        });
    </script>
</body>

</html>
