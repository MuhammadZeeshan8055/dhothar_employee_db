<?php

include('session.php');
include('database.php');

$obj = new Database();

if (isset($_POST['save_delivery_settings'])) {

    $employee_id = (int) ($_POST['employee_id'] ?? 0);
    $allowed_types = ['percentage', 'fixed'];

    $commission_type = in_array($_POST['commission_type'] ?? '', $allowed_types, true) ? $_POST['commission_type'] : 'percentage';
    $tax_type = in_array($_POST['tax_type'] ?? '', $allowed_types, true) ? $_POST['tax_type'] : 'percentage';
    $sc_type = in_array($_POST['sc_type'] ?? '', $allowed_types, true) ? $_POST['sc_type'] : 'percentage';

    $data = array(
        "employee_id" => $employee_id,
        "commission_rate" => number_format((float) ($_POST['commission_rate'] ?? 0), 2, '.', ''),
        "commission_type" => $commission_type,
        "tax_rate" => number_format((float) ($_POST['tax_rate'] ?? 0), 2, '.', ''),
        "tax_type" => $tax_type,
        "sc_rate" => number_format((float) ($_POST['sc_rate'] ?? 0), 2, '.', ''),
        "sc_type" => $sc_type,
        "updated_at" => date('Y-m-d H:i:s')
    );

    if ($employee_id > 0) {
        $obj->select("employee_rate_settings", "id", null, "employee_id = $employee_id");
        $existing = $obj->getResult();

        if (!empty($existing) && isset($existing[0]['id'])) {
            $save = $obj->update("employee_rate_settings", $data, "employee_id = $employee_id");
        } else {
            $data["created_at"] = date('Y-m-d H:i:s');
            $save = $obj->insert("employee_rate_settings", $data);
        }

        if ($save) {
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Delivery settings saved successfully!'
            ];
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'message' => 'Failed to save delivery settings. Please try again.'
            ];
        }
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Please select an employee.'
        ];
    }

    header("Location: delivery_settings");
    exit;
}

$obj->select("add_employee_details", "*", null, "work_type = 'food_delivery'", "name ASC");
$employees = $obj->getResult();

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
                <div class="col-md-12">
                    <div class="panel panel-primary" data-collapsed="0">
                        <div class="panel-heading">
                            <div class="panel-title">
                            Employee Rate Settings
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <form action="delivery_settings" method="post">

                                    <div class="row">
                                        
                                        <div class="col-md-1"></div>

                                        <div class="col-md-6">

                                            <div class="col-md-12">
                                                <label class="control-label">Name - Company name</label>
                                                <select name="employee_id" class="form-control" required>
                                                    <option value="">--Select Employee--</option>
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
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-12">
                                                <label class="control-label">Commission Rate</label>
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <input type="text" name="commission_rate" class="form-control"
                                                            placeholder="e.g. 10">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select name="commission_type" class="form-control">
                                                            <option value="percentage">%</option>
                                                            <option value="fixed">Fixed</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="clear"></div><br>

                                            <div class="col-md-12">
                                                <label class="control-label">Tax Rate</label>
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <input type="text" name="tax_rate" class="form-control"
                                                            placeholder="e.g. 10">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select name="tax_type" class="form-control">
                                                            <option value="percentage">%</option>
                                                            <option value="fixed">Fixed</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="clear"></div><br>

                                            <div class="col-md-12">
                                                <label class="control-label">SC Rate</label>
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <input type="text" name="sc_rate" class="form-control"
                                                            placeholder="e.g. 10">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select name="sc_type" class="form-control">
                                                            <option value="percentage">%</option>
                                                            <option value="fixed">Fixed</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="clear"></div><br>

                                        </div>

                                        <div class="clear"></div><br>

                                        <div class="col-md-12 text-center">
                                            <button type="submit" name="save_delivery_settings" class="btn btn-success">
                                                Submit Details
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
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
    <script src="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-1.2.2.min.js" id="script-resource-8"></script>
    <script src="<?= $base_url ?>assets/js/jvectormap/jquery-jvectormap-europe-merc-en.js"
        id="script-resource-9"></script>

    <script src="<?= $base_url ?>assets/js/neon-chat.js" id="script-resource-16"></script>
    <script src="<?= $base_url ?>assets/js/neon-custom.js" id="script-resource-17"></script>


    <script>
        function previewImg(e) {
            const img = document.getElementById('preview');
            img.src = URL.createObjectURL(e.target.files[0]);
            img.style.display = 'block';
        }
    </script>
</body>

</html>