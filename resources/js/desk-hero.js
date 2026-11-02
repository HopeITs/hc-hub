/**
 * The Desk hero (pages/desk/⚡index):
 * - deskDensity: Comfortable / Compact, kept in this browser (dk-density) and
 *   applied as <html data-desk-density>; desk-cards.css shrinks the row unit
 *   and paddings, and desk-filters.js refits rows through its ResizeObserver.
 * - deskHint: the first-visit "drag to move · pull edges to resize" bubble,
 *   dismissed for good in this browser (dk-hint-seen).
 * Storage may be blocked, so every read and write is guarded.
 */
const densityKey = 'dk-density';
const hintKey = 'dk-hint-seen';

function read(key) {
    try {
        return window.localStorage.getItem(key);
    } catch {
        return null;
    }
}

function write(key, value) {
    try {
        window.localStorage.setItem(key, value);
    } catch {
        // Not kept (private window): applies until the page reloads.
    }
}

function readDensity() {
    return read(densityKey) === 'compact' ? 'compact' : 'comfortable';
}

function applyDensity(density) {
    document.documentElement.dataset.deskDensity = density;
}

applyDensity(readDensity());

document.addEventListener('alpine:init', () => {
    window.Alpine.data('deskDensity', () => ({
        density: readDensity(),

        pick(density) {
            this.density = density === 'compact' ? 'compact' : 'comfortable';
            applyDensity(this.density);
            write(densityKey, this.density);
        },
    }));

    window.Alpine.data('deskHint', () => ({
        shown: read(hintKey) !== '1',

        dismiss() {
            this.shown = false;
            write(hintKey, '1');
        },
    }));
});
