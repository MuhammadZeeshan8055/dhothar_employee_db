<?php

include('../session.php');
include('../database.php');
include('../includes/delivery_earnings_helper.php');

header('Content-Type: application/json');

$obj = new Database();
$employee_id = (int) ($_GET['employee_id'] ?? $_POST['employee_id'] ?? 0);
$week_year = (int) ($_GET['week_year'] ?? $_POST['week_year'] ?? 0);
$week_number = (int) ($_GET['week_number'] ?? $_POST['week_number'] ?? 0);

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee']);
    exit;
}

if ($week_year > 0 && $week_number > 0) {
    $result = get_rate_settings_for_week($obj, $employee_id, $week_year, $week_number);
} else {
    $obj->select('employee_rate_settings', '*', null, "employee_id = $employee_id AND week_year IS NULL", 'id DESC', 1);
    $rows = $obj->getResult();
    $result = !empty($rows[0]['id'])
        ? ['data' => $rows[0], 'source' => 'legacy', 'source_year' => null, 'source_week' => null]
        : null;
}

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'No rate settings found for this employee. Please set rates first.',
    ]);
    exit;
}

$data = $result['data'];
$message = 'Rate settings loaded.';

if ($result['source'] === 'carried') {
    $message = 'Using settings carried from Week ' . $result['source_week'] . ' (' . $result['source_year'] . ').';
} elseif ($result['source'] === 'legacy') {
    $message = 'Using default settings (saved before weekly tracking).';
} elseif ($result['source'] === 'exact') {
    $message = 'Week ' . $week_number . ' settings loaded.';
}

echo json_encode([
    'success' => true,
    'data' => $data,
    'source' => $result['source'],
    'source_year' => $result['source_year'],
    'source_week' => $result['source_week'],
    'message' => $message,
]);

?>
