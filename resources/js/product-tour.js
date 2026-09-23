/**
 * Context-aware product tour.
 *
 * Steps come from config/tours.php, resolved server-side for the page being
 * rendered (App\Support\ProductTour) and handed to <x-tour.overlay>. This file
 * only knows how to walk through whatever steps it is given:
 *
 *   - a step's `target` is matched against data-tour="..." attributes;
 *   - targets that are missing or hidden when the tour starts are skipped, so
 *     one config serves every role and every empty/non-empty data state;
 *   - `placement` start/end follow the document direction, so a step that
 *     sits beside a card flips sides in Arabic without a second config.
 *
 * The "Take a tour" button (<x-tour.button>) lives in the Livewire header and
 * never talks to the overlay directly: it dispatches `tour-start` and reads
 * `$store.tour` to decide whether this page has a tour at all.
 */

const PAD = 8; // space between the highlighted element and the spotlight edge
const RADIUS = 12; // spotlight corner radius
const GAP = 14; // space between the spotlight and the card
const MARGIN = 12; // minimum distance between the card and the viewport edge

const SEEN_PREFIX = 'hc-tour-seen:';

// localStorage can throw (private windows, blocked site data). The tour must
// still work without it — it only drives the "new" dot on the button.
function readSeen(id) {
    try {
        return window.localStorage.getItem(SEEN_PREFIX + id) === '1';
    } catch {
        return true;
    }
}

function writeSeen(id) {
    try {
        window.localStorage.setItem(SEEN_PREFIX + id, '1');
    } catch {
        // Nothing to do: the dot simply shows again next time.
    }
}

function findTarget(target) {
    return target ? document.querySelector(`[data-tour="${CSS.escape(target)}"]`) : null;
}

function isVisible(el) {
    if (!el || el.getClientRects().length === 0) return false;
    const style = window.getComputedStyle(el);
    return style.visibility !== 'hidden' && style.display !== 'none';
}

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

