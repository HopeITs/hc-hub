<div>
    <livewire:layout.breadcrumbs :title="__('general.add_new')" :crumbs="[
        ['label' => __('general.project-basics')],
        ['label' => trans_choice('general.employee', 2), 'url' => route('employees.index')],
        ['label' => __('general.add_new')],
    ]" />

    <form wire:submit.prevent="save" class="space-y-4 mt-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border rounded-md">
            @foreach (config('app.supported_locales') as $locale)
                <div>
                    <x-forms.input-label for="name_{{ $locale }}">@lang('general.name') ({{ strtoupper($locale) }})</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.name.{{ $locale }}" id="name_{{ $locale }}" />
                    <x-error-span>{{ $errors->first('form.name.' . $locale) }}</x-error-span>
                </div>
            @endforeach

            <div>
                <x-forms.input-label for="national_number">@lang('general.national_number')</x-forms.input-label>
                <x-forms.input type="text" wire:model="form.national_number" id="national_number" />
                <x-error-span>{{ $errors->first('form.national_number') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="actual_job">@lang('general.actual_job')</x-forms.input-label>
                <x-forms.input type="text" wire:model="form.actual_job" id="actual_job" />
                <x-error-span>{{ $errors->first('form.actual_job') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="years_of_service">@lang('general.years_of_service')</x-forms.input-label>
                <x-forms.input type="number" step="0.1" wire:model="form.years_of_service" id="years_of_service" />
                <x-error-span>{{ $errors->first('form.years_of_service') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="work_email">@lang('general.work_email')</x-forms.input-label>
                <x-forms.input type="email" wire:model="form.work_email" id="work_email" />
                <x-error-span>{{ $errors->first('form.work_email') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="direct_manager_id">@lang('general.direct_manager')</x-forms.input-label>
                <x-forms.select-input wire:model="form.direct_manager_id" id="direct_manager_id">
                    <option value="">--</option>
                    @foreach ($allEmployees as $emp)
                        @if($emp)
                        <option value="{{ $emp->id }}">{{ $emp->getTranslation('name', app()->getLocale()) }}</option>
                        @endif
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.direct_manager_id') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="tech_manager_id">@lang('general.tech_manager')</x-forms.input-label>
                <x-forms.select-input wire:model="form.tech_manager_id" id="tech_manager_id">
                    <option value="">--</option>
                    @foreach ($allEmployees as $emp)
                         @if($emp)
                        <option value="{{ $emp->id }}">{{ $emp->getTranslation('name', app()->getLocale()) }}</option>
                        @endif
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.tech_manager_id') }}</x-error-span>
            </div>

            @foreach (config('app.supported_locales') as $locale)
                <div>
                    <x-forms.input-label for="father_name_{{ $locale }}">@lang('general.father_name') ({{ strtoupper($locale) }})</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.father_name.{{ $locale }}" id="father_name_{{ $locale }}" />
                    <x-error-span>{{ $errors->first('form.father_name.' . $locale) }}</x-error-span>
                </div>
            @endforeach

            @foreach (config('app.supported_locales') as $locale)
                <div>
                    <x-forms.input-label for="mother_name_{{ $locale }}">@lang('general.mother_name') ({{ strtoupper($locale) }})</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.mother_name.{{ $locale }}" id="mother_name_{{ $locale }}" />
                    <x-error-span>{{ $errors->first('form.mother_name.' . $locale) }}</x-error-span>
                </div>
            @endforeach

            <div>
                <x-forms.input-label for="dob">@lang('general.dob')</x-forms.input-label>
                <x-forms.input type="date" wire:model="form.dob" id="dob" />
                <x-error-span>{{ $errors->first('form.dob') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="gender">@lang('general.gender')</x-forms.input-label>
                <x-forms.select-input wire:model="form.gender" id="gender">
                    <option value="0">@lang('general.unknown')</option>
                    <option value="1">@lang('general.male')</option>
                    <option value="2">@lang('general.female')</option>
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.gender') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="marital_status">@lang('general.marital_status')</x-forms.input-label>
                <x-forms.select-input wire:model="form.marital_status" id="marital_status">
                    <option value="0">@lang('general.unknown')</option>
                    <option value="1">@lang('general.single')</option>
                    <option value="2">@lang('general.married')</option>
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.marital_status') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="children">@lang('general.children')</x-forms.input-label>
                <x-forms.input type="number" wire:model="form.children" id="children" />
                <x-error-span>{{ $errors->first('form.children') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="nationality">@lang('general.nationality')</x-forms.input-label>
                <x-forms.select-input wire:model="form.nationality" id="nationality">
                    <option value="syrian">Syrian</option>
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.nationality') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="civil_registry_office">@lang('general.civil_registry_office')</x-forms.input-label>
                <x-forms.input type="text" wire:model="form.civil_registry_office" id="civil_registry_office" />
                <x-error-span>{{ $errors->first('form.civil_registry_office') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="civil_registry_number">@lang('general.civil_registry_number')</x-forms.input-label>
                <x-forms.input type="text" wire:model="form.civil_registry_number" id="civil_registry_number" />
                <x-error-span>{{ $errors->first('form.civil_registry_number') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="place_of_registration">@lang('general.place_of_registration')</x-forms.input-label>
                <x-forms.input type="text" wire:model="form.place_of_registration" id="place_of_registration" />
                <x-error-span>{{ $errors->first('form.place_of_registration') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="work_phone">@lang('general.work_phone')</x-forms.input-label>
                <x-forms.input type="tel" wire:model="form.work_phone" id="work_phone" />
                <x-error-span>{{ $errors->first('form.work_phone') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="personal_phone">@lang('general.personal_phone')</x-forms.input-label>
                <x-forms.input type="tel" wire:model="form.personal_phone" id="personal_phone" />
                <x-error-span>{{ $errors->first('form.personal_phone') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="personal_email">@lang('general.personal_email')</x-forms.input-label>
                <x-forms.input type="email" wire:model="form.personal_email" id="personal_email" />
                <x-error-span>{{ $errors->first('form.personal_email') }}</x-error-span>
            </div>

            @foreach (config('app.supported_locales') as $locale)
                <div class="md:col-span-1">
                    <x-forms.input-label for="address_{{ $locale }}">@lang('general.address') ({{ strtoupper($locale) }})</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.address.{{ $locale }}" id="address_{{ $locale }}" />
                    <x-error-span>{{ $errors->first('form.address.' . $locale) }}</x-error-span>
                </div>
            @endforeach

            <div class="md:col-span-3">
                <x-forms.input-label for="skills">@lang('general.skills')</x-forms.input-label>
                <x-forms.input type="text" wire:model="form.skills" id="skills" />
                <x-error-span>{{ $errors->first('form.skills') }}</x-error-span>
            </div>

            <div class="md:col-span-3">
                <x-forms.input-label for="interests">@lang('general.interests')</x-forms.input-label>
                <x-forms.input type="text" wire:model="form.interests" id="interests" />
                <x-error-span>{{ $errors->first('form.interests') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label>@lang('general.status')</x-forms.input-label>
                <div class="flex space-x-4 items-center mt-2">
                    <x-forms.radio-input name="is_active" value="1" wire:model="form.is_active">@lang('general.active')</x-forms.radio-input>
                    <x-forms.radio-input name="is_active" value="0" wire:model="form.is_active">@lang('general.inactive')</x-forms.radio-input>
                </div>
                <x-error-span>{{ $errors->first('form.is_active') }}</x-error-span>
            </div>

            <div class="flex items-center space-x-2">
                <input type="checkbox" wire:model.live="form.is_board_member" id="is_board_member" class="rounded">
                <x-forms.input-label for="is_board_member">@lang('general.is_board_member')</x-forms.input-label>
                <x-error-span>{{ $errors->first('form.is_board_member') }}</x-error-span>
            </div>

            <div></div>

            @if ($this->form->is_board_member)
                <div>
                    <x-forms.input-label for="board_member_started_at">@lang('general.board_member_started_at')</x-forms.input-label>
                    <x-forms.input type="date" wire:model="form.board_member_started_at" id="board_member_started_at" />
                    <x-error-span>{{ $errors->first('form.board_member_started_at') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="board_member_end_at">@lang('general.board_member_end_at')</x-forms.input-label>
                    <x-forms.input type="date" wire:model="form.board_member_end_at" id="board_member_end_at" />
                    <x-error-span>{{ $errors->first('form.board_member_end_at') }}</x-error-span>
                </div>
            @endif
        </div>

        <div class="flex justify-end space-x-2 pt-4">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
        </div>
    </form>
</div>
