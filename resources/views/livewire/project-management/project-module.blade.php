<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="__('general.projects-management')" :crumbs="[['label' => trans_choice('general.project', 2)]]" :showButton="true" />
        <div>
            <x-primary-button wire:click="create">
                @lang('general.add_new')
            </x-primary-button>
        </div>

    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($items as $project)
            <x-card class="col-span-1 {{ $project->is_active ? '!border-red-200' : '' }}" :title="$project->getTranslation('name', 'en')">
                <x-slot name="icon">
                    <div class="flex justify-between">

                        @if ($project->getFirstMediaUrl('logo') !== '')
                            <img class="h-10 m-4 " src="{{ $project->getFirstMediaUrl('logo') }}" />
                        @else
                            <svg class="size-10 text-gray-800 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2"
                                    d="M4 5a1 1 0 0 1 1-1h11.586a1 1 0 0 1 .707.293l2.414 2.414a1 1 0 0 1 .293.707V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5Z" />
                                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2"
                                    d="M8 4h8v4H8V4Zm7 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        @endif


                    </div>

                </x-slot>
                <div class="text-gray-400 grid md:grid-cols-2 mb-4 gap-x-4 gap-y-2">
                    <div class="flex items-center md:col-span-2 ">
                        <x-icons.info class="me-2" />

                        <span class="font-bold me-1">
                            {{ __('general.name') . ': ' }}
                        </span>
                        <span>
                            {{ $project->getTranslation('name', 'ar') }}
                        </span>

                    </div>
                    <div class="flex items-center ">
                        <x-icons.barcode class="me-2" />

                        <span class="font-bold me-1 text-sm xl:text-base">
                            {{ __('general.code') . ': ' }}
                        </span>
                        <span>
                            {{ $project->slug . '-' . $project->code }}
                        </span>

                    </div>
                    <div class="flex items-center ">
                        <x-icons.bag class="me-2" />

                        <span class="font-bold me-1">
                            {{ trans_choice('general.required-positions', 2) . ': ' }}
                        </span>
                        <span>
                            {{ $project->required_positions_count }}
                        </span>

                    </div>
                    <div class="flex items-center">
                        <x-icons.calendar class="me-2" />
                        <span class="font-bold me-1"> {{ __('general.start_date') . ': ' }} </span>
                        <span>
                            {{ $project->start_date?->format('M, Y') }}
                        </span>
                    </div>
                    <div class="flex items-center ">
                        <x-icons.calendar class="me-2 " />
                        <span class="font-bold me-1">
                            {{ __('general.end_date') . ': ' }}
                        </span>
                        <span>

                            {{ $project->end_date !== null ? $project->end_date?->format(' M, Y') : __('general.active') }}
                        </span>
                    </div>
                    <div class="flex items-center ">
                        <x-icons.building class="me-2 " />
                        <span class="font-bold me-1">
                            {{ trans_choice('general.center', 2) . ': ' }}
                        </span>
                        <span>
                            {{ $project->centers_count }}
                        </span>
                    </div>
                    <div class="flex items-center ">
                        <x-icons.map-pin class="me-2" />
                        <span class="font-bold me-1">
                            {{ trans_choice('general.city', 2) . ': ' }}
                        </span>
                        <span>
                            {{ $project->cities_count }}
                        </span>

                    </div>
                </div>

                <div class="flex justify-between items-end mt-4 pt-4 border-t-1 border-gray-200">
                    <a href="{{ route('projects.show', ['slug' => $project->slug]) }}">عرض التفاصيل</a>
                    <div class="flex justify-between items-center">
                        <x-forms.toggle-input :active="$project->is_active" :model-id="$project->id"
                            wire-event="confirmToggleItemStatus" />
                        <x-table.edit-btn wire:click="edit({{ $project->id }})"></x-table.edit-btn>
                        <x-table.delete-btn :modelId="$project->id" />
                    </div>
                </div>
            </x-card>
        @endforeach

    </div>
    <div class="py-4">{{ $items->links() }}</div>
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal">
        <form class="space-y-4" wire:submit.prevent="save">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-forms.input-label for="logo">@lang('general.logo')</x-forms.input-label>
                    <x-forms.file-input id="logo" wire:model.live="item.logo" :old-file="$existingLogoUrl"
                        :new-file="$item['logo'] ?? null" />
                    <x-error-span>{{ $errors->first('item.logo') }}</x-error-span>

                </div>
                <div>
                    <x-forms.input-label for="code">@lang('general.code')</x-forms.input-label>
                    <x-forms.input type="text" id="code" wire:model.defer="item.code" />
                    <x-error-span>{{ $errors->first('item.code') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="slug">@lang('general.slug')</x-forms.input-label>
                    <x-forms.input type="text" id="code" wire:model.defer="item.slug" />
                    <x-error-span>{{ $errors->first('item.slug') }}</x-error-span>
                </div>
                @foreach (config('app.supported_locales') as $locale)
                    <div>
                        <x-forms.input-label for="name_{{ $locale }}">@lang('general.name')
                            ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.input type="text" id="name_{{ $locale }}"
                            wire:model.defer="item.name.{{ $locale }}" />
                        <x-error-span>{{ $errors->first('item.name.' . $locale) }}</x-error-span>
                    </div>
                @endforeach
                @foreach (config('app.supported_locales') as $locale)
                    <div class="col-span-1">
                        <x-forms.input-label for="description_{{ $locale }}">@lang('general.description')
                            ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.textarea type="text" id="description_{{ $locale }}"
                            wire:model.defer="item.description.{{ $locale }}" />
                        <x-error-span>{{ $errors->first('item.description.' . $locale) }}</x-error-span>
                    </div>
                @endforeach
                <div>
                    <x-forms.input-label for="start_date">@lang('general.start_date')
                    </x-forms.input-label>
                    <x-forms.date-input id="start_date" wire:model.defer="item.start_date" />
                    <x-error-span>{{ $errors->first('item.start_date') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="end_date">@lang('general.end_date')
                    </x-forms.input-label>
                    <x-forms.date-input id="end_date" wire:model.defer="item.end_date" />
                    <x-error-span>{{ $errors->first('item.end_date') }}</x-error-span>
                </div>

            </div>
            <div class="flex justify-end space-x-2 pt-4">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn />
            </div>
        </form>
    </x-modal.dialog>
</div>
