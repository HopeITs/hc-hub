<div x-data="{
    nextIndex: {{ count($form->educations) }},

    add() {
        const template = this.$refs.educationTemplate.innerHTML.replaceAll('__INDEX__', this.nextIndex);
        this.$refs.educationContainer.insertAdjacentHTML('beforeend', template);
        this.nextIndex++;
    },

    remove(index) {
        if (confirm('Are you sure you want to remove this item?')) {
            // Find the element to remove
            const elementToRemove = document.getElementById('education-row-' + index);
            if (elementToRemove) {
                elementToRemove.remove();
            }
            // Notify Livewire to remove the data from the backend
            $wire.removeEducation(index);
        }
    }
}">
    <form wire:submit.prevent="saveEducation" class="space-y-6">

        <div x-ref="educationContainer">
            @foreach ($form->educations as $index => $education)
                <div wire:key="education-{{ $index }}" id="education-row-{{ $index }}"
                    class="relative border-b pb-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-forms.input-label
                                for="education_title_{{ $index }}">@lang('general.title')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="form.educations.{{ $index }}.title"
                                id="education_title_{{ $index }}" />
                            <x-error-span>{{ $errors->first('form.educations.' . $index . '.title') }}</x-error-span>
                        </div>

                        <div>
                            <x-forms.input-label
                                for="education_university_{{ $index }}">@lang('general.university')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="form.educations.{{ $index }}.university"
                                id="education_university_{{ $index }}" />
                            <x-error-span>{{ $errors->first('form.educations.' . $index . '.university') }}</x-error-span>
                        </div>

                        <div>
                            <x-forms.input-label
                                for="education_type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                            <x-forms.select-input wire:model="form.educations.{{ $index }}.type"
                                id="education_type_{{ $index }}">
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
                            <x-forms.input-label
                                for="education_graduation_year_{{ $index }}">@lang('general.graduation_year')</x-forms.input-label>
                            <x-forms.input type="number" placeholder="YYYY"
                                wire:model="form.educations.{{ $index }}.graduation_year"
                                id="education_graduation_year_{{ $index }}" />
                            <x-error-span>{{ $errors->first('form.educations.' . $index . '.graduation_year') }}</x-error-span>
                        </div>
                    </div>
                    <button type="button" @click="remove({{ $index }})"
                        class="absolute top-0 right-0 text-red-500 hover:text-red-700">

                    </button>
                </div>
            @endforeach
        </div>

        <template x-ref="educationTemplate">
            <div class="relative border-b pb-6 mb-6" id="education-row-__INDEX__">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-forms.input-label for="education_title___INDEX__">@lang('general.title')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="form.educations.__INDEX__.title"
                            id="education_title___INDEX__" />
                    </div>

                    <div>
                        <x-forms.input-label
                            for="education_university___INDEX__">@lang('general.university')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="form.educations.__INDEX__.university"
                            id="education_university___INDEX__" />
                    </div>

                    <div>
                        <x-forms.input-label for="education_type___INDEX__">@lang('general.type')</x-forms.input-label>
                        <x-forms.select-input wire:model="form.educations.__INDEX__.type" id="education_type___INDEX__">
                            <option value="">-- @lang('general.select') --</option>
                            <option value="0">@lang('general.unknown')</option>
                            <option value="1">@lang('general.primary_school')</option>
                            <option value="2">@lang('general.high_school')</option>
                            <option value="3">@lang('general.institute')</option>
                            <option value="4">@lang('general.university')</option>
                            <option value="5">@lang('general.master')</option>
                            <option value="6">@lang('general.phd')</option>
                        </x-forms.select-input>
                    </div>

                    <div>
                        <x-forms.input-label
                            for="education_graduation_year___INDEX__">@lang('general.graduation_year')</x-forms.input-label>
                        <x-forms.input type="number" placeholder="YYYY"
                            wire:model="form.educations.__INDEX__.graduation_year"
                            id="education_graduation_year___INDEX__" />
                    </div>
                </div>
            </div>
        </template>

        <div>
            <button type="button" @click="add" class="px-4 py-2 rounded bg-primary text-white hover:opacity-90">
                + @lang('general.add_new')
            </button>
        </div>

        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
        </div>
    </form>
</div>
