@props(['contract'])

@php
    use App\Enums\SalaryPer;

    $salaryPerValue = (string) ($contract->salary_per ?? 0);
    $per = SalaryPer::getLocalizedName($salaryPerValue);
@endphp

<div class="mt-2 text-sm">
    <div class="flex items-center gap-2">
        <x-badge :color="$per['color']">
            {{ $per['value'] }}
        </x-badge>
        <span class="font-medium">
            {{ number_format((float) $contract->salary, 2) }}
        </span>
    </div>

    @if($contract->weekly_hours)
        <div class="mt-2">
            <span class="opacity-70">{{ __('general.weekly-hours') }}:</span>
            <span class="font-medium">{{ $contract->weekly_hours }}</span>
        </div>
    @endif
</div>
