<?php

include('session.php');
include('database.php');
include('includes/delivery_earnings_helper.php');

$obj = new Database();

$current_year = (int) date('o');
$current_week = (int) date('W');
list($current_start, $current_end) = iso_week_range($current_year, $current_week);

if (isset($_POST['save_delivery_settings'])) {

    $employee_id = (int) ($_POST['employee_id'] ?? 0);
    $week_year = (int) ($_POST['week_year'] ?? $current_year);
    $week_number = (int) ($_POST['week_number'] ?? $current_week);
    $allowed_types = ['percentage', 'fixed'];

    $commission_type = in_array($_POST['commission_type'] ?? '', $allowed_types, true) ? $_POST['commission_type'] : 'percentage';
    $tax_type = 'fixed';
    $sc_type = 'fixed';

    if ($employee_id <= 0) {
        settings_toast('error', 'Please select an employee.');
    }

    $max_week = iso_weeks_in_year($week_year);
    if ($week_year < 2000 || $week_year > 2100 || $week_number < 1 || $week_number > $max_week) {
        settings_toast('error', 'Please select a valid year and week.');
    }

    // If this week (or a later week) is paid → Admin cannot change rates
    if (is_week_paid_locked($obj, 'delivery_earnings', $employee_id, $week_year, $week_number)) {
        settings_toast('error', 'Rate settings are locked because this week (or a later week) is paid. Only Super Admin can change them.');
    }

    list($week_start, $week_end) = iso_week_range($week_year, $week_number);

    $data = [
        'employee_id' => $employee_id,
        'week_year' => $week_year,
        'week_number' => $week_number,
        'week_start' => $week_start,
        'week_end' => $week_end,
        'commission_rate' => number_format((float) ($_POST['commission_rate'] ?? 0), 2, '.', ''),
        'commission_type' => $commission_type,
        'tax_rate' => number_format((float) ($_POST['tax_rate'] ?? 0), 2, '.', ''),
        'tax_type' => $tax_type,
        'sc_rate' => number_format((float) ($_POST['sc_rate'] ?? 0), 2, '.', ''),
        'sc_type' => $sc_type,
        'service_providers' => $_POST['service_providers'] ?? '',
        'vehicle_type' => $_POST['vehicle_type'] ?? '',
        'vehicle_company_name' => $_POST['vehicle_company_name'] ?? '',
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $existing = find_week_rate_settings($obj, $employee_id, $week_year, $week_number);

    if ($existing) {
        $save = $obj->update('employee_rate_settings', $data, 'id = ' . (int) $existing['id']);
        $msg = "Week {$week_number} settings updated successfully!";
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
        $save = $obj->insert('employee_rate_settings', $data);
        $msg = "Week {$week_number} settings saved successfully!";
    }

    if ($save) {
        settings_toast('success', $msg);
    }

    settings_toast('error', 'Failed to save delivery settings. Please try again.');
}

$obj->select('add_employee_details', '*', null, "work_type = 'food_delivery'", 'name ASC');
$employees = $obj->getResult();

$default_filter_year = $current_year;
$default_filter_week = $current_week - 1;
if ($default_filter_week < 1) {
    $default_filter_year = $current_year - 1;
    $default_filter_week = iso_weeks_in_year($default_filter_year);
}

$filter_year = (isset($_GET['filter_year']) && $_GET['filter_year'] !== '') ? (int) $_GET['filter_year'] : $default_filter_year;
$filter_week = (isset($_GET['filter_week']) && $_GET['filter_week'] !== '') ? (int) $_GET['filter_week'] : $default_filter_week;

// If URL has employee_id, open the form for that person on the filter week
// Example: delivery_settings?filter_year=2026&filter_week=47&employee_id=12
$prefill_employee_id = 0;
if (isset($_GET['employee_id']) && $_GET['employee_id'] !== '') {
    $prefill_employee_id = (int) $_GET['employee_id'];
}

$form_year = $current_year;
$form_week = $current_week;
if ($prefill_employee_id > 0) {
    $form_year = $filter_year;
    $form_week = $filter_week;
}
list($form_start, $form_end) = iso_week_range($form_year, $form_week);
$form_range_label = week_range_label($form_start, $form_end);

$obj->select(
    'employee_rate_settings',
    'employee_rate_settings.*, add_employee_details.name, add_employee_details.company_name, add_employee_details.status AS employee_status',
    'LEFT JOIN add_employee_details ON add_employee_details.id = employee_rate_settings.employee_id',
    "employee_rate_settings.week_year = {$filter_year} AND employee_rate_settings.week_number = {$filter_week}",
    'add_employee_details.name ASC, employee_rate_settings.id ASC'
);
$savedSettings = $obj->getResult() ?: [];

// Employees with no exact rate row for the selected week (may still use previous week)
$missingExactRates = delivery_missing_exact_rates_for_week($obj, $filter_year, $filter_week);
$totalMissingExactRates = count($missingExactRates);
$totalSavedExactRates = count($savedSettings);

$obj->select(
    'delivery_earnings',
    'delivery_earnings.*, add_employee_details.name, add_employee_details.company_name',
    'LEFT JOIN add_employee_details ON add_employee_details.id = delivery_earnings.employee_id',
    "delivery_earnings.week_year = {$filter_year} AND delivery_earnings.week_number = {$filter_week}",
    'delivery_earnings.id ASC'
);
$earningsForSummary = enrich_earnings_with_vehicle($obj, $obj->getResult() ?: []);
$rentByCompany = build_rent_by_company_summary($earningsForSummary);

$grand_sc_rent = 0;
$grand_employees = 0;
foreach ($rentByCompany as $group) {
    $grand_sc_rent += (float) $group['total_sc'];
    $grand_employees += (int) $group['employees'];
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
    <title>Dhothar International Employee DB | Rate Settings</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <?php include('components/head_theme.php'); ?>
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2-bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/delivery_earnings.css">

    <style>
        #employee_id+.select2-container,
        #s2id_employee_id {
            width: 100% !important;
            display: block !important;
        }

        #s2id_employee_id .select2-choice,
        #s2id_employee_id.select2-drop-above .select2-choice,
        #s2id_employee_id.select2-container-active .select2-choice,
        #s2id_employee_id.select2-dropdown-open .select2-choice {
            height: 38px !important;
            line-height: 36px !important;
            padding-left: 12px !important;
            border: 1px solid #ccc !important;
            border-radius: 3px !important;
            background: #fff !important;
            background-image: none !important;
            box-shadow: none !important;
        }

        #s2id_employee_id .select2-choice .select2-chosen {
            line-height: 36px !important;
            color: #555 !important;
        }

        #s2id_employee_id .select2-choice .select2-arrow b {
            background-image: url('<?= $base_url ?>assets/js/select2/select2.png') !important;
            background-position: 2px 5px !important;
        }

        .settings-summary {
            display: none;
            margin-top: 15px;
            padding: 12px 15px;
            background: #f8f9fb;
            border: 1px solid #e3e6ea;
            border-radius: 4px;
            font-size: 13px;
            color: #555;
            line-height: 1.6;
        }

        .settings-list-wrap {
            margin-top: 20px;
        }

        .settings-list-wrap .table {
            font-size: 12px;
        }

        .settings-week-filter {
            margin-bottom: 15px;
        }

        .settings-week-filter select {
            min-width: 110px;
            margin-right: 8px;
        }

        /* Keep modal on screen: scroll the list inside, not the whole page */
        #missingRatesModal .modal-dialog {
            margin: 30px auto;
            width: 90%;
            max-width: 900px;
        }

        #missingRatesModal .modal-body {
            padding-bottom: 10px;
        }

        #missingRatesModal .missing-rates-scroll {
            max-height: 55vh;
            overflow-y: auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #e3e6ea;
            border-radius: 4px;
        }

        #missingRatesModal .missing-rates-scroll .table {
            margin-bottom: 0;
        }

        #missingRatesModal .missing-rates-scroll .table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #303641;
        }
    </style>
