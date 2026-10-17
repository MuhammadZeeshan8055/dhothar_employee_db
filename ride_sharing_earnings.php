<?php
/**
 * Ride Sharing Earnings
 *
 * - Save/update by employee + year + week
 * - Auto date range from ISO week
 * - Commission % and vehicle rent from settings
 * - Negative / unpaid balance carries into the next week
 */

include('session.php');
include('database.php');
include('includes/ride_sharing_helper.php');

$obj = new Database();

$current_year = (int) date('o');
$current_week = (int) date('W');
$form_employee_id = (int) ($_GET['employee_id'] ?? 0);
$form_year = (int) ($_GET['week_year'] ?? $current_year);
$form_week = (int) ($_GET['week_number'] ?? $current_week);

if ($form_year < 2000 || $form_year > 2100) {
    $form_year = $current_year;
}

$form_max_week = iso_weeks_in_year($form_year);
if ($form_week < 1 || $form_week > $form_max_week) {
    $form_week = $current_week;
}

list($current_start, $current_end) = iso_week_range($form_year, $form_week);

// ---------------------------------------------------------------------------
// SAVE / UPDATE
// ---------------------------------------------------------------------------
if (isset($_POST['save_ride_sharing_earning'])) {

    $employee_id = (int) ($_POST['employee_id'] ?? 0);
    $week_year = (int) ($_POST['week_year'] ?? $current_year);
    $week_number = (int) ($_POST['week_number'] ?? $current_week);
    $bolt_net = (float) ($_POST['bolt_net'] ?? 0);
    $bolt_payout = (float) ($_POST['bolt_payout'] ?? 0);
    $uber_net = (float) ($_POST['uber_net'] ?? 0);
    $uber_payout = (float) ($_POST['uber_payout'] ?? 0);
    $tax = (float) ($_POST['tax'] ?? 0);

    if ($employee_id <= 0) {
        ride_earning_toast('error', 'Please select an employee.');
    }

    $max_week = iso_weeks_in_year($week_year);
    if ($week_year < 2000 || $week_year > 2100 || $week_number < 1 || $week_number > $max_week) {
        ride_earning_toast('error', 'Please select a valid year and week.');
    }

    list($week_start, $week_end) = iso_week_range($week_year, $week_number);

    $rateResult = ride_get_rate_settings_for_week($obj, $employee_id, $week_year, $week_number);
    if (!$rateResult) {
        ride_earning_toast('error', 'No rate settings found for this employee. Set rates first.');
    }
    $rates = $rateResult['data'];

    $carry = ride_get_prev_carry($obj, $employee_id, $week_year, $week_number);
    $calc = calculate_ride_week(
        $bolt_net,
        $bolt_payout,
        $uber_net,
        $uber_payout,
        $tax,
        $rates,
        $carry['prev_carry']
    );

    $existing = ride_find_week_earning($obj, $employee_id, $week_year, $week_number);
    $isUpdate = !empty($existing);

    if ($isUpdate && (int) ($existing['status'] ?? 0) === 1 && !isSuperAdmin()) {
        ride_earning_toast('error', 'This week is paid and locked. Cannot update.');
    }

    $data = [
        'employee_id' => $employee_id,
        'earning_date' => $week_start,
        'week_year' => $week_year,
        'week_number' => $week_number,
        'week_start' => $week_start,
        'week_end' => $week_end,
        'bolt_net' => money_db($bolt_net),
        'bolt_payout' => money_db($bolt_payout),
        'uber_net' => money_db($uber_net),
        'uber_payout' => money_db($uber_payout),
        'total_net' => money_db($calc['total_net']),
        'commission' => money_db($calc['commission']),
        'vehicle_rent' => money_db($calc['vehicle_rent']),
        'total_payout' => money_db($calc['total_payout']),
        'balance' => money_db($calc['balance']),
        'tax' => money_db($tax),
        'week_balance' => money_db($calc['week_balance']),
        'prev_carry' => money_db($calc['prev_carry']),
        'adjustment_note' => $carry['note'],
        'total_balance' => money_db($calc['total_balance']),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if (!$isUpdate) {
        $data['status'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');
    }

    if ($isUpdate) {
        $save = $obj->update('ride_sharing_earnings', $data, 'id = ' . (int) $existing['id']);
    } else {
        $save = $obj->insert('ride_sharing_earnings', $data);
    }

    if (!$save) {
        ride_earning_toast('error', 'Failed to save ride sharing earning. Please try again.');
    }

    $cascaded = ride_cascade_following_weeks($obj, $employee_id, $week_year, $week_number);

    $msg = $isUpdate
        ? "Week {$week_number} updated successfully!"
        : 'Ride sharing earning saved successfully!';

    if ($calc['prev_carry'] != 0.0) {
        $msg .= ' Previous unpaid ' . number_format($calc['prev_carry'], 2) . ' carried forward.';
    }
    if ($cascaded > 0) {
        $msg .= " Updated {$cascaded} later week(s) carry.";
    }

    ride_earning_toast('success', $msg);
}

// ---------------------------------------------------------------------------
// PAGE DATA
// ---------------------------------------------------------------------------
$obj->select(
    'add_employee_details',
    'add_employee_details.*',
    null,
    "add_employee_details.work_type = 'ridesharing' AND add_employee_details.id IN (SELECT DISTINCT employee_id FROM ride_sharing_rate_settings)",
    'add_employee_details.name ASC'
);
$employees = $obj->getResult();

$filter_year = (isset($_GET['filter_year']) && $_GET['filter_year'] !== '') ? (int) $_GET['filter_year'] : null;
$filter_week = (isset($_GET['filter_week']) && $_GET['filter_week'] !== '') ? (int) $_GET['filter_week'] : null;

$where = [];
if ($filter_year !== null) {
    $where[] = 'ride_sharing_earnings.week_year = ' . $filter_year;
}
if ($filter_week !== null) {
    $where[] = 'ride_sharing_earnings.week_number = ' . $filter_week;
}
$earningsWhere = $where ? implode(' AND ', $where) : null;

$obj->select(
    'ride_sharing_earnings',
    'ride_sharing_earnings.*, add_employee_details.name, add_employee_details.company_name, add_employee_details.type_of_contract',
    'LEFT JOIN add_employee_details ON add_employee_details.id = ride_sharing_earnings.employee_id',
    $earningsWhere,
    'ride_sharing_earnings.week_year ASC, ride_sharing_earnings.week_number ASC, ride_sharing_earnings.id ASC'
);
$earnings = $obj->getResult() ?: [];

$progress_year = $filter_year ?: $form_year;
$progress_week = $filter_week ?: $form_week;

$weekRateSettings = [];
foreach ($employees as $employee) {
    $employeeId = (int) ($employee['id'] ?? 0);
    $rateResult = ride_get_rate_settings_for_week($obj, $employeeId, $progress_year, $progress_week);
    if (!$rateResult) {
        continue;
    }
    $settingRow = $rateResult['data'];
    $settingRow['employee_id'] = $employeeId;
    $settingRow['name'] = $employee['name'] ?? '';
    $settingRow['company_name'] = $employee['company_name'] ?? '';
    $weekRateSettings[] = $settingRow;
}

$obj->select(
    'ride_sharing_earnings',
    'employee_id',
    null,
    "week_year = {$progress_year} AND week_number = {$progress_week}"
);
$weekEarningRows = $obj->getResult() ?: [];

$weekEarningEmployeeIds = [];
foreach ($weekEarningRows as $earningRow) {
    $weekEarningEmployeeIds[(int) ($earningRow['employee_id'] ?? 0)] = true;
}

$missingEarnings = [];
foreach ($weekRateSettings as $settingRow) {
    $employeeId = (int) ($settingRow['employee_id'] ?? 0);
    if ($employeeId > 0 && empty($weekEarningEmployeeIds[$employeeId])) {
        $missingEarnings[] = $settingRow;
    }
}

$totalRateSettings = count($weekRateSettings);
$totalRemainingEarnings = count($missingEarnings);

$form_range_label = week_range_label($current_start, $current_end);

// Column totals for footer
$totals = [
    'bolt_net' => 0, 'bolt_payout' => 0, 'uber_net' => 0, 'uber_payout' => 0,
    'total_net' => 0, 'commission' => 0, 'vehicle_rent' => 0, 'total_payout' => 0,
    'balance' => 0, 'tax' => 0, 'week_balance' => 0, 'prev_carry' => 0, 'total_balance' => 0,
];
foreach ($earnings as $row) {
    if (empty($row['id'])) {
        continue;
    }
    foreach ($totals as $key => $_) {
        $totals[$key] += (float) ($row[$key] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Dhothar International" />
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Dhothar International Employee DB | Ride Sharing Earnings</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2-bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/delivery_earnings.css">
    <style>
        #earningForm .select2-container .select2-choice .select2-arrow b,
        #s2id_employee_id .select2-choice .select2-arrow b {
            background-image: url('<?= $base_url ?>assets/js/select2/select2.png') !important;
        }

        .bulk-status-actions {
            margin-bottom: 15px;
        }

        .bulk-status-actions .btn {
            margin-right: 8px;
        }

        #selectedCount {
            margin-left: 8px;
            font-size: 13px;
            color: #888;
        }

        .earning-row-paid td {
            background-color: #f4f4f4 !important;
            color: #777;
        }

        .earning-locked-label {
            font-size: 11px;
        }

        .deduction-label {
            color: #c62828;
        }

        #table-4 {
            font-size: 12px;
        }
    </style>
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
                            <div class="panel-title">Ride Sharing Earning Entry</div>
                        </div>
                        <div class="panel-body">
                            <form action="ride_sharing_earnings" method="post" id="earningForm">
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
                                                        <option value="<?= (int) $emp['id']; ?>"
                                                            <?= (int) $emp['id'] === $form_employee_id ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars(($emp['name'] ?? '') . ' - ' . ($emp['company_name'] ?? '') . ' - ' . ($emp['type_of_contract'] ?? '')); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="rate-info" id="rateInfo">Select employee to load rate settings.</div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Year</label>
                                                <select name="week_year" id="week_year" class="form-control" required>
                                                    <?= year_options_html($form_year, $current_year); ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Week</label>
                                                <select name="week_number" id="week_number" class="form-control" required>
                                                    <?= week_options_html($form_week, $form_year); ?>
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
                                                <label class="control-label">Bolt Net</label>
                                                <input type="number" step="0.01" min="0" name="bolt_net" id="bolt_net"
                                                    class="form-control calc-input" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Bolt Payout</label>
                                                <input type="number" step="0.01" min="0" name="bolt_payout" id="bolt_payout"
                                                    class="form-control calc-input" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Uber Net</label>
                                                <input type="number" step="0.01" min="0" name="uber_net" id="uber_net"
                                                    class="form-control calc-input" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Uber Payout</label>
                                                <input type="number" step="0.01" min="0" name="uber_payout" id="uber_payout"
                                                    class="form-control calc-input" value="0">
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="control-label">Total Net <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="total_net" id="total_net"
                                                    class="form-control calc-readonly total-highlight" readonly value="0.00">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label deduction-label">Commission <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="commission" id="commission" class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label deduction-label">Vehicle Rent <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="vehicle_rent" id="vehicle_rent" class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Total Payout <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="total_payout" id="total_payout"
                                                    class="form-control calc-readonly total-highlight" readonly value="0.00">
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="control-label">Balance <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="balance" id="balance" class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Tax</label>
                                                <input type="number" step="0.01" min="0" name="tax" id="tax"
                                                    class="form-control calc-input" value="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Final Balance <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="week_balance" id="week_balance" class="form-control calc-readonly" readonly value="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="control-label">Prev Adjust <small class="text-muted">(auto)</small></label>
                                                <input type="text" id="prev_carry_display" class="form-control calc-readonly" readonly value="0.00">
                                                <input type="hidden" name="prev_carry" id="prev_carry" value="0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Total Balance <small class="text-muted">(auto)</small></label>
                                                <input type="text" name="total_balance" id="total_balance"
                                                    class="form-control calc-readonly total-highlight" readonly value="0.00">
                                            </div>
                                        </div>

                                        <hr>
                                        <div class="text-center">
                                            <button type="submit" name="save_ride_sharing_earning" id="submitEarningBtn" class="btn btn-success">
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
                    <form method="get" action="ride_sharing_earnings" class="form-inline">
                        <label class="control-label" style="margin-right: 8px;">Filter</label>
                        <select name="filter_year" class="form-control" style="margin-right: 8px; min-width: 110px;">
                            <option value="">All Years</option>
                            <?= year_options_html($filter_year, $current_year); ?>
                        </select>
                        <select name="filter_week" class="form-control" style="margin-right: 8px; min-width: 130px;">
                            <?= week_options_html($filter_week, $filter_year ?: $current_year, true); ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm" style="margin-right: 6px;">Apply</button>
                        <a href="ride_sharing_earnings" class="btn btn-default btn-sm">Clear</a>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="row" style="display:flex;justify-content:center;flex-wrap:wrap;">
                        <div class="col-md-4 col-sm-6">
                            <div class="tile-stats tile-green">
                                <div class="icon"><i class="entypo-chart-bar"></i></div>
                                <div class="num"><?= $totalRateSettings; ?></div>
                                <h3>Employees With Rate Settings — Week <?= (int) $progress_week; ?> / <?= (int) $progress_year; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <a href="#missingEarningsModal" data-toggle="modal" style="display:block;color:inherit;">
                                <div class="tile-stats tile-red" style="cursor:pointer;">
                                    <div class="icon"><i class="entypo-users"></i></div>
                                    <div class="num"><?= $totalRemainingEarnings; ?></div>
                                    <h3>Remaining Earnings — Week <?= (int) $progress_week; ?> / <?= (int) $progress_year; ?></h3>
                                    <p style="margin:8px 0 0;color:#fff;">Click to view employees</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="missingEarningsModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">
                                Missing Earnings — Week <?= (int) $progress_week; ?> / <?= (int) $progress_year; ?>
                            </h4>
                        </div>
                        <div class="modal-body">
                            <?php if (empty($missingEarnings)): ?>
                                <div class="alert alert-success" style="margin-bottom:0;">
                                    Earnings have been entered for all employees with rate settings for this week.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" style="margin-bottom:0;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Employee</th>
                                                <th>Company</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($missingEarnings as $index => $employee): ?>
                                                <tr>
                                                    <td><?= $index + 1; ?></td>
                                                    <td><?= htmlspecialchars($employee['name'] ?? ''); ?></td>
                                                    <td><?= htmlspecialchars($employee['company_name'] ?? ''); ?></td>
                                                    <td>
                                                        <a class="btn btn-success btn-xs"
                                                            href="ride_sharing_earnings?<?= htmlspecialchars(http_build_query([
                                                                'employee_id' => (int) ($employee['employee_id'] ?? 0),
                                                                'week_year' => (int) $progress_year,
                                                                'week_number' => (int) $progress_week,
                                                            ])); ?>">
                                                            Add Earnings
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <h3>Ride Sharing Earnings</h3>
            <br />

            <div class="bulk-status-actions">
                <button type="button" class="btn btn-success btn-sm" id="markPaidBtn" disabled>
                    Mark Selected as Paid
                </button>
                <button type="button" class="btn btn-warning btn-sm" id="markUnpaidBtn" disabled>
                    Mark Selected as Unpaid
                </button>
                <span id="selectedCount">0 selected</span>
            </div>

            <div class="table-responsive">
            <table class="table table-bordered datatable table-3" id="table-4">
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" id="selectAllEarnings" title="Select all">
                        </th>
                        <th>S.no</th>
                        <th>Driver</th>
                        <th>Year</th>
                        <th>Week</th>
                        <th>Bolt Net</th>
                        <th>Bolt Payout</th>
                        <th>Uber Net</th>
                        <th>Uber Payout</th>
                        <th>Total Net</th>
                        <th class="deduction-label">Commission</th>
                        <th class="deduction-label">Vehicle Rent</th>
                        <th>Total Payout</th>
                        <th>Balance</th>
                        <th>Tax</th>
                        <th>Final Balance</th>
                        <th>Prev Adjust</th>
                        <th>Total Balance</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($earnings) && !empty($earnings[0]['id'])) {
                        $sno = 1;
                        foreach ($earnings as $row) {
                            $weekBal = (float) ($row['week_balance'] ?? 0);
                            $prevCarry = (float) ($row['prev_carry'] ?? 0);
                            $totalBal = (float) ($row['total_balance'] ?? 0);
                            $isPaid = (int) ($row['status'] ?? 0) === 1;
                            $rowLocked = $isPaid && !isSuperAdmin();
                            ?>
                            <tr class="<?= $isPaid ? 'earning-row-paid' : '' ?>">
                                <td>
                                    <input type="checkbox" class="earning-checkbox" value="<?= (int) $row['id']; ?>"
                                        <?= $rowLocked ? 'disabled' : '' ?>>
                                </td>
                                <td data-order="<?= $sno; ?>"><?= $sno++; ?></td>
                                <td><?= htmlspecialchars($row['name'] ?? ''); ?></td>
                                <td data-order="<?= (int) ($row['week_year'] ?? 0); ?>"><?= htmlspecialchars($row['week_year'] ?? ''); ?></td>
                                <td data-order="<?= (int) ($row['week_number'] ?? 0); ?>"><?= !empty($row['week_number']) ? 'Week ' . (int) $row['week_number'] : ''; ?></td>
                                <td data-order="<?= (float) $row['bolt_net']; ?>"><?= num_display($row['bolt_net']); ?></td>
                                <td data-order="<?= (float) $row['bolt_payout']; ?>"><?= num_display($row['bolt_payout']); ?></td>
                                <td data-order="<?= (float) $row['uber_net']; ?>"><?= num_display($row['uber_net']); ?></td>
                                <td data-order="<?= (float) $row['uber_payout']; ?>"><?= num_display($row['uber_payout']); ?></td>
                                <td data-order="<?= (float) $row['total_net']; ?>"><?= num_display($row['total_net']); ?></td>
                                <td data-order="<?= (float) $row['commission']; ?>"><?= num_display($row['commission']); ?></td>
                                <td data-order="<?= (float) $row['vehicle_rent']; ?>"><?= num_display($row['vehicle_rent']); ?></td>
                                <td data-order="<?= (float) $row['total_payout']; ?>"><?= num_display($row['total_payout']); ?></td>
                                <td data-order="<?= (float) $row['balance']; ?>"><?= num_display($row['balance']); ?></td>
                                <td data-order="<?= (float) $row['tax']; ?>"><?= num_display($row['tax']); ?></td>
                                <td data-order="<?= $weekBal; ?>"><?= num_display($weekBal); ?></td>
                                <td data-order="<?= $prevCarry; ?>"><?= num_display($prevCarry); ?></td>
                                <td data-order="<?= $totalBal; ?>">
                                    <strong<?= $totalBal < 0 ? ' style="color:#c62828;"' : ''; ?>>
                                        <?= num_display($totalBal); ?>
                                    </strong>
                                </td>
                                <td class="earning-status-cell">
                                    <strong><?= $isPaid ? '<span class="text-success">Paid</span>' : '<span class="text-danger">Unpaid</span>'; ?></strong>
                                </td>
                                <td>
                                    <?php if ($rowLocked): ?>
                                        <span class="label label-success earning-locked-label">
                                            <i class="entypo-lock"></i> Locked
                                        </span>
                                    <?php else: ?>
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                            <button type="button" class="btn btn-primary btn-sm edit-earning-btn"
                                                data-employee-id="<?= (int) ($row['employee_id'] ?? 0); ?>"
                                                data-week-year="<?= (int) ($row['week_year'] ?? 0); ?>"
                                                data-week-number="<?= (int) ($row['week_number'] ?? 0); ?>">
                                                <span class="entypo-pencil"></span> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm delete-earning-btn"
                                                data-id="<?= (int) $row['id']; ?>">
                                                <span class="entypo-trash"></span> Delete
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
                <?php if (!empty($earnings) && !empty($earnings[0]['id'])): ?>
                <tfoot>
                    <tr>
                        <th colspan="5">Total</th>
                        <th><?= num_display($totals['bolt_net']); ?></th>
                        <th><?= num_display($totals['bolt_payout']); ?></th>
                        <th><?= num_display($totals['uber_net']); ?></th>
                        <th><?= num_display($totals['uber_payout']); ?></th>
                        <th><?= num_display($totals['total_net']); ?></th>
                        <th><?= num_display($totals['commission']); ?></th>
                        <th><?= num_display($totals['vehicle_rent']); ?></th>
                        <th><?= num_display($totals['total_payout']); ?></th>
                        <th><?= num_display($totals['balance']); ?></th>
                        <th><?= num_display($totals['tax']); ?></th>
                        <th><?= num_display($totals['week_balance']); ?></th>
                        <th><?= num_display($totals['prev_carry']); ?></th>
                        <th><?= num_display($totals['total_balance']); ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
            </div>
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

    <script src="<?= $base_url ?>assets/js/datatables/datatables.js"></script>
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/datatables/datatables.css">
    <script src="<?= $base_url ?>assets/js/gsap/TweenMax.min.js"></script>
    <script src="<?= $base_url ?>assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js"></script>
    <script src="<?= $base_url ?>assets/js/bootstrap.js"></script>
    <script src="<?= $base_url ?>assets/js/joinable.js"></script>
    <script src="<?= $base_url ?>assets/js/resizeable.js"></script>
    <script src="<?= $base_url ?>assets/js/neon-api.js"></script>
    <script src="<?= $base_url ?>assets/js/cookies.min.js"></script>
    <script src="<?= $base_url ?>assets/js/select2/select2.min.js"></script>
    <script src="<?= $base_url ?>assets/js/neon-custom.js"></script>
    <script src="<?= $base_url ?>assets/js/ride_sharing_earnings.js"></script>

    <script type="text/javascript">
        var CAN_UNLOCK_PAID = <?= isSuperAdmin() ? 'true' : 'false' ?>;

        jQuery(document).ready(function ($) {
            var exportOpts = {
                columns: ':not(:first-child):not(:last-child)',
                orthogonal: 'sort',
                format: {
                    body: function (data, row, column, node) {
                        if (node && node.getAttribute) {
                            var order = node.getAttribute('data-order');
                            if (order !== null && order !== '') {
                                return order;
                            }
                        }
                        if (typeof data === 'string') {
                            return data.replace(/<[^>]*>/g, '').replace(/,/g, '').trim();
                        }
                        return data;
                    }
                }
            };

            jQuery('#table-4').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'copyHtml5', exportOptions: exportOpts },
                    { extend: 'excelHtml5', exportOptions: exportOpts },
                    { extend: 'csvHtml5', exportOptions: exportOpts },
                    { extend: 'pdfHtml5', exportOptions: exportOpts }
                ],
                order: [[3, 'desc'], [4, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [0, 19] }
                ]
            });

            function updateSelectionState() {
                var count = $('.earning-checkbox:checked').length;
                var total = $('.earning-checkbox:not(:disabled)').length;
                $('#selectedCount').text(count + ' selected');
                $('#markPaidBtn, #markUnpaidBtn').prop('disabled', count === 0);
                $('#selectAllEarnings').prop('checked', total > 0 && count === total);
            }

            function setRowStatus($row, status) {
                var label = status == 1
                    ? '<span class="text-success">Paid</span>'
                    : '<span class="text-danger">Unpaid</span>';
                $row.find('.earning-status-cell strong').html(label);
            }

            function lockRow($row) {
                $row.addClass('earning-row-paid');
                $row.find('.earning-checkbox').prop('checked', false);
                if (!CAN_UNLOCK_PAID) {
                    $row.find('.earning-checkbox').prop('disabled', true);
                    $row.find('td:last').html(
                        '<span class="label label-success earning-locked-label"><i class="entypo-lock"></i> Locked</span>'
                    );
                }
                setRowStatus($row, 1);
            }

            function unlockRow($row) {
                $row.removeClass('earning-row-paid');
                $row.find('.earning-checkbox').prop('disabled', false);
                setRowStatus($row, 0);
            }

            $('#selectAllEarnings').on('change', function () {
                $('.earning-checkbox:not(:disabled)').prop('checked', this.checked);
                updateSelectionState();
            });

            $(document).on('change', '.earning-checkbox', updateSelectionState);

            function updateEarningStatus(status) {
                var ids = $('.earning-checkbox:checked').map(function () {
                    return $(this).val();
                }).get();

                $.post('ajax/update_ride_sharing_status', { ids: ids, status: status }, function (res) {
                    if (res === 'denied') {
                        toastr.error('Only Super Admin can unlock paid records.');
                    } else if (res === 'success') {
                        ids.forEach(function (id) {
                            var $row = $('.earning-checkbox[value="' + id + '"]').closest('tr');
                            if (status == 1) {
                                lockRow($row);
                            } else {
                                unlockRow($row);
                            }
                        });
                        updateSelectionState();
                        toastr.success('Status updated successfully!');
                    } else {
                        toastr.error('Update failed!');
                    }
                });
            }

            $('#markPaidBtn').on('click', function () {
                updateEarningStatus(1);
            });

            $('#markUnpaidBtn').on('click', function () {
                updateEarningStatus(0);
            });

            updateSelectionState();
        });
    </script>
</body>

</html>
