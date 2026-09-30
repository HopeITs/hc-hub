<div class="space-y-6 p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-h3 font-semibold">{{ __('recruitment-applicant.interviews.title') }}</h1>
            <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.interviews.index_hint') }}</p>
        </div>

        {{-- A view switch, not tabs: two pressed-state buttons, so assistive tech is not promised arrow-key tab behaviour. --}}
        <div class="inline-flex rounded-control border border-secondary-soft bg-white p-1 text-sm shadow-e1" role="group"
             aria-label="{{ __('recruitment-applicant.interviews.title') }}">
            @foreach (['list' => 'list_view', 'calendar' => 'calendar_view'] as $option => $label)
                <button type="button" wire:click="$set('view', '{{ $option }}')" aria-pressed="{{ $view === $option ? 'true' : 'false' }}"
                        class="min-h-10 cursor-pointer rounded-control px-4 transition duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary
                               {{ $view === $option ? 'bg-selection font-semibold text-secondary-dark' : 'text-secondary-medium hover:text-secondary-dark' }}">
                    {{ __('recruitment-applicant.interviews.'.$label) }}
                </button>
            @endforeach
        </div>
    </div>

    @if ($view === 'calendar')
        <livewire:recruitment.interviews.calendar key="interviews-calendar" />
    @else
        <livewire:recruitment.interviews.interview-table key="interviews-table" />
    @endif
</div>
