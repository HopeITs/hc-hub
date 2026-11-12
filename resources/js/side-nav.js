/**
 * The application sidebar (components/desk/side-nav).
 *
 * Desktop: an icon rail that expands to labels. The state lives on
 * <html class="dk-nav-open"> (set before first paint by partials/head from
 * localStorage "hc-nav"), so wire:navigate, which keeps <html>, never
 * flashes the other state. On the rail, hovering a group shows its links in a
 * fly-out beside the rail; hovering a plain link shows its label in a tip.
 * Clicking a group on the rail expands the sidebar and opens that group.
 *
 * Expanded, each group folds open in place; the groups a person opened are
 * remembered (localStorage "hc-nav-groups"), and the group holding the
 * current page always opens.
 *
 * Pins: the star beside a link pins that page to the top of the sidebar
 * (POST nav/pins, stored on the user). The sidebar has no keyboard shortcuts.
 *
 * Below 900px the rail is hidden; the top bar's menu button lays the full
 * sidebar over the page (<html class="dk-nav-mobile">). Esc, the scrim or
 * following a link closes it.
 */

const desktop = () => window.matchMedia('(min-width: 900px)').matches;

function closeMobile() {
    document.documentElement.classList.remove('dk-nav-mobile');
}

function readGroups() {
    try {
        const saved = JSON.parse(localStorage.getItem('hc-nav-groups') ?? '[]');

        return Array.isArray(saved) ? saved : [];
    } catch {
        return [];
    }
}

window.hcSideNavClose = closeMobile;

window.hcSideNavOpenMobile = () => {
    document.documentElement.classList.add('dk-nav-mobile');
};

document.addEventListener('livewire:navigating', closeMobile);

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.documentElement.classList.contains('dk-nav-mobile')) {
        closeMobile();
    }
});

