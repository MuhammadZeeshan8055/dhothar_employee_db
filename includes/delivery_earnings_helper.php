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
