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

$update = $obj->update('delivery_earnings', ['status' => $status], "id IN ($idList) AND status != 1");

echo $update ? "success" : "error";

?>
