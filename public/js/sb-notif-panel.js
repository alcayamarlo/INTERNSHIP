/* Bell dropdown: keep the "N new" pill and the "Mark all as read" button in step with the bell badge.
   The badge is already updated by the existing notification code; this only reads it (no requests). */
(function () {
    'use strict';
    function init() {
        var menu = document.getElementById('sbNotificationPanel');
        if (!menu) return;
        var pill = menu.querySelector('[data-sbn-pill]');
        var readAll = menu.querySelector('[data-sbn-readall]');
        var bell = document.querySelector('[aria-controls="sbNotificationPanel"]');
        if (!bell) return;

        function sync() {
            var badge = bell.querySelector('[data-unread-badge]');
            var text = badge && !badge.hidden ? badge.textContent.trim() : '';
            var count = text === '9+' ? 10 : parseInt(text, 10) || 0;
            if (pill) {
                pill.hidden = count < 1;
                pill.textContent = (text || '0') + ' new';
            }
            if (readAll) readAll.hidden = count < 1;
        }

        new MutationObserver(sync).observe(bell, { childList: true, characterData: true, subtree: true, attributes: true, attributeFilter: ['hidden'] });
        sync();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
