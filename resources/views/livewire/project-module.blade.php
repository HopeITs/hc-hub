<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="__('general.projects-management')"
            :crumbs="[

        ['label' => trans_choice('general.project',2)],
    ]"
            :showButton="true"

        />
        <div>
            <x-primary-button wire:click="create">
                @lang('general.add_new')
            </x-primary-button>
        </div>

    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($items as $item)
            <x-card class="col-span-1  {{$item->is_active ? '!border-red-200' : ''}}" :title="$item->name">

                <x-slot name="icon">
                    <div class="flex justify-between">

                        @if($item->getFirstMediaUrl('logo') !== "")
                            <img class="h-10 m-4 " src="{{$item->getFirstMediaUrl('logo')}}"/>
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
                                :active="$item->is_active"
                                :model-id="$item->id"
                                wire-event="confirmToggleItemStatus"/>
                            <x-table.edit-btn wire:click="edit({{ $item->id }})"></x-table.edit-btn>
                            <x-table.delete-btn :modelId="$item->id"/>
                        </div>

                    </div>
                </x-slot>
                <p class="pb-5  border-b-1 border-gray-200 ">
                    {{$item->description}}
                </p>
                <div class="flex justify-between items-center mt-7">
                    <div>
                        <label>{{trans_choice('general.center', 2)}}</label>
                        <span>{{$item->centers_count}}</span>
                    </div>
                    <div>
                        <label>{{trans_choice('general.city', 2)}}</label>
                        <span>{{$item->cities_count}}</span>
                    </div>

                </div>


            </x-card>
        @endforeach
    </div>
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal">
        <form class="space-y-4" wire:submit.prevent="save">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-forms.input-label for="logo">@lang('general.project_logo')</x-forms.input-label>

                    <x-forms.image-input :item="$item"/>
                    <x-error-span>{{ $errors->first('item.logo') }}</x-error-span>

                </div>
                @foreach(config('app.supported_locales') as $locale)
                    <div class="col-span-1">
                        <x-forms.input-label for="name_{{ $locale }}">@lang('general.name') ({{ strtoupper($locale) }}
                            )
                        </x-forms.input-label>
                        <x-forms.input type="text" id="name_{{ $locale }}" wire:model.defer="item.name.{{ $locale }}"/>
                        <x-error-span>{{ $errors->first('item.name.' . $locale) }}</x-error-span>
                    </div>
                @endforeach
                @foreach(config('app.supported_locales') as $locale)
                    <div class="col-span-1">
                        <x-forms.input-label for="description_{{ $locale }}">@lang('general.description')
                            ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.textarea type="text" id="description_{{ $locale }}"
                                          wire:model.defer="item.description.{{ $locale }}"/>
                        <x-error-span>{{ $errors->first('item.description.' . $locale) }}</x-error-span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end space-x-2 pt-4">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>

    </x-modal.dialog>

</div>
