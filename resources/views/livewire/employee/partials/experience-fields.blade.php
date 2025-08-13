<form wire:submit.prevent="saveExperience" class="space-y-6">
    @foreach ($form->experiences as $index => $experience)
        <div wire:key="experience-{{ $index }}" class="relative border-b pb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-forms.input-label for="exp_job_title_{{ $index }}">@lang('general.job_title')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.experiences.{{ $index }}.job_title"
                                   id="exp_job_title_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.job_title') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="exp_company_{{ $index }}">@lang('general.company')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.experiences.{{ $index }}.company"
                                   id="exp_company_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.company') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="exp_start_date_{{ $index }}">@lang('general.start_date')</x-forms.input-label>
                    <x-forms.input type="date" wire:model="form.experiences.{{ $index }}.start_date"
                                   id="exp_start_date_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.start_date') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="exp_end_date_{{ $index }}">@lang('general.end_date')</x-forms.input-label>
                    <x-forms.input type="date" wire:model="form.experiences.{{ $index }}.end_date"
                                   id="exp_end_date_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.end_date') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label>@lang('general.place')</x-forms.input-label>
                    <div class="flex space-x-4 mt-2">
                        <x-forms.radio-input name="experiences_{{ $index }}_place" value="onsite"
                                             wire:model="form.experiences.{{ $index }}.place">
                            @lang('general.onsite')
                        </x-forms.radio-input>
                        <x-forms.radio-input name="experiences_{{ $index }}_place" value="remote"
                                             wire:model="form.experiences.{{ $index }}.place">
                            @lang('general.remote')
                        </x-forms.radio-input>
                    </div>
                    <x-error-span>{{ $errors->first('form.experiences.' . $index . '.place') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="exp_type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                    <x-forms.select-input wire:model="form.experiences.{{ $index }}.type"
                                          id="exp_type_{{ $index }}">
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
        </div>
    @endforeach

    <div>
        <button type="button" wire:click="form.addExperience"
                class="px-4 py-2 rounded bg-primary text-white hover:opacity-90">
            + @lang('general.add_new')
        </button>
    </div>

    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
