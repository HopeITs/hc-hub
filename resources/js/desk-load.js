/**
 * Desk panels that fail to load (pages/desk/⚡index mounts every panel with
 * `defer`; components/desk/panel-placeholder draws it until it arrives).
 *
 * A panel's first load is its own request, so a panel whose data cannot be read
 * fails alone: Livewire's error dialog is held back, the placeholder says the
 * panel could not load and offers Retry, and every other panel carries on. The
 * server has already reported the error. Retry runs the placeholder's own load
 * call again (the x-init Livewire put on it).
 */

const pending = '[data-desk-pending]';

function showFailed(panel, failed) {
    panel.toggleAttribute('data-load-failed', failed);
    panel.setAttribute('aria-busy', failed ? 'false' : 'true');
    panel.querySelectorAll('[data-desk-pending-lines]').forEach((el) => {
        el.hidden = failed;
    });

    const notice = panel.querySelector('[data-desk-failed]');

    if (notice) {
        notice.hidden = ! failed;
    }
}

document.addEventListener('livewire:init', () => {
    window.Livewire.interceptMessage(({ message, onError, onFailure }) => {
        const panel = message.component?.el;

        if (! (panel instanceof Element) || ! panel.matches(pending)) {
            return;
        }

        onError(({ preventDefault }) => {
            preventDefault();
            showFailed(panel, true);
        });

        onFailure(() => showFailed(panel, true));
    });
});

document.addEventListener('click', (event) => {
    const retry = event.target instanceof Element ? event.target.closest('[data-desk-retry]') : null;
    const panel = retry?.closest(pending);
    const load = panel?.getAttribute('x-init');

    if (! panel || ! load || ! window.Alpine) {
        return;
    }

    showFailed(panel, false);
    window.Alpine.evaluate(panel, load);
});
