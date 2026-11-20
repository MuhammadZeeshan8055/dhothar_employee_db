<?php
/**
 * Simple employee status helpers (beginner friendly).
 *
 * In the database, employee status means:
 *   1 = Active
 *   0 = Inactive
 *   2 = Leave (On Leave)
 *
 * Earnings / rates are NOT deleted when someone goes on Leave.
 * We only show a label so admin can still see them in Delivery / Ridesharing.
 */

/** Return a short text label for the status number. */
function employee_status_text($status)
{
    $status = (int) $status;

    if ($status === 1) {
        return 'Active';
    }

    if ($status === 2) {
        return 'On Leave';
    }

    return 'Inactive';
}

/** True when employee is Active. */
function employee_is_active($status)
{
    return (int) $status === 1;
}

/** True when employee is On Leave. */
function employee_is_on_leave($status)
{
    return (int) $status === 2;
}

/**
 * Extra text for dropdown options.
 * Example: "John - Company - Contract (On Leave)"
 */
function employee_status_option_suffix($status)
{
    $status = (int) $status;

    if ($status === 2) {
        return ' (On Leave)';
    }

    if ($status === 0) {
        return ' (Inactive)';
    }

    return '';
}

/**
 * Small HTML badge for tables.
 * Active employees get no badge (keeps the list clean).
 */
function employee_status_badge_html($status)
{
    $status = (int) $status;

    if ($status === 2) {
        return '<span class="label label-warning">On Leave</span>';
    }

    if ($status === 0) {
        return '<span class="label label-default">Inactive</span>';
    }

    return '';
}
