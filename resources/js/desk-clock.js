/**
 * The time of day on Desk cards (a figure marked `clock`, data-desk-clock:
 * the HR clock card). The server draws Damascus time once; here it keeps
 * turning on the minute, whatever the device's own time zone, with Latin
 * digits as the server writes them. It reads the elements on every turn, so
 * a card Livewire draws again is picked up, and it stops while the page is
 * hidden. Ported from hc-hr's dashboard clock.
 */

const zone = 'Asia/Damascus';
const time = new Intl.DateTimeFormat('en-GB', { timeZone: zone, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
let timer = null;

function turn() {
    const now = time.format(new Date());

    document.querySelectorAll('[data-desk-clock]').forEach((el) => {
        if (el.textContent !== now) {
            el.textContent = now;
        }
    });

    timer = setTimeout(turn, 60000 - (Date.now() % 60000) + 50);
}

function start() {
    clearTimeout(timer);
    timer = null;

    if (! document.hidden) {
        turn();
    }
}

document.addEventListener('visibilitychange', start);
start();
