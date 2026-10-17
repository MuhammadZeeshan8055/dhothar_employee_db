<?php
/**
 * Ride sharing earnings helpers
 *
 * Formulas:
 *   total_net     = bolt_net + uber_net
 *   commission    = % of total_net (from settings)
 *   total_payout  = bolt_payout + uber_payout
 *   balance       = total_payout - commission - vehicle_rent
 *   week_balance  = balance - tax
 *   total_balance = week_balance + prev_carry
 *   prev_carry    = previous week's total_balance while unpaid;
 *                   clears when that week is marked paid/locked
 */

if (!function_exists('money_db')) {
    function money_db($amount)
    {
        return number_format((float) $amount, 2, '.', '');
    }
}

if (!function_exists('iso_week_range')) {
    function iso_week_range($year, $week)
    {
        $dt = new DateTime();
        $dt->setISODate((int) $year, (int) $week, 1);
        $start = $dt->format('Y-m-d');
        $dt->setISODate((int) $year, (int) $week, 7);
        $end = $dt->format('Y-m-d');
        return [$start, $end];
    }
}

if (!function_exists('iso_weeks_in_year')) {
    function iso_weeks_in_year($year)
    {
        $dt = new DateTime();
        $dt->setISODate((int) $year, 53);
        return ((int) $dt->format('W') === 53) ? 53 : 52;
    }
}

if (!function_exists('week_range_label')) {
    function week_range_label($start, $end)
    {
        if ($start && $end) {
            return formatDate($start) . ' to ' . formatDate($end);
        }
        return $start ? formatDate($start) : '';
    }
}

if (!function_exists('year_options_html')) {
    function year_options_html($selected, $current_year, $years_back = 5, $years_forward = 1)
    {
        $html = '';
        for ($y = $current_year + $years_forward; $y >= $current_year - $years_back; $y--) {
            $sel = ((int) $selected === $y) ? ' selected' : '';
            $html .= "<option value=\"{$y}\"{$sel}>{$y}</option>";
        }
        return $html;
    }
}

if (!function_exists('week_options_html')) {
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
}

if (!function_exists('num_display')) {
    function num_display($value)
    {
        return number_format((float) $value, 2, '.', '');
    }
}

/** Run ride-sharing week calculations. $rates = row from ride_sharing_rate_settings */
function calculate_ride_week($bolt_net, $bolt_payout, $uber_net, $uber_payout, $tax, $rates, $prev_carry = 0)
{
    $bolt_net = (float) $bolt_net;
    $bolt_payout = (float) $bolt_payout;
    $uber_net = (float) $uber_net;
    $uber_payout = (float) $uber_payout;
    $tax = (float) $tax;

    $total_net = round($bolt_net + $uber_net, 2);
    $commission_rate = (float) ($rates['commission_rate'] ?? 0);
    $commission = round(($total_net * $commission_rate) / 100, 2);
    $vehicle_rent = round((float) ($rates['vehicle_rent'] ?? 0), 2);
    $total_payout = round($bolt_payout + $uber_payout, 2);
    $balance = round($total_payout - $commission - $vehicle_rent, 2);
    $week_balance = round($balance - $tax, 2);
    $prev_carry = round((float) $prev_carry, 2);
    $total_balance = round($week_balance + $prev_carry, 2);

    return [
        'total_net' => $total_net,
        'commission' => $commission,
        'vehicle_rent' => $vehicle_rent,
        'total_payout' => $total_payout,
        'balance' => $balance,
        'week_balance' => $week_balance,
        'prev_carry' => $prev_carry,
        'total_balance' => $total_balance,
    ];
}

function ride_empty_carry()
{
    return [
        'prev_carry' => 0.0,
        'from_week' => null,
        'from_year' => null,
        'note' => '',
    ];
}

/** Latest earlier unpaid week's total_balance carries forward. */
function ride_get_prev_carry($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $where = "employee_id = $employee_id AND ("
        . "week_year < $week_year OR (week_year = $week_year AND week_number < $week_number)"
        . ")";

    $obj->select('ride_sharing_earnings', '*', null, $where, 'week_year DESC, week_number DESC, id DESC', 1);
    $rows = $obj->getResult();

    if (empty($rows) || empty($rows[0]['id'])) {
        return ride_empty_carry();
    }

    if ((int) ($rows[0]['status'] ?? 0) === 1) {
        return ride_empty_carry();
    }

    $prev_balance = round((float) ($rows[0]['total_balance'] ?? 0), 2);
    if ($prev_balance == 0.0) {
        return ride_empty_carry();
    }

    $from_week = (int) $rows[0]['week_number'];
    $from_year = (int) $rows[0]['week_year'];

    return [
        'prev_carry' => $prev_balance,
        'from_week' => $from_week,
        'from_year' => $from_year,
        'note' => "Week {$from_week} ({$from_year}) unpaid "
            . number_format($prev_balance, 2) . ' carried into this week.',
    ];
}

function ride_find_week_earning($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $obj->select(
        'ride_sharing_earnings',
        '*',
        null,
        "employee_id = $employee_id AND week_year = $week_year AND week_number = $week_number",
        'id DESC',
        1
    );
    $rows = $obj->getResult();
    return (!empty($rows) && !empty($rows[0]['id'])) ? $rows[0] : null;
}

function ride_cascade_following_weeks($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $where = "employee_id = $employee_id AND ("
        . "week_year > $week_year OR (week_year = $week_year AND week_number > $week_number)"
        . ")";

    $obj->select('ride_sharing_earnings', '*', null, $where, 'week_year ASC, week_number ASC, id ASC');
    $rows = $obj->getResult();

    if (empty($rows) || empty($rows[0]['id'])) {
        return 0;
    }

    $updated = 0;
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $week_balance = round((float) ($row['week_balance'] ?? 0), 2);
        $carry = ride_get_prev_carry($obj, $employee_id, (int) $row['week_year'], (int) $row['week_number']);
        $prev_carry = (float) $carry['prev_carry'];
        $total_balance = round($week_balance + $prev_carry, 2);

        $ok = $obj->update(
            'ride_sharing_earnings',
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

function ride_find_week_rate_settings($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $obj->select(
        'ride_sharing_rate_settings',
        '*',
        null,
        "employee_id = $employee_id AND week_year = $week_year AND week_number = $week_number",
        'id DESC',
        1
    );
    $rows = $obj->getResult();
    return (!empty($rows[0]['id'])) ? $rows[0] : null;
}

/** Exact week settings, else latest previous week. */
function ride_get_rate_settings_for_week($obj, $employee_id, $week_year, $week_number)
{
    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    $exact = ride_find_week_rate_settings($obj, $employee_id, $week_year, $week_number);
    if ($exact) {
        return [
            'data' => $exact,
            'source' => 'exact',
            'source_year' => $week_year,
            'source_week' => $week_number,
        ];
    }

    $where = "employee_id = $employee_id AND ("
        . "week_year < $week_year OR (week_year = $week_year AND week_number < $week_number)"
        . ")";

    $obj->select('ride_sharing_rate_settings', '*', null, $where, 'week_year DESC, week_number DESC, id DESC', 1);
    $rows = $obj->getResult();

    if (!empty($rows[0]['id'])) {
        return [
            'data' => $rows[0],
            'source' => 'carried',
            'source_year' => (int) $rows[0]['week_year'],
            'source_week' => (int) $rows[0]['week_number'],
        ];
    }

    return null;
}

function ride_earning_toast($type, $message)
{
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];
    header('Location: ride_sharing_earnings');
    exit;
}

function ride_settings_toast($type, $message)
{
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];
    header('Location: ride_sharing_settings');
    exit;
}
