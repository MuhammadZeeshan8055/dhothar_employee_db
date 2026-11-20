<?php
/**
 * Attendance Report — month grid
 * Shows P / A / L / H for each day + totals and hours.
 */

include('session.php');
include('database.php');
include('includes/attendance_helper.php');

$obj = new Database();

// -----------------------------
// Month / year filter
// -----------------------------
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');

if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}
if ($month < 1 || $month > 12) {
    $month = (int) date('n');
}

$days_in_month = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
$from_date = sprintf('%04d-%02d-01', $year, $month);
$to_date = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);
$month_label = date('F Y', strtotime($from_date));

$holiday_map = attendance_get_holidays($obj, $from_date, $to_date);
$shifts = attendance_get_active_shifts($obj);
$shifts_map = attendance_shifts_map($shifts);

// Active employees
$obj->select('add_employee_details', 'id, name, designation', null, 'status = 1', 'name ASC');
$employees = $obj->getResult();
if (empty($employees) || !is_array($employees)) {
    $employees = array();
}

$employee_ids = array();
foreach ($employees as $emp) {
    $employee_ids[] = (int) $emp['id'];
}

$records = attendance_get_records($obj, $from_date, $to_date, $employee_ids);

// Build day list 1..N
$day_list = array();
for ($d = 1; $d <= $days_in_month; $d++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
    $info = attendance_resolve_day($date, $holiday_map);
    $day_list[] = array(
        'num' => $d,
        'date' => $date,
        'weekday' => date('D', strtotime($date)), // Mon, Tue...
        'locked' => !empty($info['locked']),
        'label' => $info['label'],
    );
}

/**
 * Decide what letter to show in a cell.
 * Priority: holiday → saved record → empty
 */
function report_cell_letter($date, $locked, $record)
{
    if ($locked) {
        return 'H';
    }
    if (empty($record) || empty($record['status'])) {
        return '';
    }
    if ($record['status'] === 'present') {
        return 'P';
    }
    if ($record['status'] === 'absent') {
        return 'A';
    }
    if ($record['status'] === 'leave') {
        return 'L';
    }
    if ($record['status'] === 'holiday') {
        return 'H';
    }
    return '';
}

