{{--
    On desktop this is the last card in the stack. Below lg it detaches to the
    bottom of the viewport and carries a compressed version of the contract card
    with it, so the rail's information is never simply lost on a narrow screen.
--}}
<div
    class="rounded-surface border border-neutral-200 bg-white shadow-e1
           max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-20 max-lg:rounded-b-none
           max-lg:border-x-0 max-lg:border-b-0 max-lg:shadow-e3"
>
    {{-- Small screens only: who and where, since the rail is hidden there. --}}
    <div class="flex items-center gap-2 border-b border-neutral-100 px-4 py-2 lg:hidden"
         x-cloak x-show="selectedEmployee">
        <span class="min-w-0 flex-1 truncate text-small text-neutral-800"
              x-text="selectedEmployee ? selectedEmployee.label : ''"></span>
        <span class="shrink-0 text-caption text-secondary-light" x-text="cardPosition"></span>
        <span x-cloak x-show="capacity" class="shrink-0 text-caption tabular-nums"
              x-bind:class="capacity && capacity.left === 0 ? 'text-primary' : 'text-secondary-light'"
              x-text="cardCapacity"></span>
    </div>

    <div class="flex flex-wrap items-center gap-3 p-3 sm:px-4">
        <p class="flex min-w-0 flex-1 items-center gap-2 text-small text-secondary-medium">
            <span class="flex gap-1" aria-hidden="true">
                @foreach(['assignment', 'contract', 'schedule', 'pay'] as $sectionName)
                    <span class="h-1 w-5 rounded-full transition-colors"
                          x-bind:class="isValid('{{ $sectionName }}') ? 'bg-primary' : 'bg-neutral-200'"></span>
                @endforeach
            </span>
            <span class="truncate" x-text="progressLabel"></span>
        </p>

        <div class="flex shrink-0 items-center gap-2">
            <x-secondary-button type="button" onclick="window.location.href='{{ route('contracts.index') }}'">
                @lang('general.cancel')
            </x-secondary-button>

            <x-primary-button
                type="button"
                :show-spinner="false"
                x-bind:disabled="!canSubmit"
                x-on:click="submit()"
            >
                <span x-cloak x-show="submitting" class="me-2" role="status">
                    <x-icons.spinner class="size-4 animate-spin" />
                    <span class="sr-only">@lang('general.a11y.loading')</span>
                </span>
                <span x-text="submitting ? @js(__('general.saving')) : @js(__('general.composer.create'))"></span>
            </x-primary-button>
        </div>
    </div>
</div>
