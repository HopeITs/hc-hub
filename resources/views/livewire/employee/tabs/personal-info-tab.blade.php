@php
    use App\Enums\MaritalStatus;
    use App\Enums\Nationality;
@endphp

<form wire:submit.prevent="saveInformation" class="flex flex-col space-y-6">
    <div class="">
        <x-forms.input-label for="photo">@lang('general.photo')</x-forms.input-label>
        <x-forms.file-input wire:model.live="infoForm.photo" id="photo" name="infoForm.photo" preview-type="avatar"
            accept="image/*" :old-file="$infoForm->photo" />
    </div>

    <div class="grid pb-8 mb-8 border-b md:grid-cols-2 gap-x-6 gap-y-3">
        <x-heading-label class="col-span-full">@lang('general.general_info')</x-heading-label>
        <div class="grid md:grid-cols-4 col-span-full gap-x-6 gap-y-3">
            @foreach (config('app.supported_locales') as $locale)
                <div wire:key="emp-first-name-{{ $locale }}">
                    <x-forms.input-label for="first_name_{{ $locale }}" required>@lang('general.first_name') ({{ strtoupper($locale) }})
                    </x-forms.input-label>
                    <x-forms.input type="text" wire:model="infoForm.first_name_{{ $locale }}"
                        id="first_name_{{ $locale }}" />
                    <x-error-span>{{ $errors->first('infoForm.first_name_' . $locale) }}</x-error-span>
                </div>

                <div wire:key="emp-last-name-{{ $locale }}">
                    <x-forms.input-label for="last_name_{{ $locale }}" required>@lang('general.last_name') ({{ strtoupper($locale) }})
                    </x-forms.input-label>
                    <x-forms.input type="text" wire:model="infoForm.last_name_{{ $locale }}"
                        id="last_name_{{ $locale }}" />
                    <x-error-span>{{ $errors->first('infoForm.last_name_' . $locale) }}</x-error-span>
                </div>
            @endforeach
        </div>


        <div class="grid md:grid-cols-2 gap-x-6">
            <div wire:key="emp-actual-dob">
                <x-forms.input-label for="actual_dob">@lang('general.actual-dob')</x-forms.input-label>
                <x-forms.date-input wire:model="infoForm.actual_dob" id="actual_dob" />
                <x-error-span>{{ $errors->first('infoForm.actual_dob') }}</x-error-span>
            </div>
            <div wire:key="emp-city">
                <x-forms.input-label for="city_id" required>@lang('general.current-residence')</x-forms.input-label>
                <x-forms.select2-input wire:model="infoForm.city_id" id="city_id">
                    <option value="">{{ __('general.unknown') }}</option>
                    @foreach ($cities as $cityId => $city)
                        <option value="{{ $cityId }}">{{ $city }}</option>
                    @endforeach
                </x-forms.select2-input>
                <x-error-span>{{ $errors->first('infoForm.city_id') }}</x-error-span>
            </div>
        </div>

        {{--
            A radio group has no single input to point a `for` at, so the group
            is named through aria-labelledby instead of a dangling label.
        --}}
        <div wire:key="emp-gender" role="radiogroup" aria-labelledby="gender-label">
            <x-forms.input-label id="gender-label" required>@lang('general.gender')</x-forms.input-label>
            <div class="flex mt-2 space-x-4">
                <x-forms.radio-input id="gender_male" name="gender" value="1"
                    wire:model="infoForm.gender">@lang('general.male')</x-forms.radio-input>
                <x-forms.radio-input id="gender_female" name="gender" value="2"
                    wire:model="infoForm.gender">@lang('general.female')</x-forms.radio-input>
            </div>
            <x-error-span>{{ $errors->first('infoForm.gender') }}</x-error-span>
        </div>

        <div wire:key="emp-marital_status">
            <x-forms.input-label for="marital_status" required>@lang('general.marital_status')</x-forms.input-label>
            <x-forms.select2-input wire:model="infoForm.marital_status" id="marital_status">
                @foreach (MaritalStatus::getValues() as $statusValue)
                    @php $status = MaritalStatus::getLocalizedName($statusValue); @endphp
                    <option value="{{ $statusValue }}">{{ $status['value'] }}</option>
                @endforeach
            </x-forms.select2-input>
            <x-error-span>{{ $errors->first('infoForm.marital_status') }}</x-error-span>
        </div>

        <div wire:key="emp-children">
            <x-forms.input-label for="children_count">@lang('general.children')</x-forms.input-label>
            <x-forms.input type="number" wire:model="infoForm.children" id="children_count" />
            <x-error-span>{{ $errors->first('infoForm.children') }}</x-error-span>
        </div>

        @foreach (config('app.supported_locales') as $locale)
            <div wire:key="emp-address-{{ $locale }}">
                <x-forms.input-label for="address_{{ $locale }}">@lang('general.address') ({{ strtoupper($locale) }})
                </x-forms.input-label>
                <x-forms.input type="text" wire:model="infoForm.address_{{ $locale }}"
                    id="address_{{ $locale }}" />
                <x-error-span>{{ $errors->first('infoForm.address_' . $locale) }}</x-error-span>
            </div>
        @endforeach
    </div>

    <div class="grid pb-8 mb-8 border-b md:grid-cols-2 gap-x-6 gap-y-3">
        <x-heading-label class="col-span-full">@lang('general.address_info')</x-heading-label>
