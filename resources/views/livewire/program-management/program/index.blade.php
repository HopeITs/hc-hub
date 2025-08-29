<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="__('general.projects-management')" :crumbs="[['label' => trans_choice('general.project', 2)]]" :showButton="true" />
        <div>
            <x-primary-button href="{{route('programs.create')}}">
                @lang('general.add_new')
            </x-primary-button>
        </div>

    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($items as $program)
            <x-card class="col-span-1 {{ $program->is_active ? '!border-red-200' : '' }}" :title="$program->name">
                <div class="text-gray-400 grid md:grid-cols-2 mb-4 gap-x-4 gap-y-2">
                    <x-icon-label-info :label="__('general.name')"  class=" md:col-span-2" :info="$program->name">
                        <x-icons.info class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info :label="__('general.code')"   :info="$program->slug . '-' . $program->code">
                        <x-icons.barcode class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info :label="trans_choice('general.required-positions', 2)"   :info="$program->required_positions_count">
                        <x-icons.bag class="me-2" />
                    </x-icon-label-info>

                    <x-icon-label-info :label="trans_choice('general.city', 2)"   :info="$program->cities_count">
                        <x-icons.map-pin class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info class="md:col-span-2" :label="__('general.start_date')"   :info="$program->start_date?->format('M, Y')">
                        <x-icons.calendar class="me-2" />
                    </x-icon-label-info>
                    <x-icon-label-info class="md:col-span-2" :label="__('general.end_date')"   :info="$program->end_date !== null ? $program->end_date?->format(' M, Y') : __('general.active') ">
                        <x-icons.calendar class="me-2" />
                    </x-icon-label-info>



                </div>

                <div class="flex justify-between items-end mt-4 pt-4 border-t-1 border-gray-200">
                    <a class="text-primary underline" href="{{ route('programs.show', ['program' => $program->slug]) }}">@lang('general.show-more') </a>
                    <div class="flex justify-between items-center">
                        <x-forms.toggle-input :active="$program->is_active" :model-id="$program->id"
                            wire-event="confirmToggleItemStatus" />
                        <x-table.edit-btn :href="route('programs.edit',['program' => $program->slug])"></x-table.edit-btn>
                        <x-table.delete-btn :modelId="$program->id" />
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
