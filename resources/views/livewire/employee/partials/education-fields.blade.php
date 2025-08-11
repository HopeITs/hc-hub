<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <x-forms.input-label for="education_title">@lang('general.title')</x-forms.input-label>
        <x-forms.input type="text" wire:model="form.education_title" id="education_title" />
        <x-error-span>{{ $errors->first('form.education_title') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="education_university">@lang('general.university')</x-forms.input-label>
        <x-forms.input type="text" wire:model="form.education_university" id="education_university" />
        <x-error-span>{{ $errors->first('form.education_university') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="education_type">@lang('general.type')</x-forms.input-label>
        <x-forms.select-input wire:model="form.education_type" id="education_type">
            <option value="">-- @lang('general.select') --</option>
            <option value="0">@lang('general.unknown')</option>
            <option value="1">@lang('general.primary_school')</option>
            <option value="2">@lang('general.high_school')</option>
            <option value="3">@lang('general.institute')</option>
            <option value="4">@lang('general.university')</option>
            <option value="5">@lang('general.master')</option>
            <option value="6">@lang('general.phd')</option>
        </x-forms.select-input>
        <x-error-span>{{ $errors->first('form.education_type') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="education_graduation_year">@lang('general.graduation_year')</x-forms.input-label>
        <x-forms.input type="number" placeholder="YYYY" wire:model="form.education_graduation_year" id="education_graduation_year" />
        <x-error-span>{{ $errors->first('form.education_graduation_year') }}</x-error-span>
    </div>
</div>
