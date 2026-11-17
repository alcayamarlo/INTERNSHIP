/* Industry dropdown: a searchable select (Tom Select) plus the "Other (please specify)" text box.
   Without this script (or when Tom Select cannot load) the plain select still works and the server checks the value. */
(function () {
    'use strict';

    function syncOther(select) {
        var other = document.getElementById(select.getAttribute('data-other-input'));
        if (!other) return;
        var isOther = select.value === select.getAttribute('data-other-value');
        other.hidden = !isOther;
        other.required = isOther;
        if (isOther && select.dataset.userChanged === '1') other.focus();
    }

    function init(select) {
        if (select.dataset.industryReady === '1') return;
        select.dataset.industryReady = '1';

        if (typeof window.TomSelect === 'function') {
            /* Tom Select copies the select's classes onto its wrapper. Drop the field styling so only the
               inner control draws the box (otherwise the page's own form-control rules add a second border). */
            select.className = select.classList.contains('is-invalid') ? 'industry-select is-invalid' : 'industry-select';
            new window.TomSelect(select, {
                allowEmptyOption: true,
                create: false,
                maxOptions: 60,
                closeAfterSelect: true,
                placeholder: 'Search or choose an industry'
            });
        }
        select.addEventListener('change', function () {
            select.dataset.userChanged = '1';
            syncOther(select);
        });
        syncOther(select);
    }

    function boot() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-industry-select]'), init);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
