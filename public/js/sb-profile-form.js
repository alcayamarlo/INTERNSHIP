/* Profile pages: unsaved-changes hint and Discard for form.cp-form.
   Discard restores the last saved values (no request). After the shared ajax save reports success, the new values count as saved. */
(function () {
    var form = document.querySelector('form.cp-form');
    var hint = document.querySelector('[data-cp-unsaved]');
    var discard = document.querySelector('[data-cp-discard]');
    if (!form || !hint) return;
    function setDirty(on) { hint.hidden = !on; }
    form.addEventListener('input', function () { setDirty(true); });
    form.addEventListener('change', function () { setDirty(true); });
    if (discard) discard.addEventListener('click', function () {
        form.reset();
        setDirty(false);
        [].forEach.call(form.querySelectorAll('.is-invalid'), function (el) { el.classList.remove('is-invalid'); });
    });
    var feedback = form.querySelector('[data-ajax-feedback]');
    if (feedback) {
        new MutationObserver(function () {
            if (!feedback.classList.contains('text-success') || feedback.textContent.trim() === '') return;
            [].forEach.call(form.querySelectorAll('input:not([type="hidden"]):not([type="file"]), textarea'), function (el) { el.defaultValue = el.value; });
            [].forEach.call(form.querySelectorAll('select'), function (select) { [].forEach.call(select.options, function (o) { o.defaultSelected = o.selected; }); });
            setDirty(false);
        }).observe(feedback, { childList: true, characterData: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    }
})();
