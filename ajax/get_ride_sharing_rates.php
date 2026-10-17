<?php

include('../session.php');
include('../database.php');
include('../includes/ride_sharing_helper.php');

header('Content-Type: application/json');

$obj = new Database();
$employee_id = (int) ($_GET['employee_id'] ?? $_POST['employee_id'] ?? 0);
$week_year = (int) ($_GET['week_year'] ?? $_POST['week_year'] ?? 0);
$week_number = (int) ($_GET['week_number'] ?? $_POST['week_number'] ?? 0);

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee']);
    exit;
}

if ($week_year <= 0 || $week_number <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Please select a valid year and week.',
    ]);
    exit;
}

$result = ride_get_rate_settings_for_week($obj, $employee_id, $week_year, $week_number);

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
