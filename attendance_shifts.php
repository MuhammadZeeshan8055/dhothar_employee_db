<?php
/**
 * Attendance Shifts — Add / Edit / Delete
 * Simple page: form on top, list below.
 */

include('session.php');
include('database.php');
include('includes/attendance_helper.php');

$obj = new Database();

// -----------------------------
// SAVE (Add or Update)
// -----------------------------
if (isset($_POST['save_shift'])) {

    $id = (int) ($_POST['shift_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $hours = (float) ($_POST['hours'] ?? 8);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $now = date('Y-m-d H:i:s');

    // Basic checks
    if ($name === '' || $start_time === '' || $end_time === '') {
        attendance_shifts_toast('error', 'Please fill name, start time and end time.');
    }

    if ($hours <= 0) {
        attendance_shifts_toast('error', 'Hours must be greater than 0.');
    }

    // HTML time is HH:MM — MySQL likes HH:MM:SS
    if (strlen($start_time) === 5) {
        $start_time .= ':00';
    }
    if (strlen($end_time) === 5) {
        $end_time .= ':00';
    }

    $data = array(
        'name' => $obj->escape($name),
        'start_time' => $obj->escape($start_time),
        'end_time' => $obj->escape($end_time),
        'hours' => number_format($hours, 2, '.', ''),
        'is_active' => $is_active,
        'updated_at' => $now,
    );

    if ($id > 0) {
        // Update existing shift
        $ok = $obj->update('attendance_shifts', $data, 'id = ' . $id);
        if ($ok) {
            attendance_shifts_toast('success', 'Shift updated successfully.');
        }
        attendance_shifts_toast('error', 'Failed to update shift.');
    }

    // Insert new shift
    $data['created_at'] = $now;
    $ok = $obj->insert('attendance_shifts', $data);
    if ($ok) {
        attendance_shifts_toast('success', 'Shift added successfully.');
    }
    attendance_shifts_toast('error', 'Failed to add shift.');
}

// -----------------------------
// DELETE
// -----------------------------
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {
        $ok = $obj->delete('attendance_shifts', 'id = ' . $id);
        if ($ok) {
            attendance_shifts_toast('success', 'Shift deleted successfully.');
        }
        attendance_shifts_toast('error', 'Failed to delete shift.');
    }
}

// -----------------------------
// EDIT — load one row into form
// -----------------------------
$edit_shift = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $obj->select('attendance_shifts', '*', null, 'id = ' . $edit_id);
    $rows = $obj->getResult();
    if (!empty($rows) && is_array($rows)) {
        $edit_shift = $rows[0];
    }
}

// -----------------------------
// LIST all shifts
// -----------------------------
$obj->select('attendance_shifts', '*', null, null, 'start_time ASC');
$shifts = $obj->getResult();
if (empty($shifts) || !is_array($shifts)) {
    $shifts = array();
}

// Form default values
$form_id = $edit_shift ? (int) $edit_shift['id'] : 0;
$form_name = $edit_shift ? $edit_shift['name'] : '';
$form_start = $edit_shift ? substr($edit_shift['start_time'], 0, 5) : '';
$form_end = $edit_shift ? substr($edit_shift['end_time'], 0, 5) : '';
$form_hours = $edit_shift ? (float) $edit_shift['hours'] : 8;
$form_active = $edit_shift ? (int) $edit_shift['is_active'] : 1;

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
    <title>Attendance Shifts | Dhothar International</title>

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
        .badge-active { background: #27ae60; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .badge-inactive { background: #e74c3c; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .shift-form-box { background: #f9f9f9; border: 1px solid #e5e5e5; padding: 18px; margin-bottom: 24px; border-radius: 4px; }
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
                <i class="entypo-clock"></i>
                Attendance Shifts
            </h3>
        </div>

        <!-- ========== FORM ========== -->
        <div class="shift-form-box">
            <h4 style="margin-top:0;">
                <?= $form_id > 0 ? 'Edit Shift' : 'Add New Shift' ?>
            </h4>

            <form method="post" action="">
                <input type="hidden" name="shift_id" value="<?= $form_id ?>">

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Shift Name</label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="e.g. 9-5"
                                   value="<?= htmlspecialchars($form_name) ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control" required
                                   value="<?= htmlspecialchars($form_start) ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control" required
                                   value="<?= htmlspecialchars($form_end) ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Hours</label>
                            <input type="number" name="hours" class="form-control" required
                                   step="0.5" min="0.5" value="<?= htmlspecialchars((string) $form_hours) ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Active</label><br>
                            <label style="font-weight:normal; margin-top:6px;">
                                <input type="checkbox" name="is_active" value="1"
                                    <?= $form_active ? 'checked' : '' ?>>
                                Yes
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" name="save_shift" class="btn btn-blue btn-sm">
                    <?= $form_id > 0 ? 'Update Shift' : 'Add Shift' ?>
                </button>

                <?php if ($form_id > 0): ?>
                    <a href="attendance_shifts" class="btn btn-default btn-sm">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ========== LIST ========== -->
        <h4>All Shifts</h4>
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Start</th>
                <th>End</th>
                <th>Hours</th>
                <th>Status</th>
                <th style="width:140px;">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($shifts)): ?>
                <tr>
                    <td colspan="7" class="text-center">No shifts yet. Add one above.</td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($shifts as $shift): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars($shift['name']) ?></td>
                        <td><?= htmlspecialchars(substr($shift['start_time'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars(substr($shift['end_time'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars((string) (float) $shift['hours']) ?></td>
                        <td>
                            <?php if ((int) $shift['is_active'] === 1): ?>
                                <span class="badge-active">Active</span>
                            <?php else: ?>
                                <span class="badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="attendance_shifts?edit=<?= (int) $shift['id'] ?>"
                               class="btn btn-default btn-xs">Edit</a>
                            <a href="attendance_shifts?delete=<?= (int) $shift['id'] ?>"
                               class="btn btn-danger btn-xs"
                               onclick="return confirm('Delete this shift?');">Delete</a>
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
