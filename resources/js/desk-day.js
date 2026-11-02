/**
 * The Desk hero's "My day" block (pages/desk/⚡index): weekday, a clock that
 * moves every 30 seconds, and how much of the work day is left. The hours
 * are the viewer's own (08:00–16:00 until changed), kept in this browser
 * under dk-workday; storage may be blocked, so every read and write is
 * guarded and the defaults apply.
 */
const storageKey = 'dk-workday';
const fallback = { start: '08:00', end: '16:00' };

function readHours() {
    try {
        const stored = JSON.parse(window.localStorage.getItem(storageKey) || 'null');

        if (stored && /^\d{2}:\d{2}$/.test(stored.start) && /^\d{2}:\d{2}$/.test(stored.end)) {
            return stored;
        }
    } catch {
        // Blocked or broken storage: use the defaults.
    }

    return { ...fallback };
}

function minutesOf(time) {
    const [hours, minutes] = time.split(':').map(Number);

    return hours * 60 + minutes;
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('deskDay', (phrases) => ({
        hours: readHours(),
        draftStart: '',
        draftEnd: '',
        editing: false,
        clock: '',
        date: '',
        weekday: '',
        progress: 0,
        status: '',
        timer: null,

        init() {
            this.draftStart = this.hours.start;
            this.draftEnd = this.hours.end;
            this.tick();
            this.timer = setInterval(() => this.tick(), 30000);
        },

        destroy() {
            clearInterval(this.timer);
        },

        tick() {
            const now = new Date();
            const locale = document.documentElement.lang || undefined;

            this.clock = now.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit', hour12: false });
            this.weekday = now.toLocaleDateString(locale, { weekday: 'long' });
            this.date = now.toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });

            const start = minutesOf(this.hours.start);
            const end = minutesOf(this.hours.end);
            const current = now.getHours() * 60 + now.getMinutes();

            if (end <= start || current >= end) {
                this.progress = 100;
                this.status = phrases.over;
            } else if (current < start) {
                this.progress = 0;
                this.status = phrases.before.replace(':time', this.hours.start);
            } else {
                const left = end - current;

                this.progress = Math.round(((current - start) / (end - start)) * 100);
                this.status = left >= 60
                    ? phrases.left.replace(':h', Math.floor(left / 60)).replace(':m', left % 60)
                    : phrases.leftMin.replace(':m', left);
            }
        },

        saveHours() {
            if (! /^\d{2}:\d{2}$/.test(this.draftStart) || ! /^\d{2}:\d{2}$/.test(this.draftEnd) || minutesOf(this.draftEnd) <= minutesOf(this.draftStart)) {
                return;
            }

            this.hours = { start: this.draftStart, end: this.draftEnd };

            try {
                window.localStorage.setItem(storageKey, JSON.stringify(this.hours));
            } catch {
                // Not saved (private window): the hours still apply until the page reloads.
            }

            this.editing = false;
            this.tick();
        },
    }));
});