<div class="md:grid md:grid-cols-2 gap-x-6 gap-y-3">
        @foreach (config('app.supported_locales') as $locale)
            <div wire:key="emp-father-{{ $locale }}">
                <x-forms.input-label for="father_name_{{ $locale }}" required>@lang('general.father_name')
                    ({{ strtoupper($locale) }})
                </x-forms.input-label>
                <x-forms.input type="text" wire:model="infoForm.father_name_{{ $locale }}"
                    id="father_name_{{ $locale }}" />
                <x-error-span>{{ $errors->first('infoForm.father_name_' . $locale) }}</x-error-span>
            </div>
        @endforeach
</div>
<div class="md:grid md:grid-cols-2 gap-x-6 gap-y-3">
        @foreach (config('app.supported_locales') as $locale)
            <div wire:key="emp-mother-{{ $locale }}">
                <x-forms.input-label for="mother_name_{{ $locale }}" required>@lang('general.mother_name')
                    ({{ strtoupper($locale) }})
                </x-forms.input-label>
                <x-forms.input type="text" wire:model="infoForm.mother_name_{{ $locale }}"
                    id="mother_name_{{ $locale }}" />
                <x-error-span>{{ $errors->first('infoForm.mother_name_' . $locale) }}</x-error-span>
            </div>
        @endforeach
</div>
        <div wire:key="emp-dob">
            <x-forms.input-label for="dob">@lang('general.dob')</x-forms.input-label>
            <x-forms.date-input wire:model="infoForm.dob" id="dob" />
            <x-error-span>{{ $errors->first('infoForm.dob') }}</x-error-span>
        </div>

        <div wire:key="emp-nationality">
            <x-forms.input-label for="nationality">@lang('general.nationality')</x-forms.input-label>
            <x-forms.select2-input wire:model="infoForm.nationality" id="nationality">
                @foreach (Nationality::getValues() as $statusValue)
                    @php $status = Nationality::getLocalizedName($statusValue); @endphp
                    <option value="{{ $statusValue }}">{{ $status }}</option>
                @endforeach
            </x-forms.select2-input>
            <x-error-span>{{ $errors->first('infoForm.nationality') }}</x-error-span>
        </div>

        <div wire:key="emp-national-number">
            <x-forms.input-label for="national_number" required>@lang('general.national_number')</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.national_number" id="national_number" />
            <x-error-span>{{ $errors->first('infoForm.national_number') }}</x-error-span>
        </div>


<div class="md:grid md:grid-cols-2 gap-x-6 gap-y-3">
         @foreach (config('app.supported_locales') as $locale)
            <div wire:key="pob-{{ $locale }}">
                <x-forms.input-label for="pob_{{ $locale }}">@lang('general.place_and_birth_date')
                    ({{ strtoupper($locale) }})
                </x-forms.input-label>
                <x-forms.input type="text" wire:model="infoForm.pob_{{ $locale }}"
                    id="pob_{{ $locale }}" />
                <x-error-span>{{ $errors->first('infoForm.pob_' . $locale) }}</x-error-span>
            </div>
        @endforeach
