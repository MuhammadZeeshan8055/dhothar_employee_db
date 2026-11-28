<?php

include('../session.php');
include('../database.php');
include('../includes/ride_sharing_helper.php');
include('../includes/digital_sign_helper.php');

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
    'id, employee_id, week_year, week_number, signature_image',
    null,
    "id IN ($idList)",
    'week_year ASC, week_number ASC, id ASC'
);
$affected = $obj->getResult();

if ($status === 1) {
    // Mark as Paid
    $update = $obj->update('ride_sharing_earnings', ['status' => 1], "id IN ($idList) AND status != 1");
} else {
    // Mark as Unpaid — Super Admin only
    if (!isSuperAdmin()) {
        echo 'denied';
        exit;
    }

    // Remove signature files + clear columns
    digital_sign_delete_files($affected);

    $update = $obj->update(
        'ride_sharing_earnings',
        [
            'status' => 0,
            'signature_image' => '',
            'signed_at' => '',
            'signed_by' => 0,
        ],
        "id IN ($idList) AND status = 1"
    );
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
