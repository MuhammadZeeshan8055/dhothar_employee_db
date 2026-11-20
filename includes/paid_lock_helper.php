<?php
/**
 * Paid lock helper (Delivery + Ridesharing)
 *
 * Simple rule:
 *   If week 46 is Paid for an employee,
 *   then week 46 and ALL earlier weeks (45, 44, 43, ...) are locked.
 *
 * Only Super Admin can edit / delete / unlock locked weeks.
 *
 * status in earnings tables:
 *   0 = unpaid
 *   1 = paid
 */

/**
 * Is week A before or the same as week B?
 * Example: (2026, 45) before or same as (2026, 46) → true
 */
function week_is_before_or_same($year_a, $week_a, $year_b, $week_b)
{
    $year_a = (int) $year_a;
    $week_a = (int) $week_a;
    $year_b = (int) $year_b;
    $week_b = (int) $week_b;

    if ($year_a < $year_b) {
        return true;
    }

    if ($year_a > $year_b) {
        return false;
    }

    // Same year → compare week numbers
    return $week_a <= $week_b;
}

/**
 * Latest paid week for one employee.
 * Returns [week_year, week_number] or null if none paid.
 *
 * $table = 'delivery_earnings' or 'ride_sharing_earnings'
 */
function find_latest_paid_week($obj, $table, $employee_id)
{
    $employee_id = (int) $employee_id;
    $table = preg_replace('/[^a-z_]/', '', (string) $table);

    $obj->select(
        $table,
        'week_year, week_number',
        null,
        "employee_id = {$employee_id} AND status = 1",
        'week_year DESC, week_number DESC, id DESC',
        1
    );
    $rows = $obj->getResult();

    if (!is_array($rows) || empty($rows[0]['week_year'])) {
        return null;
    }

    return [
        'week_year' => (int) $rows[0]['week_year'],
        'week_number' => (int) $rows[0]['week_number'],
    ];
}

/**
 * Map of employee_id => latest paid [week_year, week_number]
 * One query for the whole page (faster for tables).
 */
function build_latest_paid_map($obj, $table)
{
    $table = preg_replace('/[^a-z_]/', '', (string) $table);
    $map = [];

    $obj->select(
        $table,
        'employee_id, week_year, week_number',
        null,
        'status = 1',
        'week_year ASC, week_number ASC, id ASC'
    );
    $rows = $obj->getResult();

    if (!is_array($rows)) {
        return $map;
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $eid = (int) ($row['employee_id'] ?? 0);
        if ($eid <= 0) {
            continue;
        }

        // Later rows overwrite earlier ones → ends as latest paid week
        $map[$eid] = [
            'week_year' => (int) ($row['week_year'] ?? 0),
            'week_number' => (int) ($row['week_number'] ?? 0),
        ];
    }

    return $map;
}

/**
 * Is this week locked for a normal Admin?
 *
 * Locked when the employee has a paid week that is the same
 * or later than this week.
 *
 * Super Admin → always false (not locked for them).
 */
function is_week_paid_locked($obj, $table, $employee_id, $week_year, $week_number, $latest_paid_map = null)
{
    // Super Admin can always change
    if (function_exists('isSuperAdmin') && isSuperAdmin()) {
        return false;
    }

    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    if ($employee_id <= 0 || $week_year <= 0 || $week_number <= 0) {
        return false;
    }

    // Use map if given (table page), otherwise look up one employee
    if (is_array($latest_paid_map)) {
        $latestPaid = $latest_paid_map[$employee_id] ?? null;
    } else {
        $latestPaid = find_latest_paid_week($obj, $table, $employee_id);
    }

    if (!$latestPaid) {
        return false; // nothing paid yet → not locked by this rule
    }

    // If this week is on/before the latest paid week → locked
    return week_is_before_or_same(
        $week_year,
        $week_number,
        $latestPaid['week_year'],
        $latestPaid['week_number']
    );
}
