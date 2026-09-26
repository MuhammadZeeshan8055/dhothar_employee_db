<?php

include('../session.php');
include('../database.php');

$obj = new Database();

$ids = $_POST['ids'] ?? [];
$status = (int) ($_POST['status'] ?? 0);
$idList = implode(',', array_map('intval', $ids));

if ($idList === '') {
    echo "error";
    exit;
}

if ($status === 1) {
    // Mark unpaid rows as paid
    $update = $obj->update('delivery_earnings', ['status' => 1], "id IN ($idList) AND status != 1");
} else {
    // Mark paid rows as unpaid — Super Admin only (unlock)
    if (!isSuperAdmin()) {
        echo "denied";
        exit;
    }

    $update = $obj->update('delivery_earnings', ['status' => 0], "id IN ($idList) AND status = 1");
}

echo $update ? "success" : "error";

?>
