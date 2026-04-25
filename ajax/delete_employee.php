<?php

include('../session.php');
include('../database.php');

$obj = new Database();

$id = $_POST['id'];

$obj->select("add_employee_details", "*", null, "id = $id");
$emp = $obj->getResult();

if (!empty($emp)) {

    $emp = $emp[0];

    if (!empty($emp['profile_pic'])) {
        $img_path = "../uploads/" . $emp['profile_pic'];

        if (file_exists($img_path)) {
            unlink($img_path);
        }
    }

    $delete = $obj->delete("add_employee_details", "id=$id");

    if ($delete) {
        echo "success";
    } else {
        echo "error";
    }

} else {
    echo "error";
}