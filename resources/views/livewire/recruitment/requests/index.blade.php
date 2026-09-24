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
                    class="rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                    {{ __('recruitment.direct_open.title') }}
                </a>
            @endcan
        </div>
    </div>
    <livewire:recruitment.requests.request-table />
</div>
