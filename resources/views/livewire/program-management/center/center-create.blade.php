{{-- resources/views/livewire/program-management/center/center-create.blade.php --}}
<div>
    <livewire:layout.breadcrumbs
        :title="__('general.add-new')"
        :crumbs="[
            ['label' => __('general.programs-management')],
            ['label' => __('general.centers'), 'route' => 'centers.index'],
            ['label' => __('general.add-new')],
        ]"
    />

    <form wire:submit.prevent="save" class="grid grid-cols-2 gap-4 bg-white dark:bg-neutral-900 border p-6 rounded-xl shadow-md">

        <div class="md:col-span-full">
            <x-forms.input-label for="name">@lang('general.center-name')</x-forms.input-label>
            <x-forms.input id="name" type="text" wire:model.defer="item.name"/>
            <x-error-span>{{ $errors->first('item.name') }}</x-error-span>
        </div>

        <div class="md:col-span-full">
            <x-forms.input-label for="city_id">{{ trans_choice('general.city',1) }}</x-forms.input-label>
            <x-forms.select-input id="city_id" wire:model="item.city_id">
                <option value="">{{ __('general.select') }}</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                @endforeach
            </x-forms.select-input>
            <x-error-span>{{ $errors->first('item.city_id') }}</x-error-span>
        </div>

        @foreach(config('app.supported_locales') as $loc)
            <div>
                <x-forms.input-label for="address_{{ $loc }}">
                    @lang('general.address') ({{ strtoupper($loc) }})
                </x-forms.input-label>
                <x-forms.textarea id="address_{{ $loc }}" wire:model.defer="item.address_{{ $loc }}" rows="2"/>
                <x-error-span>{{ $errors->first('item.address_' . $loc) }}</x-error-span>
            </div>
        @endforeach

        <div>
            <x-forms.input-label for="open_at">@lang('general.open-at')</x-forms.input-label>
            <x-forms.input id="open_at" type="time" wire:model.defer="item.open_at"/>
            <x-error-span>{{ $errors->first('item.open_at') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="close_at">@lang('general.close-at')</x-forms.input-label>
            <x-forms.input id="close_at" type="time" wire:model.defer="item.close_at"/>
            <x-error-span>{{ $errors->first('item.close_at') }}</x-error-span>
        </div>

        <div class="md:col-span-full">
            <x-forms.input-label for="weekends">@lang('general.weekend-days')</x-forms.input-label>
            <div class="flex gap-x-4">
                @foreach(__('general.days-of-week') as $key => $day)
                    <x-forms.checkbox-input id="weekends-{{$key}}" :value="$key" wire:model="item.weekends">
                        {{$day}}
                    </x-forms.checkbox-input>
                @endforeach
            </div>
            <x-error-span>{{ $errors->first('item.weekends') }}</x-error-span>
        </div>

        <div class="md:col-span-full flex justify-end gap-2">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <a href="{{ route('centers.index') }}" class="text-sm underline text-gray-600 dark:text-gray-300">
                @lang('general.cancel')
            </a>
        </div>
    </form>
</div>
