<?php

function companies_ensure_table($db)
{
    $db->execute(
        "CREATE TABLE IF NOT EXISTS `companies` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_company_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if ((int) $db->count('companies', '*') > 0) {
        return;
    }

    $defaults = [
        'RENT WITH ME S.R.L',
        'DHOTHAR HR SERVICES S.R.L',
        'DHOTHAR INTERNATINOAL IMPEX S.R.L',
        'DIG LOGISTICS S.R.L',
        'PASIO',
    ];
    $now = date('Y-m-d H:i:s');

    foreach ($defaults as $name) {
        $db->insert('companies', [
            'name' => $db->escape($name),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

function companies_list_all($db)
{
    companies_ensure_table($db);
    $db->select('companies', '*', null, null, 'name ASC');
    $rows = $db->getResult();
    return (!empty($rows) && is_array($rows)) ? $rows : [];
}

function companies_list_active($db)
{
    companies_ensure_table($db);
    $db->select('companies', '*', null, 'is_active = 1', 'name ASC');
    $rows = $db->getResult();
    if (empty($rows) || !is_array($rows)) {
        return [];
    }

    $names = [];
    foreach ($rows as $row) {
        $names[] = $row['name'];
    }
    return $names;
}

function companies_options_html($db, $selected = '')
{
    $companies = companies_list_active($db);
    $selected = (string) $selected;
    $html = '';
    $found = false;

    foreach ($companies as $name) {
        $sel = ($selected !== '' && $selected === $name) ? ' selected' : '';
        if ($sel) {
            $found = true;
        }
        $html .= '<option value="' . htmlspecialchars($name) . '"' . $sel . '>'
            . htmlspecialchars($name) . '</option>';
    }

    if ($selected !== '' && !$found) {
        $html = '<option value="' . htmlspecialchars($selected) . '" selected>'
            . htmlspecialchars($selected) . ' (inactive)</option>' . $html;
    }

    return $html;
}

function companies_toast($type, $message)
{
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];
    header('Location: manage_companies');
    exit;
}
