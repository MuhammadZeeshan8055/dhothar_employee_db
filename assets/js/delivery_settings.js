/**
 * Delivery settings — load/save rates by employee + ISO week
 */
(function ($) {
    'use strict';

    var lastLoadMeta = null;

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

    function rateLabel(rate, type) {
        rate = parseFloat(rate) || 0;
        return type === 'fixed' ? ('Fixed ' + rate.toFixed(2)) : (rate + '%');
    }

    function selectedText($sel) {
        var val = $sel.val();
        if (!val || val.indexOf('Select') === 0) {
            return '-';
        }
        return $sel.find('option:selected').text();
    }

    function employeeName() {
        var text = $('#employee_id option:selected').text();
        if (!text) {
            return 'Employee';
        }
        return text.split(' - ')[0];
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

    function clearForm() {
        $('#commission_rate, #tax_rate, #sc_rate').val('');
        $('#commission_type').val('percentage');
        $('#tax_type').val('percentage');
        $('#sc_type').val('fixed');
        $('#service_providers').val('AML');
        $('#vehicle_type').val('');
        $('#vehicle_company_name').val('');
        $('#submitSettingsBtn').text('Submit Details').removeClass('btn-primary').addClass('btn-success');
        $('#rateInfo').text('Select employee and week to load rate settings.');
        lastLoadMeta = null;
        $('#settingsSummary').hide().html('');
    }

    function fillForm(data, meta) {
        lastLoadMeta = meta || null;
        $('#commission_rate').val(data.commission_rate);
        $('#commission_type').val(data.commission_type);
        $('#tax_rate').val(data.tax_rate);
        $('#tax_type').val(data.tax_type);
        $('#sc_rate').val(data.sc_rate);
        $('#sc_type').val(data.sc_type);
        $('#service_providers').val(data.service_providers);
        $('#vehicle_type').val(data.vehicle_type);
        $('#vehicle_company_name').val(data.vehicle_company_name);

        if (meta && meta.source === 'exact') {
            $('#submitSettingsBtn').text('Update Details').removeClass('btn-success').addClass('btn-primary');
        } else {
            $('#submitSettingsBtn').text('Save for This Week').removeClass('btn-primary').addClass('btn-success');
        }

        $('#rateInfo').text(meta && meta.message ? meta.message : 'Rate settings loaded.');
        updateSummary();
    }

    function updateSummary() {
        var employeeId = $('#employee_id').val();
        if (!employeeId) {
            $('#settingsSummary').hide().html('');
            return;
        }

        var week = $('#week_number').val();
        var range = $('#week_date_range').val();
        var vehicle = selectedText($('#vehicle_type'));
        var company = selectedText($('#vehicle_company_name'));
        var scRate = $('#sc_rate').val() || '0';
        var commission = rateLabel($('#commission_rate').val(), $('#commission_type').val());
        var tax = rateLabel($('#tax_rate').val(), $('#tax_type').val());
        var rent = rateLabel(scRate, $('#sc_type').val());

        var html = employeeName()
            + ' has vehicle <strong>' + vehicle + '</strong>, company <strong>' + company + '</strong>, '
            + 'rent <strong>' + rent + '</strong>, commission <strong>' + commission + '</strong>, tax <strong>' + tax + '</strong>. '
            + 'For Week <strong>' + week + '</strong> (' + range + ').';

        $('#settingsSummary').html(html).show();
    }

    function loadWeekSettings() {
        var employeeId = $('#employee_id').val();
        var weekYear = $('#week_year').val();
        var weekNumber = $('#week_number').val();

        if (!employeeId) {
            clearForm();
            return;
        }

        if (!weekYear || !weekNumber) {
            return;
        }

        $('#rateInfo').text('Loading rate settings...');

        $.getJSON('ajax/get_employee_rates', {
            employee_id: employeeId,
            week_year: weekYear,
            week_number: weekNumber
        }).done(function (res) {
            if (res.success) {
                fillForm(res.data, res);
            } else {
                clearForm();
                $('#rateInfo').text(res.message || 'No settings found. Enter new rates below.');
                updateSummary();
            }
        }).fail(function () {
            clearForm();
            $('#rateInfo').text('Failed to load rate settings.');
            toastr.error('Failed to load rate settings.');
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

        $employee.on('change', loadWeekSettings);

        $('#week_year').on('change', function () {
            rebuildWeeks($(this).val(), $('#week_number').val());
            refreshDateRange();
            loadWeekSettings();
        });

        $('#week_number').on('change', function () {
            refreshDateRange();
            loadWeekSettings();
        });

        $('#settingsForm').on('input change', 'input, select', updateSummary);

        refreshDateRange();
    });
})(jQuery);
