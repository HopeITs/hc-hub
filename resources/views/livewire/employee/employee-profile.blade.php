@php
    $notSpecified = __('general.not-specified');
    $currency = [0 => $notSpecified, 1 => 'SYP', 2 => 'USD', 3 => 'EUR'];
    $formatDate = fn($date) => $date ? \Carbon\Carbon::parse($date)->translatedFormat('d M Y') : $notSpecified;
    $positionName = $currentPosition?->title ?? $notSpecified;
    $workEmail = $employee->account?->email;
    $directManagerName = $directManager?->name;
    $techManagerName = $techManager?->name;
@endphp

<div class="min-h-screen bg-neutral-50 px-3 py-3 text-secondary-dark sm:px-6 sm:py-5 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-3 sm:space-y-5">
        <section
            class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm sm:rounded-3xl">
            <div class="absolute inset-x-0 top-0 h-1 bg-primary sm:h-1.5"></div>

            <div
                class="relative flex flex-wrap items-start gap-3 p-4 pt-5 sm:items-center sm:gap-6 sm:p-8">
                <div class="shrink-0">
                    <img src="{{ $employee->getFirstMediaUrl('avatar') }}" alt="{{ $employee->name }}"
                        class="h-20 w-20 rounded-2xl border-2 border-white object-fit shadow-md ring-1 ring-neutral-200 sm:h-40 sm:w-40 sm:rounded-3xl sm:border-4 sm:shadow-lg">
                </div>

                <div class="min-w-0 flex-1 basis-[calc(100%-6rem)] sm:basis-[calc(100%-11.5rem)]">
                    <div class="mb-1.5 flex flex-wrap items-center gap-1.5 sm:mb-3 sm:gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold sm:px-3 sm:py-1 sm:text-xs {{ $employee->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-neutral-100 text-secondary-medium ring-1 ring-neutral-200' }}">
                            <span
                                class="me-1 h-1.5 w-1.5 rounded-full sm:me-1.5 sm:h-2 sm:w-2 {{ $employee->is_active ? 'bg-emerald-500' : 'bg-neutral-400' }}"></span>
                            {{ $employee->is_active ? __('general.active') : __('general.inactive') }}
                        </span>
                        @if ($employee->is_board_member)
                            <span
                                class="inline-flex rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white sm:px-3 sm:py-1 sm:text-xs">
                                @lang('general.board-member')
                            </span>
                        @endif
                    </div>

                    <h1 class="whitespace-normal break-words py-1 text-xl font-bold leading-normal tracking-tight text-secondary-dark sm:text-4xl sm:leading-normal">
                        {{ $employee->name }}</h1>
                    <p class="mt-1 whitespace-normal break-words py-0.5 text-sm font-semibold leading-normal text-primary sm:mt-2 sm:text-lg sm:leading-normal">{{ $positionName }}
                    </p>

                    <div class="mt-4 flex flex-row flex-wrap gap-x-3 gap-y-2 text-xs text-secondary-medium sm:gap-x-5 sm:text-sm">
                        @if ($currentPosition?->department)
                            <span class="inline-flex items-center gap-2"><x-icons.homepage
                                    class="h-3.5 w-3.5 text-primary sm:h-4 sm:w-4" /> {{ $currentPosition->department->name }}</span>
                        @endif
                        @if ($currentPosition?->program)
                            <span class="inline-flex items-center gap-2"><x-icons.contracts
                                    class="h-3.5 w-3.5 text-primary sm:h-4 sm:w-4" /> {{ $currentPosition->program->name }}</span>
                        @endif
                        @if ($currentPosition?->city)
                            <span class="inline-flex items-center gap-2"><x-icons.map-pin
                                    class="h-3.5 w-3.5 text-primary sm:h-4 sm:w-4" /> {{ $currentPosition->city->name }}</span>
                        @endif
                    </div>

                    <div class="mt-4 flex min-w-0 flex-row flex-wrap gap-2 sm:gap-3">
                        @if ($canEdit)
                            <a href="{{ route('employees.edit', $employee) }}" title="{{ __('general.edit') }}"
                                class="inline-flex h-9 w-9 max-w-full items-center justify-center gap-2 rounded-lg bg-primary p-0 text-sm font-bold text-white shadow-sm transition hover:brightness-90 min-[701px]:h-auto min-[701px]:w-auto min-[701px]:rounded-xl min-[701px]:px-4 min-[701px]:py-2.5">
                                <x-icons.edit class="h-3.5 w-3.5 min-[701px]:h-4 min-[701px]:w-4" /> <span class="hidden min-[701px]:inline">@lang('general.edit')</span>
                            </a>
                        @endif
                        @if ($workEmail)
                            <a href="mailto:{{ $workEmail }}" title="{{ __('general.email') }}"
                                class="inline-flex h-9 w-9 max-w-full min-w-0 items-center justify-center gap-2 rounded-lg border border-neutral-200 bg-white p-0 text-sm font-semibold text-secondary-dark transition hover:border-primary hover:text-primary min-[701px]:h-auto min-[701px]:w-auto min-[701px]:rounded-xl min-[701px]:px-4 min-[701px]:py-2.5">
                                <x-icons.envelope class="h-3.5 w-3.5 shrink-0 text-primary min-[701px]:h-4 min-[701px]:w-4" /> <span
                                    class="hidden min-w-0 truncate min-[701px]:inline">@lang('general.email')</span>
                            </a>
                        @endif
                        @if ($employee->work_phone)
                            <a href="tel:{{ $employee->work_phone }}" title="{{ __('general.work-phone') }}"
                                class="inline-flex h-9 w-9 max-w-full items-center justify-center gap-2 rounded-lg border border-neutral-200 bg-white p-0 text-sm font-semibold text-secondary-dark transition hover:border-primary hover:text-primary min-[701px]:h-auto min-[701px]:w-auto min-[701px]:rounded-xl min-[701px]:px-4 min-[701px]:py-2.5">
                                <x-icons.phone class="h-3.5 w-3.5 shrink-0 text-primary min-[701px]:h-4 min-[701px]:w-4" /> <span class="hidden min-[701px]:inline">@lang('general.work-phone')</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-4">
            @foreach ([[__('general.years-of-service'), $employee->years_of_service_formatted, 'icons.clock'], [trans_choice('general.department', 1), $currentPosition?->department?->name ?? $notSpecified, 'icons.homepage'], [trans_choice('general.center', 1), $currentPosition?->center?->name ?? $notSpecified, 'icons.map-pin'], [__('general.contract-status'), $currentContract ? __('general.active') : __('general.inactive'), 'icons.contracts']] as [$label, $value, $icon])
                <div class="rounded-xl border border-neutral-200 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4">
                    <div
                        class="mb-2 flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-white sm:mb-3 sm:h-9 sm:w-9 sm:rounded-xl">
                        <x-dynamic-component :component="$icon" class="h-3.5 w-3.5 sm:h-5 sm:w-5" />
                    </div>
                    <p
                        class="truncate text-[10px] font-semibold uppercase tracking-wide text-secondary-light sm:text-xs">
                        {{ $label }}</p>
                    <p class="mt-1 truncate text-xs font-bold text-secondary-dark sm:text-base">{{ $value }}</p>
                </div>
            @endforeach
        </section>

        <div class="grid gap-5 xl:grid-cols-3">
            <main class="space-y-5 xl:col-span-2">
                <x-profile-section :title="__('general.employment-overview')" :subtitle="__('general.professional-information')" icon="icons.user">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        @foreach ([[__('general.position'), $positionName], [trans_choice('general.job-level', 1), $currentPosition?->jobLevel?->name ?? $notSpecified], [__('general.direct-manager'), $directManagerName ?? $notSpecified], [__('general.tech-manager'), $techManagerName ?? $notSpecified], [trans_choice('general.program', 1), $currentPosition?->program?->name ?? $notSpecified], [trans_choice('general.city', 1), $currentPosition?->city?->name ?? $notSpecified]] as [$label, $value])
                            <div class="rounded-2xl bg-neutral-50 p-4">
                                <dt class="text-xs font-semibold text-secondary-light">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-bold text-secondary-dark">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-profile-section>

                <x-profile-section :title="__('general.career-history')" :subtitle="__('general.contract-details')" icon="icons.contracts">
                    <div class="space-y-4">
                        @forelse($contracts as $contract)
                            <article class="relative rounded-2xl border border-neutral-200 p-4 ps-6">
                                <span
                                    class="absolute inset-y-4 start-0 w-1 rounded-e-full {{ $contract->is_active && !$contract->terminated_at ? 'bg-primary' : 'bg-neutral-300' }}"></span>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="font-bold text-secondary-dark">
                                            {{ $contract->position?->title ?? $notSpecified }}</h3>
                                        <p class="mt-1 text-sm text-secondary-medium">
                                            {{ $contract->position?->department?->name ?? $notSpecified }}
                                            @if ($contract->contractType)
                                                · {{ $contract->contractType->name }}
                                            @endif
                                        </p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ $contract->is_active && !$contract->terminated_at ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-secondary-medium' }}">
                                        {{ $contract->is_active && !$contract->terminated_at ? __('general.active') : __('general.inactive') }}
                                    </span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-secondary-light">
                                    <span>{{ $formatDate($contract->start_date) }} -
                                        {{ $formatDate($contract->terminated_at ?? $contract->end_date) }}</span>
                                    @if ($contract->weekly_hours)
                                        <span>@lang('general.weekly-hours'): {{ $contract->weekly_hours }}</span>
                                    @endif
                                    @if ($canViewFinancial && $contract->salary)
                                        <span class="font-bold text-primary">@lang('general.salary'):
                                            {{ number_format($contract->salary) }}
                                            {{ $currency[$contract->currency] ?? '' }}</span>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="rounded-2xl bg-neutral-50 p-5 text-center text-sm text-secondary-light">
                                @lang('general.no-available-data')</p>
                        @endforelse
                    </div>
                </x-profile-section>

                <div class="grid gap-5 lg:grid-cols-2">
                    <x-profile-section :title="__('general.education')" icon="icons.education">
                        <div class="space-y-4">
                            @forelse($employee->educations as $education)
                                <div class="border-s-2 border-primary ps-4">
                                    <h3 class="text-sm font-bold text-secondary-dark">
                                        {{ $education->name ?? ($education->specialization ?? $notSpecified) }}</h3>
                                    <p class="mt-1 text-xs text-secondary-medium">
                                        {{ \App\Enums\EducationType::getLocalizedName((int) $education->degree) }} ·
                                        {{ \App\Enums\EducationStatus::getLocalizedName((int) $education->status) }}
                                    </p>
                                    @if ($education->specialization)
                                        <p class="mt-1 text-xs text-secondary-light">{{ $education->specialization }}
                                        </p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-secondary-light">@lang('general.no-available-data')</p>
                            @endforelse
                        </div>
                    </x-profile-section>

                    <x-profile-section :title="__('general.experience')" icon="icons.experience">
                        <div class="space-y-4">
                            @forelse($employee->experiences as $experience)
                                <div class="border-s-2 border-primary ps-4">
                                    <h3 class="text-sm font-bold text-secondary-dark">
                                        {{ $experience->job_title ?? $notSpecified }}</h3>
                                    <p class="mt-1 text-xs text-secondary-medium">
                                        {{ $experience->company ?? $notSpecified }}</p>
                                    <p class="mt-1 text-xs text-secondary-light">
                                        {{ $formatDate($experience->start_date) }} -
                                        {{ $formatDate($experience->end_date) }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-secondary-light">@lang('general.no-available-data')</p>
                            @endforelse
                        </div>
                    </x-profile-section>
                </div>

                @if ($canViewPrivate)
                    <x-profile-section :title="__('general.personal-information')" :subtitle="__('general.private-information')" icon="icons.contact" private>
                        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ([[__('general.national-id'), $employee->national_number ?? $notSpecified], [__('general.birthday'), $formatDate($employee->actual_dob)], [__('general.birth-place'), $employee->{'pob_' . app()->getLocale()} ?? $notSpecified], [__('general.nationality'), $employee->nationality !== null ? \App\Enums\Nationality::getLocalizedName((int) $employee->nationality) : $notSpecified], [__('general.marital-status'), $employee->marital_status !== null ? \App\Enums\MaritalStatus::getLocalizedName((int) $employee->marital_status)['value'] : $notSpecified], [__('general.children'), $employee->children ?? 0], [__('general.civil-registry-office'), $employee->civil_registry_office ?? $notSpecified], [__('general.civil-registry-number'), $employee->civil_registry_number ?? $notSpecified], [__('general.address'), $employee->address ?? $notSpecified]] as [$label, $value])
                                <div class="rounded-2xl bg-neutral-50 p-4">
                                    <dt class="text-xs font-semibold text-secondary-light">{{ $label }}</dt>
                                    <dd class="mt-1 text-sm font-bold text-secondary-dark">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-profile-section>
                @endif
            </main>

            <aside class="space-y-5">
                <x-profile-section :title="__('general.work-contact')" icon="icons.contact">
                    <div class="space-y-3 text-sm">
                        @foreach ([[__('general.email'), $workEmail, 'icons.envelope', $workEmail ? 'mailto:' . $workEmail : null], [__('general.work-phone'), $employee->work_phone, 'icons.phone', $employee->work_phone ? 'tel:' . $employee->work_phone : null], ['WhatsApp', $employee->whatsapp_work, 'icons.whatsapp', null], ['Telegram', $employee->telegram_work, 'icons.telegram', null]] as [$label, $value, $icon, $href])
                            @if ($value)
                                <div class="flex min-w-0 items-center gap-3 rounded-2xl bg-neutral-50 p-3">
                                    <x-dynamic-component :component="$icon"
                                        class="h-4 w-4 shrink-0 text-primary sm:h-5 sm:w-5" />
                                    <div class="min-w-0">
                                        <p class="text-xs text-secondary-light">{{ $label }}</p>
                                        @if ($href)
                                            <a href="{{ $href }}"
                                                class="block min-w-0 break-all font-semibold text-secondary-dark hover:text-primary">{{ $value }}</a>
                                        @else<p class="block min-w-0 break-all font-semibold text-secondary-dark">
                                                {{ $value }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        @if (!$workEmail && !$employee->work_phone && !$employee->whatsapp_work && !$employee->telegram_work)
                            <p class="text-sm text-secondary-light">@lang('general.no-available-data')</p>
                        @endif
                    </div>
                </x-profile-section>

                @if ($canViewPrivate)
                    <x-profile-section :title="__('general.personal-contact')" icon="icons.phone" private>
                        <div class="space-y-3 text-sm">
                            @foreach ([[__('general.email'), $employee->personal_email], [__('general.personal-phone'), $employee->personal_phone]] as [$label, $value])
                                <div class="rounded-2xl bg-neutral-50 p-3">
                                    <p class="text-xs text-secondary-light">{{ $label }}</p>
                                    <p class="mt-1 font-semibold text-secondary-dark">{{ $value ?? $notSpecified }}</p>
                                </div>
                            @endforeach
                        </div>
                    </x-profile-section>
                @endif

                <x-profile-section :title="__('general.skills-and-interests')" icon="icons.statistics">
                    <div class="mb-4 flex flex-wrap gap-2">
                        @forelse($skills as $skill)
                            <span
                                class="rounded-full bg-primary px-3 py-1.5 text-xs font-bold text-white">{{ $skill }}</span>
                        @empty
                            <span class="text-sm text-secondary-light">@lang('general.no-available-data')</span>
                        @endforelse
                    </div>
                    @if ($interests)
                        <div class="border-t border-neutral-100 pt-4">
                            <p class="mb-3 text-xs font-semibold text-secondary-light">@lang('general.interests')</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($interests as $interest)
                                    <span
                                        class="rounded-full bg-neutral-100 px-3 py-1.5 text-xs font-semibold text-secondary-medium">{{ $interest }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </x-profile-section>

                <x-profile-section :title="__('general.courses')" icon="icons.courses">
                    <div class="space-y-4">
                        @forelse($employee->courses->take(5) as $course)
                            <div class="border-s-2 border-primary ps-3">
                                <p class="text-sm font-bold text-secondary-dark">{{ $course->title ?? $notSpecified }}
                                </p>
                                <p class="mt-1 text-xs text-secondary-light">
                                    {{ $course->organization ?? ($course->granted_by ?? $notSpecified) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-secondary-light">@lang('general.no-available-data')</p>
                        @endforelse
                    </div>
                </x-profile-section>

                <x-profile-section :title="__('general.badges')" icon="icons.badges">
                    <div class="space-y-3">
                        @forelse($employee->badges->take(5) as $badge)
                            <div class="rounded-2xl bg-neutral-50 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-bold text-secondary-dark">
                                        {{ \App\Enums\BadgeType::getLocalizedName((int) $badge->type) }}</p>
                                    <span class="text-xs text-secondary-light">{{ $formatDate($badge->date) }}</span>
                                </div>
                                @if ($badge->reason)
                                    <p class="mt-2 text-xs text-secondary-medium">{{ $badge->reason }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-secondary-light">@lang('general.no-available-data')</p>
                        @endforelse
                    </div>
                </x-profile-section>

                @if ($canViewPrivate && $employee->relatives->isNotEmpty())
                    <x-profile-section :title="__('general.family-information')" icon="icons.relatives" private>
                        <div class="space-y-3">
                            @foreach ($employee->relatives as $relative)
                                <div class="rounded-2xl bg-neutral-50 p-3">
                                    <p class="text-sm font-bold text-secondary-dark">{{ $relative->name }}</p>
                                    <p class="mt-1 text-xs text-secondary-light">{{ $relative->relation }}
                                        @if ($relative->phone)
                                            · {{ $relative->phone }}
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </x-profile-section>
                @endif
            </aside>
        </div>
    </div>
</div>
