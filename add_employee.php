<?php

include('session.php');
include('database.php');

$obj = new Database();

if (isset($_POST['add_employee'])) {

    // Handle image upload
    $profile_pic = "";

    if (!empty($_FILES['profile_pic']['name'])) {

        $target_dir = "uploads/";

        // original name
        $original = $_FILES["profile_pic"]["name"];

        // get extension
        $ext = pathinfo($original, PATHINFO_EXTENSION);

        // clean filename (remove spaces + special chars)
        $base = pathinfo($original, PATHINFO_FILENAME);
        $base = preg_replace("/[^a-zA-Z0-9]/", "_", $base); // only letters, numbers, underscore

        // final safe filename
        $file_name = time() . "_" . $base . "." . $ext;

        $target_file = $target_dir . $file_name;

        move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file);

        $profile_pic = $file_name;
    }

    // Prepare data array
    $data = array(
        "name" => $_POST['name'],
        "company_name" => $_POST['company_name'],
        "designation" => $_POST['designation'],
        "nationality" => $_POST['nationality'],
        "date_of_birth" => $_POST['date_of_birth'],

        "work_permit_date" => $_POST['work_permit_date'],
        "work_permit_no" => $_POST['work_permit_no'],
        "date_joining" => $_POST['date_joining'],

        "address" => $_POST['address'],
        "type_of_contract" => $_POST['type_of_contract'],
        "contract_no" => $_POST['contract_no'],

        "passport_no" => $_POST['passport_no'],
        "passport_issue_date" => $_POST['passport_issue_date'],
        "passport_expiry_date" => $_POST['passport_expiry_date'],

        "trc_no" => $_POST['trc_no'],
        "cnp" => $_POST['cnp'],
        "trc_expiry" => $_POST['trc_expiry'],

        "driving_license" => $_POST['driving_license'],
        "driving_license_type" => $_POST['driving_license_type'],
        "license_issue_date" => $_POST['license_issue_date'],
        "license_expiry" => $_POST['license_expiry'],

        "gross_salary" => $_POST['gross_salary'],
        "net_salary" => $_POST['net_salary'],

        "iban" => $_POST['iban'],
        "bank_name" => $_POST['bank_name'],

        "status" => $_POST['status'],

        "profile_pic" => $profile_pic
    );

    // Insert into DB
    $insert = $obj->insert("add_employee_details", $data);

    if ($insert) {
        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Employee added successfully!'
        ];
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Failed to add employee. Please try again.'
        ];
    }

    header("Location: add_employee.php");
    exit;
}


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
                                Entry Form
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <form action="add_employee" method="post" enctype="multipart/form-data">

                                    <div class="row">
                                        <div class="col-md-3 text-center profile-upload">
                                            <label class="control-label">Profile Pic</label>

                                            <div class="avatar-wrapper">
                                                <img id="preview" src="https://via.placeholder.com/120x120?text=Photo">
                                            </div>

                                            <input type="file" name="profile_pic" class="form-control" accept="image/*"
                                                onchange="previewImg(event)">
                                        </div>

                                        <style>
                                            .profile-upload {
                                                text-align: center;
                                            }

                                            .avatar-wrapper {
                                                width: 120px;
                                                height: 120px;
                                                margin: 5px auto 10px;
                                                border-radius: 50%;
                                                overflow: hidden;
                                                border: 3px solid #e9ecef;
                                                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
                                                background: #f8f9fa;
                                                transition: 0.3s ease;
                                            }

                                            .avatar-wrapper:hover {
                                                transform: scale(1.05);
                                                box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
                                            }

                                            .avatar-wrapper img {
                                                width: 100%;
                                                height: 100%;
                                                object-fit: cover;
                                            }
                                        </style>

                                        <div class="col-md-1"></div>

                                        <div class="col-md-6">

                                            <div class="col-md-12">
                                                <label class="control-label">Name</label>
                                                <input type="text" name="name" class="form-control"
                                                    placeholder="Enter full name" required>
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">Company name</label>
                                                <input type="text" name="company_name" class="form-control"
                                                    placeholder="Enter Company name">
                                            </div>

                                            <div class="clear"></div><br>

                                            <div class="col-md-12">
                                                <label class="control-label">Designation</label>
                                                <input type="text" name="designation" class="form-control"
                                                    placeholder="e.g. Accountant / Driver / Manager">
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">Work Permit Date</label>
                                                <input type="date" name="work_permit_date" class="form-control">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">Work permit no</label>
                                                <input type="text" name="work_permit_no" class="form-control"
                                                    placeholder="Enter work permit number">
                                            </div>

                                            <div class="clear"></div><br>


                                            <div class="col-md-6">
                                                <label class="control-label">Address: </label>
                                                <input type="text" name="address" class="form-control"
                                                    placeholder="Enter address">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">Date of Birth</label>
                                                <input type="date" name="date_of_birth" class="form-control">
                                            </div>

                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">Type of Contract: </label>
                                                <select name="type_of_contract" id="" class="form-control">
                                                    <option>--Select Contract Type--</option>
                                                    <option value="full_time">Full Time</option>
                                                    <option value="part_time">Part Time</option>
                                                    <option value="contract">contract</option>
                                                </select>
                                            </div>

                                            <div class="clear"></div><br>


                                            <div class="col-md-6">
                                                <label class="control-label">Contract no. </label>
                                                <input type="text" name="contract_no" class="form-control"
                                                    placeholder="Enter contract number">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">Date of Joining</label>
                                                <input type="date" name="date_joining" class="form-control">
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">Nationality</label>
                                                <input type="text" name="nationality" class="form-control"
                                                    placeholder="e.g. Pakistani / Indian">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">Passport No</label>
                                                <input type="text" name="passport_no" class="form-control"
                                                    placeholder="Enter passport number">
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">Passport Issue Date</label>
                                                <input type="date" name="passport_issue_date" class="form-control">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">Passport Expiry Date</label>
                                                <input type="date" name="passport_expiry_date" class="form-control">
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">TRC No</label>
                                                <input type="text" name="trc_no" class="form-control"
                                                    placeholder="Enter TRC number">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">CNP</label>
                                                <input type="text" name="cnp" class="form-control"
                                                    placeholder="Enter CNP">
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">TRC Expiry</label>
                                                <input type="date" name="trc_expiry" class="form-control">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">Driving License</label>
                                                <input type="text" name="driving_license" class="form-control"
                                                    placeholder="Enter license number">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">Driving License Type</label>
                                                <input type="text" name="driving_license_type" class="form-control"
                                                    placeholder="Enter license number">
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">License Issue Date</label>
                                                <input type="date" name="license_issue_date" class="form-control">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">License Expiry</label>
                                                <input type="date" name="license_expiry" class="form-control">
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">Gross Salary</label>
                                                <input type="number" name="gross_salary" class="form-control"
                                                    placeholder="e.g. 50000">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">Net Salary</label>
                                                <input type="number" name="net_salary" class="form-control"
                                                    placeholder="e.g. 45000">
                                            </div>
                                            <div class="clear"></div><br>

                                            <div class="col-md-6">
                                                <label class="control-label">IBAN</label>
                                                <input type="text" name="iban" class="form-control"
                                                    placeholder="Enter IBAN number">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="control-label">Bank Name</label>
                                                <input type="text" name="bank_name" class="form-control"
                                                    placeholder="e.g. HBL / Meezan Bank">
                                            </div>

                                            <div class="clear"></div><br>


                                            <div class="col-md-6">
                                                <label class="control-label">Status: </label>
                                                <select name="status" id="" class="form-control">
                                                    <option>--Select Status--</option>
                                                    <option value="1">Active</option>
                                                    <option value="0">Inactive</option>
                                                </select>
                                            </div>

                                        </div>

                                        <div class="clear"></div><br>

                                        <div class="col-md-12 text-center">
                                            <button type="submit" name="add_employee" class="btn btn-success">
                                                Submit Details
                                            </button>
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