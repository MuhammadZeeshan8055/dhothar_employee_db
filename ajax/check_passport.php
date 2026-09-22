<?php
/**
 * AJAX: check if passport_no already exists.
 * GET/POST: passport_no, exclude_id (optional, for edit)
 */

include('../session.php');
include('../database.php');

header('Content-Type: application/json');

$obj = new Database();
$passport_no = trim($_REQUEST['passport_no'] ?? '');
$exclude_id = (int) ($_REQUEST['exclude_id'] ?? 0);

if ($passport_no === '') {
    echo json_encode(['exists' => false, 'message' => '']);
    exit;
}

$safe = addslashes($passport_no);
$where = "passport_no = '$safe'";
if ($exclude_id > 0) {
    $where .= " AND id != $exclude_id";
}

$count = $obj->count('add_employee_details', '*', $where);
$exists = ((int) $count) > 0;

echo json_encode([
    'exists' => $exists,
    'message' => $exists ? 'Passport number already exists.' : '',
]);
