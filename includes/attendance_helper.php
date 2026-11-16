<?php
/**
 * Attendance helpers — simple functions only.
 *
 * Status values: present | absent | leave | holiday
 * Sundays are always holidays (no DB row needed).
 */

/**
 * Check if a date is Sunday.
 * $date format: Y-m-d
 */
function attendance_is_sunday($date)
{
    // N = 1 (Mon) ... 7 (Sun)
    return (int) date('N', strtotime($date)) === 7;
}

/**
 * Get Monday date for the week that contains $date.
 * Week = Monday to Sunday.
 */
function attendance_week_monday($date)
{
    $time = strtotime($date);
    $day_number = (int) date('N', $time); // 1=Mon ... 7=Sun
    $monday = strtotime('-' . ($day_number - 1) . ' days', $time);
    return date('Y-m-d', $monday);
}

/**
 * Get 7 dates (Mon..Sun) for the week of $date.
 * Returns array of Y-m-d strings.
 */
function attendance_week_dates($date)
{
    $monday = attendance_week_monday($date);
    $dates = array();

    for ($i = 0; $i < 7; $i++) {
        $dates[] = date('Y-m-d', strtotime($monday . ' +' . $i . ' days'));
    }

    return $dates;
}

/**
 * Load holidays between two dates into an easy map:
 *   [ '2026-03-23' => 'Pakistan Day', ... ]
 */
function attendance_get_holidays($db, $from_date, $to_date)
{
    $from = $db->escape($from_date);
    $to = $db->escape($to_date);

    $db->select(
        'attendance_holidays',
        '*',
        null,
        "holiday_date >= '$from' AND holiday_date <= '$to'",
        'holiday_date ASC'
    );
    $rows = $db->getResult();

    $map = array();
    if (!empty($rows) && is_array($rows)) {
        foreach ($rows as $row) {
            $map[$row['holiday_date']] = $row['title'];
        }
    }

    return $map;
}

/**
 * Decide what a day should show before staff marks it.
 * Returns: array with keys status, label, locked
 *
 * locked = true means staff cannot change it (Sunday / holiday).
 */
function attendance_resolve_day($date, $holiday_map)
{
    // 1) Sunday
    if (attendance_is_sunday($date)) {
        return array(
            'status' => 'holiday',
            'label' => 'Sunday',
            'locked' => true,
        );
    }

    // 2) Holiday from table
    if (!empty($holiday_map[$date])) {
        return array(
            'status' => 'holiday',
            'label' => $holiday_map[$date],
            'locked' => true,
        );
    }

    // 3) Normal working day — staff can mark
    return array(
        'status' => '',
        'label' => '',
        'locked' => false,
    );
}

/**
 * Get all active shifts.
 */
function attendance_get_active_shifts($db)
{
    $db->select('attendance_shifts', '*', null, 'is_active = 1', 'start_time ASC');
    $rows = $db->getResult();

    if (empty($rows) || !is_array($rows)) {
        return array();
    }

    return $rows;
}

/**
 * Get one shift by id.
 */
function attendance_get_shift($db, $shift_id)
{
    $id = (int) $shift_id;
    $db->select('attendance_shifts', '*', null, "id = $id");
    $rows = $db->getResult();

    if (!empty($rows) && is_array($rows)) {
        return $rows[0];
    }

    return null;
}

/**
 * Hours text for present + shift.
 * Example: "8 hours completed"
 */
function attendance_hours_label($shift)
{
    if (empty($shift) || !isset($shift['hours'])) {
        return '';
    }

    $hours = (float) $shift['hours'];
    // Show 8 instead of 8.00 when whole number
    if ($hours == (int) $hours) {
        $hours = (int) $hours;
    }

    return $hours . ' hours completed';
}

/**
 * Load attendance rows for many employees between two dates.
 * Returns map: [ employee_id ][ att_date ] => row
 */
