<?php
/**
 * Delivery earnings helpers
 *
 * Formulas:
 *   commission   = % or fixed of earning
 *   total_earning = earning - commission - cash_in_hand - app_tax
 *   tax / sc     = % or fixed of total_earning
 *   week_balance = total_earning - tax - sc - others
 *   total_balance = week_balance + prev_carry   (prev_carry is 0 or negative)
 */

/** Format amount for DB insert/update (2 decimals). */
function money_db($amount)
{
    return number_format((float) $amount, 2, '.', '');
}

/** % or fixed amount from a base. */
function calc_amount($base, $rate, $type)
{
    $base = (float) $base;
    $rate = (float) $rate;
    if ($type === 'fixed') {
        return round($rate, 2);
    }
    return round(($base * $rate) / 100, 2);
}

/** Monday–Sunday dates for an ISO week. Returns [start, end] as Y-m-d. */
function iso_week_range($year, $week)
{
    $dt = new DateTime();
    $dt->setISODate((int) $year, (int) $week, 1);
    $start = $dt->format('Y-m-d');
    $dt->setISODate((int) $year, (int) $week, 7);
    $end = $dt->format('Y-m-d');
    return [$start, $end];
}

/** How many ISO weeks in a year (52 or 53). */
function iso_weeks_in_year($year)
{
    $dt = new DateTime();
    $dt->setISODate((int) $year, 53);
    return ((int) $dt->format('W') === 53) ? 53 : 52;
}

/** Display label: 14-09-2026 to 20-09-2026 */
function week_range_label($start, $end)
{
    if ($start && $end) {
        return formatDate($start) . ' to ' . formatDate($end);
    }
    return $start ? formatDate($start) : '';
}

/**
 * Run all earning calculations for one week.
 * $rates = row from employee_rate_settings
 * $prev_carry = negative balance from a previous week (or 0)
 */
function calculate_week_earning($earning, $cash_in_hand, $app_tax, $others, $rates, $prev_carry = 0)
{
    $commission = calc_amount($earning, $rates['commission_rate'], $rates['commission_type']);
    $total_earning = round((float) $earning - $commission - (float) $cash_in_hand - (float) $app_tax, 2);
    $tax = calc_amount($total_earning, $rates['tax_rate'], $rates['tax_type']);
    $sc = calc_amount($total_earning, $rates['sc_rate'], $rates['sc_type']);
    $week_balance = round($total_earning - $tax - $sc - (float) $others, 2);
    $prev_carry = round((float) $prev_carry, 2);
    $total_balance = round($week_balance + $prev_carry, 2);

    return [
        'commission' => $commission,
        'total_earning' => $total_earning,
        'tax' => $tax,
        'sc' => $sc,
        'week_balance' => $week_balance,
        'prev_carry' => $prev_carry,
        'total_balance' => $total_balance,
    ];
}

/** Empty carry result. */
function empty_carry()
{
    return [
        'prev_carry' => 0.0,
        'from_week' => null,
        'from_year' => null,
        'note' => '',
    ];
}

/**
 * If the latest earlier week for this employee has a negative total_balance,
 * that amount carries into the current week.
 */
function get_prev_carry($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $where = "employee_id = $employee_id AND ("
        . "week_year < $week_year OR (week_year = $week_year AND week_number < $week_number)"
        . ")";

    $obj->select("delivery_earnings", "*", null, $where, "week_year DESC, week_number DESC, id DESC", 1);
    $rows = $obj->getResult();

    if (empty($rows) || empty($rows[0]['id'])) {
        return empty_carry();
    }

    $prev_balance = (float) ($rows[0]['total_balance'] ?? 0);
    if ($prev_balance >= 0) {
        return empty_carry();
    }

    $from_week = (int) $rows[0]['week_number'];
    $from_year = (int) $rows[0]['week_year'];
    $prev_carry = round($prev_balance, 2);

    return [
        'prev_carry' => $prev_carry,
        'from_week' => $from_week,
        'from_year' => $from_year,
        'note' => "Week {$from_week} ({$from_year}) negative "
            . number_format($prev_carry, 2) . ' adjusting in this week.',
    ];
}

