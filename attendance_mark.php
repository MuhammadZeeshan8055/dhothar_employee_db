<?php
/**
 * Mark Attendance — Day or Week
 * Auto-saves when you change Status / Shift.
 */

include('session.php');
include('database.php');
include('includes/attendance_helper.php');

$obj = new Database();

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'day';
if ($mode !== 'week') {
    $mode = 'day';
}

$selected_date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}

$week_dates = attendance_week_dates($selected_date);
$week_start = $week_dates[0];
$week_end = $week_dates[6];

if ($mode === 'week') {
    $from_date = $week_start;
    $to_date = $week_end;
} else {
    $from_date = $selected_date;
    $to_date = $selected_date;
}

$holiday_map = attendance_get_holidays($obj, $from_date, $to_date);
$shifts = attendance_get_active_shifts($obj);
$shifts_map = attendance_shifts_map($shifts);
$default_shift_id = !empty($shifts) ? (int) $shifts[0]['id'] : 0;

$shift_hours_js = array();
foreach ($shifts as $shift) {
    $shift_hours_js[(int) $shift['id']] = (float) $shift['hours'];
}

$obj->select('add_employee_details', 'id, name, designation, work_type', null, 'status = 1', 'name ASC');
$employees = $obj->getResult();
if (empty($employees) || !is_array($employees)) {
    $employees = array();
}

$employee_ids = array();
foreach ($employees as $emp) {
    $employee_ids[] = (int) $emp['id'];
}

$records = attendance_get_records($obj, $from_date, $to_date, $employee_ids);
$day_info = attendance_resolve_day($selected_date, $holiday_map);
$day_names = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
$can_mark = !empty($employees) && !empty($shifts) && !($mode === 'day' && !empty($day_info['locked']));