document.addEventListener('alpine:init', () => {
    window.Alpine.store('hcNav', {
        groups: readGroups(),

        isOpen(group) {
            return this.groups.includes(group);
        },

        remember(group, open) {
            this.groups = open ? [...new Set([...this.groups, group])] : this.groups.filter((name) => name !== group);

            try {
                localStorage.setItem('hc-nav-groups', JSON.stringify(this.groups));
            } catch {
                // Without storage the groups simply start closed next time.
            }
        },
    });

    window.Alpine.data('hcSideNav', (config = {}) => ({
        expanded: document.documentElement.classList.contains('dk-nav-open'),
        pins: config.pins ?? [],
        pages: config.pages ?? {},
        context: config.context ?? false,
        tipText: '',
        tipTop: 0,
        tipInset: 0,
        tipSide: 'left',
        fly: null,
        flyStyle: {},
        flyAnchor: null,
        flyTimer: null,
        hideTimer: null,

        // Keep the rail where it was scrolled: wire:navigate re-renders it on every page.
        init() {
            try {
                const saved = Number(sessionStorage.getItem('hc-nav-scroll') ?? 0);

                if (saved > 0) {
                    this.$refs.scroll.scrollTop = saved;
                }
            } catch {
                // Storage may be refused; the rail then starts at the top.
            }
        },

        remember() {
            try {
                sessionStorage.setItem('hc-nav-scroll', String(this.$refs.scroll.scrollTop));
            } catch {
                // Nothing to keep without storage.
            }
        },

        /**
         * Moves the sidebar between rail and full width (user decision 2026-11-11:
         * opening and closing animate). The column's width eases for 220 ms while
         * html.dk-nav-moving is set (desk-shell.css); a page load never has that
         * class, so nothing moves then. Opening shows the labels at once and the
         * growing column uncovers them. Closing narrows the column first
         * (dk-nav-closing) and only then drops dk-nav-open, so the labels never
         * vanish from a still-wide sidebar. Reduced motion and the narrow-screen
         * overlay switch at once.
         */
        slide() {
            const root = document.documentElement.classList;

            clearTimeout(this.slideTimer);
            root.remove('dk-nav-closing');

            if (! desktop() || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                root.remove('dk-nav-moving');
                root.toggle('dk-nav-open', this.expanded);

                return;
            }

            root.add('dk-nav-moving');

            if (this.expanded) {
                root.add('dk-nav-open');
            } else {
                root.add('dk-nav-closing');
            }

            this.slideTimer = setTimeout(() => {
                root.toggle('dk-nav-open', this.expanded);
                root.remove('dk-nav-closing', 'dk-nav-moving');
            }, 240);
        },

        rail() {
            return ! this.expanded && desktop();
        },

        toggle() {
            this.expanded = ! this.expanded;
            this.untip();
            this.unfly();

            this.slide();

            try {
                localStorage.setItem('hc-nav', this.expanded ? 'open' : 'rail');
            } catch {
                // Private windows may refuse storage; the toggle still works for this page.
            }
        },

        /*
         * A group row opens its links. On the collapsed desktop rail the links
         * have no room, so the click first expands the sidebar, then opens it.
         */
        openGroup(group, name) {
            if (this.rail()) {
                this.toggle();
                group.open = true;
                this.$store.hcNav.remember(name, true);

                return;
            }

            group.open = ! group.open;
            this.$store.hcNav.remember(name, group.open);
        },

        async pin(route) {
            const was = [...this.pins];
            this.pins = this.pins.includes(route) ? this.pins.filter((name) => name !== route) : [...this.pins, route];

            try {
                const response = await fetch(config.pinUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ route }),
                });

                if (! response.ok) {
                    throw new Error(String(response.status));
                }

                this.pins = (await response.json()).pins ?? this.pins;
            } catch {
                this.pins = was;
            }
        },

        hover(event) {
            const target = event.target instanceof Element ? event.target : null;
            const flyer = target?.closest('[data-fly]');

            clearTimeout(this.hideTimer);

            if (flyer && this.rail()) {
                this.untip();
                clearTimeout(this.flyTimer);
                this.flyTimer = setTimeout(() => this.showFly(flyer.dataset.fly, flyer), this.fly ? 0 : 120);

                return;
            }

            this.tip(event);
        },

        /*
         * The fly-out opens beside the icon it belongs to: its title row lines
         * up with the icon, a pointer on its edge aims at the icon's center,
         * and the icon stays highlighted while it is open. Near the bottom of
         * the screen the panel moves up, and the pointer still finds the icon.
         */
        showFly(name, anchor) {
            const box = anchor.getBoundingClientRect();
            const rtl = document.documentElement.dir === 'rtl';
            const center = box.top + box.height / 2;
            const side = rtl
                ? { right: `${window.innerWidth - box.left + 10}px`, left: 'auto' }
                : { left: `${box.right + 10}px`, right: 'auto' };

            this.flyAnchor?.classList.remove('dk-flying');
            this.flyAnchor = anchor;
            anchor.classList.add('dk-flying');

            // An object, not a string: a string binding replaces the whole style attribute and wipes x-show's display:none on the other fly-outs.
            const place = (height) => {
                const top = Math.max(8, Math.min(center - 26, window.innerHeight - height - 8));

                this.flyStyle = { ...side, top: `${top}px`, '--dk-fly-caret': `${center - top}px` };
            };

            place(0);
            this.fly = name;
            this.$nextTick(() => {
                const panel = this.$root.querySelector(`[data-side-fly="${name}"]`);

                if (panel) {
                    place(panel.offsetHeight);
                }
            });
        },

        keepFly() {
            clearTimeout(this.hideTimer);
        },

        leave() {
            clearTimeout(this.flyTimer);
            this.untip();
            this.hideTimer = setTimeout(() => this.unfly(), 220);
        },

        unfly() {
            this.fly = null;
            this.flyAnchor?.classList.remove('dk-flying');
            this.flyAnchor = null;
        },

        tip(event) {
            const link = event.target instanceof Element ? event.target.closest('[data-label]') : null;

            if (! link || ! this.rail()) {
                this.untip();

                return;
            }

            const box = link.getBoundingClientRect();
            const rtl = document.documentElement.dir === 'rtl';

            this.tipText = link.dataset.label;
            this.tipTop = box.top + box.height / 2;
            this.tipSide = rtl ? 'right' : 'left';
            this.tipInset = rtl ? window.innerWidth - box.left + 10 : box.right + 10;
        },

        untip() {
            this.tipText = '';
        },
    }));
});
