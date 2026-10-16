<?php

include('../session.php');
include('../database.php');
include('../includes/delivery_earnings_helper.php');

$obj = new Database();

$ids = $_POST['ids'] ?? [];
$status = (int) ($_POST['status'] ?? 0);
$idList = implode(',', array_map('intval', $ids));

if ($idList === '') {
    echo "error";
    exit;
}

// Need employee/week before status flip so we can refresh later weeks' carry
$obj->select(
    'delivery_earnings',
    'id, employee_id, week_year, week_number',
    null,
    "id IN ($idList)",
    'week_year ASC, week_number ASC, id ASC'
);
$affected = $obj->getResult();

if ($status === 1) {
    // Mark unpaid rows as paid (locked — stop carrying into later weeks)
    $update = $obj->update('delivery_earnings', ['status' => 1], "id IN ($idList) AND status != 1");
} else {
    // Mark paid rows as unpaid — Super Admin only (unlock — resume carry)
    if (!isSuperAdmin()) {
        echo "denied";
        exit;
    }

    $update = $obj->update('delivery_earnings', ['status' => 0], "id IN ($idList) AND status = 1");
}

if ($update && !empty($affected) && !empty($affected[0]['id'])) {
    // Cascade once per employee from the earliest affected week
    $seen = [];
    foreach ($affected as $row) {
        $employeeId = (int) ($row['employee_id'] ?? 0);
        if ($employeeId <= 0 || isset($seen[$employeeId])) {
            continue;
        }
        $seen[$employeeId] = true;
        cascade_following_weeks(
            $obj,
            $employeeId,
            (int) $row['week_year'],
            (int) $row['week_number']
        );
    }
}

echo $update ? "success" : "error";

?>
