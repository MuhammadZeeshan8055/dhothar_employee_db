<?php
/**
 * Digital Sign helpers — keep sign code in one place
 */

// delivery → delivery_earnings, ride → ride_sharing_earnings
function digital_sign_table($type)
{
    if ($type === 'delivery') {
        return 'delivery_earnings';
    }

    if ($type === 'ride') {
        return 'ride_sharing_earnings';
    }

    return '';
}

// Delete PNG files for these rows (used when unlocking / unpaid)
function digital_sign_delete_files($rows)
{
    if (empty($rows) || !is_array($rows)) {
        return;
    }

    $folder = __DIR__ . '/../uploads/signatures/';

    foreach ($rows as $row) {
        $file = trim($row['signature_image'] ?? '');

        if ($file === '') {
            continue;
        }

        $path = $folder . $file;

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
