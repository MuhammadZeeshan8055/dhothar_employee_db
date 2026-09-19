<?php

include('../session.php');
include('../database.php');

header('Content-Type: application/json');

$obj = new Database();
$employee_id = (int) ($_GET['employee_id'] ?? $_POST['employee_id'] ?? 0);

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee']);
    exit;
}

$obj->select("employee_rate_settings", "*", null, "employee_id = $employee_id");
$rates = $obj->getResult();

if (!empty($rates) && isset($rates[0]['id'])) {
    echo json_encode([
        'success' => true,
        'data' => $rates[0]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No rate settings found for this employee. Please set rates first.'
    ]);
}
