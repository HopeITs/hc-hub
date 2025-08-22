<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="__('general.projects-management')" :crumbs="[['label' => trans_choice('general.project', 2)]]" :showButton="true" />
        <div>
            <x-primary-button href="{{route('projects.create')}}">
                @lang('general.add_new')
            </x-primary-button>
        </div>

    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($items as $project)
            <x-card class="col-span-1 {{ $project->is_active ? '!border-red-200' : '' }}" :title="$project->getTranslation('name', 'en')">
                <div class="text-gray-400 grid md:grid-cols-2 mb-4 gap-x-4 gap-y-2">
                    <x-icon-label-info :label="__('general.name')"  class=" md:col-span-2" :info="$project->getTranslation('name', 'ar')">
                        <x-icons.info class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info :label="__('general.code')"   :info="$project->slug . '-' . $project->code">
                        <x-icons.barcode class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info :label="trans_choice('general.required-positions', 2)"   :info="$project->required_positions_count">
                        <x-icons.bag class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info :label="trans_choice('general.center', 2)"   :info="$project->centers_count">
                        <x-icons.building class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info :label="trans_choice('general.city', 2)"   :info="$project->cities_count">
                        <x-icons.map-pin class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info class="md:col-span-2" :label="__('general.start_date')"   :info="$project->start_date?->format('M, Y')">
                        <x-icons.calendar class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info class="md:col-span-2" :label="__('general.end_date')"   :info="$project->end_date !== null ? $project->end_date?->format(' M, Y') : __('general.active') ">
                        <x-icons.calendar class="me-2" />
                    </x-icon-label-info>



                </div>

                <div class="flex justify-between items-end mt-4 pt-4 border-t-1 border-gray-200">
                    <a class="text-primary underline" href="{{ route('projects.show', ['project' => $project->slug]) }}">@lang('general.show-more') </a>
                    <div class="flex justify-between items-center">
                        <x-forms.toggle-input :active="$project->is_active" :model-id="$project->id"
                            wire-event="confirmToggleItemStatus" />
                        <x-table.edit-btn :href="route('projects.edit',['project' => $project->slug])"></x-table.edit-btn>
                        <x-table.delete-btn :modelId="$project->id" />
                    </div>
                </div>
            </x-card>
        @endforeach

    </div>

    @if($items->count() >= $perPage)
        <div class="py-4 mt-4 text-center">
            <x-outline-button  wire:click="loadMore">@lang('general.show-more')</x-outline-button>
        </div>
    @endif
{{--    <div class="py-4">{{ $items->links() }}</div>--}}

</div>