/** Find one earning row by employee + year + week. */
function find_week_earning($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $obj->select(
        "delivery_earnings",
        "*",
        null,
        "employee_id = $employee_id AND week_year = $week_year AND week_number = $week_number",
        "id DESC",
        1
    );
    $rows = $obj->getResult();
    return (!empty($rows) && !empty($rows[0]['id'])) ? $rows[0] : null;
}

/** Stored week_balance, or rebuild from older rows that lack the column. */
function row_week_balance($row)
{
    if (isset($row['week_balance']) && $row['week_balance'] !== '' && $row['week_balance'] !== null) {
        return round((float) $row['week_balance'], 2);
    }
    return round(
        (float) $row['total_earning'] - (float) $row['tax'] - (float) $row['sc'] - (float) $row['others'],
        2
    );
}

/**
 * After saving a week, refresh prev_carry / total_balance on later weeks
 * so the chain stays correct.
 */
function cascade_following_weeks($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $where = "employee_id = $employee_id AND ("
        . "week_year > $week_year OR (week_year = $week_year AND week_number > $week_number)"
        . ")";

    $obj->select("delivery_earnings", "*", null, $where, "week_year ASC, week_number ASC, id ASC");
    $rows = $obj->getResult();

    if (empty($rows) || empty($rows[0]['id'])) {
        return 0;
    }

    $updated = 0;
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $week_balance = row_week_balance($row);
        $carry = get_prev_carry($obj, $employee_id, (int) $row['week_year'], (int) $row['week_number']);
        $prev_carry = (float) $carry['prev_carry'];
        $total_balance = round($week_balance + $prev_carry, 2);

        $ok = $obj->update(
            "delivery_earnings",
            [
                'week_balance' => money_db($week_balance),
                'prev_carry' => money_db($prev_carry),
                'adjustment_note' => $carry['note'],
                'total_balance' => money_db($total_balance),
            ],
            "id = $id"
        );
        if ($ok) {
            $updated++;
        }
    }

    return $updated;
}

/** Flash message and redirect back to the page. */
function earning_toast($type, $message)
{
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];
    header('Location: delivery_earnings');
    exit;
}

/** Build year options HTML (current ISO year ± range). */
function year_options_html($selected, $current_year, $years_back = 5, $years_forward = 1)
{
    $html = '';
    for ($y = $current_year + $years_forward; $y >= $current_year - $years_back; $y--) {
        $sel = ((int) $selected === $y) ? ' selected' : '';
        $html .= "<option value=\"{$y}\"{$sel}>{$y}</option>";
    }
    return $html;
}

/** Build week options HTML (Week 1 … Week N). */
function week_options_html($selected, $year, $include_all = false)
{
    $html = $include_all ? '<option value="">All Weeks</option>' : '';
    $max = $year ? iso_weeks_in_year($year) : 53;
    for ($w = 1; $w <= $max; $w++) {
        $sel = ((int) $selected === $w) ? ' selected' : '';
        $html .= "<option value=\"{$w}\"{$sel}>Week {$w}</option>";
    }
    return $html;
}

/** Find rate settings for exact employee + week. */
function find_week_rate_settings($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $obj->select(
        'employee_rate_settings',
        '*',
        null,
        "employee_id = $employee_id AND week_year = $week_year AND week_number = $week_number",
        'id DESC',
        1
    );
    $rows = $obj->getResult();

    return (!empty($rows[0]['id'])) ? $rows[0] : null;
}

/**
 * Rate settings for a week: exact row, else latest previous week, else legacy (NULL week).
 */
function get_rate_settings_for_week($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $exact = find_week_rate_settings($obj, $employee_id, $week_year, $week_number);
    if ($exact) {
        return [
            'data' => $exact,
            'source' => 'exact',
            'source_year' => $week_year,
            'source_week' => $week_number,
        ];
    }

    $where = "employee_id = $employee_id AND week_year IS NOT NULL AND week_number IS NOT NULL AND ("
        . "week_year < $week_year OR (week_year = $week_year AND week_number < $week_number)"
        . ")";

    $obj->select('employee_rate_settings', '*', null, $where, 'week_year DESC, week_number DESC, id DESC', 1);
    $rows = $obj->getResult();

    if (!empty($rows[0]['id'])) {
        return [
            'data' => $rows[0],
            'source' => 'carried',
            'source_year' => (int) $rows[0]['week_year'],
            'source_week' => (int) $rows[0]['week_number'],
        ];
    }

    $obj->select(
        'employee_rate_settings',
        '*',
        null,
        "employee_id = $employee_id AND week_year IS NULL",
        'id DESC',
        1
    );
    $legacy = $obj->getResult();

    if (!empty($legacy[0]['id'])) {
        return [
            'data' => $legacy[0],
            'source' => 'legacy',
            'source_year' => null,
            'source_week' => null,
        ];
    }

    return null;
}

