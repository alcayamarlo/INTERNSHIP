// Live updates: find what changed when a live region is swapped in, so it can glow
// briefly (sb-motion.css, .sb-live-changed). Items are table rows, list items, or
// anything marked data-live-item; they are matched by their visible text, so
// attributes added by scripts (labels, tooltips) do not count as changes.

const ITEM_SELECTOR = '[data-live-item], tbody > tr, .list-group > .list-group-item, ul > li, ol > li';
const GLOW_MS = 2000;

// More changes than this is a reshuffle (new sort, new page), not an update: show none.
export const MAX_HIGHLIGHTS = 12;

export const itemKey = (element) => element.textContent.replace(/\s+/g, ' ').trim();

/** Outermost items only: a list inside a changed row is part of that row. */
export const liveItems = (root) => [...root.querySelectorAll(ITEM_SELECTOR)].filter((item) => {
    const parent = item.parentElement?.closest(ITEM_SELECTOR);
    return !parent || !root.contains(parent);
});

/** Positions in newKeys with no matching entry in oldKeys (duplicates counted). */
export function changedIndexes(oldKeys, newKeys, max = MAX_HIGHLIGHTS) {
    const remaining = new Map();
    oldKeys.forEach((key) => remaining.set(key, (remaining.get(key) || 0) + 1));

    const changed = [];
    newKeys.forEach((key, index) => {
        const count = remaining.get(key) || 0;
        if (count > 0) {
            remaining.set(key, count - 1);
        } else if (key !== '') {
            changed.push(index);
        }
    });

    return changed.length > max ? [] : changed;
}

const glow = (element) => {
    element.classList.add('sb-live-changed');
    setTimeout(() => element.classList.remove('sb-live-changed'), GLOW_MS);
};

/** Marks the parts of freshRoot that differ from oldRoot. Call before swapping freshRoot in. */
export function markLiveChanges(oldRoot, freshRoot) {
    const freshItems = liveItems(freshRoot);

    // A region with no list in it (a count, a summary line): glow the whole region.
    if (!freshItems.length) {
        if (itemKey(oldRoot) !== itemKey(freshRoot)) glow(freshRoot);
        return;
    }

    changedIndexes(liveItems(oldRoot).map(itemKey), freshItems.map(itemKey))
        .forEach((index) => glow(freshItems[index]));
}
