<div x-data="{
    nextIndex: {{ count($experienceForm->experiences) }},
    add() {
        const template = this.$refs.experienceTemplate.innerHTML.replaceAll('__INDEX__', this.nextIndex);
        this.$refs.experienceContainer.insertAdjacentHTML('beforeend', template);
        this.nextIndex++;
    },
    remove(index) {
        if (confirm('Are you sure you want to remove this item?')) {
            document.getElementById('experience-row-' + index)?.remove();
            $wire.removeExperience(index);
        }
    }
}">
    <form wire:submit.prevent="saveExperience" class="space-y-6">
        <div x-ref="experienceContainer">
            @foreach ($experienceForm->experiences as $index => $experience)
                <div wire:key="experience-{{ $index }}" id="experience-row-{{ $index }}" class="relative border-b pb-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-forms.input-label for="exp_job_title_{{ $index }}">@lang('general.job_title')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="experienceForm.experiences.{{ $index }}.job_title" id="exp_job_title_{{ $index }}" />
                            <x-error-span>{{ $errors->first('experienceForm.experiences.' . $index . '.job_title') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="exp_company_{{ $index }}">@lang('general.company')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="experienceForm.experiences.{{ $index }}.company" id="exp_company_{{ $index }}" />
                            <x-error-span>{{ $errors->first('experienceForm.experiences.' . $index . '.company') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="exp_start_date_{{ $index }}">@lang('general.start_date')</x-forms.input-label>
                            <x-forms.input type="date" wire:model="experienceForm.experiences.{{ $index }}.start_date" id="exp_start_date_{{ $index }}" />
                            <x-error-span>{{ $errors->first('experienceForm.experiences.' . $index . '.start_date') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="exp_end_date_{{ $index }}">@lang('general.end_date')</x-forms.input-label>
                            <x-forms.input type="date" wire:model="experienceForm.experiences.{{ $index }}.end_date" id="exp_end_date_{{ $index }}" />
                            <x-error-span>{{ $errors->first('experienceForm.experiences.' . $index . '.end_date') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label>@lang('general.place')</x-forms.input-label>
                            <div class="flex space-x-4 mt-2">
                                <x-forms.radio-input name="experiences_{{ $index }}_place" value="onsite" wire:model="experienceForm.experiences.{{ $index }}.place">@lang('general.onsite')</x-forms.radio-input>
                                <x-forms.radio-input name="experiences_{{ $index }}_place" value="remote" wire:model="experienceForm.experiences.{{ $index }}.place">@lang('general.remote')</x-forms.radio-input>
                            </div>
                        </div>
                        <div>
                            <x-forms.input-label for="exp_type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                            <x-forms.select-input wire:model="experienceForm.experiences.{{ $index }}.type" id="exp_type_{{ $index }}">
                                <option value="">-- @lang('general.select') --</option>
                                <option value="full_time">@lang('general.full_time')</option>
                                <option value="part_time">@lang('general.part_time')</option>
                                <option value="contract">@lang('general.contract')</option>
                                <option value="internship">@lang('general.internship')</option>
                                <option value="freelance">@lang('general.freelance')</option>
                            </x-forms.select-input>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <template x-ref="experienceTemplate">
            <div class="relative border-b pb-6 mb-6" id="experience-row-__INDEX__">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-forms.input-label for="exp_job_title___INDEX__">@lang('general.job_title')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="experienceForm.experiences.__INDEX__.job_title" id="exp_job_title___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label for="exp_company___INDEX__">@lang('general.company')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="experienceForm.experiences.__INDEX__.company" id="exp_company___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label for="exp_start_date___INDEX__">@lang('general.start_date')</x-forms.input-label>
                        <x-forms.input type="date" wire:model="experienceForm.experiences.__INDEX__.start_date" id="exp_start_date___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label for="exp_end_date___INDEX__">@lang('general.end_date')</x-forms.input-label>
                        <x-forms.input type="date" wire:model="experienceForm.experiences.__INDEX__.end_date" id="exp_end_date___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label>@lang('general.place')</x-forms.input-label>
                        <div class="flex space-x-4 mt-2">
                            <x-forms.radio-input name="experiences___INDEX___place" value="onsite" wire:model="experienceForm.experiences.__INDEX__.place">@lang('general.onsite')</x-forms.radio-input>
                            <x-forms.radio-input name="experiences___INDEX___place" value="remote" wire:model="experienceForm.experiences.__INDEX__.place">@lang('general.remote')</x-forms.radio-input>
                        </div>
                    </div>
                    <div>
                        <x-forms.input-label for="exp_type___INDEX__">@lang('general.type')</x-forms.input-label>
                        <x-forms.select-input wire:model="experienceForm.experiences.__INDEX__.type" id="exp_type___INDEX__">
                            <option value="">-- @lang('general.select') --</option>
                            <option value="full_time">@lang('general.full_time')</option>
                            <option value="part_time">@lang('general.part_time')</option>
                            <option value="contract">@lang('general.contract')</option>
                            <option value="internship">@lang('general.internship')</option>
                            <option value="freelance">@lang('general.freelance')</option>
                        </x-forms.select-input>
                    </div>
                </div>
            </div>
        </template>

        <div><button type="button" @click="add" class="px-4 py-2 rounded bg-primary text-white hover:opacity-90">+ @lang('general.add_new')</button></div>
        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
        </div>
    </form>
</div>
