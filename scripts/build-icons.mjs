// Builds public/css/sb-icons.css: every Bootstrap Icons class the app uses (bi-*) is drawn with the
// matching Lucide icon (24px grid, 1.8 stroke, round caps) through a CSS mask, so templates keep their
// class names and the icon takes the text color. Run with: npm run icons
import { readFileSync, writeFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const lucideDir = resolve(root, 'node_modules/lucide-static/icons');

// Bootstrap Icons name -> Lucide name. "-fill" icons that show a state (rated, pinned, liked) are filled.
const MAP = {
    activity: 'activity', alarm: 'alarm-clock', archive: 'archive', 'arrow-counterclockwise': 'rotate-ccw',
    'arrow-down': 'arrow-down', 'arrow-left': 'arrow-left', 'arrow-repeat': 'refresh-cw', 'arrow-return-left': 'corner-down-left',
    'arrow-right': 'arrow-right', 'arrow-up': 'arrow-up', award: 'award', 'bar-chart': 'chart-column', bell: 'bell',
    'bell-slash': 'bell-off', 'box-arrow-right': 'log-out', 'box-arrow-up-right': 'external-link', briefcase: 'briefcase',
    building: 'building-2', 'building-check': 'building-2', 'building-x': 'building-2', buildings: 'building',
    calendar: 'calendar', 'calendar-event': 'calendar-days', 'calendar-plus': 'calendar-plus', 'calendar-range': 'calendar-range',
    'calendar-x': 'calendar-x', 'calendar2-week': 'calendar-days', calendar3: 'calendar', 'cash-coin': 'coins',
    chat: 'message-circle', 'chat-dots': 'message-circle-more', 'chat-left-text': 'message-square-text',
    'chat-quote': 'message-square-quote', 'chat-square-text': 'message-square-text', 'check-all': 'check-check',
    'check-circle': 'circle-check', 'check-circle-fill': 'circle-check', 'check-lg': 'check', check2: 'check',
    'check2-all': 'check-check', 'check2-circle': 'circle-check', 'chevron-down': 'chevron-down', 'chevron-left': 'chevron-left',
    'chevron-right': 'chevron-right', 'clipboard-check': 'clipboard-check', 'clipboard-data': 'clipboard-list', clock: 'clock',
    'clock-history': 'history', 'cloud-upload': 'cloud-upload', 'database-down': 'database-backup', 'diagram-3': 'network',
    download: 'download', 'emoji-smile': 'smile', envelope: 'mail', 'envelope-paper': 'mail-open', 'envelope-plus': 'mail-plus',
    'exclamation-circle': 'circle-alert', 'exclamation-circle-fill': 'circle-alert', 'exclamation-octagon-fill': 'octagon-alert',
    'exclamation-triangle': 'triangle-alert', 'exclamation-triangle-fill': 'triangle-alert', eye: 'eye', 'eye-slash': 'eye-off',
    file: 'file', 'file-earmark-bar-graph': 'file-chart-column', 'file-earmark-check': 'file-check',
    'file-earmark-excel': 'file-spreadsheet', 'file-earmark-pdf': 'file-text', 'file-earmark-person': 'file-user',
    'file-earmark-text': 'file-text', 'file-pdf': 'file-text', 'file-text': 'file-text', files: 'files',
    'filetype-csv': 'file-spreadsheet', flag: 'flag', folder: 'folder', 'folder2-open': 'folder-open', forward: 'forward',
    'forward-fill': 'forward', funnel: 'filter', gear: 'settings', 'geo-alt': 'map-pin', 'graph-up': 'chart-line',
    'graph-up-arrow': 'trending-up', 'grid-1x2': 'layout-dashboard', 'hand-thumbs-down': 'thumbs-down',
    'hand-thumbs-down-fill': ['thumbs-down', true], 'hand-thumbs-up': 'thumbs-up', 'hand-thumbs-up-fill': ['thumbs-up', true],
    hash: 'hash', hdd: 'hard-drive', 'hourglass-split': 'hourglass', inbox: 'inbox', 'info-circle': 'info',
    'info-circle-fill': 'info', 'journal-check': 'notebook-pen', 'journal-text': 'notebook-text', kanban: 'square-kanban',
    laptop: 'laptop', 'layout-sidebar-inset': 'panel-left', lightbulb: 'lightbulb', 'lightning-charge': 'zap', list: 'menu',
    'list-check': 'list-checks', lock: 'lock', 'lock-fill': 'lock', megaphone: 'megaphone', 'moon-stars': 'moon',
    mortarboard: 'graduation-cap', 'patch-check': 'badge-check', 'patch-check-fill': 'badge-check', pencil: 'pencil',
    'pencil-square': 'square-pen', people: 'users', 'people-fill': 'users', person: 'user', 'person-badge': 'id-card',
    'person-check': 'user-check', 'person-exclamation': 'user-x', 'person-plus': 'user-plus', 'person-vcard': 'contact',
    'person-workspace': 'monitor', 'pie-chart': 'chart-pie', 'pin-angle': 'pin', 'pin-angle-fill': ['pin', true],
    'pin-map': 'map-pinned', 'plus-circle': 'circle-plus', 'plus-lg': 'plus', printer: 'printer', 'reply-fill': 'reply',
    'rocket-takeoff-fill': 'rocket', save: 'save', search: 'search', 'search-heart-fill': 'search-check', send: 'send',
    'send-fill': 'send', 'shield-check': 'shield-check', 'shield-exclamation': 'shield-alert', 'shield-lock': 'shield',
    'slash-circle': 'ban', 'sort-down': 'arrow-down-wide-narrow', speedometer2: 'gauge', star: 'star',
    'star-fill': ['star', true], stars: 'sparkles', sun: 'sun', tags: 'tags', telephone: 'phone',
    'three-dots-vertical': 'ellipsis-vertical', trash: 'trash-2', trophy: 'trophy', unlock: 'lock-open', upload: 'upload',
    x: 'x', 'x-circle': 'circle-x', 'x-circle-fill': 'circle-x', 'x-lg': 'x',
};

const missing = [];
const rules = [];
for (const [bi, target] of Object.entries(MAP)) {
    const [name, filled] = Array.isArray(target) ? target : [target, false];
    let svg;
    try {
        svg = readFileSync(resolve(lucideDir, `${name}.svg`), 'utf8');
    } catch {
        missing.push(`${bi} -> ${name}`);
        continue;
    }
    svg = svg.replace(/<!--[\s\S]*?-->/g, '')
        .replace(/\s*class="[^"]*"/, '')
        .replace('stroke="currentColor"', 'stroke="#000"')
        .replace('stroke-width="2"', 'stroke-width="1.8"')
        .replace('fill="none"', filled ? 'fill="#000"' : 'fill="none"')
        .replace(/\s+/g, ' ')
        .trim();
    rules.push(`.bi-${bi}{--sb-icon:url("data:image/svg+xml,${encodeURIComponent(svg).replace(/%20/g, ' ').replace(/%3D/g, '=').replace(/%3A/g, ':').replace(/%2F/g, '/').replace(/%22/g, "'")}")}`);
}

if (missing.length) {
    console.error('Lucide icons not found:\n  ' + missing.join('\n  '));
    process.exit(1);
}

// Icons used by CSS itself (alerts, validation messages), as custom properties.
const cssIcon = (name) => {
    const svg = readFileSync(resolve(lucideDir, `${name}.svg`), 'utf8').replace(/<!--[\s\S]*?-->/g, '').replace(/\s*class="[^"]*"/, '')
        .replace('stroke="currentColor"', 'stroke="#000"').replace('stroke-width="2"', 'stroke-width="1.8"').replace(/\s+/g, ' ').trim();
    return `url("data:image/svg+xml,${encodeURIComponent(svg).replace(/%20/g, ' ').replace(/%3D/g, '=').replace(/%3A/g, ':').replace(/%2F/g, '/').replace(/%22/g, "'")}")`;
};
const rootIcons = `:root{--sb-icon-info:${cssIcon('info')};--sb-icon-success:${cssIcon('circle-check')};--sb-icon-alert:${cssIcon('circle-alert')}}`;

const selectors = Object.keys(MAP).map((bi) => `.bi-${bi}::before`).join(',\n');
const css = `/* Generated by scripts/build-icons.mjs from lucide-static. Do not edit by hand. */
/* Lucide line icons (24px grid, 1.8 stroke) drawn in the text color through a mask. */
${selectors} {
    content: "" !important;
    display: inline-block;
    width: 1em;
    height: 1em;
    vertical-align: -0.125em;
    background-color: currentColor;
    -webkit-mask: var(--sb-icon) no-repeat center / contain;
    mask: var(--sb-icon) no-repeat center / contain;
}
${rules.join('\n')}
${rootIcons}
`;
writeFileSync(resolve(root, 'public/css/sb-icons.css'), css);
console.log(`sb-icons.css: ${rules.length} icons`);