?>
<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Mark Attendance | Dhothar International</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="<?= $base_url ?>assets/js/jquery-1.11.3.min.js"></script>

    <style>
        .main-content > hr { margin: 8px 0 10px; }

        /* Top bar */
        .att-bar {
            background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 14px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }
        .att-bar h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #1e293b;
        }
        .att-bar-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 12px;
        }
        .att-bar label {
            margin: 0;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }
        .att-bar .form-control {
            height: 32px;
            padding: 4px 10px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
            border-radius: 8px;
            border-color: #cbd5e1;
            background: #fff;
        }
        .att-bar .btn {
            vertical-align: middle;
            border-radius: 8px;
            padding: 5px 12px;
        }
        .att-bar .btn-blue {
            background: #0f766e;
            border-color: #0f766e;
        }
        .att-bar .btn-success {
            background: #16a34a;
            border-color: #16a34a;
        }
        .att-sep {
            width: 1px;
            height: 22px;
            background: #e2e8f0;
        }
        .week-range {
            font-size: 12px;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 999px;
        }

        /* Scroll table */
        .att-scroll {
            max-height: calc(100vh - 210px);
            overflow: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .05);
        }
        .att-table {
            margin: 0 !important;
            font-size: 12px;
            border-color: #e2e8f0 !important;
        }
        .att-table > thead > tr > th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #f8fafc;
            padding: 10px 8px !important;
            white-space: nowrap;
            border-color: #e2e8f0 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            color: #475569;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
            font-size: 11px;
        }
        .att-table > tbody > tr > td {
            padding: 8px !important;
            vertical-align: middle !important;
            border-color: #eef2f7 !important;
        }
        .att-table > tbody > tr:hover > td {
            background: #f8fafc;
        }
        .att-table select {
            height: 30px;
            padding: 3px 6px;
            font-size: 12px;
            margin: 0;
            border-radius: 7px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            box-shadow: none;
        }
        .att-table select:focus {
            border-color: #0f766e;
            outline: none;
            box-shadow: 0 0 0 2px rgba(15, 118, 110, .15);
        }
        .day-table .status-select,
        .day-table .shift-select { width: 120px; }
        .day-table .emp-name {
            padding-left: 18px !important;
            padding-right: 12px !important;
        }
        .day-table thead th:nth-child(2) {
            padding-left: 18px !important;
        }

        .hours-text {
            display: inline-block;
            min-height: 18px;
            color: #1d4ed8;
            font-weight: 700;
            font-size: 11px;
            white-space: nowrap;
            background: #dbeafe;
            padding: 2px 8px;
            border-radius: 999px;
        }
        .hours-text:empty {
            display: none;
        }

        .emp-name {
            font-weight: 700;
            white-space: nowrap;
            color: #0f172a;
        }
        .emp-meta {
            color: #94a3b8;
            font-weight: 500;
            font-size: 11px;
        }

        .week-table .sticky-col {
            position: sticky;
            left: 0;
            z-index: 4;
            background: #fff;
            min-width: 170px;
            padding-left: 18px !important;
            padding-right: 12px !important;
            box-shadow: 4px 0 10px rgba(15, 23, 42, .04);
        }
        .week-table thead .sticky-col {
            z-index: 6;
            background: #f8fafc;
        }
        .week-table th { text-align: center; }
        .week-table .day-sub {
            font-weight: 500;
            color: #94a3b8;
            text-transform: none;
            letter-spacing: 0;
            margin-top: 2px;
            font-size: 11px;
        }
        .week-table .att-cell {
            min-width: 118px;
            transition: background .15s ease;
        }
        .week-table .cell-controls {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
            justify-content: center;
        }
        .week-table .status-select { width: 52px; }
        .week-table .shift-select { width: 58px; flex: 1; min-width: 54px; }
        .week-table .hours-text {
            width: 100%;
            text-align: center;
            margin-top: 2px;
        }

        /* Holiday = soft green */
        .holiday-cell {
            background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%) !important;
            text-align: center;
            vertical-align: middle !important;
            min-width: 72px;
        }
        .holiday-pill {
            display: inline-block;
            background: #16a34a;
            color: #fff;
            font-weight: 700;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 999px;
            letter-spacing: .03em;
        }

        /* Leave = soft red */
        .status-leave {
            background: #fff1f2 !important;
        }
        .status-leave select {
            border-color: #fda4af;
            background: #fff;
        }
        /* Present = soft blue (different from holiday green) */
        .status-present {
            background: #eff6ff !important;
        }
        .status-present select {
            border-color: #93c5fd;
        }
        /* Absent = soft slate */
        .status-absent {
            background: #f8fafc !important;
        }
        .status-absent select {
            border-color: #cbd5e1;
        }

        .day-table .att-row.status-leave > td,
        .day-table .att-row.status-present > td,
        .day-table .att-row.status-absent > td {
            background: inherit;
        }
        .day-table tr.status-leave > td { background: #fff1f2 !important; }
        .day-table tr.status-present > td { background: #eff6ff !important; }
        .day-table tr.status-absent > td { background: #f8fafc !important; }

        .locked-note {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 12px 14px;
            border-radius: 10px;
            color: #166534;
        }
        .saving-row {
            box-shadow: inset 0 0 0 2px #86efac;
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

        <!-- Simple top controls (one box only) -->
        <div class="att-bar">
            <h3><i class="entypo-check"></i> Mark Attendance</h3>

            <div class="att-bar-row">
                <!-- Day / Week -->
                <div>
                    <a href="attendance_mark?mode=day&date=<?= urlencode($selected_date) ?>"
                       class="btn btn-xs <?= $mode === 'day' ? 'btn-blue' : 'btn-default' ?>">Day</a>
                    <a href="attendance_mark?mode=week&date=<?= urlencode($selected_date) ?>"
                       class="btn btn-xs <?= $mode === 'week' ? 'btn-blue' : 'btn-default' ?>">Week</a>
                </div>

                <span class="att-sep"></span>

                <!-- Date -->
                <form method="get" action="" style="margin:0; display:inline-flex; align-items:center; gap:6px;">
                    <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control input-sm"
                           value="<?= htmlspecialchars($selected_date) ?>">
                    <button type="submit" class="btn btn-default btn-xs">Go</button>
                </form>

                <?php if ($mode === 'week'): ?>
                    <span class="week-range"><?= formatDate($week_start) ?> – <?= formatDate($week_end) ?></span>
                <?php endif; ?>

                <?php if ($can_mark): ?>
                    <span class="att-sep"></span>

                    <input type="text" id="emp-search" class="form-control input-sm"
                           placeholder="Search name..." style="min-width:150px;">

                    <?php if ($mode === 'week'): ?>
                        <label for="default_shift_id">Shift</label>
                        <select id="default_shift_id" class="form-control input-sm">
                            <?php foreach ($shifts as $shift): ?>
                                <option value="<?= (int) $shift['id'] ?>"
                                    <?= $default_shift_id === (int) $shift['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($shift['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <!-- <button type="button" class="btn btn-success btn-xs"
                            id="<?= $mode === 'week' ? 'btn-mark-all-present-week' : 'btn-mark-all-present-day' ?>">
                        Mark all Present
                    </button> -->
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($employees)): ?>
            <div class="alert alert-warning">No active employees found.</div>

        <?php elseif (empty($shifts)): ?>
            <div class="alert alert-warning">
                No active shifts. Add one in <a href="attendance_shifts">Attendance Shifts</a>.
            </div>

        <?php elseif ($mode === 'day' && $day_info['locked']): ?>
            <div class="locked-note">
                <?= htmlspecialchars(formatDate($selected_date)) ?> is a holiday
                (<?= htmlspecialchars($day_info['label']) ?>). Cannot mark attendance.
            </div>

        <?php elseif ($mode === 'day'): ?>
            <div class="att-scroll">
                <table class="table table-bordered table-hover att-table day-table" id="day-table">
                    <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Employee</th>
                        <th style="width:120px;">Status</th>
                        <th style="width:120px;">Shift</th>
                        <th style="width:130px;">Hours</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $sno = 1;
                    foreach ($employees as $emp):
                        $eid = (int) $emp['id'];
                        $row = isset($records[$eid][$selected_date]) ? $records[$eid][$selected_date] : null;
                        $cur_status = $row ? $row['status'] : '';
                        $cur_shift = $row ? (int) $row['shift_id'] : 0;
                        if ($cur_status === 'holiday') {
                            $cur_status = '';
                        }
                        $hours_text = '';
                        if ($cur_status === 'present' && $cur_shift > 0 && isset($shifts_map[$cur_shift])) {
                            $hours_text = attendance_hours_label($shifts_map[$cur_shift]);
                        }
                        ?>
                        <tr class="att-row<?= $cur_status ? ' status-' . htmlspecialchars($cur_status) : '' ?>"
                            data-employee-id="<?= $eid ?>"
                            data-date="<?= htmlspecialchars($selected_date) ?>"
                            data-name="<?= htmlspecialchars(strtolower($emp['name'])) ?>">
                            <td><?= $sno++ ?></td>
                            <td class="emp-name">
                                <?= htmlspecialchars($emp['name']) ?>
                                <?php if (!empty($emp['designation'])): ?>
                                    <span class="emp-meta"> · <?= htmlspecialchars($emp['designation']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="form-control input-sm status-select">
                                    <option value="">--</option>
                                    <option value="present" <?= $cur_status === 'present' ? 'selected' : '' ?>>Present</option>
                                    <option value="absent" <?= $cur_status === 'absent' ? 'selected' : '' ?>>Absent</option>
                                    <option value="leave" <?= $cur_status === 'leave' ? 'selected' : '' ?>>Leave</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-control input-sm shift-select" data-default="<?= $default_shift_id ?>">
                                    <option value="0">--</option>
                                    <?php foreach ($shifts as $shift): ?>
                                        <option value="<?= (int) $shift['id'] ?>"
                                            <?= $cur_shift === (int) $shift['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($shift['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><span class="hours-text"><?= htmlspecialchars($hours_text) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="att-scroll">
                <table class="table table-bordered week-table att-table" id="week-table">
                    <thead>
                    <tr>
                        <th class="sticky-col">Employee</th>
                        <?php foreach ($week_dates as $i => $d):
                            $info = attendance_resolve_day($d, $holiday_map);
                            ?>
                            <th>
                                <?= $day_names[$i] ?>
                                <div class="day-sub"><?= date('d M', strtotime($d)) ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($employees as $emp):
                        $eid = (int) $emp['id'];
                        ?>
                        <tr data-name="<?= htmlspecialchars(strtolower($emp['name'])) ?>">
                            <td class="emp-name sticky-col"><?= htmlspecialchars($emp['name']) ?></td>
                            <?php foreach ($week_dates as $d):
                                $info = attendance_resolve_day($d, $holiday_map);
                                if ($info['locked']):
                                    ?>
                                    <td class="holiday-cell" title="<?= htmlspecialchars($info['label']) ?>">
                                        <span class="holiday-pill">Holiday</span>
                                    </td>
                                <?php else:
                                    $row = isset($records[$eid][$d]) ? $records[$eid][$d] : null;
                                    $cur_status = $row ? $row['status'] : '';
                                    $cur_shift = $row ? (int) $row['shift_id'] : 0;
                                    if ($cur_status === 'holiday') {
                                        $cur_status = '';
                                    }
                                    $hours_text = '';
                                    if ($cur_status === 'present' && $cur_shift > 0 && isset($shifts_map[$cur_shift])) {
                                        $h = (float) $shifts_map[$cur_shift]['hours'];
                                        if ($h == (int) $h) {
                                            $h = (int) $h;
                                        }
                                        $hours_text = $h . 'h';
                                    }
                                    ?>
                                    <td class="att-cell<?= $cur_status ? ' status-' . htmlspecialchars($cur_status) : '' ?>"
                                        data-employee-id="<?= $eid ?>"
                                        data-date="<?= htmlspecialchars($d) ?>">
                                        <div class="cell-controls">
                                            <select class="form-control input-sm status-select">
                                                <option value="">--</option>
                                                <option value="present" <?= $cur_status === 'present' ? 'selected' : '' ?>>P</option>
                                                <option value="absent" <?= $cur_status === 'absent' ? 'selected' : '' ?>>A</option>
                                                <option value="leave" <?= $cur_status === 'leave' ? 'selected' : '' ?>>L</option>
                                            </select>
                                            <select class="form-control input-sm shift-select">
                                                <option value="0">Shift</option>
                                                <?php foreach ($shifts as $shift): ?>
                                                    <option value="<?= (int) $shift['id'] ?>"
                                                        <?= $cur_shift === (int) $shift['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($shift['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="hours-text"><?= htmlspecialchars($hours_text) ?></span>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

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

<script>
    var shiftHours = <?= json_encode($shift_hours_js) ?>;
    var defaultShiftId = <?= (int) $default_shift_id ?>;
    var saveUrl = '<?= $base_url ?>ajax/save_attendance';
    var isWeekMode = <?= $mode === 'week' ? 'true' : 'false' ?>;

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: '1800'
    };

    function hoursLabel(shiftId, shortLabel) {
        if (!shiftId || shiftId === '0' || shiftHours[shiftId] === undefined) {
            return '';
        }
        var h = shiftHours[shiftId];
        if (h === parseInt(h, 10)) {
            h = parseInt(h, 10);
        }
        return shortLabel ? (h + 'h') : (h + ' hours completed');
    }

    function updateHoursText($box) {
        var status = $box.find('.status-select').val();
        var shiftId = $box.find('.shift-select').val();
        if (status === 'present' && shiftId && shiftId !== '0') {
            $box.find('.hours-text').text(hoursLabel(shiftId, isWeekMode));
        } else {
            $box.find('.hours-text').text('');
        }
    }

    // Light colors: leave = red, present/absent helpers
    function updateStatusColor($box) {
        $box.removeClass('status-present status-absent status-leave');
        var status = $box.find('.status-select').val();
        if (status === 'present' || status === 'absent' || status === 'leave') {
            $box.addClass('status-' + status);
        }
    }

    function ensureShiftForPresent($box) {
        var status = $box.find('.status-select').val();
        var $shift = $box.find('.shift-select');
        if (status === 'present' && (!$shift.val() || $shift.val() === '0')) {
            var fallback = $shift.data('default') || $('#default_shift_id').val() || defaultShiftId;
            if (fallback) {
                $shift.val(String(fallback));
            }
        }
        if (status !== 'present') {
            $box.find('.hours-text').text('');
        }
    }

    function autoSave($box, showToast) {
        var employeeId = $box.data('employee-id');
        var attDate = $box.data('date');
        var status = $box.find('.status-select').val();
        var shiftId = $box.find('.shift-select').val() || 0;

        if (!employeeId || !attDate) {
            return;
        }
        if (!status) {
            $box.find('.hours-text').text('');
            return;
        }

        ensureShiftForPresent($box);
        shiftId = $box.find('.shift-select').val() || 0;
        updateHoursText($box);
        updateStatusColor($box);

        if (status === 'present' && (!shiftId || shiftId === '0')) {
            if (showToast !== false) {
                toastr.warning('Select a shift for Present.');
            }
            return;
        }

        $box.addClass('saving-row');
        $.ajax({
            url: saveUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                employee_id: employeeId,
                att_date: attDate,
                status: status,
                shift_id: shiftId
            },
            success: function (res) {
                $box.removeClass('saving-row');
                if (res && res.success) {
                    if (status === 'present') {
                        $box.find('.hours-text').text(hoursLabel(shiftId, isWeekMode));
                    } else {
                        $box.find('.hours-text').text('');
                    }
                    if (showToast) {
                        toastr.success('Saved');
                    }
                } else {
                    toastr.error((res && res.message) ? res.message : 'Save failed');
                }
            },
            error: function () {
                $box.removeClass('saving-row');
                toastr.error('Save failed. Please try again.');
            }
        });
    }

    $(document).ready(function () {
        $('#emp-search').on('keyup', function () {
            var q = $.trim($(this).val()).toLowerCase();
            $('#day-table tbody tr, #week-table tbody tr').each(function () {
                var name = String($(this).data('name') || '');
                $(this).toggle(!q || name.indexOf(q) !== -1);
            });
        });

        $('#day-table').on('change', '.status-select, .shift-select', function () {
            autoSave($(this).closest('.att-row'), true);
        });

        $('#week-table').on('change', '.status-select, .shift-select', function () {
            autoSave($(this).closest('.att-cell'), true);
        });

        $('#btn-mark-all-present-day').on('click', function () {
            var count = 0;
            $('#day-table .att-row:visible').each(function () {
                var $row = $(this);
                $row.find('.status-select').val('present');
                ensureShiftForPresent($row);
                updateHoursText($row);
                updateStatusColor($row);
                autoSave($row, false);
                count++;
            });
            if (count > 0) {
                toastr.success('Marked ' + count + ' Present');
            }
        });

        $('#btn-mark-all-present-week').on('click', function () {
            var count = 0;
            $('#week-table tbody tr:visible .att-cell').each(function () {
                var $cell = $(this);
                $cell.find('.status-select').val('present');
                ensureShiftForPresent($cell);
                updateHoursText($cell);
                updateStatusColor($cell);
                autoSave($cell, false);
                count++;
            });
            if (count > 0) {
                toastr.success('Marked Present (auto-saving)');
            }
        });
    });
</script>

</body>
</html>
