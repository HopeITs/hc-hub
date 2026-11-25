{{--
    The type and responsible filters of calendar/index, placed in the
    register's filters slot by ⚡index.blade.php, whose top PHP block defines
    $typeDotClasses and $selectedFilterCount. The view buttons, the period
    row and the Add menu live in ⚡index.blade.php.
--}}
            <div class="inline-flex items-center rounded-lg border border-secondary-light bg-white dark:border-field-line dark:bg-surface-1">
            <div class="filter-picker relative">
                <button
                    type="button"
                    data-flux-button
                    wire:click="$toggle('showTypeFilterMenu')"
                    aria-expanded="{{ $showTypeFilterMenu ? 'true' : 'false' }}"
                    aria-haspopup="listbox"
                    aria-label="{{ __('calendar.filter_by_type') }}"
                    data-test="calendar-type-filter-button"
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-s-lg px-2.5 py-1.5 text-secondary-dark hover:bg-secondary-soft dark:text-ink dark:hover:bg-surface-2"
                >
                    <flux:icon.funnel variant="micro" class="size-4" />
                    <span class="hidden text-sm sm:inline">{{ __('calendar.filter_by_type') }}</span>
                </button>

                @if ($showTypeFilterMenu)
                    <div
                        x-on:click.outside="$wire.showTypeFilterMenu = false"
                        x-on:keydown.escape.window="$wire.showTypeFilterMenu = false"
                        data-flux-menu
                        role="listbox"
                        aria-multiselectable="true"
                        class="filter-menu absolute end-0 z-30 mt-1 min-w-44 rounded-lg border p-[.3125rem] shadow-xs"
                    >
                        @foreach ([
                            ['milestone', 'showMilestones', 'tasks.milestone', 'calendar-filter-milestones'],
                            ['task', 'showTasks', 'tasks.task', 'calendar-filter-tasks'],
                            ['report', 'showReports', 'calendar.report_deadline', 'calendar-filter-reports'],
                            ['monthly_report', 'showMonthlyReports', 'calendar.monthly_report_due', 'calendar-filter-monthly-reports'],
                        ] as [$typeKey, $showProp, $labelKey, $testHook])
                            @continue(! $this->canSeeType($typeKey))
                            <div class="group flex items-center gap-1 rounded-md hover:bg-secondary-soft dark:hover:bg-white/[0.06]" role="option" aria-selected="{{ $$showProp ? 'true' : 'false' }}">
                                <button type="button" data-flux-menu-item wire:click="toggleType('{{ $typeKey }}')" class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 px-2 py-1.5 text-start text-sm" data-test="{{ $testHook }}">
                                    <span class="size-1.5 shrink-0 rounded-full {{ $typeDotClasses[$typeKey] }}"></span>
                                    <span class="min-w-0 flex-1 truncate">{{ __($labelKey) }}</span>
                                    @if ($$showProp)<flux:icon.check class="size-4 shrink-0 text-primary dark:text-brand-text" />@endif
                                </button>
                                <button type="button" wire:click="focusOnlyType('{{ $typeKey }}')" class="me-1 shrink-0 cursor-pointer rounded px-1.5 py-1 text-[10px] font-semibold text-secondary-medium opacity-0 hover:bg-white group-hover:opacity-100 dark:text-ink-muted dark:hover:bg-white/[0.06]" data-test="calendar-filter-only-{{ $typeKey }}">
                                    {{ __('calendar.only') }}
                                </button>
                            </div>
                        @endforeach

                        <div class="my-1 border-t border-secondary-soft dark:border-line"></div>

                        @foreach ([
                            ['proposal', 'showProposals', 'calendar.proposal_deadline', 'calendar-filter-proposals'],
                            ['transfer', 'showTransfers', 'calendar.transfer', 'calendar-filter-transfers'],
                            ['donor', 'showDonors', 'calendar.donor_follow_up', 'calendar-filter-donors'],
                            ['project', 'showProjects', 'calendar.project_timeline', 'calendar-filter-projects'],
                        ] as [$typeKey, $showProp, $labelKey, $testHook])
                            @continue(! $this->canSeeType($typeKey))
                            <div class="group flex items-center gap-1 rounded-md hover:bg-secondary-soft dark:hover:bg-white/[0.06]" role="option" aria-selected="{{ $$showProp ? 'true' : 'false' }}">
                                <button type="button" data-flux-menu-item wire:click="toggleType('{{ $typeKey }}')" class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 px-2 py-1.5 text-start text-sm" data-test="{{ $testHook }}">
                                    <span class="size-1.5 shrink-0 rounded-full {{ $typeDotClasses[$typeKey] }}"></span>
                                    <span class="min-w-0 flex-1 truncate">{{ __($labelKey) }}</span>
                                    @if ($$showProp)<flux:icon.check class="size-4 shrink-0 text-primary dark:text-brand-text" />@endif
                                </button>
                                <button type="button" wire:click="focusOnlyType('{{ $typeKey }}')" class="me-1 shrink-0 cursor-pointer rounded px-1.5 py-1 text-[10px] font-semibold text-secondary-medium opacity-0 hover:bg-white group-hover:opacity-100 dark:text-ink-muted dark:hover:bg-white/[0.06]" data-test="calendar-filter-only-{{ $typeKey }}">
                                    {{ __('calendar.only') }}
                                </button>
                            </div>
                        @endforeach

                        {{-- What other modules add to the calendar (leave, holidays…): a switch each, no "only". --}}
                        @if ($this->calendarLayers !== [])
                            <div class="my-1 border-t border-secondary-soft dark:border-line"></div>

                            @foreach ($this->calendarLayers as $layer)
                                <div class="flex items-center gap-1 rounded-md hover:bg-secondary-soft dark:hover:bg-white/[0.06]" role="option" aria-selected="{{ $layer['shown'] ? 'true' : 'false' }}">
                                    <button type="button" data-flux-menu-item wire:click="toggleLayer('{{ $layer['key'] }}')" class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 px-2 py-1.5 text-start text-sm" data-test="calendar-layer-toggle-{{ $layer['key'] }}">
                                        <flux:icon :name="$layer['icon']" variant="micro" class="size-3 shrink-0 text-secondary-medium dark:text-ink-muted" />
                                        <span class="min-w-0 flex-1 truncate">{{ $layer['label'] }}</span>
                                        @if ($layer['shown'])<flux:icon.check class="size-4 shrink-0 text-primary dark:text-brand-text" />@endif
                                    </button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif
            </div>

            <div class="h-5 w-px shrink-0 bg-secondary-light dark:bg-line"></div>

            <x-ui.multi-select-popover
                :label="__('calendar.filter_by_responsible')"
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-e-lg px-2.5 py-1.5 text-secondary-dark hover:bg-secondary-soft dark:text-ink dark:hover:bg-surface-2"
                data-test="calendar-responsible-filter-button"
            >
                <x-slot:trigger>
                    <flux:icon.user variant="micro" class="size-4" />
                    <span class="hidden text-sm sm:inline">{{ __('calendar.filter_by_responsible') }}</span>
                    @if ($selectedFilterCount > 0)
                        <span class="inline-flex min-w-4 items-center justify-center rounded-full bg-primary dark:bg-white/10 dark:text-ink px-1 text-[10px] font-semibold text-white" data-test="calendar-responsible-filter-count">{{ $selectedFilterCount }}</span>
                    @endif
                </x-slot:trigger>

                <button type="button" wire:click="showOnlyMe" class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-start text-sm hover:bg-secondary-soft dark:hover:bg-white/[0.06]" data-test="calendar-responsible-filter-me">
                    <span class="min-w-0 flex-1 truncate">{{ __('calendar.me') }}</span>
                    @if ($filterEmployeeIds === [] && ! $this->showsAllStaff())<flux:icon.check class="ms-auto size-4 shrink-0 text-primary dark:text-brand-text" />@endif
                </button>
                <button type="button" wire:click="showAllStaff" class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-start text-sm hover:bg-secondary-soft dark:hover:bg-white/[0.06]" data-test="calendar-responsible-filter-all">
                    <span class="min-w-0 flex-1 truncate">{{ __('calendar.all_staff') }}</span>
                    @if ($this->showsAllStaff())<flux:icon.check class="ms-auto size-4 shrink-0 text-primary dark:text-brand-text" />@endif
                </button>
                <div class="my-1 border-t border-secondary-soft dark:border-line"></div>
                <div
                    wire:ignore
                    x-data="employeeCheckList"
                    x-effect="paint(optionSearch, touched)"
                    x-on:click="pick($event)"
                    class="contents"
                    data-employees-url="{{ route('employees.picker-options', ['l' => app()->getLocale(), 'v' => \Modules\Hr\Models\Employee::PickerListVersion]) }}"
                    data-model="filterEmployeeIds"
                    data-role="option"
                    data-item-class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-start text-sm hover:bg-secondary-soft dark:hover:bg-white/[0.06]"
                    data-check-class="ms-auto size-4 shrink-0 text-primary dark:text-brand-text"
                ></div>
            </x-ui.multi-select-popover>
            </div>
