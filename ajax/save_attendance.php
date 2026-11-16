<?php
/**
 * Auto-save one attendance cell.
 * Expects POST: employee_id, att_date, status, shift_id
 * Returns JSON: { success, message, hours_label }
 */

include('../session.php');
include('../database.php');
include('../includes/attendance_helper.php');

header('Content-Type: application/json');

$obj = new Database();
$current_user = getCurrentUser();
$marked_by = !empty($current_user['id']) ? (int) $current_user['id'] : 0;

$employee_id = (int) ($_POST['employee_id'] ?? 0);
$att_date = trim($_POST['att_date'] ?? '');
$status = trim($_POST['status'] ?? '');
$shift_id = (int) ($_POST['shift_id'] ?? 0);

if ($employee_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $att_date)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid employee or date.', 'hours_label' => ''));
    exit;
}

// Holiday / weekend (Sat–Sun) cannot be marked
$holidays = attendance_get_holidays($obj, $att_date, $att_date);
$info = attendance_resolve_day($att_date, $holidays);
if ($info['locked']) {
    echo json_encode(array(
        'success' => false,
        'message' => 'This day is a holiday (' . $info['label'] . ').',
        'hours_label' => '',
    ));
    exit;
}

// Empty status = nothing to save
if ($status === '') {
    echo json_encode(array('success' => false, 'message' => 'Select a status first.', 'hours_label' => ''));
    exit;
}

$allowed = attendance_allowed_statuses();
if (!in_array($status, $allowed, true)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid status.', 'hours_label' => ''));
    exit;
}

if ($status === 'present' && $shift_id <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Select a shift for Present.', 'hours_label' => ''));
    exit;
}

$ok = attendance_save_record($obj, array(
    'employee_id' => $employee_id,
    'att_date' => $att_date,
    'status' => $status,
    'shift_id' => $shift_id,
    'marked_by' => $marked_by,
    'note' => '',
));

if (!$ok) {
    echo json_encode(array('success' => false, 'message' => 'Save failed.', 'hours_label' => ''));
    exit;
}

// Hours label only for Present + shift
$hours_label = '';
if ($status === 'present' && $shift_id > 0) {
    $shift = attendance_get_shift($obj, $shift_id);
    $hours_label = attendance_hours_label($shift);
}

echo json_encode(array(
    'success' => true,
    'message' => 'Saved',
    'hours_label' => $hours_label,
));
