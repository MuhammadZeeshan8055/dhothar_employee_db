<?php
/**
 * Paid lock helper (Delivery + Ridesharing)
 *
 * Simple rule for beginners:
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
 * Example: week 45 / 2026 before or same as week 46 / 2026 → yes
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
    if ($week_a <= $week_b) {
        return true;
    }

    return false;
}

/**
 * Only allow known earnings table names (keeps SQL safe and easy to read).
 */
function paid_lock_table_name($table)
{
    if ($table === 'delivery_earnings') {
        return 'delivery_earnings';
    }

    if ($table === 'ride_sharing_earnings') {
        return 'ride_sharing_earnings';
    }

    // Unknown table → empty (caller should not continue)
    return '';
}

/**
 * Latest paid week for one employee.
 * Returns array with week_year + week_number, or null if none paid.
 */
function find_latest_paid_week($obj, $table, $employee_id)
{
    $table = paid_lock_table_name($table);
    $employee_id = (int) $employee_id;

    if ($table === '' || $employee_id <= 0) {
        return null;
    }

    $where = "employee_id = " . $employee_id . " AND status = 1";

    $obj->select(
        $table,
        'week_year, week_number',
        null,
        $where,
        'week_year DESC, week_number DESC, id DESC',
        1
    );
    $rows = $obj->getResult();

    if (!is_array($rows)) {
        return null;
    }

    if (empty($rows[0]['week_year'])) {
        return null;
    }

    return array(
        'week_year' => (int) $rows[0]['week_year'],
        'week_number' => (int) $rows[0]['week_number'],
    );
}

/**
 * Build a map: employee_id => latest paid week
 * Used on list pages so we do not query once per row.
 */
function build_latest_paid_map($obj, $table)
{
    $table = paid_lock_table_name($table);
    $map = array();

    if ($table === '') {
        return $map;
    }

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

        $employee_id = (int) $row['employee_id'];
        if ($employee_id <= 0) {
            continue;
        }

        // Later rows replace earlier ones → last value is the latest paid week
        $map[$employee_id] = array(
            'week_year' => (int) $row['week_year'],
            'week_number' => (int) $row['week_number'],
        );
    }

    return $map;
}

/**
 * Is this week locked for a normal Admin?
 *
 * YES when the employee has a paid week that is the same or later.
 * Super Admin is never locked by this rule.
 */
function is_week_paid_locked($obj, $table, $employee_id, $week_year, $week_number, $latest_paid_map = null)
{
    if (function_exists('isSuperAdmin') && isSuperAdmin()) {
        return false;
    }

    $employee_id = (int) $employee_id;
    $week_year = (int) $week_year;
    $week_number = (int) $week_number;

    if ($employee_id <= 0 || $week_year <= 0 || $week_number <= 0) {
        return false;
    }

    // Prefer the map (fast on tables). Otherwise look up one employee.
    $latest_paid = null;
    if (is_array($latest_paid_map)) {
        if (isset($latest_paid_map[$employee_id])) {
            $latest_paid = $latest_paid_map[$employee_id];
        }
    } else {
        $latest_paid = find_latest_paid_week($obj, $table, $employee_id);
    }

    if (!$latest_paid) {
        return false;
    }

    return week_is_before_or_same(
        $week_year,
        $week_number,
        $latest_paid['week_year'],
        $latest_paid['week_number']
    );
}
