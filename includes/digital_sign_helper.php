<?php
/**
 * Digital Sign helpers
 * Small helpers used by the signature feature.
 */

/**
 * Pick the correct earnings table from the type.
 * Example: "delivery" → delivery_earnings
 */
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

/**
 * HTML for the Signature table column.
 * - If signed: show the signature image
 * - If not signed: show a Sign button
 */
function digital_sign_cell_html($base_url, $type, $id, $signature_image)
{
    $id = (int) $id;
    $file = trim($signature_image ?? '');
    $sign_url = htmlspecialchars($base_url . 'digital_sign?type=' . $type . '&id=' . $id);

    // Already has a signature → show the image
    if ($file !== '') {
        $img_src = htmlspecialchars($base_url . 'uploads/signatures/' . $file);

        return '<a href="' . $sign_url . '" target="_blank" title="View signature">'
            . '<img class="sign-thumb" src="' . $img_src . '" alt="Signature">'
            . '</a>';
    }

    // No signature yet → show Sign button
    return '<a href="' . $sign_url . '" class="btn btn-success btn-xs digital-sign-btn" target="_blank">Sign</a>';
}

/**
 * Delete signature PNG files for the given rows.
 * Used when Super Admin marks a week as Unpaid.
 */
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