</div>
        <div>
            <x-forms.input-label for="civil_registry_office">@lang('general.civil_registry_office')</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.civil_registry_office" id="civil_registry_office" />
            <x-error-span>{{ $errors->first('infoForm.civil_registry_office') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="civil_registry_number">@lang('general.civil_registry_number')</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.civil_registry_number" id="civil_registry_number" />
            <x-error-span>{{ $errors->first('infoForm.civil_registry_number') }}</x-error-span>
        </div>


    </div>
    <div class="grid pb-8 mb-8 border-b md:grid-cols-1 gap-x-6 gap-y-3">
        <x-heading-label class="col-span-1 ">@lang('general.membership')</x-heading-label>

        <div class="flex items-center col-span-1 space-x-2 ">
            <x-forms.input type="checkbox" wire:model.live="infoForm.is_board_member"
                wire:change="toggleBoardMember($event.target.checked)" value="1" id="is_board_member" />
            <x-forms.input-label for="is_board_member">@lang('general.is_board_member')</x-forms.input-label>
            <x-error-span>{{ $errors->first('infoForm.is_board_member') }}</x-error-span>
        </div>

        <div class="grid col-span-1 md:grid-cols-2 gap-x-6 gap-y-3"
            wire:key="board-member-{{ (int) data_get($infoForm, 'is_board_member') }}">
            <div>
                <x-forms.input-label for="board_member_started_at">@lang('general.board_member_started_at')</x-forms.input-label>
                <x-forms.date-input wire:model.live="infoForm.board_member_started_at" id="board_member_started_at"
                    :required="data_get($infoForm, 'is_board_member')" value="{{ data_get($infoForm, 'is_board_member') ?? null }}" />
                <x-error-span>{{ $errors->first('infoForm.board_member_started_at') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="board_member_end_at">@lang('general.board_member_end_at')</x-forms.input-label>
                <x-forms.date-input wire:model.live="infoForm.board_member_end_at" id="board_member_end_at"
                    value="{{ data_get($infoForm, 'is_board_member') ?? null }}" />
                <x-error-span>{{ $errors->first('infoForm.board_member_end_at') }}</x-error-span>
            </div>
        </div>
    </div>
    <div class="pb-8 mb-8 border-b">
        <x-heading-label>@lang('general.skills_and_interests')</x-heading-label>

        <div class="md:col-span-2" wire:ignore>
            <x-forms.input-label for="skills">@lang('general.skills')</x-forms.input-label>
            <x-forms.tags-input inputId="skills" placeholder='Add tag'
                suggestions="Resilience,Initiative,MS Office,Teamwork,Time management,Critical thinking,Problem Solving,Adaptability,Willingness to learn,Active listening,Emotion Intelligence,Creativity,Data analysis"
                :forceSuggestion=true wire:model="infoForm.skills" />
            <x-error-span>{{ $errors->first('infoForm.skills') }}</x-error-span>
        </div>

        <div class="md:col-span-2" wire:ignore>
            <x-forms.input-label for="interests">@lang('general.interests')</x-forms.input-label>
            <x-forms.tags-input inputId="interests" placeholder='Add tag'
                suggestions="Reading,Swimming,Cooking,Sports,Football,Basketball,Tennis,Hiking,Traveling"
                :forceSuggestion=true wire:model="infoForm.interests" />
            <x-error-span>{{ $errors->first('infoForm.interests') }}</x-error-span>
        </div>
    </div>

    <x-forms.dirty-gate watch="infoForm" :active="! $isCreateMode" class="flex justify-end space-x-2 ">
        {{--
            The tab is deliberately NOT advanced here. In create mode
            saveInformation() redirects into the edit form on success, so
            switching tabs client-side only ever took effect when validation
            FAILED — which hid this tab, and with it every error message
            belonging to its fields. In edit mode the save simply stays put.
        --}}
        <x-primary-button type="button" wire:click="saveInformation"
            class="!w-auto" x-bind:disabled="! dirty">
            @lang($isCreateMode ? 'general.next' : 'general.save')
        </x-primary-button>

        @if ($isCreateMode)
            <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
        @endif
    </x-forms.dirty-gate>

</form>
