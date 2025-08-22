<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="__('general.projects-management')"
            :crumbs="[
['label' => __('general.projects-management')],
        ['label' => trans_choice('general.project',2) , 'url' => route('projects.index')] ,
        ['label' => $project->name]
    ]"
            :showButton="true"

        />

    </div>
    <div>
        <h1>{{$project->name}}</h1>
    </div>


    <x-slot name="icon">
        <div class="flex justify-between">

            @if($project->getFirstMediaUrl('logo') !== "")
                <img class="h-10 m-4 " src="{{$project->getFirstMediaUrl('logo')}}"/>
            @else
                <svg class="size-10 text-gray-800 dark:text-white" aria-hidden="true"
                     xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linejoin="round" stroke-width="2"
                          d="M4 5a1 1 0 0 1 1-1h11.586a1 1 0 0 1 .707.293l2.414 2.414a1 1 0 0 1 .293.707V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5Z"/>
                    <path stroke="currentColor" stroke-linejoin="round" stroke-width="2"
                          d="M8 4h8v4H8V4Zm7 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
            @endif
            <div class="flex justify-between items-center">
                <x-forms.toggle-input
                    :active="$project->is_active"
                    :model-id="$project->id"
                    wire-event="confirmToggleItemStatus"/>
                <x-table.edit-btn wire:click="edit({{ $project->id }})"></x-table.edit-btn>
                <x-table.delete-btn :modelId="$project->id"/>
            </div>

        </div>
    </x-slot>
    <ul class="text-gray-400 mb-4">
        <li class="flex items-center">
            <x-icons.calendar class="me-2"/>
            {{ __('general.start_date') .': '. $project->start_date?->format('d-m-Y')}}
        </li>
        <li class="flex items-center justify-between">
            <div class="flex items-center">
                <x-icons.building class="me-2"/>
                <span>
                               {{ trans_choice('general.center' , 2) .': '. $project->centers_count}}
                           </span>
            </div>
            <div class="flex items-center">
                <x-icons.map-pin class="me-2"/>
                <span>
                                  {{ trans_choice('general.city' , 2) .': '. $project->cities_count}}
                            </span>

            </div>

        </li>


    </ul>
    <span class=" pt-4 border-t-1 border-gray-200">
                    {{$project->description}}
                </span>


</div>



