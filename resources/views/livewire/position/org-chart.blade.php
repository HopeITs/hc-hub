<div class="flex flex-col items-center w-full  py-6">
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


    <!-- Controls -->
    <div class="flex items-center gap-3 mb-4">
        <button wire:click="zoomOut" class="px-3 py-1 bg-gray-200 rounded">-</button>
        <span class="text-sm">{{ floor($scale * 100) }}%</span>
        <button wire:click="zoomIn" class="px-3 py-1 bg-gray-200 rounded">+</button>
        <button wire:click="resetZoom" class="px-3 py-1 bg-gray-200 rounded">Reset</button>
    </div>

    <!-- Zoom Container -->
    <div
        x-data @wheel.prevent="$event.deltaY < 0  ? $wire.zoomIn() : $wire.zoomOut()"
        class="origin-top transition-transform duration-200"
        style="transform: scale({{ $scale }});"
    >

        <div class="flex flex-col items-center w-full  py-6">

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

                                <x-org-section :team="$m->{$manager_type.'Team'}" :managerType="$manager_type"/>


                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</div>
