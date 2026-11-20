<?php

include('../session.php');
include('../database.php');
include('../includes/ride_sharing_helper.php');

$obj = new Database();
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo 'error';
    exit;
}

$obj->select(
    'ride_sharing_earnings',
    'id, employee_id, week_year, week_number, status',
    null,
    "id = $id",
    null,
    1
);
$row = $obj->getResult();

if (empty($row) || empty($row[0]['id'])) {
    echo 'error';
    exit;
}

// Locked if this week or any later week is paid (Super Admin can still delete)
if (is_week_paid_locked(
    $obj,
    'ride_sharing_earnings',
    (int) $row[0]['employee_id'],
    (int) $row[0]['week_year'],
    (int) $row[0]['week_number']
)) {
    echo 'locked';
    exit;
}

$employeeId = (int) $row[0]['employee_id'];
$weekYear = (int) $row[0]['week_year'];
$weekNumber = (int) $row[0]['week_number'];

$delete = $obj->delete('ride_sharing_earnings', "id=$id");

if ($delete) {
    ride_cascade_following_weeks($obj, $employeeId, $weekYear, $weekNumber);
}

echo $delete ? 'success' : 'error';
