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
                ['label' => trans_choice('general.employee_management', 2)],
                ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index', [], false)],
                ['label' => $contract->employee?->getTranslation('name', app()->getLocale()) ?? __('general.na')],
            ]"
        />

        <div class="shrink-0 flex items-center gap-2">
            <x-primary-button :href="route('contracts.edit', $contract->id)">
                {{ __('general.edit') }}
            </x-primary-button>
            <x-secondary-button :href="route('contracts.index')">
                {{ __('general.back') }}
            </x-secondary-button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <x-badge color="red" >
            {{ $contract->contractType?->getTranslation('name', $locale) ?? __('general.na') }}
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
