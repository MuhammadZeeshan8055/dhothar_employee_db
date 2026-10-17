<?php
/**
 * AJAX: load week entry for form (edit mode) + previous-week carry.
 */

include('../session.php');
include('../database.php');
include('../includes/ride_sharing_helper.php');

header('Content-Type: application/json');

$obj = new Database();
$employee_id = (int) ($_GET['employee_id'] ?? 0);
$week_year = (int) ($_GET['week_year'] ?? 0);
$week_number = (int) ($_GET['week_number'] ?? 0);

if ($employee_id <= 0 || $week_year <= 0 || $week_number <= 0) {
    echo json_encode([
        'success' => true,
        'exists' => false,
        'data' => null,
        'prev_carry' => 0,
        'message' => '',
    ]);
    exit;
}

$carry = ride_get_prev_carry($obj, $employee_id, $week_year, $week_number);
$existing = ride_find_week_earning($obj, $employee_id, $week_year, $week_number);

$message = $carry['note'];
if ($existing) {
    $editMsg = "Editing existing Week {$week_number} ({$week_year}) entry. Submit to update.";
    $message = $message !== '' ? $editMsg . ' ' . $message : $editMsg;
}

echo json_encode([
    'success' => true,
    'exists' => (bool) $existing,
    'data' => $existing,
    'prev_carry' => $carry['prev_carry'],
    'message' => $message,
]);