?>
<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Attendance Report | Dhothar International</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <?php include('components/head_theme.php'); ?>
    <script src="<?= $base_url ?>assets/js/jquery-1.11.3.min.js"></script>

    <style>
        .main-content > hr { margin: 8px 0 10px; }

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
            border-radius: 8px;
            border-color: #cbd5e1;
        }
        .att-bar .btn {
            border-radius: 8px;
            padding: 5px 12px;
        }
        .att-bar .btn-blue {
            background: #0f766e;
            border-color: #0f766e;
        }
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-left: auto;
        }
        .legend span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 3px 9px;
        }
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-p { background: #2563eb; }
        .dot-a { background: #94a3b8; }
        .dot-l { background: #f43f5e; }
        .dot-h { background: #16a34a; }

        .att-scroll {
            max-height: calc(100vh - 220px);
            overflow: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .05);
        }
        .report-table {
            margin: 0 !important;
            font-size: 12px;
            border-color: #e2e8f0 !important;
            min-width: 100%;
        }
        .report-table > thead > tr > th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #f8fafc;
            padding: 8px 4px !important;
            text-align: center;
            white-space: nowrap;
            border-color: #e2e8f0 !important;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
        }
        .report-table > tbody > tr > td {
            padding: 6px 4px !important;
            text-align: center;
            vertical-align: middle !important;
            border-color: #eef2f7 !important;
        }
        .report-table > tbody > tr:hover > td {
            background: #f8fafc;
        }
        .sticky-col {
            position: sticky;
            left: 0;
            z-index: 4;
            background: #fff;
            min-width: 180px;
            text-align: left !important;
            box-shadow: 4px 0 10px rgba(15, 23, 42, .04);
            font-weight: 700;
            color: #0f172a;
        }
        /* Stronger selector so Bootstrap table padding does not cancel left space */
        .report-table > thead > tr > th.sticky-col,
        .report-table > tbody > tr > td.sticky-col {
            padding-left: 20px !important;
            padding-right: 14px !important;
        }
        thead .sticky-col {
            z-index: 6;
            background: #f8fafc;
        }
        .total-col {
            min-width: 48px;
            font-weight: 700;
            background: #f8fafc !important;
        }
        .day-num { display: block; }
        .day-w {
            display: block;
            font-weight: 500;
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
        }

        .cell {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 26px;
            border-radius: 7px;
            font-weight: 700;
            font-size: 11px;
        }
        /* Present = blue, Holiday = green (clearly different) */
        .cell-p { background: #dbeafe; color: #1d4ed8; }
        .cell-a { background: #e2e8f0; color: #475569; }
        .cell-l { background: #ffe4e6; color: #e11d48; }
        .cell-h { background: #dcfce7; color: #15803d; }
        .cell-empty { color: #cbd5e1; }

        .total-hours {
            color: #1d4ed8;
            background: #dbeafe;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 11px;
        }
        .emp-meta {
            display: block;
            font-weight: 500;
            color: #94a3b8;
            font-size: 11px;
        }
        .th-holiday { background: #ecfdf5 !important; color: #15803d !important; }
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

        <div class="att-bar">
            <h3>
                <i class="entypo-chart-bar"></i>
                Attendance Report
                <small style="color:#64748b;font-size:13px;font-weight:500;"> — <?= htmlspecialchars($month_label) ?></small>
            </h3>

            <div class="att-bar-row">
                <form method="get" action="" style="margin:0; display:inline-flex; align-items:center; gap:6px;">
                    <label for="month">Month</label>
                    <select name="month" id="month" class="form-control input-sm">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <label for="year">Year</label>
                    <select name="year" id="year" class="form-control input-sm">
                        <?php
                        $this_year = (int) date('Y');
                        for ($y = $this_year + 1; $y >= $this_year - 5; $y--):
                            ?>
                            <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>

                    <button type="submit" class="btn btn-blue btn-xs">Show</button>
                </form>

                <input type="text" id="emp-search" class="form-control input-sm"
                       placeholder="Search name..." style="min-width:150px;">

                <a href="attendance_mark?mode=week&date=<?= urlencode($from_date) ?>"
                   class="btn btn-default btn-xs">Mark Attendance</a>

                <div class="legend">
                    <span><i class="dot dot-p"></i> P Present</span>
                    <span><i class="dot dot-a"></i> A Absent</span>
                    <span><i class="dot dot-l"></i> L Leave</span>
                    <span><i class="dot dot-h"></i> H Holiday</span>
                </div>
            </div>
        </div>

        <?php if (empty($employees)): ?>
            <div class="alert alert-warning">No active employees found.</div>
        <?php else: ?>

            <div class="att-scroll">
                <table class="table table-bordered report-table" id="report-table">
                    <thead>
                    <tr>
                        <th class="sticky-col">Employee</th>
                        <?php foreach ($day_list as $day): ?>
                            <th class="<?= $day['locked'] ? 'th-holiday' : '' ?>"
                                title="<?= $day['locked'] ? htmlspecialchars($day['label']) : $day['date'] ?>">
                                <span class="day-num"><?= (int) $day['num'] ?></span>
                                <span class="day-w"><?= htmlspecialchars($day['weekday']) ?></span>
                            </th>
                        <?php endforeach; ?>
                        <th class="total-col" title="Present">P</th>
                        <th class="total-col" title="Absent">A</th>
                        <th class="total-col" title="Leave">L</th>
                        <th class="total-col" title="Holiday">H</th>
                        <th class="total-col" title="Total hours">Hours</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($employees as $emp):
                        $eid = (int) $emp['id'];
                        $count_p = 0;
                        $count_a = 0;
                        $count_l = 0;
                        $count_h = 0;
                        $total_hours = 0;

                        // First pass: counts (needed before printing totals)
                        $letters = array();
                        foreach ($day_list as $day) {
                            $rec = isset($records[$eid][$day['date']]) ? $records[$eid][$day['date']] : null;
                            $letter = report_cell_letter($day['date'], $day['locked'], $rec);
                            $letters[$day['date']] = $letter;

                            if ($letter === 'P') {
                                $count_p++;
                                $shift_id = $rec ? (int) $rec['shift_id'] : 0;
                                if ($shift_id > 0 && isset($shifts_map[$shift_id])) {
                                    $total_hours += (float) $shifts_map[$shift_id]['hours'];
                                }
                            } elseif ($letter === 'A') {
                                $count_a++;
                            } elseif ($letter === 'L') {
                                $count_l++;
                            } elseif ($letter === 'H') {
                                $count_h++;
                            }
                        }

                        // Nice hours number
                        if ($total_hours == (int) $total_hours) {
                            $hours_show = (int) $total_hours;
                        } else {
                            $hours_show = number_format($total_hours, 1);
                        }
                        ?>
                        <tr data-name="<?= htmlspecialchars(strtolower($emp['name'])) ?>">
                            <td class="sticky-col">
                                <?= htmlspecialchars($emp['name']) ?>
                                <?php if (!empty($emp['designation'])): ?>
                                    <span class="emp-meta"><?= htmlspecialchars($emp['designation']) ?></span>
                                <?php endif; ?>
                            </td>

                            <?php foreach ($day_list as $day):
                                $letter = $letters[$day['date']];
                                $cls = 'cell-empty';
                                if ($letter === 'P') {
                                    $cls = 'cell-p';
                                } elseif ($letter === 'A') {
                                    $cls = 'cell-a';
                                } elseif ($letter === 'L') {
                                    $cls = 'cell-l';
                                } elseif ($letter === 'H') {
                                    $cls = 'cell-h';
                                }
                                ?>
                                <td>
                                    <?php if ($letter !== ''): ?>
                                        <span class="cell <?= $cls ?>"><?= $letter ?></span>
                                    <?php else: ?>
                                        <span class="cell cell-empty">·</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>

                            <td class="total-col"><?= $count_p ?></td>
                            <td class="total-col"><?= $count_a ?></td>
                            <td class="total-col"><?= $count_l ?></td>
                            <td class="total-col"><?= $count_h ?></td>
                            <td class="total-col">
                                <?php if ($total_hours > 0): ?>
                                    <span class="total-hours"><?= htmlspecialchars((string) $hours_show) ?>h</span>
                                <?php else: ?>
                                    0
                                <?php endif; ?>
                            </td>
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

<script>
    $(document).ready(function () {
        $('#emp-search').on('keyup', function () {
            var q = $.trim($(this).val()).toLowerCase();
            $('#report-table tbody tr').each(function () {
                var name = String($(this).data('name') || '');
                $(this).toggle(!q || name.indexOf(q) !== -1);
            });
        });
    });
</script>

</body>
</html>
