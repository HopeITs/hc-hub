<div class="flex flex-col items-center w-full overflow-x-auto py-6">
    <div class="flex justify-between">
        <div class="flex justify-center gap-4 mb-6">
            <x-forms.select-input wire:model.live="city_id" label="City">
                <option value="">All</option>
                @foreach(\App\Models\City::where('is_active',true)->get() as $city)
                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                @endforeach
            </x-forms.select-input>

            <x-forms.select-input wire:model.live="program_id" label="Program">
                <option value="">All</option>
                @foreach(\App\Models\Program::all() as $program)
                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                @endforeach
            </x-forms.select-input>

            <x-forms.select-input wire:model.live="center_id" label="Center">
                <option value="">All</option>
                @foreach(\App\Models\Center::all() as $center)
                    <option value="{{ $center->id }}">{{ $center->name }}</option>
                @endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model="department_id" label="Department">
                <option value="">All</option>
                @foreach(\App\Models\Department::all() as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model="manager_type" label="Manager Type">
                <option value="direct">Direct Managers</option>
                <option value="tech">Tech Managers</option>
            </x-forms.select-input>
        </div>
    </div>
    {{-- CEO --}}
    @if(isset($tree['root']))
        <div class="flex flex-col items-center mb-10">
            <x-org-node :position="$tree['root']" color="cyan" size="lg"/>
        </div>
    @endif

    {{-- Level 1: Board --}}
    @if(isset($tree['board']))
        <div class="flex flex-row gap-4 mb-10">
            @foreach($tree['board'] as $p)
                <x-org-node :position="$p" color="cyan"/>
            @endforeach
        </div>
    @endif
    @if(isset($tree['managers']))
        {{-- Level 2: Managers & Teams --}}
        <div class="flex flex-row gap-8 justify-center">
            @foreach($tree['managers'] as $m)
                <div class="flex flex-col items-center">
                    <x-org-node :position="$m" color="cyan"/>
                    <div class="flex flex-col gap-2 mt-2">

                        @foreach($tree['teams'][$m->id] ?? [] as $t)
                            @if($t->activeContracts->count() < $t->max_emp)
                                @foreach($t->activeContracts as $contract)
                                    <x-org-node :position="$t" :employee="$contract->employee" color="gray" size="sm"/>
                                @endforeach
                                @for($i=1;$i<= $t->max_emp - $t->activeContracts->count() ;$i++)
                                    <x-org-empty-node :position="$t" size="sm"/>
                                @endfor
                            @else
                                @foreach($t->activeContracts as $contract)
                                    <x-org-node :position="$t" :employee="$contract->employee" color="gray" size="sm"/>
                                @endforeach
                            @endif

                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