function attendance_get_records($db, $from_date, $to_date, $employee_ids = array())
{
    $from = $db->escape($from_date);
    $to = $db->escape($to_date);

    $where = "att_date >= '$from' AND att_date <= '$to'";

    if (!empty($employee_ids) && is_array($employee_ids)) {
        $clean_ids = array();
        foreach ($employee_ids as $eid) {
            $clean_ids[] = (int) $eid;
        }
        $where .= ' AND employee_id IN (' . implode(',', $clean_ids) . ')';
    }

    $db->select('attendance_records', '*', null, $where, 'att_date ASC');
    $rows = $db->getResult();

    $map = array();
    if (!empty($rows) && is_array($rows)) {
        foreach ($rows as $row) {
            $eid = (int) $row['employee_id'];
            $d = $row['att_date'];
            $map[$eid][$d] = $row;
        }
    }

    return $map;
}

/**
 * Save one attendance row (insert or update).
 * Returns true/false.
 *
 * $data keys: employee_id, att_date, status, shift_id, marked_by, note
 */
function attendance_save_record($db, $data)
{
    $employee_id = (int) $data['employee_id'];
    $att_date = $db->escape($data['att_date']);
    $status = $db->escape($data['status']);
    // Use 0 when no shift / no user (Database class quotes all values)
    $shift_id = !empty($data['shift_id']) ? (int) $data['shift_id'] : 0;
    $marked_by = !empty($data['marked_by']) ? (int) $data['marked_by'] : 0;
    $note = isset($data['note']) ? $db->escape($data['note']) : '';
    $now = date('Y-m-d H:i:s');

    // Only "present" keeps a shift
    if ($status !== 'present') {
        $shift_id = 0;
    }

    // Check if row already exists
    $db->select(
        'attendance_records',
        'id',
        null,
        "employee_id = $employee_id AND att_date = '$att_date'"
    );
    $existing = $db->getResult();

    if (!empty($existing) && is_array($existing)) {
        $id = (int) $existing[0]['id'];
        $update = array(
            'status' => $status,
            'shift_id' => $shift_id,
            'marked_by' => $marked_by,
            'note' => $note,
            'updated_at' => $now,
        );
        return $db->update('attendance_records', $update, "id = $id");
    }

    $insert = array(
        'employee_id' => $employee_id,
        'att_date' => $att_date,
        'status' => $status,
        'shift_id' => $shift_id,
        'marked_by' => $marked_by,
        'note' => $note,
        'created_at' => $now,
        'updated_at' => $now,
    );

    return $db->insert('attendance_records', $insert);
}

/**
 * Show toast message and reload attendance_shifts page.
 */
function attendance_shifts_toast($type, $message)
{
    $_SESSION['toast'] = array(
        'type' => $type,
        'message' => $message,
    );
    header('Location: attendance_shifts');
    exit;
}

/**
 * Show toast message and reload attendance_holidays page.
 */
function attendance_holidays_toast($type, $message)
{
    $_SESSION['toast'] = array(
        'type' => $type,
        'message' => $message,
    );
    header('Location: attendance_holidays');
    exit;
}

/**
 * Show toast and reload mark page (keep mode + date).
 */
function attendance_mark_toast($type, $message, $mode, $date)
{
    $_SESSION['toast'] = array(
        'type' => $type,
        'message' => $message,
    );
    $mode = ($mode === 'week') ? 'week' : 'day';
    $date = urlencode($date);
    header('Location: attendance_mark?mode=' . $mode . '&date=' . $date);
    exit;
}

/**
 * Allowed attendance statuses staff can pick.
 */
function attendance_allowed_statuses()
{
    return array('present', 'absent', 'leave');
}

/**
 * Shifts as id => row map (easy lookup).
 */
function attendance_shifts_map($shifts)
{
    $map = array();
    if (!empty($shifts) && is_array($shifts)) {
        foreach ($shifts as $shift) {
            $map[(int) $shift['id']] = $shift;
        }
    }
    return $map;
}

/**
 * Nice label for status badges.
 */
function attendance_status_label($status)
{
    if ($status === 'present') {
        return 'Present';
    }
    if ($status === 'absent') {
        return 'Absent';
    }
    if ($status === 'leave') {
        return 'Leave';
    }
    if ($status === 'holiday') {
        return 'Holiday';
    }
    return '';
}
