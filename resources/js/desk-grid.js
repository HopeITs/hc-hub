/**
 * The Desk's cell grid (pages/desk/⚡index, components/desk/panel). Its rows
 * are always flush (desk-rows.js): the cards fill every row's twelve columns
 * and share its height, so there is never a hole between them.
 *
 * Move: the whole panel header is the handle. A mouse picks the panel up
 * after a few pixels of travel; touch and pen pick it up after a 400ms press
 * and hold (so a swipe still scrolls). Over the middle of another card it
 * joins that card's row, before or after it; over that card's top or bottom
 * band it gets a row of its own above or below. The other cards slide and
 * resize (FLIP) to show exactly where it will land; dropping asks the host
 * page to arrangePanels(). Esc puts everything back.
 *
 * Resize: every card can be pulled from all four edges and the corner
 * (desk-rows.js resized). An inline edge between cards trades columns with
 * the neighbour; one on the Desk's side, pulled inward, lets the next card
 * slide into the freed columns. The top edge trades height with the row
 * above, and on the first row grows the row upward: the page scrolls by the
 * same amount, so the card's bottom stays where it was on screen. The bottom
 * edge makes the whole row taller. The cards snap to whole cells as you pull,
 * with a "6 × 4" badge; releasing asks the host page to arrangePanels(). Esc
 * cancels. RTL-aware throughout.
 *
 * Delegated from document so panels Livewire re-renders keep working.
 */

import { joined, resized, rowsIn, sameLayout } from './desk-rows';

const moveThreshold = 6;
const holdDelay = 400;
const interactive = 'a, button, input, select, textarea, label, [role="menu"], [contenteditable], [data-panel-search]';

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isPhone = () => window.matchMedia('(max-width: 760px)').matches;
/* Below 1280px widths collapse to fixed steps (desk-grid.css), so only heights are pulled there. */
const isNarrow = () => window.matchMedia('(max-width: 1279px)').matches;

function panelOf(target) {
    return target instanceof Element ? target.closest('[data-desk-grid] > .dk-panel') : null;
}

function panelsIn(grid) {
    return [...grid.querySelectorAll(':scope > .dk-panel')];
}

/** The grid's layout as desk-rows.js reads it: its cards in order, at their current cells. */
function layoutOf(grid) {
    return panelsIn(grid).map((panel) => ({ panel: panel.dataset.panel, cols: Number(panel.dataset.cols) || 4, rows: Number(panel.dataset.rows) || 4 }));
}

function setCells(panel, cols, rows) {
    panel.style.setProperty('--span', cols);
    panel.style.setProperty('--rows', rows);
    panel.dataset.cols = cols;
    panel.dataset.rows = rows;
}

/**
 * Put the grid's cards in the layout's order and at its cells. They are
 * re-inserted before whatever follows the last card, so Livewire's loop
 * markers around them stay where they were.
 */
function lay(grid, layout) {
    const cards = panelsIn(grid);
    const byId = new Map(cards.map((panel) => [panel.dataset.panel, panel]));
    const anchor = cards.at(-1)?.nextSibling ?? null;

    layout.forEach(({ panel, cols, rows }) => {
        const card = byId.get(panel);

        if (card) {
            grid.insertBefore(card, anchor);
            setCells(card, cols, rows);
        }
    });
}

/**
 * The Desk page's Livewire component. The panel sits inside its own component
 * too, so start from the grid (owned by the page). Note the doubled backslash:
 * '[wire\:id]' would collapse to the invalid selector '[wire:id]' and closest()
 * would throw.
 */
function hostOf(panel) {
    const host = panel.closest('[data-desk-grid]')?.closest('[wire\\:id]');

    return host ? window.Livewire?.find(host.getAttribute('wire:id')) : null;
}

