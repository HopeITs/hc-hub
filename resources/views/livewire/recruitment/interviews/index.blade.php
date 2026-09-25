<div class="space-y-6 p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-h3 font-semibold">{{ __('recruitment-applicant.interviews.title') }}</h1>
            <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.interviews.index_hint') }}</p>
        </div>

        <div class="inline-flex rounded-control border border-neutral-200 bg-white p-1 text-sm" role="tablist">
            <button type="button" role="tab" wire:click="$set('view', 'list')" aria-selected="{{ $view === 'list' ? 'true' : 'false' }}"
                    class="rounded-control px-3 py-1.5 {{ $view === 'list' ? 'bg-primary text-white' : 'text-secondary-dark hover:bg-neutral-50' }}">
                {{ __('recruitment-applicant.interviews.list_view') }}
            </button>
            <button type="button" role="tab" wire:click="$set('view', 'calendar')" aria-selected="{{ $view === 'calendar' ? 'true' : 'false' }}"
                    class="rounded-control px-3 py-1.5 {{ $view === 'calendar' ? 'bg-primary text-white' : 'text-secondary-dark hover:bg-neutral-50' }}">
                {{ __('recruitment-applicant.interviews.calendar_view') }}
            </button>
        </div>
    </div>

    @if ($view === 'calendar')
        <livewire:recruitment.interviews.calendar key="interviews-calendar" />
    @else
        <livewire:recruitment.interviews.interview-table key="interviews-table" />
    @endif
</div>
