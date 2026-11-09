/**
 * Drag a page from the sidebar onto My desk (pages/desk/⚡index). The page
 * lands as whatever stands for it on the Desk (Panels::itemForRoute): its
 * hand-made panel, its page card, or a button for it.
 *
 * While the link is carried over the Desk a "drop here" slot of the item's
 * own size opens where it would land: beside the card under the pointer (the
 * half of the card it is over says which side), or last below the cards.
 * The other cards make room exactly as the drop will save them (desk-rows.js
 * placedNew mirrors Panels::flush), so the preview is the result. Dropping
 * asks the Desk to keep that arrangement ($wire.dropItem); Esc or leaving
 * the Desk puts everything back. A page already on the Desk is ringed
 * instead, and dropping shows it. Dropped on the tab strip, the page opens as
 * a tab (desk-tabs.js).
 *
 * HTML drag and drop has no touch support: the "+" beside each sidebar link
 * (x-desk.side-add) and every page's "Add to desk" do the same by tap or key.
 */

import { flip, hostOf, lay, layoutOf, panelsIn } from './desk-grid';
import { placedNew } from './desk-rows';

const type = 'application/x-hc-desk-route';
let drag = null;

function itemsOnDesk() {
    try {
        return JSON.parse(document.querySelector('[data-desk-routes]')?.textContent ?? '{}');
    } catch {
        return {};
    }
}

function deskGrid() {
    return document.querySelector('[data-desk-drop]');
}

function slotFor(item, texts) {
    const slot = document.createElement('section');

    slot.className = 'dk-panel dk-drop-slot';
    slot.dataset.panel = item.item;
    slot.dataset.cols = item.cols;
    slot.dataset.rows = item.rows;
    slot.style.setProperty('--span', item.cols);
    slot.style.setProperty('--rows', item.rows);
    slot.setAttribute('aria-hidden', 'true');

    const label = document.createElement('span');

    label.className = 'dk-drop-slot-t';
    label.textContent = texts.replace('__I__', item.title);
    slot.append(label);

    return slot;
}

function cleanUp(restore) {
    if (! drag) {
        return;
    }

    const { grid, layout, slot, existing } = drag;

    existing?.classList.remove('dk-drop-ring');
    panelsIn(grid).forEach((card) => delete card.dataset.resizing);
    document.documentElement.classList.remove('dk-dropping');
    document.querySelector('[data-desk-tabs]')?.classList.remove('dk-tabs-droppable');

    if (slot && restore) {
        flip(grid, () => {
            slot.remove();
            lay(grid, layout);
        }, null);
    }

    drag = null;
}

document.addEventListener('dragstart', (event) => {
    const link = event.target instanceof Element ? event.target.closest('[data-desk-route]') : null;
    const grid = deskGrid();

    if (! link || ! grid || ! event.dataTransfer) {
        return;
    }

    const item = itemsOnDesk()[link.dataset.deskRoute];

    if (! item) {
        return;
    }

    event.dataTransfer.effectAllowed = 'copyLink';
    event.dataTransfer.setData(type, link.dataset.deskRoute);
    event.dataTransfer.setData('text/uri-list', link.href);
    event.dataTransfer.setData('text/plain', link.href);

    // A chip with the item's name rides under the pointer instead of the link's own picture.
    const chip = document.createElement('div');

    chip.className = 'dk-drag-chip dk-scope';
    chip.textContent = item.title;
    document.body.append(chip);
    event.dataTransfer.setDragImage(chip, 18, 18);
    setTimeout(() => chip.remove(), 0);

    drag = {
        route: link.dataset.deskRoute,
        href: link.href,
        item,
        grid,
        layout: layoutOf(grid),
        placed: null,
        target: undefined,
        place: null,
        slot: null,
        existing: item.placed ? grid.querySelector(`:scope > [data-panel="${CSS.escape(item.item)}"]`) : null,
        text: grid.dataset.dropText ?? '__I__',
    };

    // The cards change cells while the slot moves: they fit their rows again once the drop is saved, not on every step.
    panelsIn(grid).forEach((card) => (card.dataset.resizing = ''));
    document.documentElement.classList.add('dk-dropping');
    document.querySelector('[data-desk-tabs]')?.classList.add('dk-tabs-droppable');
});

document.addEventListener('dragover', (event) => {
    if (! drag || ! (event.target instanceof Element)) {
        return;
    }

    if (event.target.closest('.dk-tabs-bar, [data-desk-tabframes]')) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'link';

        return;
    }

    const desk = event.target.closest('[data-desk]');

    if (! desk || document.documentElement.classList.contains('dk-tab-active')) {
        return;
    }

    event.preventDefault();
    event.dataTransfer.dropEffect = 'copy';

    if (drag.existing) {
        drag.existing.classList.add('dk-drop-ring');

        return;
    }

    // Over the slot itself the drop stays where the slot is.
    if (event.target.closest('.dk-drop-slot')) {
        return;
    }

    const card = event.target.closest('[data-desk-grid] > .dk-panel');
    let target = null;
    let place = 'after';

    if (card) {
        const box = card.getBoundingClientRect();
        const along = getComputedStyle(drag.grid).direction === 'rtl' ? box.right - event.clientX : event.clientX - box.left;

        target = card.dataset.panel;
        place = along < box.width / 2 ? 'before' : 'after';
    }

    if (target === drag.target && place === drag.place) {
        return;
    }

    drag.target = target;
    drag.place = place;
    drag.placed = placedNew(drag.layout, { panel: drag.item.item, cols: drag.item.cols, rows: drag.item.rows }, target, place);

    const { grid, placed } = drag;

    flip(grid, () => {
        if (! drag.slot) {
            drag.slot = slotFor(drag.item, drag.text);
            grid.append(drag.slot);
        }

        lay(grid, placed);
    }, null);
});

document.addEventListener('drop', (event) => {
    if (! drag || ! (event.target instanceof Element)) {
        return;
    }

    if (event.target.closest('.dk-tabs-bar, [data-desk-tabframes]')) {
        event.preventDefault();
        window.hcDeskTabs?.open(drag.href);
        cleanUp(true);

        return;
    }

    if (! event.target.closest('[data-desk]')) {
        return;
    }

    event.preventDefault();

    const { grid, item, slot, existing } = drag;

    if (existing) {
        existing.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        existing.classList.add('dk-drop-found');
        setTimeout(() => existing.classList.remove('dk-drop-found'), 1200);
        cleanUp(false);

        return;
    }

    const placed = drag.placed ?? placedNew(drag.layout, { panel: item.item, cols: item.cols, rows: item.rows });
    const host = hostOf(slot ?? grid.querySelector(':scope > .dk-panel') ?? grid);

    slot?.classList.add('dk-drop-landing');
    drag.dropped = true;
    cleanUp(false);

    // The slot holds the place until the Desk draws the real card there.
    Promise.resolve(host?.dropItem(item.item, placed.map(({ panel, cols, rows }) => ({ panel, cols, rows })))).finally(() => slot?.remove());
});

document.addEventListener('dragend', () => cleanUp(true));
