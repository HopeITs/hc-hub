/**
 * HC Hub service worker.
 *
 * It makes the site installable as the "HC Hub" app and owns its desktop
 * notifications (shown by resources/js/notifications.js with the app's name
 * and banner artwork). Popups carry no buttons: a click brings an app window
 * forward on the record, or on the inbox when several were piled up.
 *
 * Web Push: the server sends a message to this browser's push service,
 * encrypted to this device's keys. The worker shows the notification it is
 * given (title, text, link), tells open pages so the bell refreshes, and on a
 * click opens the link, same origin only. Every push shows a notification
 * (userVisibleOnly). When the browser rotates the subscription the worker
 * re-subscribes with the same server key and reports the new one to
 * /push/renew, proving ownership with the old one's auth secret; the next
 * page load syncs it as a fallback.
 *
 * It deliberately stores nothing and never intercepts page requests: every
 * page is always loaded live from the network, so nobody ever sees stale data.
 */

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

/** Every open HC Hub window, most recently focused first. */
async function appWindows() {
    return self.clients.matchAll({ type: 'window', includeUncontrolled: true });
}

/** Focus an existing window and send it to the record, or open a new one. */
async function openRecord(url) {
    const [client] = await appWindows();

    if (client) {
        await client.focus();
        client.postMessage({ type: 'hc-notification-open', url });

        return;
    }

    await self.clients.openWindow(url);
}

/** Only this site's pages may be opened from a notification. */
function sameOriginUrl(value) {
    const home = new URL('/notifications', self.location.origin).href;

    try {
        const url = new URL(value || '/notifications', self.location.origin);

        return url.origin === self.location.origin ? url.href : home;
    } catch {
        return home;
    }
}

self.addEventListener('notificationclick', (event) => {
    const data = event.notification.data ?? {};

    event.notification.close();

    event.waitUntil(openRecord(sameOriginUrl(data.url)));
});

/* ---- Web Push ------------------------------------------------------------ */

const PUSH_FALLBACK = { title: 'HC Hub', body: 'You have a new notification.' };
const PUSH_ICON = '/app-icons/icon-192.png';
const PUSH_BADGE = '/app-icons/badge-96.png';

const text = (value, fallback) => (typeof value === 'string' && value.trim() !== '' ? value : fallback);

self.addEventListener('push', (event) => {
    let data = {};

    try {
        data = event.data ? event.data.json() : {};
    } catch {
        // Unreadable payload: still show something (userVisibleOnly).
        data = {};
    }

    if (!data || typeof data !== 'object') {
        data = {};
    }

    const options = {
        body: text(data.body, PUSH_FALLBACK.body),
        icon: text(data.icon, PUSH_ICON),
        badge: text(data.badge, PUSH_BADGE),
        data: { url: sameOriginUrl(data.url) },
        silent: data.silent === true,
        requireInteraction: data.urgent === true,
    };

    if (typeof data.tag === 'string' && data.tag !== '') {
        options.tag = data.tag;
        options.renotify = true;
    }

    event.waitUntil(Promise.all([
        self.registration.showNotification(text(data.title, PUSH_FALLBACK.title), options),
        appWindows()
            .then((windows) => windows.forEach((client) => client.postMessage({ type: 'hc-push' })))
            .catch(() => {}),
    ]));
});

function base64url(buffer) {
    if (!buffer) {
        return '';
    }

    let binary = '';
    new Uint8Array(buffer).forEach((byte) => {
        binary += String.fromCharCode(byte);
    });

    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

/**
 * The browser replaced (or dropped) the subscription. Without the old one
 * there is nothing to prove ownership with; the next page load's sync, or the
 * next enable from the settings page, fixes it.
 */
self.addEventListener('pushsubscriptionchange', (event) => {
    const old = event.oldSubscription;

    if (!old) {
        return;
    }

    event.waitUntil((async () => {
        try {
            const subscription = event.newSubscription || await self.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: old.options?.applicationServerKey,
            });

            await fetch('/push/renew', {
                method: 'POST',
                credentials: 'omit',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    old_endpoint: old.endpoint,
                    old_auth: base64url(old.getKey && old.getKey('auth')),
                    subscription: subscription.toJSON(),
                }),
            });
        } catch {
            // Fail silently: the server drops the old one when the push service reports it gone.
        }
    })());
});
