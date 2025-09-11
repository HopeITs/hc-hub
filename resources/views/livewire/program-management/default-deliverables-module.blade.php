<div>
    @php($locale = app()->getLocale())

    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.default-deliverables')"
            :crumbs="[
                ['label' => trans_choice('general.employee-management', 2)],
                ['label' => __('general.default-deliverables')],
            ]"
            :showButton="true"
        />
        <div class="flex items-center gap-2">
            <x-primary-button type="button" wire:click.prevent="create">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>

    <livewire:program-management.default-deliverables-table />

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model="showModal">
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <x-forms.input-label for="employee_id">@lang('general.employee')</x-forms.input-label>
                    <x-forms.select-input id="employee_id" wire:model="item.employee_id">
                        <option value="">{{ __('general.select') }}</option>
                        @foreach($employees as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.employee_id') }}</x-error-span>
                </div>

                <div class="col-span-2 md:col-span-1">
                    <x-forms.input-label for="city_id">{{ trans_choice('general.city', 1) }}</x-forms.input-label>
                    <x-forms.select-input id="city_id" wire:model="item.city_id">
                        <option value="">{{ __('general.select') }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">
                                {{ is_array($city->name) ? ($city->name[$locale] ?? ($city->name['en'] ?? $city->id)) : $city->name }}
                            </option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.city_id') }}</x-error-span>
                </div>

                <div class="col-span-2">
                    <x-forms.input-label for="item_id">@lang('general.item')</x-forms.input-label>
                    <x-forms.select-input id="item_id" wire:model="item.item_id">
                        <option value="">{{ __('general.select') }}</option>
                        @foreach($checklistItems as $ci)
                            <option value="{{ $ci->id }}">{{ $ci->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.item_id') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>
</div>
