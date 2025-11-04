<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs :title="__('general.news')" :crumbs="[['label' => __('general.news-management')]]" :showButton="true" />
        <x-primary-button type="button" wire:click.prevent="create">@lang('general.add-new')</x-primary-button>
    </div>

    <livewire:news.news-table />

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model="showModal">
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-forms.input-label for="title">@lang('general.title')</x-forms.input-label>
                    <x-forms.input id="title" type="text" wire:model="item.title" />
                    <x-error-span>{{ $errors->first('item.title') }}</x-error-span>
                </div>

                <div class="col-span-2">
                    <x-forms.input-label for="description">@lang('general.description')</x-forms.input-label>
                    <x-forms.textarea id="description" wire:model="item.description" />
                    <x-error-span>{{ $errors->first('item.description') }}</x-error-span>
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <x-forms.input-label for="newsable_type">@lang('general.target_type')</x-forms.input-label>
                    <x-forms.select-input id="newsable_type" wire:model.live="item.newsable_type">
                        <option value="">@lang('general.all_org')</option>
                        <option value="App\Models\City">@lang('general.city')</option>
                        <option value="App\Models\Center">@lang('general.center')</option>
                        <option value="App\Models\Program">@lang('general.program')</option>
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.newsable_type') }}</x-error-span>
                </div>

                @if (!empty($item['newsable_type']))
                    <div class="col-span-2 sm:col-span-1">
                        <x-forms.input-label for="newsable_id">@lang('general.target_entity')</x-forms.input-label>
                        <x-forms.select-input id="newsable_id" wire:model="item.newsable_id">
                            <option value="">@lang('general.select_entity')</option>
                            @if ($item['newsable_type'] === 'App\Models\City')
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            @elseif ($item['newsable_type'] === 'App\Models\Center')
                                @foreach ($centers as $center)
                                    <option value="{{ $center->id }}">{{ $center->name }}</option>
                                @endforeach
                            @elseif ($item['newsable_type'] === 'App\Models\Program')
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            @endif
                        </x-forms.select-input>
                        <x-error-span>{{ $errors->first('item.newsable_id') }}</x-error-span>
                    </div>
                @else
                    <div class="col-span-2 sm:col-span-1"></div>
                @endif

                <div class="col-span-2">
                    <x-forms.input-label for="photo">@lang('general.photo')</x-forms.input-label>
                    <input type="file" id="photo" wire:model="item.photo"
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                    @if ($item['photo'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                        <img src="{{ $item['photo']->temporaryUrl() }}" class="h-16 mt-2 rounded">
                    @elseif(is_string($item['photo']) && !empty($item['photo']))
                        <img src="{{ Storage::url($item['photo']) }}" class="h-16 mt-2 rounded">
                    @endif
                    <x-error-span>{{ $errors->first('item.photo') }}</x-error-span>
                </div>

                <div class="col-span-2">
                    <x-forms.input-label for="display_until">@lang('general.display_until')
                        (@lang('general.optional'))</x-forms.input-label>
                    <x-forms.input id="display_until" type="date" wire:model="item.display_until" />
                    <x-error-span>{{ $errors->first('item.display_until') }}</x-error-span>
                </div>

            </div>

            <div class="flex justify-end gap-2">
                <x-primary-button type="submit">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn />
            </div>
        </form>
    </x-modal.dialog>
</div>
