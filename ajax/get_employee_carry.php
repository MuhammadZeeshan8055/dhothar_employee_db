<?php
/**
 * AJAX: previous-week negative carry only.
 * Prefer ajax/get_week_earning which also loads edit data.
 */

include('../session.php');
include('../database.php');
include('../includes/delivery_earnings_helper.php');

header('Content-Type: application/json');

$obj = new Database();
$employee_id = (int) ($_GET['employee_id'] ?? 0);
$week_year = (int) ($_GET['week_year'] ?? 0);
$week_number = (int) ($_GET['week_number'] ?? 0);

if ($employee_id <= 0 || $week_year <= 0 || $week_number <= 0) {
    echo json_encode([
        'success' => true,
        'prev_carry' => 0,
        'message' => '',
        'from_week' => null,
        'from_year' => null,
    ]);
    exit;
}

$carry = get_prev_carry($obj, $employee_id, $week_year, $week_number);

echo json_encode([
    'success' => true,
    'prev_carry' => $carry['prev_carry'],
    'message' => $carry['note'],
    'from_week' => $carry['from_week'],
    'from_year' => $carry['from_year'],
]);
