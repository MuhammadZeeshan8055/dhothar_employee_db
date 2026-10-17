/**
 * Ride sharing earnings form JS
 *
 * Manual: Bolt Net, Bolt Payout, Uber Net, Uber Payout, Tax
 * From settings: Commission %, Vehicle Rent
 * Auto: Total Net, Commission, Total Payout, Balance, Final Balance, Total Balance
 */
(function ($) {
    'use strict';

    var rates = null;
    var prevCarry = 0;

    function toNum(val) {
        var n = parseFloat(val);
        return isNaN(n) ? 0 : n;
    }

    function money(n) {
        return (Math.round(n * 100) / 100).toFixed(2);
    }

    function pad2(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function displayDate(d) {
        return pad2(d.getUTCDate()) + '-' + pad2(d.getUTCMonth() + 1) + '-' + d.getUTCFullYear();
    }

    function isoDate(d) {
        return d.getUTCFullYear() + '-' + pad2(d.getUTCMonth() + 1) + '-' + pad2(d.getUTCDate());
    }

    function weekDates(year, week) {
        year = parseInt(year, 10);
        week = parseInt(week, 10);
        var jan4 = new Date(Date.UTC(year, 0, 4));
        var day = jan4.getUTCDay() || 7;
        var monday = new Date(jan4);
        monday.setUTCDate(jan4.getUTCDate() - day + 1 + (week - 1) * 7);
        var sunday = new Date(monday);
        sunday.setUTCDate(monday.getUTCDate() + 6);
        return { start: monday, end: sunday };
    }

    function weeksInYear(year) {
        year = parseInt(year, 10);
        var jan4 = new Date(Date.UTC(year, 0, 4));
        var day = jan4.getUTCDay() || 7;
        var week1Mon = new Date(jan4);
        week1Mon.setUTCDate(jan4.getUTCDate() - day + 1);
        var week53Mon = new Date(week1Mon);
        week53Mon.setUTCDate(week1Mon.getUTCDate() + 52 * 7);
        var thursday = new Date(week53Mon);
        thursday.setUTCDate(week53Mon.getUTCDate() + 3);
        return thursday.getUTCFullYear() === year ? 53 : 52;
    }

    function setAlert(message, isEdit) {
        var $el = $('#carryAlert');
        if (!message) {
            $el.text('').removeClass('active edit-mode');
            return;
        }
        $el.text(message).addClass('active').toggleClass('edit-mode', !!isEdit);
    }

    function setEditMode(isEdit, id) {
        $('#earning_id').val(isEdit ? id : '');
        $('#submitEarningBtn')
            .text(isEdit ? 'Update Details' : 'Submit Details')
            .toggleClass('btn-primary', !!isEdit)
            .toggleClass('btn-success', !isEdit);
    }

    function clearInputs() {
        $('#bolt_net, #bolt_payout, #uber_net, #uber_payout, #tax').val('0');
    }

    function fillInputs(data) {
        $('#bolt_net').val(data.bolt_net != null ? data.bolt_net : '0');
        $('#bolt_payout').val(data.bolt_payout != null ? data.bolt_payout : '0');
        $('#uber_net').val(data.uber_net != null ? data.uber_net : '0');
        $('#uber_payout').val(data.uber_payout != null ? data.uber_payout : '0');
        $('#tax').val(data.tax != null ? data.tax : '0');
    }

    function refreshDateRange() {
        var year = $('#week_year').val();
        var week = $('#week_number').val();
        if (!year || !week) {
            return;
        }
        var range = weekDates(year, week);
        $('#week_date_range').val(displayDate(range.start) + ' to ' + displayDate(range.end));
        $('#week_start').val(isoDate(range.start));
        $('#week_end').val(isoDate(range.end));
        $('#earning_date').val(isoDate(range.start));
    }

    function rebuildWeeks(year, selected) {
        var max = weeksInYear(year);
        var current = parseInt(selected, 10) || 1;
        if (current > max) {
            current = max;
        }
        var $sel = $('#week_number').empty();
        for (var w = 1; w <= max; w++) {
            $sel.append($('<option></option>').val(w).text('Week ' + w).prop('selected', w === current));
        }
    }

    // Same formulas as PHP calculate_ride_week()
    function recalculate() {
        var boltNet = toNum($('#bolt_net').val());
        var boltPayout = toNum($('#bolt_payout').val());
        var uberNet = toNum($('#uber_net').val());
        var uberPayout = toNum($('#uber_payout').val());
        var tax = toNum($('#tax').val());

        var totalNet = boltNet + uberNet;
        var commission = 0;
        var vehicleRent = 0;

        if (rates) {
            commission = (totalNet * toNum(rates.commission_rate)) / 100;
            vehicleRent = toNum(rates.vehicle_rent);
        }

        var totalPayout = boltPayout + uberPayout;
        var balance = totalPayout - commission - vehicleRent;
        var weekBalance = balance - tax;
        var totalBalance = weekBalance + prevCarry;

        $('#total_net').val(money(totalNet));
        $('#commission').val(money(commission));
        $('#vehicle_rent').val(money(vehicleRent));
        $('#total_payout').val(money(totalPayout));
        $('#balance').val(money(balance));
        $('#week_balance').val(money(weekBalance));
        $('#prev_carry, #prev_carry_display').val(money(prevCarry));
        $('#total_balance').val(money(totalBalance));
        $('#total_balance')
            .toggleClass('total-negative', totalBalance < 0)
            .toggleClass('total-highlight', totalBalance >= 0);
    }

    function loadWeekEntry() {
        var employeeId = $('#employee_id').val();
        var year = $('#week_year').val();
        var week = $('#week_number').val();

        prevCarry = 0;
        setAlert('');
        setEditMode(false, '');

        if (!employeeId || !year || !week) {
            clearInputs();
            recalculate();
            return;
        }

        $.getJSON('ajax/get_ride_sharing_week', {
            employee_id: employeeId,
            week_year: year,
            week_number: week
        })
            .done(function (res) {
                prevCarry = toNum(res.prev_carry);
                if (res.exists && res.data) {
                    fillInputs(res.data);
                    setEditMode(true, res.data.id);
                    setAlert(res.message || '', true);
                } else {
                    clearInputs();
                    setEditMode(false, '');
                    setAlert(res.message || '', false);
                }
                recalculate();
            })
            .fail(function () {
                prevCarry = 0;
                clearInputs();
                setEditMode(false, '');
                setAlert('');
                recalculate();
            });
    }

    function loadRates(employeeId) {
        rates = null;
        $('#rateInfo').text('Loading rate settings...');

        if (!employeeId) {
            $('#rateInfo').text('Select employee to load rate settings.');
            loadWeekEntry();
            return;
        }

        $.getJSON('ajax/get_ride_sharing_rates', {
            employee_id: employeeId,
            week_year: $('#week_year').val(),
            week_number: $('#week_number').val()
        })
            .done(function (res) {
                if (res.success) {
                    rates = res.data;
                    $('#rateInfo').html(
                        'Rates — Commission: <strong>' + rates.commission_rate +
                        '%</strong> of Total Net | Vehicle Rent: <strong>' +
                        money(rates.vehicle_rent) + '</strong>'
                    );
                } else {
                    rates = null;
                    $('#rateInfo').text(res.message || 'No rate settings found.');
                    toastr.warning(res.message || 'No rate settings found for this employee.');
                }
                loadWeekEntry();
            })
            .fail(function () {
                rates = null;
                $('#rateInfo').text('Failed to load rate settings.');
                toastr.error('Failed to load rate settings.');
                loadWeekEntry();
            });
    }

    $(function () {
        var $employee = $('#employee_id');

        if ($.fn.select2) {
            if ($employee.data('select2')) {
                $employee.select2('destroy');
            }
            $employee.select2({
                allowClear: true,
                placeholder: 'Search employee...',
                width: '100%'
            });
        }

        $employee.on('change', function () {
            loadRates($(this).val());
        });

        $('.calc-input').on('input change', recalculate);

        $('#week_year').on('change', function () {
            rebuildWeeks($(this).val(), $('#week_number').val());
            refreshDateRange();
            loadRates($('#employee_id').val());
        });

        $('#week_number').on('change', function () {
            refreshDateRange();
            loadRates($('#employee_id').val());
        });

        refreshDateRange();

        if ($employee.val()) {
            loadRates($employee.val());
        }

        $(document).on('click', '.edit-earning-btn', function () {
            var employeeId = String($(this).data('employee-id') || '');
            var year = String($(this).data('week-year') || '');
            var week = String($(this).data('week-number') || '');

            if (!employeeId || !year || !week) {
                toastr.error('Cannot edit: missing employee or week data.');
                return;
            }

            $('#week_year').val(year);
            rebuildWeeks(year, week);
            $('#week_number').val(week);
            refreshDateRange();

            var $employee = $('#employee_id');
            $employee.val(employeeId);
            if ($employee.data('select2')) {
                $employee.select2('val', employeeId);
            }

            loadRates(employeeId);

            $('html, body').animate({
                scrollTop: $('#earningForm').offset().top - 20
            }, 300);
        });

        $(document).on('click', '.delete-earning-btn', function () {
            var id = $(this).data('id');
            var $row = $(this).closest('tr');

            if (!confirm('Are you sure you want to delete this earning record?')) {
                return;
            }

            $.post('ajax/delete_ride_sharing_earning', { id: id }, function (response) {
                if (response == 'success') {
                    $row.fadeOut(400, function () {
                        $(this).remove();
                    });
                    toastr.success('Earning record deleted successfully!');
                } else if (response == 'locked') {
                    toastr.error('Paid record is locked and cannot be deleted.');
                } else {
                    toastr.error('Delete failed!');
                }
            });
        });
    });
})(jQuery);
