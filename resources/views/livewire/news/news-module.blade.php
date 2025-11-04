<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.news')"
            :crumbs="[['label'=>__('general.news-management')]]"
            :showButton="true"
        />
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

                <div class="col-span-2">
                    <x-forms.input-label for="category">@lang('general.category')</x-forms.input-label>
                    <x-forms.input id="category" type="text" wire:model="item.category" />
                    <x-error-span>{{ $errors->first('item.category') }}</x-error-span>
                </div>

                <div class="col-span-2">
                    <x-forms.input-label for="photo">@lang('general.photo')</x-forms.input-label>
                    <input type="file" id="photo" wire:model="item.photo" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">

                    @if ($item['photo'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                        {{-- Preview for newly selected file --}}
                        <img src="{{ $item['photo']->temporaryUrl() }}" class="h-16 mt-2 rounded">
                    @elseif($item['photo'])
                        {{-- Preview for existing file --}}
                        <img src="{{ Storage::url($item['photo']) }}" class="h-16 mt-2 rounded">
                    @endif

                    <x-error-span>{{ $errors->first('item.photo') }}</x-error-span>
                </div>

                <div class="col-span-2">
                    <x-forms.input-label for="display_duration">@lang('general.display_duration')</x-forms.input-label>
                    <x-forms.select-input id="display_duration" wire:model="item.display_duration">
                        <option value="1_hour">1 Hour</option>
                        <option value="2_hours">2 Hours</option>
                        <option value="3_hours">3 Hours</option>
                        <option value="4_hours">4 Hours</option>
                        <option value="5_hours">5 Hours</option>
                        <option value="6_hours">6 Hours</option>
                        <option value="7_hours">7 Hours</option>
                        <option value="8_hours">8 Hours</option>
                        <option value="1_day">1 Day</option>
                        <option value="2_days">2 Days</option>
                        <option value="3_days">3 Days</option>
                        <option value="4_days">4 Days</option>
                        <option value="5_days">5 Days</option>
                        <option value="6_days">6 Days</option>
                        <option value="1_week">1 Week</option>
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.display_duration') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-primary-button type="submit">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn />
            </div>
        </form>
    </x-modal.dialog>
</div>
