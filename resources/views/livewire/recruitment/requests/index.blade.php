<x-slot:title>{{ __('recruitment.requests') }}</x-slot:title>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-h2 font-semibold text-neutral-900">{{ __('recruitment.requests') }}</h1>
            <p class="mt-2 text-secondary-medium">{{ __('recruitment.list_description') }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('create', \App\Models\RecruitmentRequest::class)
                <x-primary-button :href="route('recruitment.requests.create')">{{ __('recruitment.create') }}</x-primary-button>
            @endcan
            {{-- Its own permission, never implied by managing requests (D15). --}}
            @can('directOpen', \App\Models\RecruitmentRequest::class)
                <a href="{{ route('recruitment.requests.direct-open') }}" wire:navigate
                    class="rounded-control bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                    {{ __('recruitment.direct_open.title') }}
                </a>
            @endcan
        </div>
    </div>

    @if (count($views) > 1)
        <nav aria-label="{{ __('recruitment.request_view') }}">
            <x-tabs-list>
                @foreach ($views as $view)
                    <x-tabs-list-item
                        wire:key="request-view-{{ $view }}"
                        action="setRequestView"
                        :active="$viewFilter === $view"
                        :active-tab="$view">
                        {{ __('recruitment.request_views.'.$view) }}
                        ({{ $viewCounts[$view] ?? 0 }})
                    </x-tabs-list-item>
                @endforeach
            </x-tabs-list>
        </nav>
    @endif

    <livewire:recruitment.requests.request-table
        :viewFilter="$viewFilter"
        :key="'requests-'.$viewFilter" />
</div>
