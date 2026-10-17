<?php

include('../session.php');
include('../database.php');
include('../includes/ride_sharing_helper.php');

$obj = new Database();

$ids = $_POST['ids'] ?? [];
$status = (int) ($_POST['status'] ?? 0);
$idList = implode(',', array_map('intval', $ids));

if ($idList === '') {
    echo 'error';
    exit;
}

$obj->select(
    'ride_sharing_earnings',
    'id, employee_id, week_year, week_number',
    null,
    "id IN ($idList)",
    'week_year ASC, week_number ASC, id ASC'
);
$affected = $obj->getResult();

if ($status === 1) {
    $update = $obj->update('ride_sharing_earnings', ['status' => 1], "id IN ($idList) AND status != 1");
} else {
    if (!isSuperAdmin()) {
        echo 'denied';
        exit;
    }

    $update = $obj->update('ride_sharing_earnings', ['status' => 0], "id IN ($idList) AND status = 1");
}

if ($update && !empty($affected) && !empty($affected[0]['id'])) {
    $seen = [];
    foreach ($affected as $row) {
        $employeeId = (int) ($row['employee_id'] ?? 0);
        if ($employeeId <= 0 || isset($seen[$employeeId])) {
            continue;
        }
        $seen[$employeeId] = true;
        ride_cascade_following_weeks(
            $obj,
            $employeeId,
            (int) $row['week_year'],
            (int) $row['week_number']
        );
    }
}

echo $update ? 'success' : 'error';
