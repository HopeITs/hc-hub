@php
    $compact = $density === 'compact';
    $isTech = $manager_type === 'tech';
    // Structural connectors mirror the active line: dashed amber for tech.
    $trunk = $isTech ? 'border-s border-dashed border-amber-300' : 'bg-gray-300';
    $branchBorder = $isTech ? 'border-dashed border-amber-300' : 'border-gray-300';
    $colGap = $compact ? 'gap-3' : 'gap-7';

    // Recursively count filled positions (staff) within a manager's whole branch.
    $staffCount = function ($team, $rel) use (&$staffCount) {
        $total = 0;
        foreach ($team as $position) {
            $total += $position->activeContracts->count();
            $total += $staffCount($position->{$rel}, $rel);
        }
        return $total;
    };
@endphp

<div class="space-y-4 pb-8"
     x-data="{
        filtersOpen: false,
        exporting: false,
        async exportChart(format) {
            const node = document.getElementById('org-chart-export')
            if (!node || this.exporting) return
            this.exporting = true
            try {
                const { toPng } = await import('https://esm.sh/html-to-image@1.11.11')
                const dataUrl = await toPng(node, { backgroundColor: '#ffffff', pixelRatio: 2, cacheBust: true })
                if (format === 'png') {
                    const a = document.createElement('a')
                    a.href = dataUrl; a.download = 'org-chart.png'; a.click()
                } else {
                    const { jsPDF } = await import('https://esm.sh/jspdf@2.5.1')
                    const img = new Image(); img.src = dataUrl
                    await new Promise(r => { img.onload = r })
                    const pdf = new jsPDF({ orientation: img.width >= img.height ? 'l' : 'p', unit: 'pt', format: [img.width, img.height] })
                    pdf.addImage(dataUrl, 'PNG', 0, 0, img.width, img.height)
                    pdf.save('org-chart.pdf')
                }
            } catch (e) {
                console.error('Org chart export failed', e)
            } finally {
                this.exporting = false
            }
        }
     }"
     x-on:keydown.escape.window="filtersOpen = false">

    <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        {{-- ---------- Header + stats ---------- --}}
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

        {{-- ---------- View switcher ---------- --}}
        <div class="flex items-center gap-1 border-b border-gray-200 bg-white px-4 pt-3">
            <button type="button" wire:click="$set('view', 'chart')"
                    class="flex items-center gap-2 rounded-t-xl border-b-2 px-4 py-2.5 text-sm font-bold transition {{ $view === 'chart' ? 'border-primary text-primary' : 'border-transparent text-secondary-light hover:text-secondary-dark' }}">
                <x-icons.code-fork class="size-4" />
                @lang('general.chart-view')
            </button>
            <button type="button" wire:click="$set('view', 'directory')"
                    class="flex items-center gap-2 rounded-t-xl border-b-2 px-4 py-2.5 text-sm font-bold transition {{ $view === 'directory' ? 'border-primary text-primary' : 'border-transparent text-secondary-light hover:text-secondary-dark' }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18M3 12h18M3 19h18"/></svg>
                @lang('general.directory-view')
            </button>
        </div>

        @if($view === 'directory')
            <livewire:position.directory />
        @else
        {{-- ---------- Slim sticky toolbar ---------- --}}
        <div class="sticky top-0 z-20 flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-white/95 px-4 py-2.5 backdrop-blur">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-2.5 flex items-center text-secondary-light">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3-3"/>
                        </svg>
                    </span>
                    <input type="search" wire:model.live.debounce.350ms="search"
                           placeholder="{{ __('general.search-name-or-position') }}"
                           class="w-52 rounded-xl border border-gray-200 bg-gray-50 py-2 ps-8 pe-3 text-xs font-medium text-secondary-dark placeholder:text-secondary-light focus:border-primary-200 focus:bg-white focus:ring-2 focus:ring-primary-100" />
                    @if($this->isSearching)
                        <button type="button" wire:click="$set('search', '')"
                                class="absolute inset-y-0 end-2 flex items-center text-secondary-light transition hover:text-primary">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                            </svg>
                        </button>
                    @endif
                </div>

                <button type="button" x-on:click="filtersOpen = true"
                        class="relative flex items-center gap-2 rounded-xl bg-secondary-dark px-3 py-2 text-xs font-semibold text-white transition hover:bg-secondary-medium">
                    <x-icons.cog class="size-4" />
                    @lang('general.filters')
                    @if($this->activeFilterCount > 0)
                        <span class="flex size-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold">
                            {{ $this->activeFilterCount }}
                        </span>
                    @endif
                </button>

                {{-- Reporting line quick switch --}}
                <div class="flex items-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50 text-xs font-semibold">
                    <button type="button" wire:click="$set('manager_type', 'direct')"
                            class="px-3 py-2 transition {{ ! $isTech ? 'bg-primary text-white' : 'text-secondary-medium hover:bg-white' }}">
                        @lang('general.direct-manager')
                    </button>
                    <button type="button" wire:click="$set('manager_type', 'tech')"
                            class="px-3 py-2 transition {{ $isTech ? 'bg-amber-500 text-white' : 'text-secondary-medium hover:bg-white' }}">
                        @lang('general.tech-manager')
                    </button>
                </div>

                <button type="button" wire:click="toggleDensity"
                        class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-secondary-medium transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary">
                    <x-icons.code-fork class="size-3.5" />
                    {{ $compact ? __('general.comfortable-view') : __('general.compact-view') }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                    <button type="button" wire:click="zoomOut" title="{{ __('general.zoom-out') }}"
                            class="flex size-8 items-center justify-center text-secondary-medium transition hover:bg-white hover:text-primary">
                        <span class="text-lg font-bold">-</span>
                    </button>
                    <span class="min-w-12 border-x border-gray-200 bg-white px-1 py-1.5 text-center text-xs font-bold text-secondary-dark">
                        {{ round($scale * 100) }}%
                    </span>
                    <button type="button" wire:click="zoomIn" title="{{ __('general.zoom-in') }}"
                            class="flex size-8 items-center justify-center text-secondary-medium transition hover:bg-white hover:text-primary">
                        <span class="text-lg font-bold">+</span>
                    </button>
                </div>
                <button type="button" wire:click="fitToScreen"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-secondary-medium transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary">
                    @lang('general.fit-to-screen')
                </button>
                <button type="button" wire:click="expandAll"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-secondary-medium transition hover:bg-gray-50">
                    @lang('general.expand-all')
                </button>
                <button type="button" wire:click="collapseAll"
                        class="rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-600">
                    @lang('general.collapse-all')
                </button>

                {{-- Export menu --}}
                <div class="relative" x-data="{ menu: false }" x-on:click.outside="menu = false">
                    <button type="button" x-on:click="menu = !menu" :disabled="exporting"
                            class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-secondary-medium transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary disabled:opacity-60">
                        <svg x-show="!exporting" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                        <x-icons.spinner x-show="exporting" x-cloak class="size-4 animate-spin" />
                        @lang('general.export')
                    </button>
                    <div x-show="menu" x-cloak x-transition
                         class="absolute end-0 z-30 mt-1 w-36 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg">
                        <button type="button" x-on:click="menu = false; exportChart('png')"
                                class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-secondary-dark transition hover:bg-primary-50 hover:text-primary">
                            @lang('general.export-png')
                        </button>
                        <button type="button" x-on:click="menu = false; exportChart('pdf')"
                                class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-secondary-dark transition hover:bg-primary-50 hover:text-primary">
                            @lang('general.export-pdf')
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------- Canvas ---------- --}}
        <div class="relative">
            <div wire:loading.flex
                 wire:target="city_id,program_id,center_id,department_id,job_level_id,focus_manager_id,manager_type,search,resetFilters,toggleDensity"
                 class="absolute inset-0 z-30 items-center justify-center bg-white/75 backdrop-blur-[1px]">
                <div class="flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-secondary-medium shadow-lg">
                    <x-icons.spinner class="size-5 animate-spin text-primary" />
                    @lang('general.updating-chart')
                </div>
            </div>

            @if($this->isSearching)
                {{-- Flat search results grid (hierarchy is not meaningful while searching). --}}
                @php($results = $this->searchResults)
                <div class="min-h-[680px] w-full bg-[radial-gradient(circle_at_center,_#e5e7eb_1px,_transparent_1px)] [background-size:22px_22px]">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-white/80 px-5 py-3">
                        <span class="text-sm font-semibold text-secondary-medium">
                            {{ trans_choice('general.search-results-count', $results->count(), ['count' => $results->count()]) }}
                            <span class="text-secondary-light">— "{{ $search }}"</span>
                        </span>
                        <button type="button" wire:click="$set('search', '')"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary-50">
                            @lang('general.clear-search')
                        </button>
                    </div>

                    @if($results->isEmpty())
                        <div class="flex min-h-[560px] items-center justify-center p-6">
                            <div class="max-w-md rounded-3xl border border-dashed border-gray-300 bg-white/90 px-8 py-10 text-center shadow-sm">
                                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-primary-50 text-primary">
                                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3-3"/>
                                    </svg>
                                </span>
                                <h2 class="mt-4 text-lg font-bold text-secondary-dark">@lang('general.no-search-results')</h2>
                                <p class="mt-2 text-sm text-secondary-light">@lang('general.no-search-results-help')</p>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-wrap justify-center gap-4 p-6">
                            @foreach($results as $result)
                                @if($result->activeContracts->isNotEmpty())
                                    @foreach($result->activeContracts as $contract)
                                        <x-org-node :position="$result" :employee="$contract->employee" :manager-type="$manager_type" :compact="$compact" size="md"/>
                                    @endforeach
                                @else
                                    <x-org-empty-node :position="$result" :compact="$compact" size="md"/>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
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
                        <div id="org-chart-export" class="flex min-w-max flex-col items-center {{ $compact ? 'px-8 py-8' : 'px-14 py-12' }}">
                            {{-- Root (CEO) and their deputies/assistants share the first row. --}}
                            <div class="relative flex items-end justify-center gap-4 pb-10">
                                @foreach($tree['root'] as $root)
                                    @if($root->activeContracts->isNotEmpty())
                                        <x-org-node :position="$root" :is-manager="true" :manager-type="$manager_type" :compact="$compact" size="lg"/>
                                    @else
                                        <x-org-empty-node :position="$root" :compact="$compact" size="lg"/>
                                    @endif
                                @endforeach

                                @foreach($tree['rootAssistant'] as $assistant)
                                    @if($assistant->activeContracts->isNotEmpty())
                                        <x-org-node :position="$assistant" :manager-type="$manager_type" :compact="$compact" size="lg"/>
                                    @else
                                        <x-org-empty-node :position="$assistant" :compact="$compact" size="lg"/>
                                    @endif
                                @endforeach

                                <span class="absolute bottom-0 start-1/2 h-10 w-px {{ $trunk }}"></span>
                            </div>

                            @if(collect($tree['managers'])->isNotEmpty())
                                <div class="flex items-start justify-center {{ $colGap }} border-t {{ $branchBorder }} px-4">
                                    @foreach($tree['managers'] as $manager)
                                        @php($team = $manager->{$manager_type.'Team'})
                                        <section
                                            x-data="{ open: @js(!($collapsed[$manager->id] ?? true)) }"
                                            x-on:expand-all.window="open = true"
                                            x-on:collapse-all.window="open = false"
                                            class="relative flex flex-col items-center pt-6"
                                        >
                                            <span class="absolute start-1/2 top-0 h-6 w-px {{ $trunk }}"></span>
                                            <div class="rounded-3xl border border-gray-200 bg-white/80 {{ $compact ? 'p-2' : 'p-3' }} shadow-sm backdrop-blur-sm">
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

                                                {{-- Center the manager card over its (potentially wider) team. --}}
                                                <div class="flex justify-center">
                                                    @if($manager->activeContracts->isNotEmpty())
                                                        <x-org-node :position="$manager" :is-manager="$team->isNotEmpty()" :manager-type="$manager_type" :compact="$compact" size="lg"/>
                                                    @else
                                                        <x-org-empty-node :position="$manager" :compact="$compact" size="lg"/>
                                                    @endif
                                                </div>

                                                @if($team->isNotEmpty())
                                                    @php($branchStaff = $staffCount($team, $manager_type.'Team'))
                                                    {{-- Collapsed: a compact department-summary node instead of the team. --}}
                                                    <button type="button" x-show="!open" x-cloak
                                                            wire:click="toggleCollapse({{ $manager->id }})"
                                                            x-on:click="open = true"
                                                            class="mx-auto mt-3 flex w-44 items-center justify-between gap-2 rounded-2xl border border-dashed border-primary-200 bg-primary-50/60 px-3 py-2 text-start transition hover:border-primary hover:bg-primary-50">
                                                        <span class="min-w-0">
                                                            <span class="block text-[11px] font-bold text-primary">{{ $team->count() }} @lang('general.direct-reports')</span>
                                                            <span class="block text-[10px] font-semibold text-secondary-light">{{ $branchStaff }} @lang('general.staff-total')</span>
                                                        </span>
                                                        <svg class="size-4 shrink-0 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                                    </button>

                                                    <div x-cloak x-show="open" x-collapse>
                                                        <div class="relative mt-4 flex items-start justify-center gap-4 border-t {{ $branchBorder }}">
                                                            <x-org-section :team="$team" :managerType="$manager_type" :compact="$compact"/>
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
            @endif
        </div>
        @endif
    </section>

    {{-- Employee profile drawer (shared by chart + directory cards). --}}
    <livewire:position.profile-drawer />

    {{-- ---------- Off-canvas filter drawer ---------- --}}
    <div x-cloak x-show="filtersOpen" class="fixed inset-0 z-40" x-transition.opacity>
        <div class="absolute inset-0 bg-secondary-dark/40 backdrop-blur-[1px]" x-on:click="filtersOpen = false"></div>

        <div class="absolute inset-y-0 end-0 flex w-full max-w-sm flex-col bg-white shadow-2xl"
             x-show="filtersOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-full rtl:-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full rtl:-translate-x-full">

            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <div class="flex items-center gap-2 text-sm font-bold text-secondary-dark">
                    <x-icons.cog class="size-4 text-primary" />
                    @lang('general.chart-filters')
                </div>
                <button type="button" x-on:click="filtersOpen = false"
                        class="flex size-8 items-center justify-center rounded-lg text-secondary-light transition hover:bg-gray-100 hover:text-primary">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5">
                <label class="block space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">{{ trans_choice('general.city', 1) }}</span>
                    <x-forms.select-input wire:model.live="city_id">
                        <option value="">@lang('general.all-cities')</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">{{ trans_choice('general.program', 1) }}</span>
                    <x-forms.select-input wire:model.live="program_id">
                        <option value="">@lang('general.all-programs')</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">{{ trans_choice('general.job-level', 1) }}</span>
                    <x-forms.select-input wire:model.live="job_level_id">
                        <option value="">@lang('general.all-job-levels')</option>
                        @foreach($jobLevels as $level)
                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">
                        {{ $isTech ? __('general.tech-manager') : __('general.direct-manager') }}
                    </span>
                    <x-forms.select-input wire:model.live="focus_manager_id">
                        <option value="">@lang('general.all-managers')</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}">
                                {{ $manager->activeContracts->first()?->employee?->name }} — {{ $manager->title }}
                            </option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">{{ trans_choice('general.center', 1) }}</span>
                    <x-forms.select-input wire:model.live="center_id">
                        <option value="">@lang('general.all-centers')</option>
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">{{ trans_choice('general.department', 1) }}</span>
                    <x-forms.select-input wire:model.live="department_id">
                        <option value="">@lang('general.all-departments')</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-semibold text-secondary-medium">@lang('general.reporting-line')</span>
                    <x-forms.select-input wire:model.live="manager_type">
                        <option value="direct">@lang('general.direct-manager')</option>
                        <option value="tech">@lang('general.tech-manager')</option>
                    </x-forms.select-input>
                    <span class="block pt-1 text-[11px] leading-snug text-secondary-light">@lang('general.reporting-line-help')</span>
                </label>
            </div>

            <div class="flex items-center gap-2 border-t border-gray-200 px-5 py-4">
                <button type="button" wire:click="resetFilters" x-on:click="filtersOpen = false"
                        class="flex-1 rounded-xl border border-gray-200 px-3 py-2.5 text-xs font-semibold text-secondary-medium transition hover:bg-gray-50">
                    @lang('general.reset-filters')
                </button>
                <button type="button" x-on:click="filtersOpen = false"
                        class="flex-1 rounded-xl bg-primary px-3 py-2.5 text-xs font-semibold text-white transition hover:bg-primary-600">
                    @lang('general.apply')
                </button>
            </div>
        </div>
    </div>
</div>
