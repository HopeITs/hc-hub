@php
    $employee = $this->employee;
    $position = $this->position;
    $manager = $position?->directManager;
    $managerEmployee = $manager?->activeContracts?->first()?->employee;
    $reports = $this->directReports;
@endphp

<div x-data="{ show: @entangle('open') }"
     x-cloak
     x-show="show"
     x-on:keydown.escape.window="show = false"
     class="fixed inset-0 z-50">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-secondary-dark/40 backdrop-blur-[1px]"
         x-show="show" x-transition.opacity
         wire:click="close"></div>

    {{-- Panel: right drawer on desktop, bottom sheet on mobile --}}
    <div class="absolute inset-x-0 bottom-0 max-h-[90vh] sm:inset-y-0 sm:end-0 sm:start-auto sm:max-h-none sm:w-[26rem]
                flex flex-col rounded-t-3xl bg-white shadow-2xl sm:rounded-none"
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-full sm:translate-y-0 sm:translate-x-full sm:rtl:-translate-x-full"
         x-transition:enter-end="translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-y-0 sm:translate-x-0"
         x-transition:leave-end="translate-y-full sm:translate-y-0 sm:translate-x-full sm:rtl:-translate-x-full">

        @if($employee)
            {{-- Header --}}
            <div class="relative shrink-0 overflow-hidden bg-gradient-to-br from-secondary-dark to-secondary-medium px-6 pb-6 pt-7 text-white">
                <div class="absolute -end-10 -top-12 size-40 rounded-full bg-primary/20 blur-2xl"></div>
                <button type="button" wire:click="close"
                        class="absolute end-4 top-4 flex size-8 items-center justify-center rounded-lg text-white/80 transition hover:bg-white/10 hover:text-white">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>

                <div class="relative flex flex-col items-center text-center">
                    <img src="{{ $employee->getFirstMediaUrl('avatar') }}" alt="{{ $employee->name }}"
                         class="size-24 rounded-full object-cover ring-4 ring-white/30 shadow-lg">
                    <h2 class="mt-3 text-lg font-extrabold tracking-tight">{{ $employee->name }}</h2>
                    @if($position)
                        <p class="mt-0.5 text-sm font-semibold text-white/80">{{ $position->title }}</p>
                    @endif
                    @if($employee->is_board_member)
                        <span class="mt-2 rounded-full bg-primary px-3 py-0.5 text-[11px] font-bold uppercase tracking-wide">
                            @lang('general.board-member')
                        </span>
                    @endif
                </div>
            </div>

            {{-- Body --}}
            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                {{-- Org placement --}}
                @if($position)
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([
                            ['label' => trans_choice('general.program', 1), 'value' => $position->program?->name],
                            ['label' => trans_choice('general.department', 1), 'value' => $position->department?->name],
                            ['label' => trans_choice('general.center', 1), 'value' => $position->center?->name],
                            ['label' => trans_choice('general.city', 1), 'value' => $position->city?->name ?? $employee->city?->name],
                        ] as $item)
                            <div class="rounded-xl border border-gray-100 bg-secondary-soft/5 px-3 py-2">
                                <div class="text-[10px] font-bold uppercase tracking-wide text-secondary-light">{{ $item['label'] }}</div>
                                <div class="mt-0.5 truncate text-sm font-semibold text-secondary-dark">{{ $item['value'] ?? __('general.not-specified') }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Contact --}}
                @php($contacts = collect([
                    ['icon' => 'phone', 'value' => $employee->work_phone, 'href' => 'tel:'],
                    ['icon' => 'phone', 'value' => $employee->personal_phone, 'href' => 'tel:'],
                    ['icon' => 'mail', 'value' => $employee->personal_email, 'href' => 'mailto:'],
                ])->filter(fn ($c) => filled($c['value'])))
                @if($contacts->isNotEmpty())
                    <div>
                        <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-secondary-light">@lang('general.contact')</h3>
                        <div class="space-y-1.5">
                            @foreach($contacts as $contact)
                                <a href="{{ $contact['href'].$contact['value'] }}"
                                   class="flex items-center gap-2.5 rounded-xl border border-gray-100 px-3 py-2 text-sm font-medium text-secondary-dark transition hover:border-primary-200 hover:bg-primary-50">
                                    <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-secondary-soft/10 text-primary">
                                        @if($contact['icon'] === 'mail')
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                                        @else
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2 5a2 2 0 0 1 2-2h2l2 5-2 1a12 12 0 0 0 5 5l1-2 5 2v2a2 2 0 0 1-2 2A16 16 0 0 1 2 5Z"/></svg>
                                        @endif
                                    </span>
                                    <span class="truncate" dir="ltr">{{ $contact['value'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Manager --}}
                @if($managerEmployee)
                    <div>
                        <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-secondary-light">@lang('general.direct-manager')</h3>
                        <button type="button" wire:click="$dispatch('open-profile', { employeeId: {{ $managerEmployee->id }} })"
                                class="flex w-full items-center gap-3 rounded-xl border border-gray-100 px-3 py-2 text-start transition hover:border-primary-200 hover:bg-primary-50">
                            <img src="{{ $managerEmployee->getFirstMediaUrl('avatar') }}" alt="" class="size-9 rounded-full object-cover">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-bold text-secondary-dark">{{ $managerEmployee->name }}</div>
                                <div class="truncate text-xs text-secondary-light">{{ $manager->title }}</div>
                            </div>
                        </button>
                    </div>
                @endif

                {{-- Direct reports --}}
                @if($reports->isNotEmpty())
                    <div>
                        <h3 class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-secondary-light">
                            @lang('general.direct-reports')
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-secondary-medium">{{ $reports->count() }}</span>
                        </h3>
                        <div class="space-y-1.5">
                            @foreach($reports as $report)
                                @php($rEmployee = $report->activeContracts->first()?->employee)
                                @if($rEmployee)
                                    <button type="button" wire:click="$dispatch('open-profile', { employeeId: {{ $rEmployee->id }} })"
                                            class="flex w-full items-center gap-3 rounded-xl border border-gray-100 px-3 py-2 text-start transition hover:border-primary-200 hover:bg-primary-50">
                                        <img src="{{ $rEmployee->getFirstMediaUrl('avatar') }}" alt="" class="size-9 rounded-full object-cover">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-bold text-secondary-dark">{{ $rEmployee->name }}</div>
                                            <div class="truncate text-xs text-secondary-light">{{ $report->title }}</div>
                                        </div>
                                    </button>
                                @else
                                    <div class="flex items-center gap-3 rounded-xl border border-dashed border-gray-200 px-3 py-2 text-secondary-light">
                                        <span class="flex size-9 items-center justify-center rounded-full border border-dashed border-gray-300"><x-icons.user class="size-4"/></span>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold">@lang('general.vacant-position')</div>
                                            <div class="truncate text-xs">{{ $report->title }}</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Full profile link --}}
                @if(filled($employee->username))
                    <a href="{{ route('employees.show', ['employee' => $employee->username]) }}" wire:navigate
                       class="flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600">
                        @lang('general.view-full-profile')
                        <svg class="size-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
