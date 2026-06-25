@props(['team', 'managerType' => 'direct', 'compact' => false])

@php
    // Solid connectors for the direct line, dashed for the technical line.
    $isTech = $managerType === 'tech';
    $vLine = $isTech ? 'w-0 border-s border-dashed border-amber-300' : 'w-px bg-gray-300';
    $hBorder = $isTech ? 'border-t border-dashed border-amber-300' : 'border-t border-gray-300';
    $gap = $compact ? 'gap-2' : 'gap-4';
    $teamRel = $managerType.'Team';

    // Split the team: leaves (no further reports) get pooled into a compact
    // wrapped grid so wide teams stop sprawling; branches keep the tree layout.
    $members = collect($team)->filter(fn ($p) => $p->is_active);
    $leaves = $members->filter(fn ($p) => $p->{$teamRel}->isEmpty());
    $branches = $members->filter(fn ($p) => $p->{$teamRel}->isNotEmpty());
    $leafRows = $leaves
        ->sortBy(fn ($p) => sprintf('%04d-%010d', $p->jobLevel?->grade ?? 999, $p->id))
        ->groupBy(fn ($p) => $p->jobLevel?->grade ?? 999);

    // Keep the pool roughly square: cap columns by the leaf count.
    $poolMax = $compact ? 'max-w-[36rem]' : 'max-w-[44rem]';
@endphp

{{-- Leaf reports: a flexible, wrapping pool that keeps the chart narrow. --}}
{{-- Branch reports first (higher grade), then the leaf pool, so each row reads grade-ascending. --}}
@foreach($branches as $position)
    <div class="relative flex flex-col items-center {{ $compact ? 'gap-2 pt-4' : 'gap-3 pt-5' }}">
        <span class="absolute start-1/2 top-0 {{ $compact ? 'h-4' : 'h-5' }} {{ $vLine }}"></span>

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

        <div class="relative mt-2 flex items-start justify-center {{ $gap }} {{ $hBorder }}">
            <x-org-section :team="$position->{$teamRel}" :managerType="$managerType" :compact="$compact"/>
        </div>
    </div>
@endforeach

{{-- Leaf reports: a flexible, wrapping pool that keeps the chart narrow. --}}
@if($leaves->isNotEmpty())
    <div class="relative flex flex-col items-center {{ $compact ? 'pt-4' : 'pt-5' }}">
        <span class="absolute start-1/2 top-0 {{ $compact ? 'h-4' : 'h-5' }} {{ $vLine }}"></span>

        <div class="flex flex-col items-center {{ $gap }} {{ $poolMax }} rounded-2xl border border-gray-100 bg-gray-50/40 {{ $compact ? 'p-2' : 'p-3' }}">
            @foreach($leafRows as $grade => $positions)
                <div class="flex flex-wrap content-start justify-center {{ $gap }} w-full">
                    @foreach($positions as $position)
                        @foreach($position->activeContracts as $contract)
                            <x-org-node
                                :position="$position"
                                :employee="$contract->employee"
                                :manager-type="$managerType"
                                :compact="$compact"
                            />
                        @endforeach

                        @for($i = 0; $i < max(0, $position->max_emp - $position->activeContracts->count()); $i++)
                            <x-org-empty-node :position="$position" :compact="$compact"/>
                        @endfor
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endif
