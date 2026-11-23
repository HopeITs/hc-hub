/**
 * "Install the app": what the browser can do for this visitor. The deferred
 * browser prompt itself is kept by the inline script in partials/head.blade.php
 * (window.__hcInstallPrompt); window.hcApp in notifications.js wraps it, and
 * this file adds the device detection, the iOS-aware standalone check and the
 * dismissal memory the settings steps and the install banner share.
 *
 * Platforms: ios (Safari), ios_non_safari, android, firefox_android, firefox
 * (desktop, cannot install), desktop_chromium, desktop_safari, other. The keys
 * match lang/{en,ar}/notifications.php under app.platforms.
 */

export const DISMISS_KEY = 'hc-install-dismissed';
const DISMISS_FOR_MS = 30 * 24 * 60 * 60 * 1000;

export function detectPlatform(nav = navigator) {
    const ua = nav.userAgent || '';

    // iPadOS 13+ reports itself as a Mac; a Mac has no multi-touch screen.
    const ios = /iPad|iPhone|iPod/.test(ua) || (/Macintosh/.test(ua) && (nav.maxTouchPoints || 0) > 1);

    if (ios) {
        return /CriOS|FxiOS|EdgiOS|OPiOS|GSA\//.test(ua) ? 'ios_non_safari' : 'ios';
    }

    const firefox = /Firefox\//.test(ua);

    if (/Android/i.test(ua)) {
        return firefox ? 'firefox_android' : 'android';
    }

    if (firefox) {
        return 'firefox';
    }

    const chromium = /Chrome\/|Chromium\/|Edg\//.test(ua)
        || Boolean(nav.userAgentData?.brands?.some((brand) => /Chromium/.test(brand.brand)));

    if (chromium) {
        return 'desktop_chromium';
    }

    if (/Macintosh/.test(ua) && /Version\/[\d.]+.*Safari\//.test(ua)) {
        return 'desktop_safari';
    }

    return 'other';
}

/** Running as the installed app: its own window, or an iOS home-screen app. */
export function isStandalone() {
    try {
        if (window.matchMedia?.('(display-mode: standalone)').matches) {
            return true;
        }
    } catch {
        // No matchMedia: fall through to the iOS flag.
    }

    return window.navigator.standalone === true;
}

/** Storage may throw or come back empty; the banner then shows again, the safe direction. */
export function isDismissed(now = Date.now()) {
    try {
        const at = Number(window.localStorage.getItem(DISMISS_KEY));

        return Number.isFinite(at) && at > 0 && now - at < DISMISS_FOR_MS;
    } catch {
        return false;
    }
}

export function rememberDismissal() {
    try {
        window.localStorage.setItem(DISMISS_KEY, String(Date.now()));
    } catch {
        // Not remembered: hidden for this page only.
    }
}

document.addEventListener('alpine:init', () => {
    /**
     * The install banner (x-notifications.install-banner): shown only while a
     * browser prompt is waiting, or on iOS Safari where Share is the way in;
     * never when installed, and not for 30 days after "Not now".
     */
    window.Alpine.data('appInstallBanner', () => ({
        state: 'unavailable',
        platform: 'other',
        dismissed: false,

        init() {
            this.platform = window.hcApp?.platform() ?? detectPlatform();
            this.refresh();
        },

        refresh(state = null) {
            this.state = state ?? window.hcApp?.state() ?? 'unavailable';
            this.dismissed = isDismissed();
        },

        get visible() {
            return this.state !== 'installed' && !this.dismissed && (this.state === 'available' || this.platform === 'ios');
        },

        dismiss() {
            rememberDismissal();
            this.dismissed = true;
        },
    }));
});
