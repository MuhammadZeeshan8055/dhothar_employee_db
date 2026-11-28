<?php
/**
 * Save a digital signature image.
 * Steps:
 * 1. Check type / id / signature data
 * 2. Load the earning row
 * 3. Save PNG into uploads/signatures/
 * 4. Update the row (signature + mark Paid)
 */

include('../session.php');
include('../database.php');
include('../includes/digital_sign_helper.php');
include('../includes/delivery_earnings_helper.php');
include('../includes/ride_sharing_helper.php');

header('Content-Type: application/json');

// Helper: send error JSON and stop
function sign_fail($message)
{
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
    exit;
}

$obj = new Database();

// ---- Step 1: read form data ----
$type = trim($_POST['type'] ?? '');
$id = (int) ($_POST['id'] ?? 0);
$signature = trim($_POST['signature'] ?? '');

$table = digital_sign_table($type);

if ($table === '') {
    sign_fail('Invalid type.');
}

if ($id <= 0) {
    sign_fail('Invalid record id.');
}

if ($signature === '') {
    sign_fail('Signature is empty.');
}

// ---- Step 2: load the earning record ----
$obj->select($table, '*', null, 'id = ' . $id, null, 1);
$result = $obj->getResult();

if (empty($result[0]['id'])) {
    sign_fail('Record not found.');
}

$row = $result[0];

if (!empty($row['signature_image'])) {
    sign_fail('Already signed.');
}

// ---- Step 3: turn canvas base64 into a PNG file ----
// Canvas sends: data:image/png;base64,AAAA...
if (strpos($signature, 'base64,') === false) {
    sign_fail('Bad signature data.');
}

$parts = explode('base64,', $signature);
$image_data = base64_decode($parts[1]);

if ($image_data === false || $image_data === '') {
    sign_fail('Could not read signature image.');
}

$folder = '../uploads/signatures/';

if (!is_dir($folder)) {
    mkdir($folder, 0755, true);
}

$file_name = 'sign_' . $type . '_' . $id . '_' . time() . '.png';
$file_path = $folder . $file_name;

if (file_put_contents($file_path, $image_data) === false) {
    sign_fail('Could not save file.');
}

// ---- Step 4: save in database + mark as Paid ----
$user = getCurrentUser();
$user_id = (int) ($user['id'] ?? 0);
$was_unpaid = ((int) ($row['status'] ?? 0) !== 1);

$ok = $obj->update(
    $table,
    [
        'signature_image' => $file_name,
        'signed_at' => date('Y-m-d H:i:s'),
        'signed_by' => $user_id,
        'status' => 1,
    ],
    'id = ' . $id
);

if (!$ok) {
    // Clean up the file if DB update failed
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    sign_fail('Database update failed.');
}

// If this was unpaid, refresh later weeks (same as Mark Paid)
if ($was_unpaid) {
    $employee_id = (int) ($row['employee_id'] ?? 0);
    $week_year = (int) ($row['week_year'] ?? 0);
    $week_number = (int) ($row['week_number'] ?? 0);

    if ($employee_id > 0 && $week_year > 0 && $week_number > 0) {
        if ($type === 'delivery') {
            cascade_following_weeks($obj, $employee_id, $week_year, $week_number);
        } else {
            ride_cascade_following_weeks($obj, $employee_id, $week_year, $week_number);
        }
    }
}

$message = 'Signature saved.';
if ($was_unpaid) {
    $message = 'Signature saved. Marked as Paid.';
}

echo json_encode([
    'success' => true,
    'message' => $message,
    'file' => $file_name,
]);
