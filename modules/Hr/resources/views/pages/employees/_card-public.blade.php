{{--
    The public digital ID card (hr.employees.card.show), in English: what a
    person who scans a Hope Center card sees. Rendered by EmployeeCardController.
--}}
<x-layouts::print :title="__('hr_employees.card.public.heading')">
    <article class="mx-auto flex max-w-sm flex-col items-center gap-4 rounded-panel border border-panel-border bg-white p-6 text-center shadow-card" data-digital-id>
        <img src="{{ asset('logo-hub.png') }}" alt="Hope Center" class="h-10 w-auto">
        <p class="text-xs font-semibold uppercase tracking-wide text-secondary-medium">{{ __('hr_employees.card.public.heading') }}</p>
        <img src="{{ $photoUrl }}" alt="{{ $name }}" class="size-32 rounded-full object-cover ring-4 ring-primary/20">
        <div>
            <h1 class="text-2xl font-bold" dir="ltr">{{ $name }}</h1>
            @if ($positionTitle)
                <p class="mt-1 text-base font-semibold text-primary" dir="ltr">{{ $positionTitle }}</p>
            @endif
            <p class="mt-1 text-sm text-secondary-medium" dir="ltr">{{ collect([$centerName, $cityName])->filter()->implode(' · ') }}</p>
        </div>

        <dl class="grid w-full gap-2 text-start text-sm">
            @if ($emergency)
                <div class="flex items-center justify-between gap-3 rounded-lg bg-status-critical-soft px-3 py-2">
                    <dt class="font-semibold">{{ __('hr_employees.card.public.emergency') }}</dt>
                    <dd><a href="{{ $emergencyHref }}" dir="ltr" class="font-bold underline">{{ $emergency }}</a></dd>
                </div>
            @endif
            @if ($phone)
                <div class="flex items-center justify-between gap-3 px-3 py-1">
                    <dt>{{ __('hr_employees.card.public.phone') }}</dt>
                    <dd><a href="{{ $phoneHref }}" dir="ltr" class="underline">{{ $phone }}</a></dd>
                </div>
            @endif
        </dl>

        @if ($telegramHref || $linkedinHref)
            <div class="flex gap-3">
                @if ($telegramHref)
                    <a href="{{ $telegramHref }}" rel="noopener noreferrer nofollow" target="_blank" class="rounded-lg border border-panel-border px-3 py-1.5 text-sm font-semibold">{{ __('hr_employees.card.public.telegram') }}</a>
                @endif
                @if ($linkedinHref)
                    <a href="{{ $linkedinHref }}" rel="noopener noreferrer nofollow" target="_blank" class="rounded-lg border border-panel-border px-3 py-1.5 text-sm font-semibold">{{ __('hr_employees.card.public.linkedin') }}</a>
                @endif
            </div>
        @endif
    </article>
</x-layouts::print>
