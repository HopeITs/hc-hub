@props(['contract', 'locale' => app()->getLocale()])

<div class="text-sm space-y-1">
    <div><span class="opacity-70">{{ __('general.name') }}:</span>
        <span class="font-medium">{{ $contract->employee?->getTranslation('name', $locale) ?? '—' }}</span>
    </div>
    <div><span class="opacity-70">{{ trans_choice('general.positions',1) }}:</span>
        <span class="font-medium">{{ $contract->position?->requiredPosition?->title ?? '#'.$contract->position_id }}</span>
    </div>
    <div><span class="opacity-70">{{ trans_choice('general.center',1) }}:</span>
        <span class="font-medium">{{ $contract->position?->center?->getTranslation('name', $locale) ?? '—' }}</span>
    </div>
    <div><span class="opacity-70">{{ trans_choice('general.department',1) }}:</span>
        <span class="font-medium">{{ $contract->department?->name ?? '—' }}</span>
    </div>
    <div><span class="opacity-70">{{ __('general.job-level') }}:</span>
        <span class="font-medium">{{ $contract->jobLevel?->getTranslation('name', $locale) ?? '—' }}</span>
    </div>
</div>