</head>

<body class="page-body">
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
                            <div class="panel-title">Employee Rate Settings</div>
                        </div>
                        <div class="panel-body">
                            <form action="delivery_settings" method="post" id="settingsForm">
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
                                                            <?= $prefill_employee_id === (int) $emp['id'] ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars(($emp['name'] ?? '') . ' - ' . ($emp['company_name'] ?? '') . ' - ' . ($emp['type_of_contract'] ?? '') . employee_status_option_suffix($emp['status'] ?? 1)); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="rate-info" id="rateInfo" style="margin-top:6px;font-size:12px;color:#999;">
                                                    Select employee and week to load rate settings.
                                                </div>
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
                                                <input type="hidden" name="week_start" id="week_start" value="<?= htmlspecialchars($form_start); ?>">
                                                <input type="hidden" name="week_end" id="week_end" value="<?= htmlspecialchars($form_end); ?>">
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="control-label">Commission Rate</label>
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <input type="text" name="commission_rate" id="commission_rate"
                                                            class="form-control" placeholder="e.g. 10">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select name="commission_type" id="commission_type" class="form-control">
                                                            <option value="percentage">%</option>
                                                            <option value="fixed">Fixed</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Tax (Fixed)</label>
                                                <input type="text" name="tax_rate" id="tax_rate" class="form-control"
                                                    placeholder="e.g. 10">
                                                <input type="hidden" name="tax_type" id="tax_type" value="fixed">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="control-label">Service Providers</label>
                                                <select name="service_providers" id="service_providers" class="form-control">
                                                    <option value="AML">AML</option>
                                                    <option value="ARANCA">ARANCA</option>
                                                </select>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            
                                            <div class="col-md-4">
                                                <label class="control-label">Vehicle Type</label>
                                                <select name="vehicle_type" id="vehicle_type" class="form-control" required>
                                                    <option value="">Select</option>
                                                    <option value="bicyle">Bicycle</option>
                                                    <option value="sc">Scooter</option>
                                                    <option value="car">Car</option>
                                                    <option value="own_car">Own Car</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="control-label">Vehicle Rate (Fixed)</label>
                                                <input type="text" name="sc_rate" id="sc_rate" class="form-control"
                                                    placeholder="e.g. 10">
                                                <input type="hidden" name="sc_type" id="sc_type" value="fixed">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="control-label">Vehicle Company Name</label>
                                                <select name="vehicle_company_name" id="vehicle_company_name" class="form-control" required>
                                                    <option value="">Select Company</option>
                                                    <?= vehicle_companies_options_html($obj) ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div id="settingsSummary" class="settings-summary"></div>

                                        <br>

                                        <div class="text-center">
                                            <button type="submit" name="save_delivery_settings" id="submitSettingsBtn"
                                                class="btn btn-success">
                                                Submit Details
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </form>

                            <div class="settings-list-wrap">
                                <hr>
                                <h4>Saved Rate Settings</h4>

                                <form method="get" class="form-inline settings-week-filter">
                                    <label class="control-label" style="margin-right:8px;">Week</label>
                                    <select name="filter_year" class="form-control">
                                        <?= year_options_html($filter_year, $current_year); ?>
                                    </select>
                                    <select name="filter_week" class="form-control">
                                        <?= week_options_html($filter_week, $filter_year); ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Show</button>
                                </form>

                                <div class="row" style="display:flex;justify-content:center;flex-wrap:wrap;margin:15px 0 10px;">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="tile-stats tile-green">
                                            <div class="icon"><i class="entypo-chart-bar"></i></div>
                                            <div class="num"><?= $totalSavedExactRates; ?></div>
                                            <h3>Rates Saved — Week <?= (int) $filter_week; ?> / <?= (int) $filter_year; ?></h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <a href="#missingRatesModal" data-toggle="modal" style="display:block;color:inherit;">
                                            <div class="tile-stats tile-orange" style="cursor:pointer;">
                                                <div class="icon"><i class="entypo-users"></i></div>
                                                <div class="num"><?= $totalMissingExactRates; ?></div>
                                                <h3>No Rates Saved — Week <?= (int) $filter_week; ?> / <?= (int) $filter_year; ?></h3>
                                                <p style="margin:8px 0 0;color:inherit;opacity:0.75;">Click to view employees</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <div class="modal fade" id="missingRatesModal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                                <h4 class="modal-title">
                                                    No Rate Settings Saved — Week <?= (int) $filter_week; ?> / <?= (int) $filter_year; ?>
                                                </h4>
                                            </div>
                                            <div class="modal-body">
                                                <!-- <div class="alert alert-info" style="margin-bottom:15px;">
                                                    Ignore this list if you want the <strong>same rates as the previous week</strong> —
                                                    the system will keep using those automatically.
                                                    Only set rates when something changed, or when an employee has never had rates.
                                                </div> -->
                                                <?php if (empty($missingExactRates)): ?>
                                                    <div class="alert alert-success" style="margin-bottom:0;">
                                                        Every active delivery employee has rate settings saved for this week.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="missing-rates-scroll">
                                                        <table class="table table-bordered table-striped">
                                                            <thead>
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Employee</th>
                                                                    <th>Company</th>
                                                                    <th>Note</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($missingExactRates as $index => $employee): ?>
                                                                    <?php
                                                                    // Simple note: ignore if previous week rates are OK
                                                                    $note = missing_rate_note($employee);
                                                                    $employee_id = 0;
                                                                    if (isset($employee['employee_id'])) {
                                                                        $employee_id = (int) $employee['employee_id'];
                                                                    }
                                                                    $employee_name = '';
                                                                    if (isset($employee['name'])) {
                                                                        $employee_name = $employee['name'];
                                                                    }
                                                                    $company_name = '';
                                                                    if (isset($employee['company_name'])) {
                                                                        $company_name = $employee['company_name'];
                                                                    }
                                                                    ?>
                                                                    <tr>
                                                                        <td><?= $index + 1; ?></td>
                                                                        <td><?= htmlspecialchars($employee_name); ?></td>
                                                                        <td><?= htmlspecialchars($company_name); ?></td>
                                                                        <td class="<?= htmlspecialchars($note['class']); ?>">
                                                                            <?= htmlspecialchars($note['text']); ?>
                                                                        </td>
                                                                        <td>
                                                                            <button type="button"
                                                                                class="btn btn-success btn-xs edit-settings-btn"
                                                                                data-dismiss="modal"
                                                                                data-employee-id="<?= $employee_id; ?>"
                                                                                data-week-year="<?= (int) $filter_year; ?>"
                                                                                data-week-number="<?= (int) $filter_week; ?>">
                                                                                Set Rates
                                                                            </button>
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

                                <table class="table table-bordered datatable table-3" id="settings-table">
                                    <thead>
                                        <tr>
                                            <th>S.no</th>
                                            <th>Employee</th>
                                            <th>Year</th>
                                            <th>Week</th>
                                            <th>Date Range</th>
                                            <th>Commission Rate</th>
                                            <th>Service Provider</th>
                                            <th>Tax Rate</th>
                                            <th>Vehicle Rate</th>
                                            <th>Vehicle Company</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sno = 1;
                                        foreach ($savedSettings as $row):
                                            $commissionRate = (float) ($row['commission_rate'] ?? 0);
                                            $taxRate = (float) ($row['tax_rate'] ?? 0);
                                            $scRate = (float) ($row['sc_rate'] ?? 0);
                                        ?>
                                            <tr>
                                                <td data-order="<?= $sno; ?>"><?= $sno++; ?></td>
                                                <td>
                                                    <?= htmlspecialchars($row['name'] ?? ''); ?>
                                                    <?= employee_status_badge_html($row['employee_status'] ?? 1); ?>
                                                </td>
                                                <td data-order="<?= (int) ($row['week_year'] ?? 0); ?>"><?= (int) ($row['week_year'] ?? 0); ?></td>
                                                <td data-order="<?= (int) ($row['week_number'] ?? 0); ?>"><?= (int) ($row['week_number'] ?? 0); ?></td>
                                                <td><?= htmlspecialchars(week_range_label($row['week_start'] ?? '', $row['week_end'] ?? '')); ?></td>
                                                <td data-order="<?= $commissionRate; ?>"><?= num_display($commissionRate); ?></td>
                                                <td><?= htmlspecialchars($row['service_providers'] ?? ''); ?></td>
                                                <td data-order="<?= $taxRate; ?>"><?= num_display($taxRate); ?></td>
                                                <td data-order="<?= $scRate; ?>"><?= num_display($scRate); ?></td>
                                                <td><?= htmlspecialchars(vehicle_company_label($row['vehicle_company_name'] ?? '')); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-primary btn-sm edit-settings-btn"
                                                        data-employee-id="<?= (int) ($row['employee_id'] ?? 0); ?>"
                                                        data-week-year="<?= (int) ($row['week_year'] ?? 0); ?>"
                                                        data-week-number="<?= (int) ($row['week_number'] ?? 0); ?>">
                                                        Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (!empty($rentByCompany)): ?>
                                <hr>
                                <div class="rent-by-company-wrap">
                                    <h4>Rent by Vehicle Company — Week <?= (int) $filter_week; ?> / <?= (int) $filter_year; ?></h4>
                                    <table class="table table-bordered table-sm" id="rent-by-company">
                                        <thead>
                                            <tr>
                                                <th>Vehicle Company</th>
                                                <th>Employees</th>
                                                <th>Total Vehicle Rent</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rentByCompany as $group): ?>
                                                <tr class="rent-company-row">
                                                    <td><?= htmlspecialchars($group['label']); ?></td>
                                                    <td><?= (int) $group['employees']; ?></td>
                                                    <td><?= number_format((float) $group['total_sc'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr>
                                                <td><strong>Total</strong></td>
                                                <td><strong><?= $grand_employees; ?></strong></td>
                                                <td><strong><?= number_format($grand_sc_rent, 2); ?></strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <?php /* Click a company to view filtered earnings. View all earnings for this week */ ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

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
    <script src="<?= $base_url ?>assets/js/delivery_settings.js"></script>

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            /* Export raw numbers via data-order so Excel formulas work. */
            var exportOpts = {
                columns: ':not(:last-child)',
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

            jQuery('#settings-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'copyHtml5', exportOptions: exportOpts },
                    { extend: 'excelHtml5', exportOptions: exportOpts },
                    { extend: 'csvHtml5', exportOptions: exportOpts },
                    { extend: 'pdfHtml5', exportOptions: exportOpts }
                ],
                order: [[3, 'asc'], [2, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [10] }
                ]
            });
        });
    </script>
</body>

</html>
