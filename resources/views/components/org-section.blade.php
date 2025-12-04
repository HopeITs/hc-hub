@props(['team' , 'managerType'])

@foreach($team ?? [] as $t)
<div class="flex flex-col items-center gap-2">
    @foreach($t->activeContracts as $contract)
        <x-org-node :position="$t" :employee="$contract->employee" color="gray"
                    size="sm"/>
        @if($contract->position->{$managerType.'Team'}->count() > 0)
            <div class="flex {{$t->job_level_id === 3 ? ' flex-col items-start justify-start' : ' flex-row items-start justify-start'}}  gap-2 mt-2">
                <x-org-section :team="$contract->position->{$managerType.'Team'}"
                               :managerType="$managerType"/>
            </div>
        @endif
    @endforeach
    @if($t->activeContracts->count() < $t->max_emp)
        @for($i=1;$i<= $t->max_emp - $t->activeContracts->count() ;$i++)
            <x-org-empty-node :position="$t" size="sm"/>

            @if($t->{$managerType.'Team'}->count() > 0)
                <div class="flex {{$t->job_level_id === 3 ? ' flex-col items-start justify-start' : ' flex-row items-start justify-start'}}  gap-2 mt-2">
                    <x-org-section :team="$t->{$managerType.'Team'}" :managerType="$managerType"/>
                </div>
            @endif
        @endfor
    @endif</div>


@endforeach




