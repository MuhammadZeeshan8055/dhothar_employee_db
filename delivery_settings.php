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
    $tax_type = in_array($_POST['tax_type'] ?? '', $allowed_types, true) ? $_POST['tax_type'] : 'percentage';
    $sc_type = in_array($_POST['sc_type'] ?? '', $allowed_types, true) ? $_POST['sc_type'] : 'percentage';

    if ($employee_id <= 0) {
        settings_toast('error', 'Please select an employee.');
    }

    $max_week = iso_weeks_in_year($week_year);
    if ($week_year < 2000 || $week_year > 2100 || $week_number < 1 || $week_number > $max_week) {
        settings_toast('error', 'Please select a valid year and week.');
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
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Dhothar International Employee DB | Rate Settings</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/js/select2/select2-bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/delivery_earnings.css">

    <script src="<?= $base_url ?>assets/js/jquery-1.11.3.min.js"></script>

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
                                                        <option value="<?= (int) $emp['id']; ?>">
                                                            <?= htmlspecialchars(($emp['name'] ?? '') . ' - ' . ($emp['company_name'] ?? '') . ' - ' . ($emp['type_of_contract'] ?? '')); ?>
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
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            <div class="col-md-12">
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
                                        </div>

                                        <br>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <label class="control-label">Service Providers</label>
                                                <select name="service_providers" id="service_providers" class="form-control">
                                                    <option value="AML">AML</option>
                                                    <option value="ARANCA">ARANCA</option>
                                                </select>
                                            </div>
                                        </div>

                                        <br>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <label class="control-label">Tax</label>
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <input type="text" name="tax_rate" id="tax_rate" class="form-control"
                                                            placeholder="e.g. 10">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select name="tax_type" id="tax_type" class="form-control">
                                                            <option value="fixed">Fixed</option>
                                                            <option value="percentage">%</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <br>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="control-label">Vehicle Type</label>
                                                <select name="vehicle_type" id="vehicle_type" class="form-control" required>
                                                    <option value="">Select</option>
                                                    <option value="bicyle">Bicycle</option>
                                                    <option value="sc">Scooter</option>
                                                    <option value="car">Car</option>
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="control-label">Vehicle Rate</label>
                                                <div style="display:flex; gap:10px;">
                                                    <input type="text" name="sc_rate" id="sc_rate" class="form-control"
                                                        placeholder="e.g. 10">
                                                    <select name="sc_type" id="sc_type" class="form-control">
                                                        <option value="percentage">%</option>
                                                        <option value="fixed">Fixed</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <br>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <label class="control-label">Vehicle Company Name</label>
                                                <select name="vehicle_company_name" id="vehicle_company_name" class="form-control" required>
                                                    <option value="">Select Company</option>
                                                    <option value="uny_mobility_srl">UNY MOBILITY SRL</option>
                                                    <option value="kiris_rent_srl">KIRIS RENT SRL</option>
                                                    <option value="rbj_brothers_srl">RBJ BROTHERS SRL</option>
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

    <script src="<?= $base_url ?>assets/js/bootstrap.js"></script>
    <script src="<?= $base_url ?>assets/js/select2/select2.min.js"></script>
    <script src="<?= $base_url ?>assets/js/delivery_settings.js"></script>
</body>

</html>
