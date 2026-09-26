<x-slot:title>{{ __('recruitment.team_staffing.title') }}</x-slot:title>

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="mb-2 text-caption font-semibold uppercase tracking-wider text-secondary-light">
                {{ __('recruitment.requests') }}
            </div>
            <h1 class="text-h2 font-semibold text-neutral-900">{{ __('recruitment.team_staffing.title') }}</h1>
            <p class="mt-2 max-w-2xl text-secondary-medium">{{ __('recruitment.team_staffing.description') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('recruitment.requests.index') }}" wire:navigate
               class="rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold text-secondary-dark transition hover:border-secondary-light hover:bg-neutral-50">
                {{ __('recruitment.team_staffing.back_to_requests') }}
            </a>
            <button type="button" wire:click="expandAll"
                    class="rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold text-secondary-dark transition hover:border-secondary-light hover:bg-neutral-50">
                {{ __('general.expand-all') }}
            </button>
            <button type="button" wire:click="collapseAll"
                    class="rounded-control bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                {{ __('general.collapse-all') }}
            </button>
        </div>
    </div>

    <nav class="flex gap-1 border-b border-neutral-200" aria-label="{{ __('recruitment.requests') }}">
        <a href="{{ route('recruitment.requests.index') }}" wire:navigate
           class="border-b-2 border-transparent px-4 py-3 text-sm font-semibold text-secondary-medium transition hover:border-primary hover:text-primary">
            {{ __('recruitment.navigation_requests') }}
        </a>
        <a href="{{ route('recruitment.requests.team') }}" wire:navigate
           class="border-b-2 border-primary px-4 py-3 text-sm font-semibold text-primary">
            {{ __('recruitment.team_staffing.tab') }}
        </a>
    </nav>

    <section class="grid gap-3 sm:grid-cols-4" aria-label="{{ __('recruitment.team_staffing.summary') }}">
        @foreach([
            ['value' => $summary['positions'], 'label' => __('recruitment.team_staffing.positions')],
            ['value' => $summary['capacity'], 'label' => __('recruitment.team_staffing.approved_headcount')],
            ['value' => $summary['filled'], 'label' => __('recruitment.team_staffing.filled_headcount')],
            ['value' => $summary['vacancies'], 'label' => __('recruitment.team_staffing.open_capacity')],
        ] as $stat)
            <div class="rounded-surface border border-neutral-200 bg-white px-4 py-4 shadow-e1">
                <div class="text-h3 font-bold tabular-nums text-secondary-dark">{{ number_format($stat['value']) }}</div>
                <div class="mt-1 text-caption font-medium text-secondary-medium">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </section>

    @if ($root)
        <section class="overflow-hidden rounded-surface border border-neutral-200 bg-white shadow-e1">
            <div class="border-b border-neutral-200 bg-secondary-soft px-5 py-4">
                <h2 class="text-lg font-semibold text-secondary-dark">{{ __('recruitment.team_staffing.tree_heading') }}</h2>
                <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment.team_staffing.tree_help') }}</p>
            </div>

            <div class="space-y-3 p-4 sm:p-6">
                @include('livewire.recruitment.requests.partials.team-staffing-node', ['node' => $root])
            </div>
        </section>
    @else
        <div class="rounded-surface border border-neutral-200 bg-white px-6 py-12 text-center shadow-e1">
            <h2 class="text-lg font-semibold text-secondary-dark">{{ __('recruitment.team_staffing.no_position') }}</h2>
            <p class="mt-2 text-sm text-secondary-medium">{{ __('recruitment.team_staffing.no_position_help') }}</p>
        </div>
    @endif
</div>
