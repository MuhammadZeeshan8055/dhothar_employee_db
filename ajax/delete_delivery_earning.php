<?php

include('../session.php');
include('../database.php');

$obj = new Database();
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo "error";
    exit;
}

$obj->select("delivery_earnings", "status", null, "id = $id", null, 1);
$row = $obj->getResult();

if (!empty($row) && (int) $row[0]['status'] === 1) {
    echo "locked";
    exit;
}

$delete = $obj->delete("delivery_earnings", "id=$id");

echo $delete ? "success" : "error";
