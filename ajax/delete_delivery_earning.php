<?php

include('../session.php');
include('../database.php');
include('../includes/delivery_earnings_helper.php');

$obj = new Database();
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo "error";
    exit;
}

$obj->select(
    'delivery_earnings',
    'id, employee_id, week_year, week_number, status',
    null,
    "id = $id",
    null,
    1
);
$row = $obj->getResult();

if (empty($row) || empty($row[0]['id'])) {
    echo "error";
    exit;
}

if ((int) ($row[0]['status'] ?? 0) === 1 && !isSuperAdmin()) {
    echo "locked";
    exit;
}

$employeeId = (int) $row[0]['employee_id'];
$weekYear = (int) $row[0]['week_year'];
$weekNumber = (int) $row[0]['week_number'];

$delete = $obj->delete('delivery_earnings', "id=$id");

if ($delete) {
    cascade_following_weeks($obj, $employeeId, $weekYear, $weekNumber);
}

echo $delete ? "success" : "error";

?>
