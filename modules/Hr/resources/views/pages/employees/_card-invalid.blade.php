{{--
    The answer to every public card link that is malformed, old, disabled or
    not the current card (404). Rendered by EmployeeCardController.
--}}
<x-layouts::print :title="__('hr_employees.card.public.invalid_title')">
    <article class="mx-auto flex max-w-sm flex-col items-center gap-3 rounded-panel border border-panel-border bg-white p-6 dark:bg-surface-1 text-center shadow-card" data-digital-id-invalid>
        <img src="{{ asset('logo-hub.png') }}" alt="{{ __('common.organisation_short_name') }}" class="h-10 w-auto dark:hidden" data-no-dim>
        <img src="{{ asset('logo-hub-white.png') }}" alt="{{ __('common.organisation_short_name') }}" class="hidden h-10 w-auto dark:block" data-no-dim>
        <h1 class="text-xl font-bold">{{ __('hr_employees.card.public.invalid_title') }}</h1>
        <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_employees.card.public.invalid_body') }}</p>
    </article>
</x-layouts::print>
