@props(['scope'])

<div
    x-data="{
        open: false,
        busy: false,
        action: '',
        args: [],
        title: @js(__('general.confirmation')),
        message: '',
        confirmLabel: @js(__('general.confirmation')),
        tone: 'primary',
        requireReason: false,
        reason: '',
        reasonLabel: '',
        reasonHint: '',
        reasonRequiredMessage: '',
        reasonError: '',
        show(detail) {
            if (detail.scope !== @js($scope)) return;

            this.action = detail.action;
            this.args = Array.isArray(detail.args) ? detail.args : [];
            this.title = detail.title || @js(__('general.confirmation'));
            this.message = detail.message || '';
            this.confirmLabel = detail.confirmLabel || @js(__('general.confirmation'));
            this.tone = detail.tone === 'danger' ? 'danger' : 'primary';
            this.requireReason = detail.requireReason === true;
            this.reason = '';
            this.reasonLabel = detail.reasonLabel || '';
            this.reasonHint = detail.reasonHint || '';
            this.reasonRequiredMessage = detail.reasonRequiredMessage || '';
            this.reasonError = '';
            this.open = true;
            this.$nextTick(() => (this.requireReason ? this.$refs.reasonInput : this.$refs.confirmButton).focus());
        },
        close() {
            if (!this.busy) this.open = false;
        },
        async confirm() {
            if (!this.action || this.busy) return;

            if (this.requireReason && !this.reason.trim()) {
                this.reasonError = this.reasonRequiredMessage;
                this.$nextTick(() => this.$refs.reasonInput.focus());
                return;
            }

            this.busy = true;
            const action = this.action;
            const args = this.args;
            this.open = false;

            try {
                if (this.requireReason) {
                    await this.$wire.set('reason', this.reason.trim());
                }

                await this.$wire.call(action, ...args);
            } finally {
                this.busy = false;
            }
        },
    }"
    x-on:open-recruitment-confirmation.window="show($event.detail)"
    x-on:keydown.escape.window="close()"
    data-recruitment-confirmation-modal="{{ $scope }}"
>
    <div
        x-cloak
        x-show="open"
        class="fixed inset-0 z-[100] overflow-y-auto"
        role="dialog"
        aria-modal="true"
        x-bind:aria-labelledby="@js($scope.'-confirmation-title')"
    >
        <div
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 bg-neutral-900/60 backdrop-blur-[1px]"
            x-on:click="close()"
            aria-hidden="true"
        ></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <section
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="translate-y-3 opacity-0 sm:scale-95"
                x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave-end="translate-y-3 opacity-0 sm:scale-95"
                x-on:click.stop
                class="relative w-full max-w-md overflow-hidden rounded-surface border border-neutral-200 bg-white shadow-e3"
            >
                <div class="flex items-start gap-4 p-5 sm:p-6">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-full"
                        x-bind:class="tone === 'danger' ? 'bg-red-100 text-red-700' : 'bg-primary/10 text-primary'"
                        aria-hidden="true"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 2.7 17.1A2 2 0 0 0 4.4 20h15.2a2 2 0 0 0 1.7-2.9L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 id="{{ $scope }}-confirmation-title" class="text-h4 font-semibold text-neutral-900" x-text="title"></h2>
                        <p class="mt-2 whitespace-pre-line text-body leading-6 text-neutral-600" x-text="message"></p>

                        <div x-cloak x-show="requireReason" class="mt-5">
                            <label for="{{ $scope }}-confirmation-reason" class="block text-body font-semibold text-neutral-800">
                                <span x-text="reasonLabel"></span>
                                <span class="text-primary" aria-hidden="true">*</span>
                            </label>
                            <textarea
                                x-ref="reasonInput"
                                id="{{ $scope }}-confirmation-reason"
                                x-model="reason"
                                x-on:input="reasonError = ''"
                                rows="3"
                                x-bind:required="requireReason"
                                x-bind:aria-invalid="reasonError ? 'true' : 'false'"
                                class="mt-2 w-full rounded-control border-neutral-300 text-body focus:border-primary focus:ring-primary"
                            ></textarea>
                            <p class="mt-1 text-small text-neutral-500" x-text="reasonHint"></p>
                            <p x-cloak x-show="reasonError" role="alert" class="mt-1 text-small font-medium text-red-700" x-text="reasonError"></p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-neutral-200 bg-neutral-50 px-5 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        x-on:click="close()"
                        x-bind:disabled="busy"
                        class="cursor-pointer rounded-control border border-neutral-300 bg-white px-4 py-2 text-body font-semibold text-neutral-700 transition hover:bg-neutral-100 disabled:cursor-wait disabled:opacity-60"
                    >
                        {{ __('general.cancel') }}
                    </button>
                    <button
                        x-ref="confirmButton"
                        type="button"
                        x-on:click="confirm()"
                        x-bind:disabled="busy"
                        class="cursor-pointer rounded-control bg-primary px-4 py-2 text-body font-semibold text-white transition hover:bg-primary-hover disabled:cursor-wait disabled:opacity-60"
                    >
                        <span x-show="!busy" x-text="confirmLabel"></span>
                        <span x-cloak x-show="busy">{{ __('general.loading') }}…</span>
                    </button>
                </div>
            </section>
        </div>
    </div>
</div>
