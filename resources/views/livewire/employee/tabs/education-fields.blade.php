@php
use App\Enums\EducationType;
@endphp
<form wire:submit.prevent="saveEducation" class="space-y-6">
    @foreach ($educationForm->educations as $index => $education)

        <input type="hidden" wire:model="educationForm.educations.{{ $index }}.id">
        <div wire:key="education-{{ $index }}" class="relative border-b pb-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2 flex justify-end">
                    <x-primary-button type="button"
                                      :showSpinner="false"
                                      wire:click="removeEducation({{ $index }} , {{$education['id']}})"
                    >
                        <x-icons.trash/>
                    </x-primary-button>
                </div>

                <div>
                    <x-forms.input-label for="education_title_{{ $index }}">@lang('general.title')</x-forms.input-label>
                    <x-forms.input
                        id="education_title_{{ $index }}"
                        type="text"
                        wire:model.defer="educationForm.educations.{{ $index }}.title"/>
                    <x-error-span>{{ $errors->first("educationForm.educations.$index.title") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="education_university_{{ $index }}">@lang('general.university')</x-forms.input-label>
                    <x-forms.input
                        id="education_university_{{ $index }}"
                        type="text"
                        wire:model.defer="educationForm.educations.{{ $index }}.university"/>
                    <x-error-span>{{ $errors->first("educationForm.educations.$index.university") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="education_type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                    <x-forms.select-input
                        id="education_type_{{ $index }}"
                        wire:model.defer="educationForm.educations.{{ $index }}.type">
                        <option value="">-- @lang('general.select') --</option>
                        @foreach(EducationType::getValues() as $key => $value)
                            <option value="{{$value}}">{{ EducationType::getLocalizedName($value)}}</option>
                        @endforeach

                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first("educationForm.educations.$index.type") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="education_graduation_year_{{ $index }}">@lang('general.graduation_year')</x-forms.input-label>
                    <x-forms.input
                        id="education_graduation_year_{{ $index }}"
                        type="year"
                        placeholder="YYYY"
                        wire:model.defer="educationForm.educations.{{ $index }}.graduation_year"/>
                    <x-error-span>{{ $errors->first("educationForm.educations.$index.graduation_year") }}</x-error-span>
                </div>



                <div>
                    <x-forms.input-label for="edu_attachment_{{ $index }}">@lang('general.attachment')</x-forms.input-label>
                    <x-forms.file-input wire:model="educationForm.educations.{{ $index }}.edu_attachment"
                                        name="educationForm.educations.{{ $index }}.edu_attachment"
                                        preview-type="pdf"
                                        :old-file="$education['edu_attachment']"
                                        :file-name="$education['file_name']"
                                        accept=".pdf"/>

                </div>
            </div>


        </div>
    @endforeach
<div class="flex justify-center">
    <x-primary-button type="button"  :showSpinner="false"
                      wire:click="addEducation"
    >
        + @lang('general.add-new')
    </x-primary-button>
</div>


    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
