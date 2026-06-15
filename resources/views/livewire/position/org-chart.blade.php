<div class="space-y-5 pb-8">
    <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        <div class="relative bg-gradient-to-r from-secondary-dark to-secondary-medium px-6 py-6 text-white">
            <div class="absolute -end-16 -top-20 size-56 rounded-full bg-white/5"></div>
            <div class="absolute end-32 top-8 size-24 rounded-full bg-primary/30 blur-2xl"></div>

            <div class="relative flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                <div>
                    <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-white/70">
                        <x-icons.code-fork class="size-4" />
                        @lang('general.organization-overview')
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">@lang('general.orgn-chart')</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-white/70">@lang('general.org-chart-subtitle')</p>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach([
                        ['value' => $stats['positions'], 'label' => __('general.total-positions'), 'color' => 'bg-white/10'],
                        ['value' => $stats['employees'], 'label' => __('general.filled-positions'), 'color' => 'bg-emerald-400/15'],
                        ['value' => $stats['vacancies'], 'label' => __('general.vacant-positions'), 'color' => 'bg-amber-300/15'],
                        ['value' => $stats['managers'], 'label' => __('general.top-level-managers'), 'color' => 'bg-primary/30'],
                    ] as $stat)
                        <div class="min-w-28 rounded-2xl border border-white/10 {{ $stat['color'] }} px-4 py-3 backdrop-blur-sm">
                            <div class="text-2xl font-extrabold">{{ number_format($stat['value']) }}</div>
                            <div class="mt-0.5 text-[11px] font-medium text-white/70">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="border-b border-gray-200 bg-gray-50/70 px-5 py-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-sm font-bold text-secondary-dark">
                    <x-icons.cog class="size-4 text-primary" />
                    @lang('general.chart-filters')
                </div>
                <button type="button"
                        wire:click="resetFilters"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary-50">
                    @lang('general.reset-filters')
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <label class="space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">{{ trans_choice('general.city', 1) }}</span>
                    <x-forms.select-input wire:model.live="city_id">
                        <option value="">@lang('general.all-cities')</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">{{ trans_choice('general.program', 1) }}</span>
                    <x-forms.select-input wire:model.live="program_id">
                        <option value="">@lang('general.all-programs')</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">{{ trans_choice('general.center', 1) }}</span>
                    <x-forms.select-input wire:model.live="center_id">
                        <option value="">@lang('general.all-centers')</option>
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">{{ trans_choice('general.department', 1) }}</span>
                    <x-forms.select-input wire:model.live="department_id">
                        <option value="">@lang('general.all-departments')</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">@lang('general.reporting-line')</span>
                    <x-forms.select-input wire:model.live="manager_type">
                        <option value="direct">@lang('general.direct-manager')</option>
                        <option value="tech">@lang('general.tech-manager')</option>
                    </x-forms.select-input>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-white px-5 py-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                    <button type="button" wire:click="zoomOut" title="{{ __('general.zoom-out') }}"
                            class="flex size-9 items-center justify-center text-secondary-medium transition hover:bg-white hover:text-primary">
                        <span class="text-lg font-bold">-</span>
                    </button>
                    <span class="min-w-14 border-x border-gray-200 bg-white px-2 py-2 text-center text-xs font-bold text-secondary-dark">
                        {{ round($scale * 100) }}%
                    </span>
                    <button type="button" wire:click="zoomIn" title="{{ __('general.zoom-in') }}"
                            class="flex size-9 items-center justify-center text-secondary-medium transition hover:bg-white hover:text-primary">
                        <span class="text-lg font-bold">+</span>
                    </button>
                </div>

                <button type="button" wire:click="fitToScreen"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-secondary-medium transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary">
                    @lang('general.fit-to-screen')
                </button>
                <button type="button" wire:click="resetZoom"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-secondary-medium transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary">
                    @lang('general.reset-view')
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="hidden items-center gap-1.5 text-xs text-secondary-light lg:flex">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a2 2 0 1 1 4 0v3-5a2 2 0 1 1 4 0v5-2a2 2 0 1 1 4 0v5a8 8 0 0 1-8 8h-1a7 7 0 0 1-5.6-2.8L2.8 15A2 2 0 0 1 6 12.6L7 14v-3Z"/>
                    </svg>
                    @lang('general.drag-to-pan')
                </span>
                <button type="button" wire:click="expandAll"
                        class="rounded-xl bg-secondary-dark px-3 py-2 text-xs font-semibold text-white transition hover:bg-secondary-medium">
                    @lang('general.expand-all')
                </button>
                <button type="button" wire:click="collapseAll"
                        class="rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-600">
                    @lang('general.collapse-all')
                </button>
            </div>
        </div>

        <div class="relative">
            <div wire:loading.flex
                 wire:target="city_id,program_id,center_id,department_id,manager_type,resetFilters"
                 class="absolute inset-0 z-30 items-center justify-center bg-white/75 backdrop-blur-[1px]">
                <div class="flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-secondary-medium shadow-lg">
                    <x-icons.spinner class="size-5 animate-spin text-primary" />
                    @lang('general.updating-chart')
                </div>
            </div>

            <div
                x-data="{
                    isDragging: false,
                    startX: 0,
                    startY: 0,
                    translateX: 0,
                    translateY: 0,
                    mouseDown(event) {
                        if (event.target.closest('a, button')) return
                        this.isDragging = true
                        this.startX = event.clientX - this.translateX
                        this.startY = event.clientY - this.translateY
                    },
                    mouseMove(event) {
                        if (!this.isDragging) return
                        this.translateX = event.clientX - this.startX
                        this.translateY = event.clientY - this.startY
                    },
                    mouseUp() { this.isDragging = false }
                }"
                @mousedown="mouseDown($event)"
                @mousemove.window="mouseMove($event)"
                @mouseup.window="mouseUp"
                x-on:reset-pan.window="translateX = 0; translateY = 0"
                class="relative min-h-[680px] w-full select-none overflow-hidden bg-[radial-gradient(circle_at_center,_#e5e7eb_1px,_transparent_1px)] [background-size:22px_22px]"
                :class="isDragging ? 'cursor-grabbing' : 'cursor-grab'"
            >
                @if(collect($tree['root'])->isEmpty())
                    <div class="absolute inset-0 flex items-center justify-center p-6">
                        <div class="max-w-md rounded-3xl border border-dashed border-gray-300 bg-white/90 px-8 py-10 text-center shadow-sm">
                            <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-primary-50 text-primary">
                                <x-icons.code-fork class="size-7" />
                            </span>
                            <h2 class="mt-4 text-lg font-bold text-secondary-dark">@lang('general.no-org-chart-data')</h2>
                            <p class="mt-2 text-sm text-secondary-light">@lang('general.no-org-chart-data-help')</p>
                        </div>
                    </div>
                @else
                    <div class="origin-top transition-transform duration-200"
                         :style="`transform: translate(${translateX}px, ${translateY}px) scale({{ $scale }});`"
                         @wheel.prevent="$event.deltaY < 0 ? $wire.zoomIn() : $wire.zoomOut()">
                        <div class="flex min-w-max flex-col items-center px-14 py-12">
                            <div class="relative flex items-center gap-4 pb-10">
                                @foreach($tree['root'] as $root)
                                    @if($root->activeContracts->isNotEmpty())
                                        <x-org-node :position="$root" :is-manager="true" size="lg"/>
                                    @else
                                        <x-org-empty-node :position="$root" size="lg"/>
                                    @endif
                                @endforeach
                                <span class="absolute bottom-0 start-1/2 h-10 w-px bg-gray-300"></span>
                            </div>

                            @if(collect($tree['rootAssistant'])->isNotEmpty())
                                <div class="relative flex items-center gap-4 border-t border-gray-300 pb-10 pt-5">
                                    @foreach($tree['rootAssistant'] as $assistant)
                                        <div class="relative pt-3">
                                            <span class="absolute start-1/2 top-0 h-3 w-px bg-gray-300"></span>
                                            @if($assistant->activeContracts->isNotEmpty())
                                                <x-org-node :position="$assistant" size="md"/>
                                            @else
                                                <x-org-empty-node :position="$assistant" size="md"/>
                                            @endif
                                        </div>
                                    @endforeach
                                    <span class="absolute bottom-0 start-1/2 h-10 w-px bg-gray-300"></span>
                                </div>
                            @endif

                            @if(collect($tree['managers'])->isNotEmpty())
                                <div class="flex items-start justify-center gap-7 border-t border-gray-300 px-4">
                                    @foreach($tree['managers'] as $manager)
                                        @php($team = $manager->{$manager_type.'Team'})
                                        <section
                                            x-data="{ open: @js(!($collapsed[$manager->id] ?? true)) }"
                                            x-on:expand-all.window="open = true"
                                            x-on:collapse-all.window="open = false"
                                            class="relative flex flex-col items-center pt-6"
                                        >
                                            <span class="absolute start-1/2 top-0 h-6 w-px bg-gray-300"></span>
                                            <div class="rounded-3xl border border-gray-200 bg-white/80 p-3 shadow-sm backdrop-blur-sm">
                                                <div class="mb-2 flex items-center justify-between gap-2 px-1">
                                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-[10px] font-bold text-secondary-medium">
                                                        {{ $team->count() }} @lang('general.direct-reports')
                                                    </span>
                                                    @if($team->isNotEmpty())
                                                        <button type="button"
                                                                wire:click="toggleCollapse({{ $manager->id }})"
                                                                x-on:click="open = !open"
                                                                class="flex size-7 items-center justify-center rounded-lg text-secondary-light transition hover:bg-primary-50 hover:text-primary"
                                                                :title="open ? @js(__('general.collapse')) : @js(__('general.expand'))">
                                                            <svg class="size-4 transition-transform" :class="open ? 'rotate-180' : ''"
                                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>

                                                @if($manager->activeContracts->isNotEmpty())
                                                    <x-org-node :position="$manager" :is-manager="$team->isNotEmpty()" size="lg"/>
                                                @else
                                                    <x-org-empty-node :position="$manager" size="lg"/>
                                                @endif

                                                @if($team->isNotEmpty())
                                                    <div x-cloak x-show="open" x-collapse>
                                                        <div class="relative mt-4 flex items-start gap-4 border-t border-gray-300">
                                                            <x-org-section :team="$team" :managerType="$manager_type"/>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </section>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-8 rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-4 text-sm text-secondary-light">
                                    @lang('general.no-data-filters')
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
