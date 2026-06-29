@props(['positions', 'managerType' => 'direct', 'compact' => false, 'vLine' => 'w-px bg-gray-300', 'gap' => 'gap-4', 'wide' => false])

@php
    $leafRows = collect($positions)
        ->sortBy(fn ($p) => sprintf('%04d-%010d', $p->jobLevel?->grade ?? 999, $p->id))
        ->groupBy(fn ($p) => $p->jobLevel?->grade ?? 999);

    $poolMax = $wide ? 'max-w-full' : ($compact ? 'max-w-[36rem]' : 'max-w-[44rem]');
@endphp

<div class="relative flex w-full flex-col items-center {{ $compact ? 'pt-4' : 'pt-5' }}">
    <span class="absolute start-1/2 top-0 {{ $compact ? 'h-4' : 'h-5' }} {{ $vLine }}"></span>

    <div class="flex w-full flex-col items-center {{ $gap }} {{ $poolMax }} rounded-2xl border border-gray-100 bg-gray-50/40 {{ $compact ? 'p-2' : 'p-3' }}">
        @foreach($leafRows as $grade => $gradePositions)
            <div class="flex w-full flex-wrap content-start justify-center {{ $gap }}">
                @foreach($gradePositions as $position)
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
