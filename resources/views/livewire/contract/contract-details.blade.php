@php
    /** @var \App\Models\Contract $contract */
    use Illuminate\Support\Str;

    $locale = $locale ?? app()->getLocale();
@endphp

<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.contracts', 1)"
            :crumbs="$crumbs ?? [
                ['label' => trans_choice('general.employee-management', 2)],
                ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index', [], false)],

            ]"
        />

        <div class="shrink-0 flex items-center gap-2">
            <a href="{{ route('contracts.export', $contract) }}"
               class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-white text-sm font-medium shadow hover:bg-primary-400">
                <svg class="h-4 w-4 ms-2 me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <path d="M7 10l5 5 5-5"/>
                    <path d="M12 15V3"/>
                </svg>
                @lang('general.export-pdf')
            </a>
            <x-primary-button :href="route('contracts.edit', $contract->id)">
                {{ __('general.edit') }}
            </x-primary-button>
            <x-secondary-button :href="route('contracts.index')">
                {{ __('general.back') }}
            </x-secondary-button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <x-badge color="red">
            {{ $contract->contractType?->name
                ?? $contract->contractType?->name
                ?? __('general.na') }}
        </x-badge>
    </div>

    @includeIf($partialPath)

</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('scroll-to-top', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
</script>

