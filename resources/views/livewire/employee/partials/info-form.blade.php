@php
use App\Enums\MaritalStatus;
@endphp

<form wire:submit.prevent="saveInformation" class="flex flex-col space-y-6">
    @if($errors->any())
        <ul>
            @foreach($errors->all() as $error)
                <li>{{$error}}</li>
            @endforeach
        </ul>
        @endif
    <div class="">
        <x-forms.input-label for="photo">@lang('general.photo')</x-forms.input-label>
        <div class="mt-2 flex items-center space-x-6">
            <div class="shrink-0">
                @include('livewire.employee.partials.photo-upload')
            </div>
            <div>
                <input type="file" wire:model.live="form.photo" id="photo"
                       class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100" />
                <x-error-span>{{ $errors->first('form.photo') }}</x-error-span>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3">
        <h2 class="text-xl font-semibold mb-4 col-span-2">@lang('general.general_info')</h2>

        @foreach (config('app.supported_locales') as $locale)
            <div>
                <x-forms.input-label for="name_{{ $locale }}">@lang('general.name') ({{ strtoupper($locale) }})</x-forms.input-label>
                <x-forms.input type="text" wire:model.blur="form.name.{{ $locale }}" id="name_{{ $locale }}" />
                <x-error-span>{{ $errors->first('form.name.' . $locale) }}</x-error-span>
                @error('form.name.'.$locale)
                <x-error-span>{{ $message }}</x-error-span>
                @enderror
            </div>
        @endforeach

        <div>
            <x-forms.input-label for="dob">@lang('general.dob')</x-forms.input-label>
            <x-forms.date-input wire:model.blur="form.dob" id="dob" />
            <x-error-span>{{ $errors->first('dob') }}</x-error-span>
            @error('form.dob')
            <x-error-span>{{ $message }}</x-error-span>
            @enderror
        </div>

        <div>
            <x-forms.input-label>@lang('general.gender')</x-forms.input-label>
            <div class="flex space-x-4 mt-2">
                <x-forms.radio-input name="gender" value="1" wire:model.blur="form.gender">@lang('general.male')</x-forms.radio-input>
                <x-forms.radio-input name="gender" value="2" wire:model.blur="form.gender">@lang('general.female')</x-forms.radio-input>
            </div>
            <x-error-span>{{ $errors->first('form.gender') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="marital_status">@lang('general.marital_status')</x-forms.input-label>
            <x-forms.select-input wire:model.blur="form.marital_status" id="marital_status">
                @foreach (MaritalStatus::getValues() as $statusValue)
                    @php $status = MaritalStatus::getLocalizedName($statusValue); @endphp
                    <option value="{{ $statusValue }}">{{ $status['value'] }}</option>
                @endforeach
            </x-forms.select-input>
            <x-error-span>{{ $errors->first('form.marital_status') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="children">@lang('general.children')</x-forms.input-label>
            <x-forms.input type="number" wire:model.blur="form.children" id="children" />
            <x-error-span>{{ $errors->first('form.children') }}</x-error-span>
        </div>

        @foreach (config('app.supported_locales') as $locale)
            <div>
                <x-forms.input-label for="address_{{ $locale }}">@lang('general.address') ({{ strtoupper($locale) }})</x-forms.input-label>
                <x-forms.input type="text" wire:model.blur="form.address.{{ $locale }}" id="address_{{ $locale }}" />
                <x-error-span>{{ $errors->first('form.address.' . $locale) }}</x-error-span>
            </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3">
        <h2 class="text-xl col-span-full font-semibold mb-4">@lang('general.address_info')</h2>

        @foreach (config('app.supported_locales') as $locale)
            <div>
                <x-forms.input-label for="father_name_{{ $locale }}">@lang('general.father_name') ({{ strtoupper($locale) }})</x-forms.input-label>
                <x-forms.input type="text" wire:model.blur="form.father_name.{{ $locale }}" id="father_name_{{ $locale }}" />
                <x-error-span>{{ $errors->first('form.father_name.' . $locale) }}</x-error-span>
            </div>
        @endforeach

        @foreach (config('app.supported_locales') as $locale)
            <div>
                <x-forms.input-label for="mother_name_{{ $locale }}">@lang('general.mother_name') ({{ strtoupper($locale) }})</x-forms.input-label>
                <x-forms.input type="text" wire:model.blur="form.mother_name.{{ $locale }}" id="mother_name_{{ $locale }}" />
                <x-error-span>{{ $errors->first('form.mother_name.' . $locale) }}</x-error-span>
            </div>
        @endforeach

        <div>
            <x-forms.input-label for="nationality">@lang('general.nationality')</x-forms.input-label>
            <x-forms.select-input wire:model.blur="form.nationality" id="nationality">
                <option value="syrian">Syrian</option>
            </x-forms.select-input>
            <x-error-span>{{ $errors->first('form.nationality') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="national_number">@lang('general.national_number')</x-forms.input-label>
            <x-forms.input type="text" wire:model.blur="form.national_number" id="national_number" />
            <x-error-span>{{ $errors->first('form.national_number') }}</x-error-span>
            @error('form.national_number')
            <x-error-span>{{ $message }}</x-error-span>
            @enderror
        </div>

        <div>
            <x-forms.input-label for="civil_registry_office">@lang('general.civil_registry_office')</x-forms.input-label>
            <x-forms.input type="text" wire:model.blur="form.civil_registry_office" id="civil_registry_office" />
            <x-error-span>{{ $errors->first('form.civil_registry_office') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="civil_registry_number">@lang('general.civil_registry_number')</x-forms.input-label>
            <x-forms.input type="text" wire:model.blur="form.civil_registry_number" id="civil_registry_number" />
            <x-error-span>{{ $errors->first('form.civil_registry_number') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="place_of_registration">@lang('general.place_of_registration')</x-forms.input-label>
            <x-forms.input type="text" wire:model.blur="form.place_of_registration" id="place_of_registration" />
            <x-error-span>{{ $errors->first('form.place_of_registration') }}</x-error-span>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3">
        <div>
            <h2 class="text-xl font-semibold mb-4">@lang('general.employment_info')</h2>

            <div>
                <x-forms.input-label for="work_email_username">@lang('general.work_email')</x-forms.input-label>
                <div class="flex mt-1">
                    <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                        hcsyria.org@
                    </span>
                    <x-forms.input type="text" wire:model.blur="form.work_email_username" id="work_email_username" class="rounded-r-none" placeholder="username" />
                </div>
                <x-error-span>{{ $errors->first('form.work_email_username') }}</x-error-span>
                <x-error-span>{{ $errors->first('form.email') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="direct_manager_id">@lang('general.direct_manager')</x-forms.input-label>
                <x-forms.select-input wire:model.blur="form.direct_manager_id" id="direct_manager_id">
                    <option value="">--</option>
                    @foreach ($allEmployees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->getTranslation('name', app()->getLocale()) }}</option>
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.direct_manager_id') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="tech_manager_id">@lang('general.tech_manager')</x-forms.input-label>
                <x-forms.select-input wire:model.blur="form.tech_manager_id" id="tech_manager_id">
                    <option value="">--</option>
                    @foreach ($allEmployees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->getTranslation('name', app()->getLocale()) }}</option>
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.tech_manager_id') }}</x-error-span>
            </div>
        </div>

        <div>
            <h2 class="text-xl font-semibold mb-4">@lang('general.contact_info')</h2>

            <div>
                <x-forms.input-label for="personal_phone">@lang('general.personal_phone')</x-forms.input-label>
                <x-forms.input type="tel" wire:model.blur="form.personal_phone" id="personal_phone" />
                <x-error-span>{{ $errors->first('form.personal_phone') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="work_phone">@lang('general.work_phone')</x-forms.input-label>
                <x-forms.input type="tel" wire:model.blur="form.work_phone" id="work_phone" />
                <x-error-span>{{ $errors->first('form.work_phone') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="personal_email">@lang('general.personal_email')</x-forms.input-label>
                <x-forms.input type="email" wire:model.blur="form.personal_email" id="personal_email" />
                <x-error-span>{{ $errors->first('form.personal_email') }}</x-error-span>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">@lang('general.skills_and_interests')</h2>

        <div class="md:col-span-2">
            <x-forms.input-label for="skills">@lang('general.skills')</x-forms.input-label>
            <x-forms.input type="text" wire:model.blur="form.skills" id="skills" />
            <x-error-span>{{ $errors->first('form.skills') }}</x-error-span>
        </div>

        <div class="md:col-span-2">
            <x-forms.input-label for="interests">@lang('general.interests')</x-forms.input-label>
            <x-forms.input type="text" wire:model.blur="form.interests" id="interests" />
            <x-error-span>{{ $errors->first('form.interests') }}</x-error-span>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">@lang('general.board_member')</h2>

        <div class="flex items-center space-x-2">
            <input type="checkbox" wire:model.live="form.is_board_member" id="is_board_member" class="rounded">
            <x-forms.input-label for="is_board_member">@lang('general.is_board_member')</x-forms.input-label>
            <x-error-span>{{ $errors->first('form.is_board_member') }}</x-error-span>
        </div>

        @if ($form->is_board_member)
            <div>
                <x-forms.input-label for="board_member_started_at">@lang('general.board_member_started_at')</x-forms.input-label>
                <x-forms.date-input wire:model.blur="form.board_member_started_at" id="board_member_started_at" />
                <x-error-span>{{ $errors->first('form.board_member_started_at') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="board_member_end_at">@lang('general.board_member_end_at')</x-forms.input-label>
                <x-forms.input type="date" wire:model.blur="form.board_member_end_at" id="board_member_end_at" />
                <x-error-span>{{ $errors->first('form.board_member_end_at') }}</x-error-span>
            </div>
        @endif
    </div>

    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
