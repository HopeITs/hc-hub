<div class="space-y-6">
    @foreach ($form->experiences as $index => $experience)
        <div wire:key="experience-{{ $index }}" class="relative">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-forms.input-label for="exp_job_title_{{ $index }}">@lang('general.job_title')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.experiences.{{ $index }}.job_title" id="exp_job_title_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.job_title') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="exp_company_{{ $index }}">@lang('general.company')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.experiences.{{ $index }}.company" id="exp_company_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.company') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="exp_start_date_{{ $index }}">@lang('general.start_date')</x-forms.input-label>
                    <x-forms.input type="date" wire:model="form.experiences.{{ $index }}.start_date" id="exp_start_date_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.start_date') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="exp_end_date_{{ $index }}">@lang('general.end_date')</x-forms.input-label>
                    <x-forms.input type="date" wire:model="form.experiences.{{ $index }}.end_date" id="exp_end_date_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.end_date') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="exp_place_{{ $index }}">@lang('general.place')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.experiences.{{ $index }}.place" id="exp_place_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.place') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="exp_type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                    <x-forms.select-input wire:model="form.experiences.{{ $index }}.type" id="exp_type_{{ $index }}">
                        <option value="">-- @lang('general.select') --</option>
                        <option value="full_time">@lang('general.full_time')</option>
                        <option value="part_time">@lang('general.part_time')</option>
                        <option value="contract">@lang('general.contract')</option>
                        <option value="internship">@lang('general.internship')</option>
                        <option value="freelance">@lang('general.freelance')</option>
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.type') }}</x-error-span>
                </div>
            </div>
            <button type="button" wire:click="form.removeExperience({{ $index }})" class="absolute top-0 -right-2 text-gray-400 hover:text-red-600 transition-colors" title="@lang('general.remove_experience')">
            </button>
        </div>
    @endforeach
</div>
