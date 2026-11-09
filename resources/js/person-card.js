/**
 * The person card shown while hovering (or focusing) an assignee's avatar
 * on the task board, list and calendar.
 *
 * One card for the whole page (pages/tasks/_person-card.blade.php, teleported
 * to <body> so a scrolling column never clips it). Each avatar carries its
 * person in data attributes (data-person-name, -alt-name, -role, -photo);
 * nothing is fetched. A short delay before showing keeps the card from
 * flashing while the pointer crosses a row of avatars, and a short delay
 * before hiding lets the pointer move onto the card itself.
 */

const showDelay = 250;
const hideDelay = 150;

document.addEventListener('alpine:init', () => {
    window.Alpine.store('personCard', {
        open: false,
        name: '',
        altName: '',
        role: '',
        photo: '',
        top: 0,
        left: 0,
        right: null,
        showTimer: null,
        hideTimer: null,

        show(el) {
            clearTimeout(this.hideTimer);
            clearTimeout(this.showTimer);

            this.showTimer = setTimeout(() => {
                const rect = el.getBoundingClientRect();
                const data = el.dataset;

                this.name = data.personName ?? '';
                this.altName = data.personAltName ?? '';
                this.role = data.personRole ?? '';
                this.photo = data.personPhoto ?? '';
                this.top = rect.bottom + 6;
                this.left = rect.left;
                this.right = document.documentElement.dir === 'rtl' ? window.innerWidth - rect.right : null;
                this.open = true;
            }, showDelay);
        },

        keep() {
            clearTimeout(this.hideTimer);
        },

        hide() {
            clearTimeout(this.showTimer);
            this.hideTimer = setTimeout(() => {
                this.open = false;
            }, hideDelay);
        },
    });
});