function metrics(grid) {
    const styles = getComputedStyle(grid);
    const columns = styles.gridTemplateColumns.split(' ').length || 12;
    const colGap = parseFloat(styles.columnGap) || 0;
    const rowGap = parseFloat(styles.rowGap) || colGap;
    const rowUnit = parseFloat(styles.gridAutoRows) || 104;
    const column = (grid.clientWidth - colGap * (columns - 1)) / columns;

    return { columns, colGap, rowGap, rowUnit, column, rtl: styles.direction === 'rtl' };
}

function seenHint() {
    try {
        window.localStorage.setItem('dk-hint-seen', '1');
    } catch {
        // Storage blocked: the hint simply shows again next visit.
    }
}

/* ── Move ─────────────────────────────────────────────────────────── */

let move = null;
let swallowClick = false;

/** Slide every panel but the carried one from where it was to where it now sits. */
function flip(grid, mutate, carried = move?.panel) {
    const others = panelsIn(grid).filter((panel) => panel !== carried);
    const before = new Map(others.map((panel) => [panel, panel.getBoundingClientRect()]));

    mutate();

    if (reducedMotion()) {
        return;
    }

    others.forEach((panel) => {
        panel.style.transition = 'none';
        panel.style.transform = '';
    });

    others.forEach((panel) => {
        const was = before.get(panel);
        const now = panel.getBoundingClientRect();
        const dx = was.left - now.left;
        const dy = was.top - now.top;

        if (dx || dy) {
            panel.style.transform = `translate(${dx}px, ${dy}px)`;
        }
    });

    grid.getBoundingClientRect();

    others.forEach((panel) => {
        panel.style.transition = 'transform .32s cubic-bezier(.2, .9, .25, 1.12)';
        panel.style.transform = '';
    });
}

/** Keep the carried panel under the pointer wherever its slot moved to, even when the slot made it smaller. */
function follow(x, y) {
    const { panel } = move;

    panel.style.transform = '';
    const slot = panel.getBoundingClientRect();
    const grabX = Math.min(move.grabX, slot.width - 24);
    const grabY = Math.min(move.grabY, slot.height - 24);

    panel.style.transform = `translate(${x - grabX - slot.left}px, ${y - grabY - slot.top}px) scale(1.02)`;
}

function pickUp() {
    const { panel, grid } = move;

    move.active = true;
    move.layout = layoutOf(grid);
    move.placed = move.layout;
    panel.classList.add('dk-dragging');
    grid.classList.add('dk-moving');
    document.documentElement.classList.add('dk-moving-doc');
    follow(move.x, move.y);
    autoScroll();
}

/**
 * Where a drop at (x, y) would put the card: over another card's top or
 * bottom band a row of its own, over its middle that card's row, before or
 * after it by which half the pointer is on.
 */
function reflow(x, y) {
    const { panel, grid } = move;

    panel.style.pointerEvents = 'none';
    const target = panelOf(document.elementFromPoint(x, y));

    panel.style.pointerEvents = '';

    if (! target || target === panel || target.closest('[data-desk-grid]') !== grid) {
        return;
    }

    const box = target.getBoundingClientRect();
    const band = Math.min(box.height / 4, 80);
    const { rtl } = metrics(grid);
    const along = rtl ? box.right - x : x - box.left;
    let place = along > box.width / 2 ? 'after' : 'before';

    if (y < box.top + band) {
        place = 'above';
    } else if (y > box.bottom - band) {
        place = 'below';
    }

    const placed = joined(move.layout, panel.dataset.panel, target.dataset.panel, place);

    if (sameLayout(placed, move.placed)) {
        return;
    }

    move.placed = placed;
    flip(grid, () => lay(grid, placed));
}

function autoScroll() {
    if (! move?.active) {
        return;
    }

    const edge = 72;
    const { y } = move;
    const speed = y < edge ? -(edge - y) / 3 : y > window.innerHeight - edge ? (y - (window.innerHeight - edge)) / 3 : 0;

    if (speed) {
        window.scrollBy(0, speed);
        follow(move.x, move.y);
    }

    move.frame = requestAnimationFrame(autoScroll);
}

