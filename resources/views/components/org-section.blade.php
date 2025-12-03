@props(['team' , 'managerType'])

@foreach($team ?? [] as $t)

    @foreach($t->activeContracts as $contract)
        <x-org-node :position="$t" :employee="$contract->employee" color="gray"
                    size="sm"/>
        @if($contract->position->{$managerType.'Team'}->count() > 0)
            <x-org-sub-section :subTeams="$contract->position->{$managerType.'Team'}" :managerType="$managerType"/>
            @foreach($contract->position->{$managerType.'Team'} ?? [] as $subTeam)
                @if($subTeam->activeContracts->count() < $subTeam->max_emp)
                    @foreach($subTeam->activeContracts as $subContract)
                        <x-org-node :position="$subContract"
                                    :employee="$subContract->employee" color="gray"
                                    size="sm"/>
                        @if($subContract->position->{$managerType.'Team'}->count() > 0)
                            @foreach($subContract->position->directTeam ?? [] as $subSubTeam)
                                @if($subSubTeam->activeContracts->count() < $subSubTeam->max_emp)
                                    @foreach($subSubTeam->activeContracts as $subSubContract)
                                        <x-org-node :position="$subSubContract"
                                                    :employee="$subSubContract->employee"
                                                    color="gray"
                                                    size="sm"/>
                                    @endforeach
                                    @for($i=1;$i<= $subSubTeam->max_emp - $subSubTeam->activeContracts->count() ;$i++)
                                        <x-org-empty-node :position="$subSubTeam"
                                                          size="sm"/>
                                    @endfor
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                    @for($i=1;$i<= $subTeam->max_emp - $subTeam->activeContracts->count() ;$i++)
                        <x-org-empty-node :position="$subTeam" size="sm"/>
                        @if($subTeam->directTeam->count() > 0)
                            <div
                                class=" border-s-1 border-secondary-dark ms-3 ps-2 flex flex-col gap-2 ">
                                @foreach($subTeam->directTeam ?? [] as $subSubTeam)
                                    @if($subSubTeam->activeContracts->count() < $subSubTeam->max_emp)
                                        @foreach($subSubTeam->activeContracts as $subSubContract)
                                            <x-org-node :position="$subSubContract"
                                                        :employee="$subSubContract->employee"
                                                        color="gray"
                                                        size="sm"/>
                                        @endforeach
                                        @for($i=1;$i<= $subSubTeam->max_emp - $subSubTeam->activeContracts->count() ;$i++)
                                            <x-org-empty-node :position="$subSubTeam"
                                                              size="sm"/>
                                        @endfor
                                    @endif
                                @endforeach
                            </div>

                        @endif
                    @endfor
                @endif
            @endforeach
        @endif
    @endforeach
    @if($t->activeContracts->count() < $t->max_emp)
        @for($i=1;$i<= $t->max_emp - $t->activeContracts->count() ;$i++)
            <x-org-empty-node :position="$t" size="sm"/>

            @if($t->{$managerType.'Team'}->count() > 0)
                <x-org-sub-section :subTeams="$t->{$managerType.'Team'}" :managerType="$managerType"/>

            @endif
        @endfor

    @endif

@endforeach
