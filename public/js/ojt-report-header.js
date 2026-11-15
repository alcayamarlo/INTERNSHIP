/*
 * Weekly report "Report details": pencil to edit a field on this report only, "Reset to profile value"
 * to go back. Inputs stay disabled until edited, so only changed fields are sent. Period of Evaluation
 * and Inclusive Dates follow the chosen week until the student edits them.
 */
(function () {
    'use strict';

    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    function parseDate(value) {
        var parts = (value || '').split('-');
        if (parts.length !== 3) { return null; }
        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        return isNaN(date.getTime()) ? null : date;
    }

    function mondayOf(date) {
        var monday = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        var day = monday.getDay(); // 0 Sunday
        monday.setDate(monday.getDate() - (day === 0 ? 6 : day - 1));
        return monday;
    }

    function inclusiveDates(monday) {
        var friday = new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + 4);
        if (monday.getFullYear() !== friday.getFullYear()) {
            return MONTHS[monday.getMonth()] + ' ' + monday.getDate() + ', ' + monday.getFullYear() + ' - ' + MONTHS[friday.getMonth()] + ' ' + friday.getDate() + ', ' + friday.getFullYear();
        }
        if (monday.getMonth() === friday.getMonth()) {
            return MONTHS[monday.getMonth()] + ' ' + monday.getDate() + ' - ' + friday.getDate() + ', ' + friday.getFullYear();
        }
        return MONTHS[monday.getMonth()] + ' ' + monday.getDate() + ' - ' + MONTHS[friday.getMonth()] + ' ' + friday.getDate() + ', ' + friday.getFullYear();
    }

    function setEditing(row, editing) {
        var editor = row.querySelector('[data-hf-editor]');
        var display = row.querySelector('[data-hf-display]');
        row.toggleAttribute('data-editing', editing);
        editor.hidden = !editing;
        display.hidden = editing;
        row.querySelector('[data-hf-edit]').hidden = editing;
        row.querySelector('[data-hf-reset]').hidden = !editing;
        editor.querySelectorAll('input, select').forEach(function (field) { field.disabled = !editing; });

        var text = row.querySelector('[data-hf-supervisor-text]');
        var choice = row.querySelector('[data-hf-supervisor-choice]');
        if (editing) {
            var main = editor.querySelector('[name^="header["]');
            if (main && !text) { main.value = row.dataset.default; }
            if (choice) {
                var known = Array.prototype.some.call(choice.options, function (option) { return option.value === row.dataset.default; });
                choice.value = known ? row.dataset.default : '__other';
                syncSupervisor(choice, text, true);
                if (!known) { text.value = row.dataset.default; }
            }
            if (main) { main.focus(); }
        } else {
            editor.querySelectorAll('input[type="checkbox"]').forEach(function (box) { box.checked = false; });
            editor.querySelectorAll('.is-invalid').forEach(function (field) { field.classList.remove('is-invalid'); });
        }
    }

    function syncSupervisor(choice, text, fromChoice) {
        var other = choice.value === '__other';
        text.hidden = !other;
        if (fromChoice) { text.value = other ? '' : choice.value; }
        if (other) { text.focus(); }
    }

    function updateWeek(container, weekInput) {
        var day = parseDate(weekInput.value);
        if (!day) { return; }
        var monday = mondayOf(day);
        var startMonday = parseDate(container.dataset.startMonday);
        var names = {};
        try { names = JSON.parse(container.dataset.periodNames || '{}'); } catch (e) { names = {}; }
        var period = names[String(monday.getMonth() + 1)] || MONTHS[monday.getMonth()].toUpperCase();
        if (startMonday) {
            var weeks = Math.floor(Math.round((monday - startMonday) / 86400000) / 7) + 1;
            if (weeks >= 1) { period += ' (Week ' + weeks + ')'; }
        }
        var values = { period_of_evaluation: period, inclusive_dates: inclusiveDates(monday) };
        Object.keys(values).forEach(function (field) {
            var row = container.querySelector('[data-hf="' + field + '"]');
            if (!row) { return; }
            row.dataset.default = values[field];
            if (!row.hasAttribute('data-editing')) {
                row.querySelector('[data-hf-display]').textContent = values[field];
            }
        });
    }

    document.querySelectorAll('[data-report-header]').forEach(function (container) {
        container.addEventListener('click', function (event) {
            var edit = event.target.closest('[data-hf-edit]');
            var reset = event.target.closest('[data-hf-reset]');
            if (edit) { setEditing(edit.closest('[data-hf]'), true); }
            if (reset) { setEditing(reset.closest('[data-hf]'), false); }
        });
        container.querySelectorAll('[data-hf-supervisor-choice]').forEach(function (choice) {
            var text = choice.closest('[data-hf]').querySelector('[data-hf-supervisor-text]');
            choice.addEventListener('change', function () { syncSupervisor(choice, text, true); });
        });
        var weekInput = container.closest('form') && container.closest('form').querySelector('[name="week_start"]');
        if (weekInput) {
            weekInput.addEventListener('change', function () { updateWeek(container, weekInput); });
        }
    });
})();