function settle(panel) {
    panel.style.transition = reducedMotion() ? '' : 'transform .26s cubic-bezier(.2, .9, .25, 1.12)';
    panel.style.transform = '';
    setTimeout(() => {
        panel.style.transition = '';
    }, 300);
}

function endMove(commit) {
    if (! move) {
        return;
    }

    const current = move;

    move = null;
    clearTimeout(current.hold);

    if (! current.active) {
        return;
    }

    cancelAnimationFrame(current.frame);
    swallowClick = true;
    setTimeout(() => {
        swallowClick = false;
    }, 0);

    const { panel, grid, layout, placed } = current;

    panel.classList.remove('dk-dragging');
    grid.classList.remove('dk-moving');
    document.documentElement.classList.remove('dk-moving-doc');

    if (! commit || sameLayout(layout, placed)) {
        flip(grid, () => lay(grid, layout));
        settle(panel);

        return;
    }

    settle(panel);
    seenHint();
    save(grid, layout, placed);
}

/**
 * Ask the host page to keep the new arrangement, then let every card whose
 * cells changed fit its rows again (desk-filters.js).
 */
function save(grid, before, after) {
    const changed = panelsIn(grid).filter((panel) => {
        const was = before.find((entry) => entry.panel === panel.dataset.panel);
        const now = after.find((entry) => entry.panel === panel.dataset.panel);

        return ! was || ! now || was.cols !== now.cols || was.rows !== now.rows;
    });
    const saving = hostOf(grid.querySelector(':scope > .dk-panel'))?.arrangePanels(after.map(({ panel, cols, rows }) => ({ panel, cols, rows })));

    Promise.resolve(saving).finally(() => {
        panelsIn(grid).forEach((card) => delete card.dataset.resizing);
        changed.forEach(fitAgain);
    });
}

document.addEventListener('pointerdown', (event) => {
    const handle = event.target instanceof Element ? event.target.closest('[data-drag]') : null;
    const panel = handle ? panelOf(handle) : null;

    if (! panel || event.button !== 0 || move || resize || event.target.closest(interactive)) {
        return;
    }

    const grid = panel.closest('[data-desk-grid]');
    const box = panel.getBoundingClientRect();

    move = {
        panel,
        grid,
        pointer: event.pointerType,
        startX: event.clientX,
        startY: event.clientY,
        x: event.clientX,
        y: event.clientY,
        grabX: event.clientX - box.left,
        grabY: event.clientY - box.top,
        active: false,
        hold: 0,
        frame: 0,
    };

    if (event.pointerType !== 'mouse') {
        move.hold = setTimeout(() => move && ! move.active && pickUp(), holdDelay);
    }
});

document.addEventListener('pointermove', (event) => {
    if (move) {
        move.x = event.clientX;
        move.y = event.clientY;
        const travelled = Math.hypot(event.clientX - move.startX, event.clientY - move.startY);

        if (! move.active) {
            if (move.pointer === 'mouse' && travelled > moveThreshold) {
                pickUp();
            } else if (move.pointer !== 'mouse' && travelled > 10) {
                endMove(false);
            }

            return;
        }

        event.preventDefault();
        reflow(event.clientX, event.clientY);
        follow(event.clientX, event.clientY);
    }
});

// Once a panel is held on touch, the finger moves it instead of the page.
document.addEventListener('touchmove', (event) => {
    if (move?.active || resize) {
        event.preventDefault();
    }
}, { passive: false });

document.addEventListener('click', (event) => {
    if (swallowClick) {
        event.preventDefault();
        event.stopPropagation();
        swallowClick = false;
    }
}, true);

document.addEventListener('contextmenu', (event) => {
    if (move) {
        event.preventDefault();
    }
});

/* ── Resize ───────────────────────────────────────────────────────── */

let resize = null;

/**
 * While data-resizing is on, the panel does not fit its rows (desk-filters.js):
 * that round trip would re-render it at its saved size mid-pull. Lift it once
 * the new size is saved (or dropped) and let the panel fit again.
 */
