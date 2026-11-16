<?php
/**
 * Attendance Holidays — Add / Edit / Delete
 * Saturday + Sunday are always holidays in code (no need to add them here).
 */

include('session.php');
include('database.php');
include('includes/attendance_helper.php');

$obj = new Database();
$current_user = getCurrentUser();

// -----------------------------
// SAVE (Add or Update)
// -----------------------------
if (isset($_POST['save_holiday'])) {

    $id = (int) ($_POST['holiday_id'] ?? 0);
    $holiday_date = trim($_POST['holiday_date'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $now = date('Y-m-d H:i:s');
    $created_by = !empty($current_user['id']) ? (int) $current_user['id'] : 0;

    if ($holiday_date === '' || $title === '') {
        attendance_holidays_toast('error', 'Please fill date and title.');
    }

    // Block Sat/Sun — they are automatic holidays
    if (attendance_is_weekend($holiday_date)) {
        attendance_holidays_toast('error', 'Saturday and Sunday are already holidays. No need to add them.');
    }

    $safe_date = $obj->escape($holiday_date);
    $safe_title = $obj->escape($title);

    // Same date cannot be added twice
    $extra_where = $id > 0 ? ' AND id != ' . $id : '';
    $exists = (int) $obj->count(
        'attendance_holidays',
        '*',
        "holiday_date = '$safe_date'" . $extra_where
    );
    if ($exists > 0) {
        attendance_holidays_toast('error', 'This date is already added as a holiday.');
    }

    $data = array(
        'holiday_date' => $safe_date,
        'title' => $safe_title,
        'updated_at' => $now,
    );

    if ($id > 0) {
        $ok = $obj->update('attendance_holidays', $data, 'id = ' . $id);
        if ($ok) {
            attendance_holidays_toast('success', 'Holiday updated successfully.');
        }
        attendance_holidays_toast('error', 'Failed to update holiday.');
    }

    $data['created_by'] = $created_by;
    $data['created_at'] = $now;
    $ok = $obj->insert('attendance_holidays', $data);
    if ($ok) {
        attendance_holidays_toast('success', 'Holiday added successfully.');
    }
    attendance_holidays_toast('error', 'Failed to add holiday.');
}

// -----------------------------
// DELETE
// -----------------------------
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {
        $ok = $obj->delete('attendance_holidays', 'id = ' . $id);
        if ($ok) {
            attendance_holidays_toast('success', 'Holiday deleted successfully.');
        }
        attendance_holidays_toast('error', 'Failed to delete holiday.');
    }
}

// -----------------------------
// EDIT — load one row into form
// -----------------------------
$edit_holiday = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $obj->select('attendance_holidays', '*', null, 'id = ' . $edit_id);
    $rows = $obj->getResult();
    if (!empty($rows) && is_array($rows)) {
        $edit_holiday = $rows[0];
    }
}

// -----------------------------
// LIST all holidays (newest first)
// -----------------------------
$obj->select('attendance_holidays', '*', null, null, 'holiday_date DESC');
$holidays = $obj->getResult();
if (empty($holidays) || !is_array($holidays)) {
    $holidays = array();
}

// Form defaults
$form_id = $edit_holiday ? (int) $edit_holiday['id'] : 0;
$form_date = $edit_holiday ? $edit_holiday['holiday_date'] : '';
$form_title = $edit_holiday ? $edit_holiday['title'] : '';

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
    <title>Attendance Holidays | Dhothar International</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <script src="<?= $base_url ?>assets/js/jquery-1.11.3.min.js"></script>

    <style>
        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }
        .holiday-form-box {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            padding: 18px;
            margin-bottom: 24px;
            border-radius: 4px;
        }
        .sunday-note {
            background: #fff8e6;
            border: 1px solid #f0e0b2;
            padding: 10px 14px;
            margin-bottom: 18px;
            border-radius: 4px;
            color: #7a5b00;
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

        <div class="page-header-row">
            <h3>
                <i class="entypo-calendar"></i>
                Attendance Holidays
            </h3>
        </div>

        <div class="sunday-note">
            <strong>Note:</strong> Saturday and Sunday are always treated as holidays automatically.
            You only need to add extra holidays here (e.g. national holidays).
        </div>

        <!-- ========== FORM ========== -->
        <div class="holiday-form-box">
            <h4 style="margin-top:0;">
                <?= $form_id > 0 ? 'Edit Holiday' : 'Add New Holiday' ?>
            </h4>

            <form method="post" action="">
                <input type="hidden" name="holiday_id" value="<?= $form_id ?>">

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="holiday_date" class="form-control" required
                                   value="<?= htmlspecialchars($form_date) ?>">
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" required
                                   placeholder="e.g. Independence Day"
                                   value="<?= htmlspecialchars($form_title) ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" name="save_holiday" class="btn btn-blue btn-sm">
                    <?= $form_id > 0 ? 'Update Holiday' : 'Add Holiday' ?>
                </button>

                <?php if ($form_id > 0): ?>
                    <a href="attendance_holidays" class="btn btn-default btn-sm">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ========== LIST ========== -->
        <h4>All Holidays</h4>
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Title</th>
                <th style="width:140px;">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($holidays)): ?>
                <tr>
                    <td colspan="4" class="text-center">No holidays added yet.</td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($holidays as $holiday): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars(formatDate($holiday['holiday_date'])) ?></td>
                        <td><?= htmlspecialchars($holiday['title']) ?></td>
                        <td>
                            <a href="attendance_holidays?edit=<?= (int) $holiday['id'] ?>"
                               class="btn btn-default btn-xs">Edit</a>
                            <a href="attendance_holidays?delete=<?= (int) $holiday['id'] ?>"
                               class="btn btn-danger btn-xs"
                               onclick="return confirm('Delete this holiday?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <?php include('components/footer.php'); ?>
    </div>
</div>

<script src="<?= $base_url ?>assets/js/gsap/TweenMax.min.js"></script>
<script src="<?= $base_url ?>assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js"></script>
<script src="<?= $base_url ?>assets/js/bootstrap.js"></script>
<script src="<?= $base_url ?>assets/js/joinable.js"></script>
<script src="<?= $base_url ?>assets/js/resizeable.js"></script>
<script src="<?= $base_url ?>assets/js/neon-api.js"></script>
<script src="<?= $base_url ?>assets/js/cookies.min.js"></script>
<script src="<?= $base_url ?>assets/js/neon-custom.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<?php if (isset($_SESSION['toast'])): ?>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: '3000'
        };
        toastr["<?= $_SESSION['toast']['type'] ?>"]("<?= htmlspecialchars($_SESSION['toast']['message'], ENT_QUOTES) ?>");
    </script>
    <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

</body>
</html>
