@php
    use App\Enums\MaritalStatus;
@endphp
<div>
    <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-forms.input-label for="name">@lang('general.name')</x-forms.input-label>
                    <x-forms.input type="text" id="name" wire:model="item.name" />
                </div>

                <div>
                    <x-forms.input-label for="dob">@lang('general.dob')</x-forms.input-label>
                    <x-forms.input type="date" id="dob" wire:model="item.dob" />
                </div>

                <div>
                    <x-forms.input-label for="years_of_service">@lang('general.years_of_service')</x-forms.input-label>
                    <x-forms.input type="number" id="years_of_service" wire:model="item.years_of_service" />
                </div>

                <div>
                    <x-forms.input-label for="national_number">@lang('general.national_number')</x-forms.input-label>
                    <x-forms.input type="text" id="national_number" wire:model="item.national_number" />
                </div>

                <div>
                    <x-forms.input-label for="civil_registry_office">@lang('general.civil_registry_office')</x-forms.input-label>
                    <x-forms.input type="text" id="civil_registry_office" wire:model="item.civil_registry_office" />
                </div>

                <div>
                    <x-forms.input-label for="civil_registry_number">@lang('general.civil_registry_number')</x-forms.input-label>
                    <x-forms.input type="text" id="civil_registry_number" wire:model="item.civil_registry_number" />
                </div>

                <div>
                    <x-forms.input-label for="place_of_registration">@lang('general.place_of_registration')</x-forms.input-label>
                    <x-forms.input type="text" id="place_of_registration" wire:model="item.place_of_registration" />
                </div>

                <div>
                    <x-forms.input-label for="gender">@lang('general.gender')</x-forms.input-label>
                    <select id="gender" wire:model="item.gender" class="w-full border-gray-300 rounded shadow-sm">
                        <option value="">--</option>
                        <option value="male">@lang('general.male')</option>
                        <option value="female">@lang('general.female')</option>
                    </select>
                </div>

                <div>
                    <x-forms.input-label for="father_name">@lang('general.father_name')</x-forms.input-label>
                    <x-forms.input type="text" id="father_name"
                        wire:model="item.father_name.{{ app()->getLocale() }}" />
                </div>

                <div>
                    <x-forms.input-label for="mother_name">@lang('general.mother_name')</x-forms.input-label>
                    <x-forms.input type="text" id="mother_name"
                        wire:model="item.mother_name.{{ app()->getLocale() }}" />
                </div>

                <div>
                    <x-forms.input-label for="nationality">@lang('general.nationality')</x-forms.input-label>
                    <x-forms.input type="text" id="nationality" wire:model="item.nationality" />
                </div>

                <div>
                    <x-forms.input-label for="marital_status">@lang('general.marital_status')</x-forms.input-label>
                    <x-forms.select-input id="marital_status" wire:model="item.marital_status">
                        <option value="">{{ __('general.select') }}</option>
                        <option value="{{ MaritalStatus::Single }}">
                            {{ MaritalStatus::getLocalizedName(MaritalStatus::Single)['value'] }}</option>
                        <option value="{{ MaritalStatus::Engaged }}">
                            {{ MaritalStatus::getLocalizedName(MaritalStatus::Engaged)['value'] }}</option>
                        <option value="{{ MaritalStatus::Married }}">
                            {{ MaritalStatus::getLocalizedName(MaritalStatus::Married)['value'] }}</option>
                        <option value="{{ MaritalStatus::Divorced }}">
                            {{ MaritalStatus::getLocalizedName(MaritalStatus::Divorced)['value'] }}</option>
                        <option value="{{ MaritalStatus::Widowed }}">
                            {{ MaritalStatus::getLocalizedName(MaritalStatus::Widowed)['value'] }}</option>
                        <option value="{{ MaritalStatus::Separated }}">
                            {{ MaritalStatus::getLocalizedName(MaritalStatus::Separated)['value'] }}</option>
                        <option value="{{ MaritalStatus::Unknown }}">
                            {{ MaritalStatus::getLocalizedName(MaritalStatus::Unknown)['value'] }}</option>
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.marital_status') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="children">@lang('general.children')</x-forms.input-label>
                    <x-forms.input type="number" id="children" wire:model="item.children" />
                </div>

                <div>
                    <x-forms.input-label for="work_phone">@lang('general.work_phone')</x-forms.input-label>
                    <x-forms.input type="text" id="work_phone" wire:model="item.work_phone" />
                </div>

                <div>
                    <x-forms.input-label for="personal_phone">@lang('general.personal_phone')</x-forms.input-label>
                    <x-forms.input type="text" id="personal_phone" wire:model="item.personal_phone" />
                </div>

                <div>
                    <x-forms.input-label for="personal_email">@lang('general.personal_email')</x-forms.input-label>
                    <x-forms.input type="email" id="personal_email" wire:model="item.personal_email" />
                </div>

                <div>
                    <x-forms.input-label for="work_email">@lang('general.work_email')</x-forms.input-label>
                    <x-forms.input type="email" id="work_email" wire:model="item.work_email" />
                    <x-error-span>{{ $errors->first('item.work_email') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="address">@lang('general.address')</x-forms.input-label>
                    <x-forms.textarea id="address" wire:model="item.address.{{ app()->getLocale() }}" />
                </div>

                <div>
                    <x-forms.input-label for="skills">@lang('general.skills')</x-forms.input-label>
                    <x-forms.textarea id="skills" wire:model="item.skills" />
                </div>

                <div>
                    <x-forms.input-label for="interests">@lang('general.interests')</x-forms.input-label>
                    <x-forms.textarea id="interests" wire:model="item.interests" />
                </div>

                <div class="flex items-center space-x-2 mt-2">
                    <input type="checkbox" id="is_active" wire:model="item.is_active"
                        class="rounded border-gray-300 shadow-sm focus:ring-indigo-500" />
                    <label for="is_active" class="text-sm text-gray-700">@lang('general.active')</label>
                </div>

                <div>
                    <x-forms.input-label for="actual_job">@lang('general.actual_job')</x-forms.input-label>
                    <x-forms.input type="text" id="actual_job" wire:model="item.actual_job" />
                </div>

                <div>
                    <x-forms.input-label for="direct_manager_id">@lang('general.direct_manager')</x-forms.input-label>
                    <x-forms.input type="number" id="direct_manager_id" wire:model="item.direct_manager_id" />
                </div>

                <div>
                    <x-forms.input-label for="tech_manager_id">@lang('general.tech_manager')</x-forms.input-label>
                    <x-forms.input type="number" id="tech_manager_id" wire:model="item.tech_manager_id" />
                </div>

                <div>
                    <x-forms.input-label for="job_level_id">@lang('general.job_level')</x-forms.input-label>
                    <x-forms.input type="number" id="job_level_id" wire:model="item.job_level_id" />
                </div>

                <div>
                    <x-forms.input-label for="contract_type_id">@lang('general.contract_type')</x-forms.input-label>
                    <x-forms.input type="number" id="contract_type_id" wire:model="item.contract_type_id" />
                </div>

                <div>
                    <x-forms.input-label for="department_id">@lang('general.department')</x-forms.input-label>
                    <x-forms.input type="number" id="department_id" wire:model="item.department_id" />
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
            </div>
        </form>
</div>