function rate_type_label($rate, $type)
{
    $rate = number_format((float) $rate, 2);
    return $type === 'fixed' ? "Fixed {$rate}" : "{$rate}%";
}

function vehicle_type_label($value)
{
    $map = [
        'bicyle' => 'Bicycle',
        'sc' => 'Scooter',
        'car' => 'Car',
    ];

    return $map[$value] ?? $value;
}

function vehicle_company_label($value)
{
    $map = [
        'uny_mobility' => 'UNY MOBILITY',
        'uny_mobility_srl' => 'UNY MOBILITY SRL',
        'kiris_rent_srl' => 'KIRIS RENT SRL',
        'rbj_brothers_srl' => 'RBJ BROTHERS SRL',
    ];

    return $map[$value] ?? $value;
}

function settings_toast($type, $message)
{
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];
    header('Location: delivery_settings');
    exit;
}

/** Distinct vehicle companies from rate settings (for filter dropdown). */
function vehicle_company_filter_options($obj)
{
    $obj->sql(
        "SELECT DISTINCT vehicle_company_name FROM employee_rate_settings
         WHERE vehicle_company_name IS NOT NULL AND vehicle_company_name != ''
         ORDER BY vehicle_company_name ASC"
    );
    return $obj->getResult();
}

/** Group SC rent totals by vehicle company. */
function build_rent_by_company_summary(array $earnings)
{
    $groups = [];

    foreach ($earnings as $row) {
        $key = trim($row['vehicle_company_key'] ?? '');
        $groupKey = $key !== '' ? $key : '_none';

        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = [
                'key' => $key,
                'label' => $key !== '' ? vehicle_company_label($key) : 'Not set',
                'employees' => 0,
                'total_sc' => 0.0,
            ];
        }

        $groups[$groupKey]['employees']++;
        $groups[$groupKey]['total_sc'] += (float) ($row['sc'] ?? 0);
    }

    $summary = array_values($groups);
    usort($summary, function ($a, $b) {
        return strcasecmp($a['label'], $b['label']);
    });

    return $summary;
}

/** Build filter URL for delivery earnings list. */
function delivery_earnings_filter_url($base_url, $year, $week, $vehicleCompany = '')
{
    $params = [];
    if ($year !== null && $year !== '') {
        $params['filter_year'] = (int) $year;
    }
    if ($week !== null && $week !== '') {
        $params['filter_week'] = (int) $week;
    }
    if ($vehicleCompany !== '') {
        $params['filter_vehicle_company'] = $vehicleCompany;
    }

    $query = http_build_query($params);
    return $base_url . 'delivery_earnings' . ($query ? '?' . $query : '');
}

/** Attach vehicle info from week rate settings onto earning rows. */
function enrich_earnings_with_vehicle($obj, array $earnings)
{
    foreach ($earnings as $i => $row) {
        if (empty($row['id'])) {
            continue;
        }

        $rateResult = get_rate_settings_for_week(
            $obj,
            (int) $row['employee_id'],
            (int) ($row['week_year'] ?? 0),
            (int) ($row['week_number'] ?? 0)
        );

        if ($rateResult) {
            $rates = $rateResult['data'];
            $earnings[$i]['vehicle_type'] = vehicle_type_label($rates['vehicle_type'] ?? '');
            $earnings[$i]['vehicle_company_name'] = vehicle_company_label($rates['vehicle_company_name'] ?? '');
            $earnings[$i]['vehicle_company_key'] = $rates['vehicle_company_name'] ?? '';

            if (empty($earnings[$i]['service_providers'])) {
                $earnings[$i]['service_providers'] = $rates['service_providers'] ?? '';
            }
        } else {
            $earnings[$i]['vehicle_type'] = '';
            $earnings[$i]['vehicle_company_name'] = '';
            $earnings[$i]['vehicle_company_key'] = '';
        }
    }

    return $earnings;
}
