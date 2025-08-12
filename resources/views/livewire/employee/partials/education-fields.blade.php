<div class="space-y-6">
    @foreach ($form->educations as $index => $education)
        <div wire:key="education-{{ $index }}" class="relative">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-forms.input-label for="education_title_{{ $index }}">@lang('general.title')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.educations.{{ $index }}.title" id="education_title_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.educations.' . $index . '.title') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="education_university_{{ $index }}">@lang('general.university')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.educations.{{ $index }}.university" id="education_university_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.educations.' . $index . '.university') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="education_type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                    <x-forms.select-input wire:model="form.educations.{{ $index }}.type" id="education_type_{{ $index }}">
                        <option value="">-- @lang('general.select') --</option>
                        <option value="0">@lang('general.unknown')</option>
                        <option value="1">@lang('general.primary_school')</option>
                        <option value="2">@lang('general.high_school')</option>
                        <option value="3">@lang('general.institute')</option>
                        <option value="4">@lang('general.university')</option>
                        <option value="5">@lang('general.master')</option>
                        <option value="6">@lang('general.phd')</option>
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('form.educations.' . $index . '.type') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="education_graduation_year_{{ $index }}">@lang('general.graduation_year')</x-forms.input-label>
                    <x-forms.input type="number" placeholder="YYYY" wire:model="form.educations.{{ $index }}.graduation_year" id="education_graduation_year_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.educations.' . $index . '.graduation_year') }}</x-error-span>
                </div>
            </div>

            <button
                type="button"
                wire:click="removeEducation({{ $index }})"
                class="absolute top-0 -right-2 text-gray-400 hover:text-red-600 transition-colors"
                title="@lang('general.remove_education')">
                &times;
            </button>
        </div>
    @endforeach

    <div>
        <button
            type="button"
            wire:click="addEducation"
            class="px-4 py-2 rounded bg-primary text-white hover:opacity-90">
            + @lang('general.add_new')
        </button>
    </div>
</div>
