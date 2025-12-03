<div class="flex flex-col items-center w-full overflow-x-auto py-6">
    <div class="flex justify-between">
        <div class="grid grid-cols-5 gap-4 mb-6">

            <x-forms.select-input wire:model.live="city_id" label="City">
                <option value="">All Cities</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                @endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model.live="program_id" label="Program">
                <option value="">All Program</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                @endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model.live="center_id" label="Center">
                <option value="">All Centers</option>
                @foreach($centers as $center)
                    <option value="{{ $center->id }}">{{ $center->name }}</option>
                @endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model.live="department_id" label="Department">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model.live="manager_type" label="Manager Type">
                <option value="direct">Direct Managers</option>
                <option value="tech">Tech Managers</option>
            </x-forms.select-input>

        </div>
    </div>
    {{-- CEO --}}
    @if(isset($tree['root']))
        <div class="flex gap-4 items-center mb-10">
            @foreach($tree['root'] as $root)
                @if($root->employee() !== null)
                    <x-org-node :position="$root" color="cyan" size="lg"/>
                @else
                    <x-org-empty-node :position="$root" color="cyan" size="lg"/>
                @endif
            @endforeach

        </div>
    @endif

    {{-- Level 1: Board --}}
    @if(isset($tree['rootAssistant']))
        <div class="flex flex-row gap-4 mb-10">
            @foreach($tree['rootAssistant'] as $p)
                @if($p->employee() !== null)
                    <x-org-node :position="$p" color="cyan"/>
                @else
                    <x-org-empty-node :position="$p"/>
                @endif

            @endforeach
        </div>
    @endif
    @if(isset($tree['managers']))
        {{-- Level 2: Managers & Teams --}}
        <div class="flex flex-row gap-8 justify-center">
            @foreach($tree['managers'] as $m)
                <div class="flex flex-col items-center">
                    @if($m->employee() !== null)
                        <x-org-node :position="$m"/>
                    @else
                        <x-org-empty-node :position="$m"/>
                    @endif

                    <div class="flex flex-col gap-2 mt-2">
                        @if($manager_type === 'direct')
                            @foreach($m->directTeam ?? [] as $t)
                                @if($t->activeContracts->count() < $t->max_emp)
                                    @foreach($t->activeContracts as $contract)
                                        <x-org-node :position="$t" :employee="$contract->employee" color="gray"
                                                    size="sm"/>
                                        @if($contract->position->directTeam->count() > 0)
                                            @foreach($contract->position->directTeam ?? [] as $subTeam)
                                                @if($subTeam->activeContracts->count() < $subTeam->max_emp)
                                                    @foreach($subTeam->activeContracts as $subContract)
                                                        <x-org-node :position="$subContract"
                                                                    :employee="$subContract->employee" color="gray"
                                                                    size="sm"/>
                                                    @endforeach
                                                    @for($i=1;$i<= $subTeam->max_emp - $subTeam->activeContracts->count() ;$i++)
                                                        <x-org-empty-node :position="$subTeam" size="sm"/>
                                                    @endfor
                                                @endif
                                            @endforeach
                                        @endif
                                    @endforeach
                                    @for($i=1;$i<= $t->max_emp - $t->activeContracts->count() ;$i++)
                                        <x-org-empty-node :position="$t" size="sm"/>

                                        @if($t->directTeam->count() > 0)
                                            <div
                                                class=" border-s-1 border-secondary-dark ms-3 ps-2 flex flex-col gap-2 ">
                                                @foreach($t->directTeam ?? [] as $subTeam)
                                                    @if($subTeam->activeContracts->count() < $subTeam->max_emp)
                                                        @foreach($subTeam->activeContracts as $subContract)
                                                            <x-org-node :position="$subContract"
                                                                        :employee="$subContract->employee" color="gray"
                                                                        size="sm"/>
                                                        @endforeach
                                                        @for($i=1;$i<= $subTeam->max_emp - $subTeam->activeContracts->count() ;$i++)
                                                            <x-org-empty-node :position="$subTeam" size="sm"/>
                                                        @endfor
                                                    @endif
                                                @endforeach
                                            </div>

                                        @endif
                                    @endfor
                                @else
                                    @foreach($t->activeContracts as $contract)
                                        <x-org-node :position="$t" :employee="$contract->employee" color="gray"
                                                    size="sm"/>

                                        @if($t->directTeam->count() > 0)
                                            <div class="flex flex-row gap-2 mt-2">
                                                @foreach($t->directTeam ?? [] as $tt)
                                                    @if($tt->activeContracts->count() < $tt->max_emp)
                                                        @foreach($tt->activeContracts as $contract)
                                                            @dump('hi')
                                                            <x-org-node class="ps-4" :position="$contract"
                                                                        :employee="$contract->employee" color="gray"
                                                                        size="xs"/>
                                                        @endforeach
                                                        @for($i=1;$i<= $tt->max_emp - $tt->activeContracts->count() ;$i++)
                                                            <x-org-empty-node :position="$tt" size="xs"/>
                                                        @endfor
                                                    @else
                                                        @foreach($tt->activeContracts as $contract)
                                                            <x-org-node :position="$t" :employee="$contract->employee"
                                                                        color="gray"
                                                                        size="xs"/>
                                                            {{--                                                            @if($tt->techTeam->count() > 0)--}}
                                                            {{--                                                                <div class="flex flex-col gap-2 mt-2">--}}

                                                            {{--                                                                </div>--}}
                                                            {{--                                                            @endif--}}
                                                        @endforeach
                                                    @endif

                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                @endif

                            @endforeach
                        @else
                            @foreach($m->techTeam ?? [] as $t)
                                @if($t->activeContracts->count() < $t->max_emp)
                                    @foreach($t->activeContracts as $contract)
                                        <x-org-node :position="$t" :employee="$contract->employee" color="gray"
                                                    size="sm"/>
                                        @if($contract->position->directTeam->count() > 0)
                                            @foreach($contract->position->directTeam ?? [] as $subTeam)
                                                @if($subTeam->activeContracts->count() < $subTeam->max_emp)
                                                    @foreach($subTeam->activeContracts as $subContract)
                                                        <x-org-node :position="$subContract"
                                                                    :employee="$subContract->employee" color="gray"
                                                                    size="sm"/>
                                                    @endforeach
                                                    @for($i=1;$i<= $subTeam->max_emp - $subTeam->activeContracts->count() ;$i++)
                                                        <x-org-empty-node :position="$subTeam" size="sm"/>
                                                    @endfor
                                                @endif
                                            @endforeach
                                        @endif
                                    @endforeach
                                    @for($i=1;$i<= $t->max_emp - $t->activeContracts->count() ;$i++)
                                        <x-org-empty-node :position="$t" size="sm"/>

                                        @if($t->techTeam->count() > 0)
                                            <div
                                                class=" border-s-1 border-secondary-dark ms-3 ps-2 flex flex-col gap-2 ">
                                                @foreach($t->techTeam ?? [] as $subTeam)
                                                    @if($subTeam->activeContracts->count() < $subTeam->max_emp)
                                                        @foreach($subTeam->activeContracts as $subContract)
                                                            <x-org-node :position="$subContract"
                                                                        :employee="$subContract->employee" color="gray"
                                                                        size="sm"/>
                                                        @endforeach
                                                        @for($i=1;$i<= $subTeam->max_emp - $subTeam->activeContracts->count() ;$i++)
                                                            <x-org-empty-node :position="$subTeam" size="sm"/>
                                                        @endfor
                                                    @endif
                                                @endforeach
                                            </div>

                                        @endif
                                    @endfor
                                @else
                                    @foreach($t->activeContracts as $contract)
                                        <x-org-node :position="$t" :employee="$contract->employee" color="gray"
                                                    size="sm"/>

                                        @if($t->techTeam->count() > 0)
                                            <div class="flex flex-row gap-2 mt-2">
                                                @foreach($t->techTeam ?? [] as $tt)
                                                    @if($tt->activeContracts->count() < $tt->max_emp)
                                                        @foreach($tt->activeContracts as $contract)
                                                            @dump('hi')
                                                            <x-org-node class="ps-4" :position="$contract"
                                                                        :employee="$contract->employee" color="gray"
                                                                        size="xs"/>
                                                        @endforeach
                                                        @for($i=1;$i<= $tt->max_emp - $tt->activeContracts->count() ;$i++)
                                                            <x-org-empty-node :position="$tt" size="xs"/>
                                                        @endfor
                                                    @else
                                                        @foreach($tt->activeContracts as $contract)
                                                            <x-org-node :position="$t" :employee="$contract->employee"
                                                                        color="gray"
                                                                        size="xs"/>
                                                        @endforeach
                                                    @endif

                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                @endif

                            @endforeach
                        @endif

                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
