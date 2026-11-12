/**
 * Leaving a page with unsaved changes (ported from hc-hr's unsaved.js).
 *
 * One registry of "is there unsaved work here?" checks, asked before the page
 * goes away:
 *
 *   - wire:navigate links (`livewire:navigate`): one question, in the modal
 *     shell's own wording ("Discard your changes?" / Keep editing / Discard),
 *     through x-ui.unsaved-prompt in the layout;
 *   - reload, closing the browser tab, plain links (`beforeunload`): the
 *     browser's own prompt, whose wording the browser decides;
 *   - Back / Forward: the step is undone first, then the same question;
 *   - closing a Desk tab (desk-tabs.js reads the tab page's `hcUnsaved`).
 *
 * What registers:
 *   - x-ui.unsaved-guard, for the Livewire properties a page form is bound to.
 *     The baseline is what they held when the page was first shown;
 *   - x-ui.modal dialogs that carry a discard guard (modal-shell.js). They
 *     count for `beforeunload` and for Back, which closes the dialog through
 *     its own question instead of leaving the page.
 *
 * A redirect the server sends after an action is never questioned: the action
 * (a save) has run. A save that comes back with errors sends none, so the
 * form stays unsaved. Nothing is kept in browser storage.
 */

const PromptName = 'unsaved-leave';

const entries = new Map();

let bypassNextNavigation = false;
let leavingByServer = false;

/*
 * Back/Forward cannot be cancelled, only undone. Livewire has already moved to
 * the other history entry when it asks whether it may swap the page, so a
 * refused step is undone by stepping the browser the other way, which Livewire
 * follows too. The order of entries is kept here because Livewire's keys are
 * random.
 */
const positions = new Map();
let lastPosition = 0;
let currentKey = null;
// 'pending' while the refused step's own popstate finishes, then 'armed' until the undo's popstate arrives.
let undoingStep = null;

function entryKey() {
    return window.history.state?.alpine?.snapshotIdx ?? null;
}

function rememberCurrentEntry() {
    currentKey = entryKey();

    if (currentKey && ! positions.has(currentKey)) {
        positions.set(currentKey, ++lastPosition);
    }
}

/** Returns the step the user took (-1 Back, 1 Forward) after undoing it. */
function undoHistoryStep() {
    // An entry never recorded is one older than every recorded page: Back.
    const target = positions.get(entryKey()) ?? 0;
    const current = positions.get(currentKey) ?? lastPosition + 1;
    const step = target > current ? 1 : -1;

    undoingStep = 'pending';
    window.history.go(-step);

    return step;
}

window.addEventListener('popstate', () => {
    if (undoingStep === 'pending') {
        undoingStep = 'armed';
    } else if (undoingStep === 'armed') {
        // Livewire did not ask to navigate for the undo: nothing to swallow.
        setTimeout(() => (undoingStep = null));
    }
});

function alive(entry) {
    return ! entry.wireId || Boolean(window.Livewire?.find(entry.wireId));
}

function prune() {
    for (const [id, entry] of entries) {
        if (! alive(entry)) {
            entries.delete(id);
        }
    }
}

function snapshot(wireId, paths) {
    const component = window.Livewire?.find(wireId);

    // Livewire.find() hands back the component's $wire itself.
    return JSON.stringify(paths.map((path) => component?.$get(path) ?? null));
}

function prompt() {
    // Flux puts the attribute on <ui-modal>; the native <dialog> is inside it.
    return document.querySelector('[data-unsaved-prompt] dialog');
}

const unsaved = {
    register(id, entry) {
        entries.set(id, entry);
    },

    unregister(id) {
        entries.delete(id);
    },

    /**
     * Livewire properties of one component, compared with what they held when
     * the guard first saw them. Registering again keeps the original baseline.
     */
    watch(wireId, paths) {
        const id = `form:${wireId}:${paths.join(',')}`;

        if (! entries.has(id)) {
            entries.set(id, {
                wireId,
                paths,
                baseline: snapshot(wireId, paths),
                dirty() {
                    return snapshot(this.wireId, this.paths) !== this.baseline;
                },
            });
        }

        return entries.get(id);
    },

    pageIsDirty() {
        prune();

        return [...entries.values()].some((entry) => entry.kind !== 'dialog' && entry.dirty());
    },

    openDialog() {
        return [...entries.values()].find((entry) => entry.kind === 'dialog' && entry.isOpen());
    },

    anythingUnsaved() {
        return this.pageIsDirty() || Boolean(this.openDialog()?.dirty());
    },

    /** Resolves true when the person chose to discard. */
    ask() {
        const dialog = prompt();

        if (! dialog) {
            return Promise.resolve(window.confirm(document.querySelector('[data-unsaved-question]')?.dataset.unsavedQuestion ?? ''));
        }

        return new Promise((resolve) => {
            let discard = false;
            const button = dialog.querySelector('[data-unsaved-discard]');
            const choose = () => {
                discard = true;
                dialog.close();
            };

            button?.addEventListener('click', choose);
            dialog.addEventListener('close', () => {
                button?.removeEventListener('click', choose);
                resolve(discard);
            }, { once: true });

            if (window.Flux?.modal) {
                window.Flux.modal(PromptName).show();
            } else {
                dialog.showModal();
            }
        });
    },
};

window.hcUnsaved = unsaved;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('unsavedGuard', (paths) => ({
        init() {
            unsaved.watch(this.$wire.$id, paths);
        },
    }));
});

document.addEventListener('livewire:init', () => {
    window.Livewire.interceptRequest(({ onRedirect }) => {
        onRedirect(() => {
            leavingByServer = true;
            // A redirect that is prevented or fails must not leave the guard off.
            setTimeout(() => (leavingByServer = false), 4000);
        });
    });
});

document.addEventListener('livewire:navigated', () => {
    leavingByServer = false;
    rememberCurrentEntry();
    prune();
});

document.addEventListener('livewire:navigate', (event) => {
    const { url, history } = event.detail ?? {};

    if (history && undoingStep === 'armed') {
        // Back where the person was: keep the live page as it is.
        undoingStep = null;
        event.preventDefault();

        return;
    }

    // Another listener (Desk tabs) already took this navigation over.
    if (event.defaultPrevented || leavingByServer) {
        return;
    }

    if (bypassNextNavigation) {
        bypassNextNavigation = false;

        return;
    }

    if (history) {
        const dialog = unsaved.openDialog();

        if (dialog) {
            event.preventDefault();
            undoHistoryStep();
            dialog.close();

            return;
        }
    }

    if (! unsaved.pageIsDirty()) {
        return;
    }

    event.preventDefault();

    const step = history ? undoHistoryStep() : 0;

    unsaved.ask().then((discard) => {
        if (! discard) {
            return;
        }

        bypassNextNavigation = true;

        // Take the person's own step again, so history stays as they left it.
        if (step) {
            window.history.go(step);
        } else {
            window.Livewire.navigate(url?.href ?? String(url));
        }
    });
});

window.addEventListener('beforeunload', (event) => {
    if (leavingByServer || ! unsaved.anythingUnsaved()) {
        return;
    }

    // The text is the browser's own; neither of these can change it.
    event.preventDefault();
    event.returnValue = '';
});
