<div>
    <h3 class="text-lg font-medium text-gray-900">@lang('general.add_new_education')</h3>
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end mt-2 p-4 border rounded-md">
        <div class="md:col-span-2">
            <x-forms.input-label for="new_education_title">@lang('general.title')</x-forms.input-label>
            <x-forms.input type="text" wire:model="newEducationTitle" id="new_education_title" />
            <x-error-span>{{ $errors->first('newEducationTitle') }}</x-error-span>
        </div>
        <div class="md:col-span-2">
            <x-forms.input-label for="new_education_university">@lang('general.university')</x-forms.input-label>
            <x-forms.input type="text" wire:model="newEducationUniversity" id="new_education_university" />
            <x-error-span>{{ $errors->first('newEducationUniversity') }}</x-error-span>
        </div>
        <div>
            <x-forms.input-label for="new_education_type">@lang('general.type')</x-forms.input-label>
            <x-forms.select-input wire:model="newEducationType" id="new_education_type">
                <option value="">-- @lang('general.select') --</option>
                <option value="0">@lang('general.unknown')</option>
                <option value="1">@lang('general.primary_school')</option>
                <option value="2">@lang('general.high_school')</option>
                <option value="3">@lang('general.institute')</option>
                <option value="4">@lang('general.university')</option>
                <option value="5">@lang('general.master')</option>
                <option value="6">@lang('general.phd')</option>
            </x-forms.select-input>
            <x-error-span>{{ $errors->first('newEducationType') }}</x-error-span>
        </div>
        <div>
            <x-forms.input-label for="new_education_year">@lang('general.graduation_year')</x-forms.input-label>
            <x-forms.input type="number" placeholder="YYYY" wire:model="newEducationYear" id="new_education_year" />
            <x-error-span>{{ $errors->first('newEducationYear') }}</x-error-span>
        </div>
        <div class="flex justify-end">
            <x-primary-button type="button" wire:click="addEducation">@lang('general.add')</x-primary-button>
        </div>
    </div>
</div>
<div class="space-y-3 mt-6">
    <h3 class="text-lg font-medium text-gray-900">@lang('general.existing_records')</h3>
    @forelse ($educations as $edu)
        <div wire:key="education-{{ $edu->id }}"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
            <div>
                <p class="font-semibold text-gray-800">{{ $edu->title }}</p>
                <p class="text-sm text-gray-600">{{ $edu->university }} - {{ $edu->graduation_year }}</p>
            </div>
            <button type="button" wire:click="deleteEducation({{ $edu->id }})" wire:confirm="@lang('general.confirm-delete-item')"
                class="text-red-500 hover:text-red-700">
                @lang('general.delete')
            </button>
        </div>
    @empty
        <p class="text-center text-gray-500 py-4">@lang('general.no_education_records')</p>
    @endforelse
</div>