document.addEventListener('alpine:init', () => {
    window.Alpine.store('tour', {
        available: false,
        seen: true,
    });

    window.Alpine.data('productTour', (tour) => ({
        open: false,
        steps: [],
        index: 0,
        rect: null, // padded viewport rect of the current target, or null for a centred step
        side: null, // side of the target the card sits on: top | bottom | left | right | null
        card: { top: 0, left: 0, arrow: 0 },
        returnFocus: null,
        frame: null,
        settled: false, // false until the card's first position is known, so it does not fly in from 0,0

        init() {
            // Always rendered (with a null tour when the page has none), so a
            // wire:navigate to a page without a tour resets the button too.
            const store = window.Alpine.store('tour');
            store.available = Boolean(tour && tour.steps.length);
            store.seen = !tour || readSeen(tour.id);

            this.onViewportChange = () => this.scheduleMeasure();
        },

        destroy() {
            this.unbind();
        },

        // Looked up rather than read from $refs: the card lives inside an
        // x-if template, and refs registered there are not visible to this
        // component's methods.
        cardEl() {
            return this.$root.querySelector('[data-tour-card]');
        },

        get step() {
            return this.steps[this.index] ?? null;
        },

        get isFirst() {
            return this.index === 0;
        },

        get isLast() {
            return this.index === this.steps.length - 1;
        },

        get rtl() {
            return document.documentElement.dir === 'rtl';
        },

        start() {
            if (this.open || !tour) return;

            this.steps = tour.steps.filter((step) => !step.target || isVisible(findTarget(step.target)));
            if (this.steps.length === 0) return;

            this.returnFocus = document.activeElement;
            this.index = 0;
            this.rect = null;
            this.settled = false;
            this.open = true;

            writeSeen(tour.id);
            window.Alpine.store('tour').seen = true;

            window.addEventListener('resize', this.onViewportChange);
            window.addEventListener('scroll', this.onViewportChange, true);

            this.show();
        },

        close() {
            if (!this.open) return;

            this.open = false;
            this.unbind();

            const focusBack = this.returnFocus;
            this.returnFocus = null;
            if (focusBack && document.contains(focusBack)) focusBack.focus({ preventScroll: true });
        },

        next() {
            if (this.isLast) return this.close();
            this.index++;
            this.show();
        },

        previous() {
            if (this.isFirst) return;
            this.index--;
            this.show();
        },

        unbind() {
            window.removeEventListener('resize', this.onViewportChange);
            window.removeEventListener('scroll', this.onViewportChange, true);
            if (this.frame) cancelAnimationFrame(this.frame);
            this.frame = null;
        },

        show() {
            const el = findTarget(this.step?.target);
            if (el) this.scrollIntoView(el);

            this.measure();

            // A smooth scroll fires scroll events, which re-measure on their
            // own; this catches the end state if the browser skipped them.
            setTimeout(() => this.scheduleMeasure(), 450);

            this.$nextTick(() => this.cardEl()?.focus({ preventScroll: true }));
        },

        // Brings the target into view clear of the fixed navbar, leaving room
        // for the card above or below it when the two fit on screen together.
        // Targets too tall for that are aligned to the top, so their start
        // (usually the heading) stays visible and the card overlaps the rest.
        scrollIntoView(el) {
            const r = el.getBoundingClientRect();
            const header = document.getElementById('app-navbar')?.offsetHeight ?? 0;
            const top = header + MARGIN;
            const bottom = window.innerHeight - MARGIN;
            const available = bottom - top;
            const card = (this.cardEl()?.offsetHeight || 220) + GAP;
            const height = r.height + PAD * 2;

            let delta;

            if (height + card <= available) {
                const roomAbove = r.top - PAD - top >= card;
                const roomBelow = bottom - (r.bottom + PAD) >= card;
                const cw = (this.cardEl()?.offsetWidth || 352) + GAP;
                const roomBeside = window.innerWidth >= 640
                    && (r.left - PAD - cw >= MARGIN || r.right + PAD + cw <= window.innerWidth - MARGIN);
                const inView = r.top - PAD >= top && r.bottom + PAD <= bottom;

                if (inView && (roomAbove || roomBelow || roomBeside)) return;

                // Centre target + card as one block, card below.
                delta = r.top - PAD - top - (available - height - card) / 2;
            } else {
                if (r.top - PAD >= top && r.bottom + PAD <= bottom) return;

                delta = height > available * 0.6
                    ? r.top - PAD - top
                    : r.top - top - (available - r.height) / 2;
            }

            window.scrollBy({ top: delta, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
        },

        scheduleMeasure() {
            if (!this.open || this.frame) return;
            this.frame = requestAnimationFrame(() => {
                this.frame = null;
                this.measure();
            });
        },

        measure() {
            if (!this.open) return;

            // Re-queried every time: Livewire morphs can replace the element.
            const el = findTarget(this.step?.target);

            if (el && isVisible(el)) {
                const r = el.getBoundingClientRect();
                this.rect = {
                    x: r.left - PAD,
                    y: r.top - PAD,
                    w: r.width + PAD * 2,
                    h: r.height + PAD * 2,
                };
            } else {
                this.rect = null;
            }

            this.$nextTick(() => this.settle());
        },

        placeCard() {
            const cardEl = this.cardEl();
            if (!cardEl) return;

            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const cw = cardEl.offsetWidth;
            const ch = cardEl.offsetHeight;
            const clamp = (value, min, max) => Math.min(Math.max(value, min), Math.max(min, max));

            if (!this.rect) {
                this.side = null;
                this.card = { top: (vh - ch) / 2, left: (vw - cw) / 2, arrow: 0 };
                return;
            }

            const { x, y, w, h } = this.rect;

            const fits = {
                bottom: y + h + GAP + ch <= vh - MARGIN,
                top: y - GAP - ch >= MARGIN,
                left: x - GAP - cw >= MARGIN,
                right: x + w + GAP + cw <= vw - MARGIN,
            };

            // start/end are logical; resolve them against the reading direction.
            const physical = {
                top: 'top',
                bottom: 'bottom',
                start: this.rtl ? 'right' : 'left',
                end: this.rtl ? 'left' : 'right',
            };
            const preferred = physical[this.step.placement] ?? 'bottom';
            const opposite = { top: 'bottom', bottom: 'top', left: 'right', right: 'left' }[preferred];
            const order = [preferred, opposite, 'bottom', 'top', 'right', 'left'];

            // On phones a side-by-side card never has room; skip straight to vertical.
            const side = order.find((s) => fits[s] && !(vw < 640 && (s === 'left' || s === 'right')));

            if (!side) {
                // The target fills the screen: pin the card to the bottom, over it.
                this.side = null;
                this.card = { top: vh - ch - MARGIN, left: clamp(x + w / 2 - cw / 2, MARGIN, vw - cw - MARGIN), arrow: 0 };
                return;
            }

            let top;
            let left;

            if (side === 'top' || side === 'bottom') {
                left = clamp(x + w / 2 - cw / 2, MARGIN, vw - cw - MARGIN);
                top = side === 'bottom' ? y + h + GAP : y - GAP - ch;
            } else {
                top = clamp(y + h / 2 - ch / 2, MARGIN, vh - ch - MARGIN);
                left = side === 'right' ? x + w + GAP : x - GAP - cw;
            }

            // Arrow offset along the card edge, pointing at the target's centre.
            const arrow = side === 'top' || side === 'bottom'
                ? clamp(x + w / 2 - left, 20, cw - 20)
                : clamp(y + h / 2 - top, 20, ch - 20);

            this.side = side;
            this.card = { top, left, arrow };
        },

        // Wraps the placement so the first one lands without a transition.
        settle() {
            this.placeCard();
            if (!this.settled) requestAnimationFrame(() => { this.settled = true; });
        },

        // The dimmed layer is one full-screen element with the spotlight cut out
        // (even-odd fill), so the backdrop blur applies everywhere except the
        // target. A centred step collapses the hole to a point in the middle,
        // which keeps the path shape identical and lets clip-path animate.
        get clipPath() {
            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const { x, y, w, h } = this.rect ?? { x: vw / 2, y: vh / 2, w: 0, h: 0 };
            const r = Math.min(RADIUS, w / 2, h / 2);

            const hole = `M${x + r} ${y}H${x + w - r}A${r} ${r} 0 0 1 ${x + w} ${y + r}`
                + `V${y + h - r}A${r} ${r} 0 0 1 ${x + w - r} ${y + h}`
                + `H${x + r}A${r} ${r} 0 0 1 ${x} ${y + h - r}`
                + `V${y + r}A${r} ${r} 0 0 1 ${x + r} ${y}Z`;

            return `path(evenodd, "M0 0H${vw}V${vh}H0Z ${hole}")`;
        },

        get ringStyle() {
            // Collapse to the centre (not 0,0) so moving to or from a centred
            // step grows the ring out of the middle instead of a corner.
            const { x, y, w, h } = this.rect ?? { x: window.innerWidth / 2, y: window.innerHeight / 2, w: 0, h: 0 };
            return `top:${y}px;left:${x}px;width:${w}px;height:${h}px;border-radius:${RADIUS}px;opacity:${this.rect ? 1 : 0}`;
        },

        get cardStyle() {
            return `top:${this.card.top}px;left:${this.card.left}px`;
        },

        get arrowStyle() {
            const offset = `${this.card.arrow - 6}px`;
            switch (this.side) {
                case 'bottom': return `top:-6px;left:${offset}`;
                case 'top': return `bottom:-6px;left:${offset}`;
                case 'right': return `left:-6px;top:${offset}`;
                case 'left': return `right:-6px;top:${offset}`;
                default: return 'display:none';
            }
        },

        onKeydown(event) {
            if (!this.open) return;

            const forward = this.rtl ? 'ArrowLeft' : 'ArrowRight';
            const back = this.rtl ? 'ArrowRight' : 'ArrowLeft';

            if (event.key === 'Escape') {
                event.preventDefault();
                this.close();
            } else if (event.key === forward) {
                event.preventDefault();
                this.next();
            } else if (event.key === back) {
                event.preventDefault();
                this.previous();
            } else if (event.key === 'Tab') {
                this.trapFocus(event);
            }
        },

        // Keeps Tab cycling inside the card while the rest of the page is inert.
        trapFocus(event) {
            const card = this.cardEl();
            if (!card) return;

            const focusable = [...card.querySelectorAll('button:not([disabled])')];
            if (focusable.length === 0) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            const active = document.activeElement;

            if (!card.contains(active)) {
                event.preventDefault();
                first.focus();
            } else if (event.shiftKey && (active === first || active === card)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && active === last) {
                event.preventDefault();
                first.focus();
            }
        },
    }));
});

// A Livewire re-render can move or resize the highlighted element without any
// scroll or resize event, so re-measure after every commit.
window.Livewire?.hook('commit', ({ succeed }) => {
    succeed(() => queueMicrotask(() => window.dispatchEvent(new CustomEvent('tour-reflow'))));
});
