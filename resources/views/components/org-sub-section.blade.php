@props(['subTeams' , 'managerType'])

<div class=" border-s-1 border-secondary-dark ms-3 ps-2 flex flex-col gap-2 ">
    @foreach($subTeams ?? [] as $subTeam)

        @foreach($subTeam->activeContracts as $subContract)
            <x-org-node :position="$subTeam"
                        :employee="$subContract->employee" color="gray"
                        size="sm"/>
            @if($subContract->position->{$managerType.'Team'}->count() > 0)
                <x-org-sub-section :sub-teams="$subContract->position->{$managerType.'Team'}"
                                   :manager-type="$managerType"/>
            @endif
        @endforeach
        @if($subTeam->activeContracts->count() < $subTeam->max_emp)
            @for($i=1;$i<= $subTeam->max_emp - $subTeam->activeContracts->count() ;$i++)
                <x-org-empty-node :position="$subTeam" size="sm"/>
                @if($subTeam->{$managerType.'Team'}->count() > 0)
                    <x-org-sub-section :sub-teams="$subTeam->{$managerType.'Team'}"
                                       :manager-type="$managerType"/>

                @endif
            @endfor
        @endif
    @endforeach
</div>