function fitAgain(panel) {
    delete panel.dataset.resizing;
    panel.dispatchEvent(new CustomEvent('dk-panel-resized'));
}

document.addEventListener('pointerdown', (event) => {
    const handle = event.target instanceof Element ? event.target.closest('[data-resize]') : null;
    const panel = handle ? panelOf(handle) : null;

    if (! panel || event.button !== 0 || isPhone()) {
        return;
    }

    const grid = panel.closest('[data-desk-grid]');
    const layout = layoutOf(grid);
    const index = panelsIn(grid).indexOf(panel);
    const edge = handle.dataset.resize;

    event.preventDefault();
    event.stopPropagation();

    if (isNarrow() && ! ['top', 'bottom'].includes(edge)) {
        return;
    }

    const badge = document.createElement('b');

    badge.className = 'dk-resize-badge';
    badge.textContent = `${layout[index].cols} × ${layout[index].rows}`;
    panel.append(badge);

    resize = {
        panel,
        grid,
        badge,
        edge,
        index,
        layout,
        placed: layout,
        firstRow: rowsIn(layout)[0].includes(index),
        scrolled: 0,
        m: metrics(grid),
        startX: event.clientX,
        startY: event.clientY,
    };

    try {
        handle.setPointerCapture(event.pointerId);
    } catch {
        // Capture is a nicety; document listeners still see the drag.
    }

    panelsIn(grid).forEach((card) => (card.dataset.resizing = ''));
    panel.classList.add('dk-resizing');
    document.documentElement.classList.add('dk-resizing-doc');
    document.documentElement.dataset.resizeEdge = edge;
}, true);

document.addEventListener('pointermove', (event) => {
    if (! resize) {
        return;
    }

    event.preventDefault();

    const { panel, grid, badge, edge, index, layout, m, startX, startY } = resize;
    const stepsX = Math.round(((event.clientX - startX) * (m.rtl ? -1 : 1)) / (m.column + m.colGap));
    const stepsY = Math.round((event.clientY - startY) / (m.rowUnit + m.rowGap));
    const placed = resized(layout, index, edge, stepsX, stepsY);

    if (sameLayout(placed, resize.placed)) {
        return;
    }

    const grown = placed[index].rows - resize.placed[index].rows;

    resize.placed = placed;
    badge.textContent = `${placed[index].cols} × ${placed[index].rows}`;
    flip(grid, () => lay(grid, placed), panel);

    if (edge === 'top' && resize.firstRow && grown) {
        scrollWith(grown * (m.rowUnit + m.rowGap));
    }
});

/** The first row grown from its top: scroll by what it grew, so its bottom stays where it was on screen and its top follows the pointer. */
function scrollWith(by) {
    const before = window.scrollY;

    window.scrollTo({ top: before + by, behavior: 'instant' });
    resize.scrolled += window.scrollY - before;
}

function endResize(commit) {
    if (! resize) {
        return;
    }

    const current = resize;
    const { panel, grid, badge, layout, placed } = current;

    resize = null;
    badge.remove();
    panel.classList.remove('dk-resizing');
    document.documentElement.classList.remove('dk-resizing-doc');
    delete document.documentElement.dataset.resizeEdge;

    if (! commit || sameLayout(layout, placed)) {
        flip(grid, () => lay(grid, layout), panel);
        window.scrollTo({ top: window.scrollY - current.scrolled, behavior: 'instant' });
        panelsIn(grid).forEach(fitAgain);

        return;
    }

    seenHint();
    save(grid, layout, placed);
}

/* ── Shared release / cancel ──────────────────────────────────────── */

document.addEventListener('pointerup', () => {
    endResize(true);
    endMove(true);
});

document.addEventListener('pointercancel', () => {
    endResize(false);
    endMove(false);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && (resize || move?.active)) {
        event.stopPropagation();
        endResize(false);
        endMove(false);
    }
}, true);
