<form wire:submit.prevent="saveExperience" class="space-y-6">
    @foreach ($experienceForm->experiences as $index => $experience)
        <input type="hidden" wire:model="experienceForm.experiences.{{ $index }}.id">

        <div wire:key="experience-{{ $index }}" class="relative border-b pb-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2 flex justify-end">
                    <x-primary-button type="button"
                        wire:click="removeExperience({{ $index }}, {{ $experience['id'] ?? 'null' }})"
                        title="@lang('general.remove')">
                        <x-icons.trash />
                    </x-primary-button>
                </div>

                <div>
                    <x-forms.input-label for="job_title_{{ $index }}">@lang('general.job_title')</x-forms.input-label>
                    <x-forms.input id="job_title_{{ $index }}" type="text"
                        wire:model.defer="experienceForm.experiences.{{ $index }}.job_title" />
                    <x-error-span>{{ $errors->first("experienceForm.experiences.$index.job_title") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="company_{{ $index }}">@lang('general.company')</x-forms.input-label>
                    <x-forms.input id="company_{{ $index }}" type="text"
                        wire:model.defer="experienceForm.experiences.{{ $index }}.company" />
                    <x-error-span>{{ $errors->first("experienceForm.experiences.$index.company") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="start_date_{{ $index }}">@lang('general.start_date')</x-forms.input-label>
                    <x-forms.date-input id="start_date_{{ $index }}"
                        wire:model.defer="experienceForm.experiences.{{ $index }}.start_date" />
                    <x-error-span>{{ $errors->first("experienceForm.experiences.$index.start_date") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="end_date_{{ $index }}">@lang('general.end_date')</x-forms.input-label>
                    <x-forms.date-input id="end_date_{{ $index }}"
                        wire:model.defer="experienceForm.experiences.{{ $index }}.end_date" />
                    <x-error-span>{{ $errors->first("experienceForm.experiences.$index.end_date") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="place_{{ $index }}">@lang('general.place')</x-forms.input-label>
                    <x-forms.input id="place_{{ $index }}" type="text"
                        wire:model.defer="experienceForm.experiences.{{ $index }}.place" />
                    <x-error-span>{{ $errors->first("experienceForm.experiences.$index.place") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                    <x-forms.input id="type_{{ $index }}" type="text"
                        placeholder="e.g., Full-time, Part-time, Contract"
                        wire:model.defer="experienceForm.experiences.{{ $index }}.type" />
                    <x-error-span>{{ $errors->first("experienceForm.experiences.$index.type") }}</x-error-span>
                </div>
            </div>
        </div>
    @endforeach

    <div class="flex justify-center">
        <x-primary-button type="button" wire:click="addExperience">
            + @lang('general.add-new')
        </x-primary-button>
    </div>

    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
