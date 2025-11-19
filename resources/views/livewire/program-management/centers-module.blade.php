<div>
    @php($locale = app()->getLocale())

    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.centers')"
            :crumbs="[
                ['label' => __('general.programs-management')],
                ['label' => __('general.centers'), 'route' => 'centers.index'],
            ]"
            :showButton="true"
        />
        <div class="flex items-center gap-2">
            <x-primary-button wire:click="create">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>
    <livewire:program-management.center.centers-table/>

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" max-width="3xl" wire:ignore.self>
        <form wire:submit.prevent="save" class="grid grid-cols-2 gap-4">

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
                            <x-forms.textarea id="address_{{ $loc }}" wire:model.defer="item.address_{{ $loc }}"
                                              rows="2"/>
                            <x-error-span>{{ $errors->first('item.address_' . $loc) }}</x-error-span>
                        </div>
                    @endforeach
            <div class="md:col-span-1">
                <x-forms.input-label for="open_at">@lang('general.open-at')</x-forms.input-label>
                <x-forms.input id="open_at" type="time" wire:model.defer="item.open_at"/>
                <x-error-span>{{ $errors->first('item.open_at') }}</x-error-span>
            </div>
            <div class="md:col-span-1">
                <x-forms.input-label for="close_at">@lang('general.close-at')</x-forms.input-label>
                <x-forms.input id="close_at" type="time" wire:model.defer="item.close_at"/>
                <x-error-span>{{ $errors->first('item.close_at') }}</x-error-span>
            </div>

            <div class="md:col-span-full">
                <x-forms.input-label for="weekends">
                    @lang('general.weekend-days')
                </x-forms.input-label>
                <div class="flex gap-x-4">
                    @foreach(__('general.days-of-week') as $key => $day)
                        <x-forms.checkbox-input id="weekends-{{$key}}" type="checkbox" :value="$key" wire:model="item.weekends">
                            {{$day}}
                        </x-forms.checkbox-input>
                    @endforeach


                </div>

{{--                <x-forms.tags-input wire:model="item.weekends"--}}
{{--                                    :suggestions="__('general.days-of-week')"--}}
{{--                                    :force-suggestion="true"--}}
{{--                                    placeholder="Add tags"/>--}}
                <x-error-span>{{ $errors->first('item.weekends') }}</x-error-span>
            </div>
            <div class="md:col-span-full  flex justify-end gap-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>
</div>
