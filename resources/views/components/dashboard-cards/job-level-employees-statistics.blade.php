<div class="w-full bg-white rounded-surface shadow-e1 border border-[var(--color-neutral-200)] overflow-hidden">
    <div class="flex justify-between items-center px-6 py-4 bg-[var(--color-primary)]">
        <h3 class="text-h4 font-semibold text-white">
            {{ __('Employees per Job Level') }}
        </h3>
        <span class="text-body bg-white/20 px-3 py-1 rounded-full text-white font-medium">
            {{ now()->year }}
        </span>
    </div>

    <div class="p-6 flex items-center justify-center">
        <div id="jobLevelsChart" class="w-full h-80"></div>
    </div>
</div>
