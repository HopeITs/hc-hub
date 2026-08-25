{{--
    On desktop this is the last card in the stack, pushed to the foot of the
    column (lg:mt-auto) so it stays level with the bottom of the contract card
    whichever of the two is taller. Below lg it detaches to the bottom of the
    viewport and carries a compressed version of the card with it.
--}}
<div
    class="rounded-surface border border-neutral-200 bg-white shadow-e1 lg:mt-auto
           max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-20 max-lg:rounded-b-none
           max-lg:border-x-0 max-lg:border-b-0 max-lg:shadow-e3"
>
    {{-- Small screens only: who and where, since the rail is hidden there. --}}
    <div class="flex items-center gap-2 border-b border-neutral-200 px-4 py-2 lg:hidden"
         x-cloak x-show="selectedEmployee">
        <span class="min-w-0 flex-1 truncate text-small font-medium text-neutral-800"
              x-text="selectedEmployee ? selectedEmployee.label : ''"></span>
        <span class="shrink-0 text-caption text-secondary-medium" x-text="cardPosition"></span>
        <span x-cloak x-show="capacity" class="shrink-0 text-caption font-medium tabular-nums"
              x-bind:class="capacity && capacity.left === 0 ? 'text-primary' : 'text-secondary-medium'"
              x-text="cardCapacity"></span>
    </div>

    <div class="flex flex-wrap items-center gap-3 p-3 sm:px-4">
        <p class="flex min-w-0 flex-1 items-center gap-2.5 text-small">
            <span class="flex gap-1" aria-hidden="true">
                @foreach(['assignment', 'contract', 'schedule', 'pay'] as $sectionName)
                    <span class="h-1.5 w-6 rounded-full transition-colors"
                          x-bind:class="isValid('{{ $sectionName }}') ? 'bg-primary' : 'bg-neutral-300'"></span>
                @endforeach
            </span>
            <span class="truncate font-medium"
                  x-bind:class="completedCount === 4 ? 'text-neutral-900' : 'text-secondary-dark'"
                  x-text="progressLabel"></span>
        </p>

        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ route('contracts.index') }}" wire:navigate
               class="rounded-control border border-neutral-300 bg-white px-4 py-2 text-body font-semibold
                      text-secondary-dark shadow-e1 transition hover:bg-neutral-100 active:scale-[0.98]
                      focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                @lang('general.cancel')
            </a>

            <button
                type="button"
                x-bind:disabled="!canSubmit"
                x-on:click="submit()"
                class="flex items-center justify-center rounded-control bg-primary px-5 py-2 text-body
                       font-semibold text-white shadow-e1 transition
                       hover:bg-primary-hover active:scale-[0.98] active:shadow-none
                       focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary
                       disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:text-secondary-medium
                       disabled:shadow-none disabled:active:scale-100"
            >
                <span x-cloak x-show="submitting" class="me-2" role="status">
                    <x-icons.spinner class="size-4 animate-spin" />
                    <span class="sr-only">@lang('general.a11y.loading')</span>
                </span>
                <span x-text="submitting ? @js(__('general.saving')) : @js(__('general.composer.create'))"></span>
            </button>
        </div>
    </div>
</div>
