<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.news')"
            :crumbs="[
                ['label' => __('general.news-management')],
                ['label' => __('general.add-new')],
            ]"
        />
    </div>

    <form wire:submit.prevent="save" class="grid grid-cols-2 gap-4">

        <div class="col-span-2">
            <x-forms.input-label for="title">@lang('general.title')</x-forms.input-label>
            <x-forms.input id="title" type="text" wire:model.defer="item.title" />
            <x-error-span>{{ $errors->first('item.title') }}</x-error-span>
        </div>

        <div class="col-span-2">
            <x-forms.input-label for="description">@lang('general.description')</x-forms.input-label>
            <x-forms.textarea id="description" wire:model.defer="item.description" />
            <x-error-span>{{ $errors->first('item.description') }}</x-error-span>
        </div>

        <div class="col-span-2 sm:col-span-1">
            <x-forms.input-label>@lang('general.target_type')</x-forms.input-label>
            <x-forms.select-input wire:model="item.newsable_type">
                <option value="">@lang('general.all_org')</option>
                <option value="{{ \App\Models\City::class }}">{{ trans_choice('general.city', 1) }}</option>
                <option value="{{ \App\Models\Center::class }}">{{ trans_choice('general.center', 1) }}</option>
                <option value="{{ \App\Models\Program::class }}">{{ trans_choice('general.program', 1) }}</option>
                <option value="{{ \App\Models\Employee::class }}">{{ trans_choice('general.employee', 1) }}</option>
            </x-forms.select-input>
            <x-error-span>{{ $errors->first('item.newsable_type') }}</x-error-span>
        </div>

        @if (!empty($item['newsable_type']))
            <div class="col-span-2 sm:col-span-1">
                <x-forms.input-label>@lang('general.target_entity')</x-forms.input-label>
                <x-forms.select-input wire:model="item.newsable_id">
                    <option value="">@lang('general.select_entity')</option>
                    @foreach($this->entities as $entity)
                        <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('item.newsable_id') }}</x-error-span>
            </div>
        @endif

        <div class="col-span-2">
            <x-forms.input-label>@lang('general.photo')</x-forms.input-label>
            <input type="file" wire:model="item.photo" class="block w-full text-sm text-gray-900 border rounded-lg" />
            @if ($item['photo'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                <img src="{{ $item['photo']->temporaryUrl() }}" class="h-16 mt-2 rounded" />
            @endif
            <x-error-span>{{ $errors->first('item.photo') }}</x-error-span>
        </div>

        <div class="col-span-2 sm:col-span-1">
            <x-forms.input-label>@lang('general.display_until')</x-forms.input-label>
            <x-forms.input type="date" wire:model.defer="item.display_until" />
            <x-error-span>{{ $errors->first('item.display_until') }}</x-error-span>
        </div>

        <div class="col-span-2 sm:col-span-1">
            <x-forms.input-label>@lang('general.event_date')</x-forms.input-label>
            <x-forms.input type="date" wire:model.defer="item.event_date" />
            <x-error-span>{{ $errors->first('item.event_date') }}</x-error-span>
        </div>

        <div class="col-span-2 flex justify-end gap-2">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <x-primary-button type="button" class="!bg-gray-200 !text-black hover:!bg-gray-300"
                onclick="window.location='{{ route('news.index') }}'">
                @lang('general.cancel')
            </x-primary-button>
        </div>
    </form>
</div>
