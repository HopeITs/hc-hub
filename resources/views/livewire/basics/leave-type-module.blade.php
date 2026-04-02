@php
    use App\Enums\LeaveType;
@endphp
<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="trans_choice('general.leave-type', 2)" :crumbs="[
            ['label' => __('general.project-basics')],
            ['label' => trans_choice('general.leave-type', 2), 'route' => 'leave-types.index'],
        ]" :showButton="true" />
        @activerole('HR', 'super-admin')
            <div>
                <x-primary-button wire:click="create" :showSpinner="false">
                    @lang('general.add-new')
                </x-primary-button>
            </div>
        @endactiverole
    </div>

    <livewire:basics.leave-type-table />

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                @foreach (config('app.supported_locales') as $locale)
                    <div>
                        <x-forms.input-label for="name_{{ $locale }}">
                            @lang('general.name') ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.input type="text" wire:model="item.name_{{ $locale }}"
                            id="name_{{ $locale }}" />
                        <x-error-span>{{ $errors->first('item.name_' . $locale) }}</x-error-span>
                    </div>
                @endforeach

                <div>
                    <x-forms.input-label for="base_days">@lang('general.base-days')</x-forms.input-label>
                    <x-forms.input type="number" wire:model="item.base_days" id="base_days" />
                    <x-error-span>{{ $errors->first('item.base_days') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label>@lang('general.allowed-gender')</x-forms.input-label>
                    <div class="flex justify-between">
                        <x-forms.radio-input id="any_gender" name="allowed_gender" value="{{ LeaveType::GENDER_ANY }}"
                            wire:model="item.allowed_gender">{{ LeaveType::getGender(LeaveType::GENDER_ANY)['value'] }}</x-forms.radio-input>
                        <x-forms.radio-input id="male" name="allowed_gender" value="{{ LeaveType::GENDER_MALE }}"
                            wire:model="item.allowed_gender">{{ LeaveType::getGender(LeaveType::GENDER_MALE)['value'] }}</x-forms.radio-input>
                        <x-forms.radio-input id="female" name="allowed_gender"
                            value="{{ LeaveType::GENDER_FEMALE }}"
                            wire:model="item.allowed_gender">{{ LeaveType::getGender(LeaveType::GENDER_FEMALE)['value'] }}</x-forms.radio-input>
                    </div>
                    <x-error-span>{{ $errors->first('item.allowed_gender') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label>@lang('general.marital_status')</x-forms.input-label>
                    <div class="flex justify-between">
                        <x-forms.radio-input id="any_status" name="marital_status"
                            value="{{ LeaveType::MARITAL_STATUS_ANY }}"
                            wire:model="item.marital_status">{{ LeaveType::getMaritalStatus(LeaveType::MARITAL_STATUS_ANY)['value'] }}</x-forms.radio-input>
                        <x-forms.radio-input id="single" name="marital_status"
                            value="{{ LeaveType::MARITAL_STATUS_SINGLE }}"
                            wire:model="item.marital_status">{{ LeaveType::getMaritalStatus(LeaveType::MARITAL_STATUS_SINGLE)['value'] }}</x-forms.radio-input>
                        <x-forms.radio-input id="married" name="marital_status"
                            value="{{ LeaveType::MARITAL_STATUS_MARRIED }}"
                            wire:model="item.marital_status">{{ LeaveType::getMaritalStatus(LeaveType::MARITAL_STATUS_MARRIED)['value'] }}</x-forms.radio-input>
                    </div>

                    <x-error-span>{{ $errors->first('item.marital_status') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn />
            </div>
        </form>
    </x-modal.dialog>
</div>
