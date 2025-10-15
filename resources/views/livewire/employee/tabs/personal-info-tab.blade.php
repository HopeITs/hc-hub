@php
    use App\Enums\MaritalStatus;
    use App\Enums\Nationality;
@endphp
@section('css')
    <link href="https://unpkg.com/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css">
@endsection
<form wire:submit.prevent="saveInformation" class="flex flex-col space-y-6">
    <x-forms.errors-validation-list/>

    <div class="">
        <x-forms.input-label for="photo">@lang('general.photo')</x-forms.input-label>
        <x-forms.file-input wire:model.live="infoForm.photo" id="photo" name="infoForm.photo" preview-type="avatar"
                            accept="image/*" :old-file="$infoForm->photo"/>
    </div>

    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3 border-b pb-8 mb-8">
        <x-heading-label class="col-span-full">@lang('general.general_info')</x-heading-label>

        @foreach (config('app.supported_locales') as $locale)
            <div wire:key="emp-name-{{ $locale }}">
                <x-forms.input-label for="name_{{ $locale }}">@lang('general.name') ({{ strtoupper($locale) }})
                </x-forms.input-label>
                <x-forms.input type="text" wire:model="infoForm.name_{{ $locale }}" id="name_{{ $locale }}"/>
                <x-error-span>{{ $errors->first('infoForm.name_.' . $locale) }}</x-error-span>

            </div>
        @endforeach
        <div class="grid md:grid-cols-2 gap-x-6">
        <div wire:key="emp-actual-dob">
            <x-forms.input-label for="dob">@lang('general.actual-dob')</x-forms.input-label>
            <x-forms.date-input wire:model="infoForm.actual_dob" id="actual_dob"/>
            <x-error-span>{{ $errors->first('infoForm.actual_dob') }}</x-error-span>

        </div>
            <div wire:key="emp-actual-dob">
                <x-forms.input-label for="city_id">{{trans_choice('general.city',1)}}</x-forms.input-label>
                <x-forms.select-input wire:model="infoForm.city_id" id="city_id">
                    @foreach($cities as $id => $city)
                        <option value="{{$id}}">{{$city}}</option>
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('infoForm.city_id') }}</x-error-span>

            </div>
        </div>

        <div wire:key="emp-gender">
            <x-forms.input-label for="gender">@lang('general.gender')</x-forms.input-label>
            <div class="flex space-x-4 mt-2">
                <x-forms.radio-input name="gender" value="1"
                                     wire:model="infoForm.gender">@lang('general.male')</x-forms.radio-input>
                <x-forms.radio-input name="gender" value="2"
                                     wire:model="infoForm.gender">@lang('general.female')</x-forms.radio-input>
            </div>
            <x-error-span>{{ $errors->first('infoForm.gender') }}</x-error-span>
        </div>

        <div wire:key="emp-marital_status">
            <x-forms.input-label for="marital_status">@lang('general.marital_status')</x-forms.input-label>
            <x-forms.select-input wire:model="infoForm.marital_status" id="marital_status">
                @foreach (MaritalStatus::getValues() as $statusValue)
                    @php $status = MaritalStatus::getLocalizedName($statusValue); @endphp
                    <option value="{{ $statusValue }}">{{ $status['value'] }}</option>
                @endforeach
            </x-forms.select-input>
            <x-error-span>{{ $errors->first('infoForm.marital_status') }}</x-error-span>
        </div>

        <div wire:key="emp-children">
            <x-forms.input-label for="children_count">@lang('general.children')</x-forms.input-label>
            <x-forms.input type="number" wire:model="infoForm.children" id="children_count"/>
            <x-error-span>{{ $errors->first('infoForm.children') }}</x-error-span>
        </div>

        @foreach (config('app.supported_locales') as $locale)
            <div wire:key="emp-address-{{ $locale }}">
                <x-forms.input-label for="address_{{ $locale }}">@lang('general.address') ({{ strtoupper($locale) }}
                    )
                </x-forms.input-label>
                <x-forms.input type="text" wire:model="infoForm.address_{{ $locale }}" id="address_{{ $locale }}"/>
                <x-error-span>{{ $errors->first('infoForm.address_' . $locale) }}</x-error-span>
            </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3 border-b pb-8 mb-8">
        <x-heading-label class="col-span-full">@lang('general.address_info')</x-heading-label>

        @foreach (config('app.supported_locales') as $locale)
            <div wire:key="emp-father-{{ $locale }}">
                <x-forms.input-label for="father_name_{{ $locale }}">@lang('general.father_name')
                    ({{ strtoupper($locale) }})
                </x-forms.input-label>
                <x-forms.input type="text" wire:model="infoForm.father_name_{{ $locale }}"
                               id="father_name_{{ $locale }}"/>
                <x-error-span>{{ $errors->first('infoForm.father_name_' . $locale) }}</x-error-span>
            </div>
        @endforeach

        @foreach (config('app.supported_locales') as $locale)
            <div wire:key="emp-mother-{{ $locale }}">
                <x-forms.input-label for="mother_name_{{ $locale }}">@lang('general.mother_name')
                    ({{ strtoupper($locale) }})
                </x-forms.input-label>
                <x-forms.input type="text" wire:model="infoForm.mother_name_{{ $locale }}"
                               id="mother_name_{{ $locale }}"/>
                <x-error-span>{{ $errors->first('infoForm.mother_name_' . $locale) }}</x-error-span>
            </div>
        @endforeach
        <div wire:key="emp-dob">
            <x-forms.input-label for="dob">@lang('general.dob')</x-forms.input-label>
            <x-forms.date-input wire:model="infoForm.dob" id="dob"/>
            <x-error-span>{{ $errors->first('infoForm.dob') }}</x-error-span>

        </div>
        <div wire:key="emp-nationality">
            <x-forms.input-label for="nationality">@lang('general.nationality')</x-forms.input-label>

            <x-forms.select-input wire:model="infoForm.nationality" id="nationality">
                @foreach (Nationality::getValues() as $statusValue)
                    @php $status = Nationality::getLocalizedName($statusValue); @endphp
                    <option value="{{ $statusValue }}">{{ $status }}</option>
                @endforeach
            </x-forms.select-input>
            <x-error-span>{{ $errors->first('infoForm.nationality') }}</x-error-span>
        </div>

        <div wire:key="emp-national-number">
            <x-forms.input-label for="national_number">@lang('general.national_number')</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.national_number" id="national_number"/>
            <x-error-span>{{ $errors->first('infoForm.national_number') }}</x-error-span>

        </div>
        <div>
            <x-forms.input-label
                for="place_and_birth_date">@lang('general.place_and_birth_date')</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.place_and_birth_date" id="place_and_birth_date"/>
            <x-error-span>{{ $errors->first('infoForm.place_and_birth_date') }}</x-error-span>
        </div>
        <div>
            <x-forms.input-label
                for="civil_registry_office">@lang('general.civil_registry_office')</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.civil_registry_office" id="civil_registry_office"/>
            <x-error-span>{{ $errors->first('infoForm.civil_registry_office') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label
                for="civil_registry_number">@lang('general.civil_registry_number')</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.civil_registry_number" id="civil_registry_number"/>
            <x-error-span>{{ $errors->first('infoForm.civil_registry_number') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label
                for="place_of_registration">@lang('general.place_of_registration')</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.place_of_registration" id="place_of_registration"/>
            <x-error-span>{{ $errors->first('infoForm.place_of_registration') }}</x-error-span>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3 border-b pb-8 mb-8">

        <x-heading-label>@lang('general.employment_info')</x-heading-label>
        <div>
            <div class="flex justify-between">
                <x-forms.input-label for="username">@lang('general.work_email')</x-forms.input-label>
                <span class="text-secondary-light text-xs">password is: HopeCenterHR</span>
            </div>
            <div class="flex rtl:flex-row-reverse  ">

                <x-forms.input type="text" wire:model="infoForm.username" id="username"
                               class="rounded-r-none" placeholder="username"/>
                <span
                    class="inline-flex items-center px-3 ltr:rounded-e-md rtl:rounded-s-md shadow-md border ltr:border-s-0 rtl:border-e-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                        @hcsyria.org
                    </span>
            </div>

            <x-error-span>{{ $errors->first('infoForm.username') }}</x-error-span>
        </div>
        <div>
            <x-forms.input-label for="work_phone">@lang('general.work_phone')</x-forms.input-label>
            <x-forms.input type="tel" wire:model="infoForm.work_phone" id="work_phone"/>
            <x-error-span>{{ $errors->first('infoForm.work_phone') }}</x-error-span>
        </div>
        <div class="col-span-full">
            <x-forms.input-label for="roles">{{trans_choice('general.role',2)}}</x-forms.input-label>

            <input class="hidden" wire:model="infoForm.roles"  id="roles_hidden">
            <x-forms.input class="tagify" id="roles" value="{{$infoForm->roles}}"
                           data-whitelist="{{implode(',',$roles)}}"/>

            <x-error-span>{{ $errors->first('infoForm.roles') }}</x-error-span>
        </div>


    </div>
    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3 border-b pb-8 mb-8">
        <x-heading-label>@lang('general.contact_info')</x-heading-label>

        <div>
            <x-forms.input-label for="personal_phone">@lang('general.personal_phone')</x-forms.input-label>
            <x-forms.input type="tel" wire:model="infoForm.personal_phone" id="personal_phone"/>
            <x-error-span>{{ $errors->first('infoForm.personal_phone') }}</x-error-span>
        </div>


        <div>
            <x-forms.input-label for="personal_email">@lang('general.personal_email')</x-forms.input-label>
            <x-forms.input type="email" wire:model="infoForm.personal_email" id="personal_email"/>
            <x-error-span>{{ $errors->first('infoForm.personal_email') }}</x-error-span>
        </div>
    </div>
    <div class="border-b pb-8 mb-8">
        <x-heading-label>@lang('general.skills_and_interests')</x-heading-label>

        <div class="md:col-span-2">
            <x-forms.input-label for="skills">@lang('general.skills')</x-forms.input-label>
            <input class="hidden" wire:model="infoForm.skills" id="skills_hidden">
            <x-forms.input class="tagify" type="text" data-enforceWhitelist="0" value="{{$infoForm->skills}}"
                           data-whitelist="Resilience,Initiative,MS Office,Teamwork,Time management,Critical thinking ,Problem Solving, Adaptability, Willingness to learn,Active listening,Emotion Intelligence,Creativity , Data analysis     "
                           id="skills"/>
            <x-error-span>{{ $errors->first('infoForm.skills') }}</x-error-span>
        </div>

        <div class="md:col-span-2">
            <x-forms.input-label for="interests">@lang('general.interests')</x-forms.input-label>
            <input class="hidden" wire:model="infoForm.interests" id="interests_hidden">
            <x-forms.input class="tagify" data-enforceWhitelist="1" value="{{$infoForm->interests}}"
                           data-whitelist="Reading,Swimming,Cooking,Sports,Football,Basketball,Tennis,Hiking,Traveling"
                           type="text" id="interests"/>
            <x-error-span>{{ $errors->first('infoForm.interests') }}</x-error-span>
        </div>
    </div>

    <div class="grid md:grid-cols-1 gap-x-6 gap-y-3 border-b pb-8 mb-8">
        <x-heading-label class="col-span-1 ">@lang('general.board_member')</x-heading-label>
        <div class=" col-span-1 flex items-center space-x-2">
            {{--                <x-forms.input type="checkbox" wire:model.live="infoForm.is_board_member" value="0" class="hidden"/>--}}
            <x-forms.input type="checkbox" wire:model.live="infoForm.is_board_member"
                           wire:change="toggleBoardMember($event.target.checked)"
                           value="1" id="is_board_member"/>
            <x-forms.input-label for="is_board_member">@lang('general.is_board_member')</x-forms.input-label>
            <x-error-span>{{ $errors->first('infoForm.is_board_member') }}</x-error-span>
        </div>

        {{-- Key the whole conditional block by state --}}
        <div class="col-span-1 grid  md:grid-cols-2 gap-x-6 gap-y-3"
             wire:key="board-member-{{ (int) data_get($infoForm, 'is_board_member') }}">


            <div>
                <x-forms.input-label
                    for="board_member_started_at">@lang('general.board_member_started_at')</x-forms.input-label>
                {{--                    <x-forms.date-input wire:model="infoForm.board_member_started_at"--}}
                {{--                                        id="board_member_started_at" :required="data_get($infoForm, 'is_board_member')"/>--}}

                <x-forms.date-input
                    wire:model.live="infoForm.board_member_started_at"
                    id="board_member_started_at"
                    :required="data_get($infoForm, 'is_board_member')"
                    value="{{data_get($infoForm, 'is_board_member') ?? null}}"
                />
                <x-error-span>{{ $errors->first('infoForm.board_member_started_at') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label
                    for="board_member_end_at">@lang('general.board_member_end_at')</x-forms.input-label>
                {{--                    <x-forms.date-input wire:model="infoForm.board_member_end_at" id="board_member_end_at"/>--}}

                <x-forms.date-input
                    wire:model.live="infoForm.board_member_end_at"
                    id="board_member_end_at"
                    value="{{data_get($infoForm, 'is_board_member') ?? null}}"
                />
                <x-error-span>{{ $errors->first('infoForm.board_member_end_at') }}</x-error-span>
            </div>

        </div>


    </div>

    <div class="flex justify-end space-x-2 ">
        <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')"
                            class="!w-auto">@lang('general.cancel')</x-secondary-button>
    </div>
</form>

{{--@section('js')--}}
{{--    <link rel="stylesheet" href="{{}}">--}}
{{--    <script>--}}
{{--        new TomSelect("#input-tags",{--}}
{{--            persist: false,--}}
{{--            createOnBlur: true,--}}
{{--            create: true--}}
{{--        });--}}
{{--    </script>--}}

{{--@endsection--}}
