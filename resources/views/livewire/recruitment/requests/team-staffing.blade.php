@php($compact = $density === 'compact')

<x-slot:title>{{ __('recruitment.team_staffing.title') }}</x-slot:title>

<div class="space-y-4 pb-8">
    <section class="isolate overflow-hidden rounded-surface border border-neutral-200 bg-white shadow-e1">

        {{-- ---------- Header + summary ---------- --}}
        <div class="relative bg-secondary-dark px-6 py-6 text-white">
            <div class="relative flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <div class="mb-2 flex items-center gap-2 text-caption font-semibold uppercase tracking-wider text-white/70">
                        <x-icons.code-fork class="size-4" />
                        {{ __('recruitment.requests') }}
                    </div>
                    <h1 class="text-h3 font-bold leading-tight tracking-tight sm:text-h2">
                        {{ __('recruitment.team_staffing.title') }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-body leading-relaxed text-white/75">
                        {{ __('recruitment.team_staffing.description') }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([
                        ['value' => $summary['positions'], 'label' => __('recruitment.team_staffing.positions'), 'accent' => false],
                        ['value' => $summary['capacity'], 'label' => __('recruitment.team_staffing.approved_headcount'), 'accent' => false],
                        ['value' => $summary['filled'], 'label' => __('recruitment.team_staffing.filled_headcount'), 'accent' => false],
                        ['value' => $summary['vacancies'], 'label' => __('recruitment.team_staffing.open_capacity'), 'accent' => true],
                    ] as $stat)
                        <div class="relative min-w-28 overflow-hidden rounded-surface border border-white/10 bg-white/10 px-4 py-3">
                            @if ($stat['accent'])
                                <span class="absolute inset-y-0 start-0 w-1 bg-primary" aria-hidden="true"></span>
                            @endif
                            <div class="text-h3 font-bold tabular-nums">{{ number_format($stat['value']) }}</div>
                            <div class="mt-1 text-caption font-medium leading-snug text-white/80">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ---------- Tabs ---------- --}}
        <nav class="flex items-center gap-1 border-b border-neutral-200 bg-white px-4 pt-3"
             aria-label="{{ __('recruitment.requests') }}">
            <a href="{{ route('recruitment.requests.index') }}" wire:navigate
               class="flex items-center gap-2 rounded-t-xl border-b-2 border-transparent px-4 py-2 text-body font-semibold text-secondary-light transition hover:text-secondary-dark">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18M3 12h18M3 19h18" />
                </svg>
                {{ __('recruitment.navigation_requests') }}
            </a>
            <span class="flex items-center gap-2 rounded-t-xl border-b-2 border-primary px-4 py-2 text-body font-semibold text-primary"
                  aria-current="page">
                <x-icons.code-fork class="size-4" />
                {{ __('recruitment.team_staffing.tab') }}
            </span>
        </nav>

        @if ($root)
            {{-- ---------- Toolbar ---------- --}}
            <div class="sticky top-0 z-20 flex flex-wrap items-center justify-between gap-2 border-b border-neutral-200 bg-white/95 px-4 py-2 backdrop-blur">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- The one filter a manager actually wants: hide everything
                         that is fully staffed and quiet. Goes solid red while on,
                         so the chart never silently omits cards. --}}
                    <button type="button" wire:click="toggleNeedsAction"
                            aria-pressed="{{ $needsActionOnly ? 'true' : 'false' }}"
                            class="flex items-center gap-2 rounded-surface border px-3 py-2 text-small font-semibold transition
                                   {{ $needsActionOnly
                                       ? 'border-primary bg-primary text-white hover:opacity-90'
                                       : 'border-neutral-200 bg-white text-secondary-medium hover:border-secondary-light hover:bg-secondary-soft hover:text-primary' }}">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18l-7 8v6l-4 2v-8L3 5Z" />
                        </svg>
                        {{ __('recruitment.team_staffing.needs_action_only') }}
                        @if ($summary['vacancies'] > 0)
                            <span class="flex size-5 items-center justify-center rounded-full text-caption font-bold
                                         {{ $needsActionOnly ? 'bg-white text-primary' : 'bg-primary text-white' }}">
                                {{ $summary['vacancies'] }}
                            </span>
                        @endif
                    </button>

                    @if ($needsActionOnly && $hidden > 0)
                        <span class="text-small font-medium text-secondary-light">
                            {{ __('recruitment.team_staffing.hidden_positions', ['count' => $hidden]) }}
                        </span>
                    @endif

                    <button type="button" wire:click="toggleDensity"
                            class="flex items-center gap-2 rounded-surface border border-neutral-200 bg-white px-3 py-2 text-small font-semibold text-secondary-medium transition hover:border-secondary-light hover:bg-secondary-soft hover:text-primary">
                        <x-icons.code-fork class="size-3.5" />
                        {{ $compact ? __('general.comfortable-view') : __('general.compact-view') }}
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center overflow-hidden rounded-surface border border-neutral-200 bg-neutral-50">
                        <button type="button" wire:click="zoomOut" title="{{ __('general.zoom-out') }}"
                                class="flex size-8 items-center justify-center text-secondary-medium transition hover:bg-white hover:text-primary">
                            <span class="text-h4 font-bold">-</span>
                        </button>
                        <span class="min-w-12 border-x border-neutral-200 bg-white px-1 py-2 text-center text-small font-semibold tabular-nums text-secondary-dark">
                            {{ round($scale * 100) }}%
                        </span>
                        <button type="button" wire:click="zoomIn" title="{{ __('general.zoom-in') }}"
                                class="flex size-8 items-center justify-center text-secondary-medium transition hover:bg-white hover:text-primary">
                            <span class="text-h4 font-bold">+</span>
                        </button>
                    </div>
                    <button type="button" wire:click="fitToScreen"
                            class="rounded-surface border border-neutral-200 bg-white px-3 py-2 text-small font-semibold text-secondary-medium transition hover:border-secondary-light hover:bg-secondary-soft hover:text-primary">
                        {{ __('general.fit-to-screen') }}
                    </button>
                    <button type="button" wire:click="expandAll" x-on:click="$dispatch('staffing-expand-all')"
                            class="rounded-surface border border-neutral-200 bg-white px-3 py-2 text-small font-semibold text-secondary-medium transition hover:bg-neutral-50">
                        {{ __('general.expand-all') }}
                    </button>
                    <button type="button" wire:click="collapseAll" x-on:click="$dispatch('staffing-collapse-all')"
                            class="rounded-surface bg-primary px-3 py-2 text-small font-semibold text-white transition hover:opacity-90">
                        {{ __('general.collapse-all') }}
                    </button>
                </div>
            </div>

            {{-- ---------- Canvas ---------- --}}
            <div class="relative">
                <div wire:loading.flex wire:target="toggleNeedsAction,toggleDensity,expandAll,collapseAll"
                     class="absolute inset-0 z-30 items-center justify-center bg-white/75 backdrop-blur-[1px]">
                    <div class="flex items-center gap-2 rounded-surface border border-neutral-200 bg-white px-4 py-3 text-body font-semibold text-secondary-medium shadow-e3">
                        <x-icons.spinner class="size-5 animate-spin text-primary" />
                        {{ __('general.updating-chart') }}
                    </div>
                </div>

                <div
                    x-ref="viewport"
                    x-data="{
                        scale: @entangle('scale').live,
                        translateX: 0,
                        translateY: 0,
                        dragging: false,
                        pointerId: null,
                        lastX: 0,
                        lastY: 0,

                        transformStyle() {
                            return `transform: translate3d(${this.translateX}px, ${this.translateY}px, 0) scale(${this.scale})`
                        },

                        zoomAt(clientX, clientY, delta) {
                            const rect = this.$refs.viewport.getBoundingClientRect()
                            const x = clientX - rect.left
                            const y = clientY - rect.top
                            const prev = this.scale
                            const next = Math.min(1.6, Math.max(0.4, +(prev + delta).toFixed(2)))
                            if (next === prev) return
                            const ratio = next / prev
                            this.translateX = x - (x - this.translateX) * ratio
                            this.translateY = y - (y - this.translateY) * ratio
                            this.scale = next
                        },

                        onWheel(event) {
                            if (event.ctrlKey || event.metaKey) {
                                this.zoomAt(event.clientX, event.clientY, event.deltaY < 0 ? 0.06 : -0.06)
                                return
                            }
                            const factor = event.deltaMode === 1 ? 16 : 1
                            this.translateX -= event.deltaX * factor
                            this.translateY -= event.deltaY * factor
                        },

                        onPointerDown(event) {
                            if (event.button !== 0 || event.target.closest('button, a, input, select, textarea, [data-no-pan]')) return
                            this.dragging = true
                            this.pointerId = event.pointerId
                            this.lastX = event.clientX
                            this.lastY = event.clientY
                            this.$refs.viewport.setPointerCapture(event.pointerId)
                        },

                        onPointerMove(event) {
                            if (! this.dragging || event.pointerId !== this.pointerId) return
                            this.translateX += event.clientX - this.lastX
                            this.translateY += event.clientY - this.lastY
                            this.lastX = event.clientX
                            this.lastY = event.clientY
                        },

                        onPointerUp(event) {
                            if (! this.dragging || event.pointerId !== this.pointerId) return
                            this.dragging = false
                            this.$refs.viewport.releasePointerCapture(event.pointerId)
                        },

                        resetPan() {
                            this.translateX = 0
                            this.translateY = 0
                        },
                    }"
                    @wheel.prevent="onWheel($event)"
                    @pointerdown="onPointerDown($event)"
                    @pointermove="onPointerMove($event)"
                    @pointerup="onPointerUp($event)"
                    @pointercancel="onPointerUp($event)"
                    x-on:reset-pan.window="resetPan()"
                    :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
                    class="relative min-h-[min(72vh,820px)] w-full touch-none select-none overflow-hidden overscroll-none bg-[radial-gradient(circle_at_center,_#e5e7eb_1px,_transparent_1px)] [background-size:22px_22px]"
                >
                    <p class="pointer-events-none absolute bottom-3 start-3 z-10 rounded-surface border border-neutral-200/80 bg-white/90 px-2 py-1 text-caption font-medium text-secondary-light shadow-e1 backdrop-blur-sm">
                        {{ __('general.org-chart-nav-hint') }}
                    </p>

                    <div class="origin-top-left will-change-transform" :style="transformStyle()">
                        <div class="flex min-w-max flex-col items-center {{ $compact ? 'px-8 py-8' : 'px-16 py-12' }}">
                            @include('livewire.recruitment.requests.partials.team-staffing-node', [
                                'node' => $root,
                                'compact' => $compact,
                                'isFirst' => true,
                                'isLast' => true,
                                'isRootLevel' => true,
                            ])
                        </div>
                    </div>
                </div>
            </div>

            <p class="border-t border-neutral-200 bg-neutral-50 px-4 py-3 text-caption leading-relaxed text-secondary-light">
                {{ __('recruitment.team_staffing.tree_help') }}
            </p>
        @else
            <div class="px-6 py-16 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-surface bg-secondary-soft text-primary">
                    <x-icons.code-fork class="size-7" />
                </span>
                <h2 class="mt-4 text-h4 font-semibold text-secondary-dark">
                    {{ __('recruitment.team_staffing.no_position') }}
                </h2>
                <p class="mx-auto mt-2 max-w-sm text-body leading-relaxed text-secondary-light">
                    {{ __('recruitment.team_staffing.no_position_help') }}
                </p>
            </div>
        @endif
    </section>

    {{-- The chart's cards are the organisation chart's own, so clicking one
         opens the same employee profile it opens there. --}}
    <livewire:position.profile-drawer />
</div>
