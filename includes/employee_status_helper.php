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

/**
 * Short note for the "no rates saved this week" list.
 *
 * Simple meanings:
 *   carried / legacy → older rates exist, admin can ignore if unchanged
 *   none             → never set rates, admin should set them
 *
 * Returns: array with keys "text" and "class"
 */
function missing_rate_note($employee)
{
    $source = 'none';
    if (isset($employee['rate_source'])) {
        $source = $employee['rate_source'];
    }

    // Older week rates will be used automatically
    if ($source === 'carried') {
        $week = 0;
        $year = 0;
        if (isset($employee['source_week'])) {
            $week = (int) $employee['source_week'];
        }
        if (isset($employee['source_year'])) {
            $year = (int) $employee['source_year'];
        }

        return [
            'text' => 'Will use Week ' . $week . ' / ' . $year . ' (ignore if unchanged)',
            'class' => 'text-muted',
        ];
    }

    // Old default settings (before weekly tracking)
    if ($source === 'legacy') {
        return [
            'text' => 'Will use default/legacy settings (ignore if unchanged)',
            'class' => 'text-muted',
        ];
    }

    // Never had rates
    return [
        'text' => 'No previous rates — please set',
        'class' => 'text-danger',
    ];
}
