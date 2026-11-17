<?php

function vehicle_companies_ensure_table($db)
{
    $db->execute(
        "CREATE TABLE IF NOT EXISTS `vehicle_companies` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `slug` varchar(100) NOT NULL,
          `name` varchar(255) NOT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_vehicle_company_slug` (`slug`),
          UNIQUE KEY `uniq_vehicle_company_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if ((int) $db->count('vehicle_companies', '*') > 0) {
        return;
    }

    $defaults = [
        ['uny_mobility_srl', 'UNY MOBILITY SRL'],
        ['kiris_rent_srl', 'KIRIS RENT SRL'],
        ['rbj_brothers_srl', 'RBJ BROTHERS SRL'],
        ['pasio', 'PASIO'],
        ['phoenix_car_rental', 'Phoenix car rental'],
        ['own_car', 'Own Car'],
    ];
    $now = date('Y-m-d H:i:s');

    foreach ($defaults as $row) {
        $db->insert('vehicle_companies', [
            'slug' => $db->escape($row[0]),
            'name' => $db->escape($row[1]),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

function vehicle_companies_slugify($name)
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
    $slug = trim($slug, '_');
    return $slug !== '' ? $slug : 'company';
}

function vehicle_companies_unique_slug($db, $name)
{
    vehicle_companies_ensure_table($db);
    $base = vehicle_companies_slugify($name);
    $slug = $base;
    $i = 2;

    while ((int) $db->count('vehicle_companies', '*', "slug = '" . $db->escape($slug) . "'") > 0) {
        $slug = $base . '_' . $i;
        $i++;
    }

    return $slug;
}

function vehicle_companies_list_all($db)
{
    vehicle_companies_ensure_table($db);
    $db->select('vehicle_companies', '*', null, null, 'name ASC');
    $rows = $db->getResult();
    return (!empty($rows) && is_array($rows)) ? $rows : [];
}

function vehicle_companies_list_active($db)
{
    vehicle_companies_ensure_table($db);
    $db->select('vehicle_companies', '*', null, 'is_active = 1', 'name ASC');
    $rows = $db->getResult();
    return (!empty($rows) && is_array($rows)) ? $rows : [];
}

function vehicle_companies_label_map($db)
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    vehicle_companies_ensure_table($db);
    $db->select('vehicle_companies', 'slug, name', null, null, 'name ASC');
    $rows = $db->getResult();

    $cache = [
        'uny_mobility' => 'UNY MOBILITY',
        'on_car' => 'On Car',
    ];

    if (!empty($rows) && is_array($rows)) {
        foreach ($rows as $row) {
            $cache[$row['slug']] = $row['name'];
        }
    }

    return $cache;
}

function vehicle_company_label($value)
{
    if ($value === null || $value === '') {
        return '';
    }

    static $map = null;
    if ($map === null && class_exists('Database')) {
        $map = vehicle_companies_label_map(new Database());
    }

    return isset($map[$value]) ? $map[$value] : $value;
}

function vehicle_companies_options_html($db, $selected = '')
{
    $companies = vehicle_companies_list_active($db);
    $selected = (string) $selected;
    $html = '';
    $found = false;

    foreach ($companies as $row) {
        $sel = ($selected !== '' && $selected === $row['slug']) ? ' selected' : '';
        if ($sel) {
            $found = true;
        }
        $html .= '<option value="' . htmlspecialchars($row['slug']) . '"' . $sel . '>'
            . htmlspecialchars($row['name']) . '</option>';
    }

    if ($selected !== '' && !$found) {
        $html = '<option value="' . htmlspecialchars($selected) . '" selected>'
            . htmlspecialchars(vehicle_company_label($selected)) . ' (inactive)</option>' . $html;
    }

    return $html;
}

function vehicle_companies_toast($type, $message)
{
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];
    header('Location: manage_vehicle_companies');
    exit;
}
