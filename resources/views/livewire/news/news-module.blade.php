<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs :title="__('general.news')" :crumbs="[['label' => __('general.news-management')]]" :showButton="true" />
        <x-primary-button href="{{ route('news.create') }}">@lang('general.add-new')</x-primary-button>
    </div>

    <livewire:news.news-table />

    @if ($showModal)
        <div class="mt-8 p-6 bg-white shadow rounded-lg">
            <h2 class="text-xl font-semibold mb-4">{{ $modalTitle }}</h2>

            <form wire:submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <x-forms.input-label for="title">@lang('general.title')</x-forms.input-label>
                        <x-forms.input id="title" type="text" wire:model.live="item.title" />
                        <x-error-span>{{ $errors->first('item.title') }}</x-error-span>
                    </div>

                    <div class="col-span-2">
                        <x-forms.input-label for="description">@lang('general.description')</x-forms.input-label>
                        <x-forms.textarea id="description" wire:model.live="item.description" />
                        <x-error-span>{{ $errors->first('item.description') }}</x-error-span>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <x-forms.input-label>@lang('general.target_type')</x-forms.input-label>
                        <x-forms.select-input wire:model.live="item.newsable_type">
                            <option value="">@lang('general.all_org')</option>
                            <option value="App\Models\City">{{ trans_choice('general.city', 1) }}</option>
                            <option value="App\Models\Center">{{ trans_choice('general.center', 1) }}</option>
                            <option value="App\Models\Program">{{ trans_choice('general.program', 1) }}</option>
                            <option value="App\Models\Employee">{{ trans_choice('general.employee', 1) }}</option>
                        </x-forms.select-input>
                        <x-error-span>{{ $errors->first('item.newsable_type') }}</x-error-span>
                    </div>

                    @if (!empty($item['newsable_type']))
                        <div class="col-span-2 sm:col-span-1">
                            <x-forms.input-label>@lang('general.target_entity')</x-forms.input-label>
                            <x-forms.select-input wire:model.live="item.newsable_id">
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
                                @elseif ($item['newsable_type'] === 'App\Models\Employee')
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                @endif
                            </x-forms.select-input>
                            <x-error-span>{{ $errors->first('item.newsable_id') }}</x-error-span>
                        </div>
                    @endif

                    <div class="col-span-2">
                        <x-forms.input-label>@lang('general.photo')</x-forms.input-label>
                        <x-forms.file-input wire:model.live="item.photo" name="item.photo" previewType="image"
                            :oldFile="is_string($item['photo']) && !empty($item['photo'])
                                ? Storage::url($item['photo'])
                                : null" :fileName="is_string($item['photo']) && !empty($item['photo'])
                                ? basename($item['photo'])
                                : null" accept=".jpg,.jpeg,.png,.gif,.webp" />
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <x-forms.input-label>@lang('general.display_until')</x-forms.input-label>
                        <x-forms.input type="date" wire:model.live="item.display_until" />
                        <x-error-span>{{ $errors->first('item.display_until') }}</x-error-span>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <x-forms.input-label>@lang('general.event_date')</x-forms.input-label>
                        <x-forms.input type="date" wire:model.live="item.event_date" />
                        <x-error-span>{{ $errors->first('item.event_date') }}</x-error-span>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <x-primary-button type="submit">@lang('general.save')</x-primary-button>
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-4 py-2 bg-gray-200 rounded">@lang('general.cancel')</button>
                </div>
            </form>
        </div>
    @endif

</div>
