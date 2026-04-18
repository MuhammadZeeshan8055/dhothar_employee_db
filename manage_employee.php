<?php

include('session.php');
include('database.php');

$obj = new Database();

$obj->select("add_employee_details", "*");
$employees = $obj->getResult();

if (isset($_GET['type']) && $_GET['type'] == 'delete') {

    $id = $_GET['id'];

    // Get employee first (for image delete)
    $obj->select("add_employee_details", "*", null, "id = $id");
    $emp = $obj->getResult();

    if (!empty($emp)) {

        $emp = $emp[0];

        // Delete profile image if exists
        if (!empty($emp['profile_pic'])) {
            $img_path = "uploads/" . $emp['profile_pic'];

            if (file_exists($img_path)) {
                unlink($img_path);
            }
        }

        // Delete record from DB
        $obj->delete("add_employee_details", "id=$id");

        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Employee deleted successfully!'
        ];

    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Employee not found!'
        ];
    }

    header("Location: manage_employee.php");
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

            <h3>Exporting Table Data</h3>
            <br />
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var $table4 = jQuery("#table-4");
                    $table4.DataTable({
                        dom: 'Bfrtip',
                        buttons: [
                            'copyHtml5',
                            'excelHtml5',
                            'csvHtml5',
                            'pdfHtml5'
                        ]
                    });




                });
            </script>
            <table class="table table-bordered datatable table-3" id="table-4">
                <thead>
                    <tr>
                        <th>S.no</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Passport No</th>
                        <th>Nationality</th>
                        <th>Gross Salary</th>
                        <th>Net Salary</th>
                        <th>Date Joining</th>
                        <th>Profile Pic</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sno = 1;
                    foreach ($employees as $emp) {
                        ?>
                        <tr>
                            <td>
                                <?php echo $sno++; ?>
                            </td>

                            <td>
                                <?php echo $emp['name']; ?>
                            </td>

                            <td>
                                <?php echo $emp['designation']; ?>
                            </td>

                            <td>
                                <?php echo $emp['passport_no']; ?>
                            </td>

                            <td>
                                <?php echo $emp['nationality']; ?>
                            </td>

                            <td>
                                <?php echo $emp['gross_salary']; ?>
                            </td>

                            <td>
                                <?php echo $emp['net_salary']; ?>
                            </td>

                            <td>
                                <?php echo formatDate($emp['date_joining']); ?>
                            </td>

                            <td>
                                <?php if (!empty($emp['profile_pic'])) { ?>
                                    <img src="<?= $base_url ?>uploads/<?php echo $emp['profile_pic']; ?>" width="50" height="50"
                                        style="border-radius:50%;">
                                <?php } else { ?>
                                    <img src="https://via.placeholder.com/50" width="50" height="50" style="border-radius:50%;">
                                <?php } ?>
                            </td>

                            <td>
                                <div style="display:flex; gap:10px;">
                                    <a href="employee_edit?id=<?php echo $emp['id']; ?>"
                                        class="btn btn-primary btn-sm edit-btn">
                                        Edit
                                    </a>
                                    <a href="manage_employee?id=<?php echo $emp['id']; ?>&type=delete"
                                        class="btn btn-danger btn-sm delete-btn">
                                        Delete
                                    </a>
                                </div>

                            </td>

                        </tr>
                    <?php } ?>
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
    <script>
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                if (!confirm("Are you sure you want to delete this employee?")) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>