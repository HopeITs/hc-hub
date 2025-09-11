<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="__('general.programs-management')"
                                     :crumbs="[['label' => trans_choice('general.program', 2)]]" :showButton="true"/>
        <div>
            <x-primary-button href="{{route('programs.create')}}">
                @lang('general.add-new')
            </x-primary-button>
        </div>

    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($items as $program)
            <x-card class="col-span-1 {{ $program->is_active ? '!border-red-200' : '' }}" :title="$program->name">
                <x-slot name="icon">
                    <span  class="bg-primary-100 text-primary-800 text-xs h-fit font-medium px-2.5 py-0.5 rounded-full">
                         {{ $program->code . '-' . $program->slug}}
                    </span>

                </x-slot>
                <div class="text-gray-400   mb-4 gap-x-4 gap-y-2">
                    <x-icon-label-info :label="__('general.program-manager')" class=" md:col-span-2"
                                       :info="$program->program_manager_name ?? '-'">
                        <x-icons.user class=" !size-4 !text-gray-400 me-2"/>
                    </x-icon-label-info>
                    <div class="flex justify-between">
                        <x-icon-label-info :label="trans_choice('general.city', 2)" :info="$program->cities_count">
                            <x-icons.map-pin class="me-2"/>
                        </x-icon-label-info>
                        <x-icon-label-info :label="trans_choice('general.employee', 2)"
                                           :info="$program->contracts_count">
                            <x-icons.users class=" !size-4 !text-gray-400 me-2"/>
                        </x-icon-label-info>
                    </div>


                    <x-icon-label-info class="md:col-span-2" :label="__('general.start-date')"
                                       :info="$program->start_date?->format('M, Y')">
                        <x-icons.calendar class="me-2"/>
                    </x-icon-label-info>
                    <x-icon-label-info class="md:col-span-2" :label="__('general.end-date')"
                                       :info="$program->end_date !== null ? $program->end_date?->format(' M, Y') : __('general.active') ">
                        <x-icons.calendar class="me-2"/>
                    </x-icon-label-info>


                </div>

                <div class="flex justify-between items-end mt-4 pt-4 border-t-1 border-gray-200">
                    <a class="text-primary underline"
                       href="{{ route('programs.show', ['program' => $program->slug]) }}">@lang('general.show-more') </a>
                    <div class="flex justify-between items-center">
                        <x-forms.toggle-input :active="$program->is_active" :model-id="$program->id"
                                              wire-event="confirmToggleItemStatus"/>
                        <x-table.edit-btn
                                          :href="route('programs.edit',['program' => $program->slug])"></x-table.edit-btn>
                        <x-table.delete-btn class="!p-0" :modelId="$program->id"/>
                    </div>
                </div>
            </x-card>
        @endforeach

    </div>

    @if($items->count() >= $perPage)
        <div class="py-4 mt-4 text-center">
            <x-outline-button class="text-sm" wire:click="loadMore">@lang('general.show-more')</x-outline-button>
        </div>
    @endif


</div>
