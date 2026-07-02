@props(['team', 'managerType' => 'direct', 'compact' => false, 'collapsed' => []])

@php
    // Solid connectors for the direct line, dashed for the technical line.
    $isTech = $managerType === 'tech';
    $vLine = $isTech ? 'w-0 border-s border-dashed border-amber-300' : 'w-px bg-gray-300';
    $hBorder = $isTech ? 'border-t border-dashed border-amber-300' : 'border-t border-gray-300';
    $gap = $compact ? 'gap-2' : 'gap-4';
    $teamRel = $managerType.'Team';

    // Split the team: leaves (no further reports) get pooled into a compact
    // wrapped grid below the branch row so wide teams stop sprawling.
    $members = collect($team)->filter(fn ($p) => $p->is_active);
    $leaves = $members->filter(fn ($p) => $p->{$teamRel}->isEmpty());
    $branches = $members->filter(fn ($p) => $p->{$teamRel}->isNotEmpty());
@endphp

<div class="flex w-full flex-col items-center {{ $gap }}">
    {{-- Branch reports first (higher grade / managing others). --}}
    @if($branches->isNotEmpty())
        <div class="flex w-full items-start justify-center {{ $gap }}">
            @foreach($branches as $position)
                @php($subTeam = $position->{$teamRel})
                <section
                    x-data="{ open: @js(!($collapsed[$position->id] ?? true)) }"
                    x-on:expand-all.window="open = true"
                    x-on:collapse-all.window="open = false"
                    class="relative flex flex-col items-center {{ $compact ? 'gap-2 pt-4' : 'gap-3 pt-5' }}"
                >
                    <span class="absolute start-1/2 top-0 {{ $compact ? 'h-4' : 'h-5' }} {{ $vLine }}"></span>

                    <div class="rounded-3xl border border-gray-200 bg-white/80 {{ $compact ? 'p-2' : 'p-3' }} shadow-sm backdrop-blur-sm">
                        <div class="flex flex-wrap justify-center gap-2">
                            @foreach($position->activeContracts as $contract)
                                <x-org-node
                                    :position="$position"
                                    :employee="$contract->employee"
                                    :is-manager="true"
                                    :manager-type="$managerType"
                                    :compact="$compact"
                                />
                            @endforeach

                            @for($i = 0; $i < max(0, $position->max_emp - $position->activeContracts->count()); $i++)
                                <x-org-empty-node :position="$position" :compact="$compact"/>
                            @endfor
                        </div>

                        @if($subTeam->isNotEmpty())
                            <div x-cloak x-show="open" x-collapse>
                                <div class="relative mt-4 flex items-start justify-center {{ $gap }} {{ $hBorder }}">
                                    <x-org-section :team="$subTeam" :managerType="$managerType" :compact="$compact" :collapsed="$collapsed"/>
                                </div>
                            </div>
                        @endif

                        @if($subTeam->isNotEmpty())
                            <button type="button"
                                    wire:click="toggleCollapse({{ $position->id }})"
                                    x-on:click="open = !open"
                                    class="mt-2 flex w-full items-center justify-between gap-2 rounded-[0.5cqw] bg-gray-100 px-2.5 py-1.5 text-[10px] font-bold text-secondary-medium transition hover:bg-secondary-soft hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-1"
                                    :aria-expanded="open"
                                    :title="open ? @js(__('general.collapse')) : @js(__('general.expand'))">
                                <span>{{ $subTeam->count() }} @lang('general.direct-reports')</span>
                                <svg class="size-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    {{-- Leaf reports: lower-grade individual contributors in a wrapped pool below. --}}
    @if($leaves->isNotEmpty())
        <x-org-leaf-pool :positions="$leaves" :managerType="$managerType" :compact="$compact" :vLine="$vLine" :gap="$gap"/>
    @endif
</div>
