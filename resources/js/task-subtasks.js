/**
 * The task list and board's "show all subtasks" switch.
 *
 * Each list group and the board keep their own `subtasksOpen` map (one
 * entry per parent task a person opened or closed by hand). A task with no
 * entry follows the page-wide switch, which is remembered per browser
 * (localStorage "hc-task-subtasks"). Flipping the switch clears the
 * by-hand entries, so every task follows it again.
 */

const storageKey = 'hc-task-subtasks';

function rememberedAllOpen() {
    try {
        return window.localStorage.getItem(storageKey) === 'open';
    } catch {
        return false;
    }
}

document.addEventListener('alpine:init', () => {
    window.Alpine.store('taskSubtasks', {
        allOpen: rememberedAllOpen(),

        toggleAll() {
            this.allOpen = !this.allOpen;

            try {
                window.localStorage.setItem(storageKey, this.allOpen ? 'open' : 'closed');
            } catch {
                // Private windows may refuse storage: the switch still works for this page.
            }
        },
    });

    // A map whose missing task ids read as the page-wide switch. Only
    // numeric keys default: Alpine's reactivity probes internal keys
    // (`__v_skip`, `__v_isReactive`…) and must get their real value.
    const followingSwitch = () => new Proxy({}, {
        get: (target, key) => (key in target || typeof key !== 'string' || !/^\d+$/.test(key)
            ? target[key]
            : window.Alpine.store('taskSubtasks').allOpen),
    });

    window.Alpine.data('taskSubtaskScope', () => ({
        subtasksOpen: followingSwitch(),

        init() {
            this.$watch('$store.taskSubtasks.allOpen', () => {
                this.subtasksOpen = followingSwitch();
            });
        },
    }));
});
