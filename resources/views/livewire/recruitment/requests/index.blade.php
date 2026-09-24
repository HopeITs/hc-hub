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
    {{-- A filter over one list, not several lists: every tab narrows the same
         scoped query, so nothing appears here that "all" would hide. --}}
    <x-tabs-list>
        <x-tabs-list-item :active="$statusFilter === 'all'" active-tab="all">
            {{ __('general.all') }} ({{ $counts['all'] ?? 0 }})
        </x-tabs-list-item>

        @foreach ($tabs as $status)
            <x-tabs-list-item
                :active="$statusFilter === (string) $status"
                :active-tab="$status"
                wire:key="status-tab-{{ $status }}">
                {{ \App\Enums\RecruitmentRequestStatus::getLocalizedName($status)['value'] }}
                ({{ $counts[(string) $status] ?? 0 }})
            </x-tabs-list-item>
        @endforeach
    </x-tabs-list>

    <livewire:recruitment.requests.request-table
        :statusFilter="$statusFilter"
        :key="'requests-'.$statusFilter" />
</div>
