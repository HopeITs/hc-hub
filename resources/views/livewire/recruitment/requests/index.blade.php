<x-slot:title>{{ __('recruitment.requests') }}</x-slot:title>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
        <h1 class="text-h2 font-semibold text-neutral-900">{{ __('recruitment.requests') }}</h1>
        <p class="mt-2 text-secondary-medium">{{ __('recruitment.list_description') }}</p>
        </div>
        @can('create', \App\Models\RecruitmentRequest::class)
            <x-primary-button :href="route('recruitment.requests.create')">{{ __('recruitment.create') }}</x-primary-button>
        @endcan
    </div>
    <livewire:recruitment.requests.request-table />
</div>
