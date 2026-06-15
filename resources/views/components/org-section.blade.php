@props(['team', 'managerType'])

@foreach($team ?? [] as $position)
    <div class="relative flex flex-col items-center gap-3 pt-5">
        <span class="absolute start-1/2 top-0 h-5 w-px bg-gray-300"></span>

        @foreach($position->activeContracts as $contract)
            <x-org-node
                :position="$position"
                :employee="$contract->employee"
                :is-manager="$position->{$managerType.'Team'}->isNotEmpty()"
                size="sm"
            />
        @endforeach

        @for($i = 0; $i < max(0, $position->max_emp - $position->activeContracts->count()); $i++)
            <x-org-empty-node :position="$position" size="sm"/>
        @endfor

        @if($position->{$managerType.'Team'}->isNotEmpty())
            <div class="relative mt-2 flex items-start gap-4 border-t border-gray-300">
                <x-org-section :team="$position->{$managerType.'Team'}" :managerType="$managerType"/>
            </div>
        @endif
    </div>
@endforeach
