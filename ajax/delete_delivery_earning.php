<?php

include('../session.php');
include('../database.php');

$obj = new Database();
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo "error";
    exit;
}

$delete = $obj->delete("delivery_earnings", "id=$id");

echo $delete ? "success" : "error";
