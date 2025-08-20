<div x-data="{
    nextIndex: {{ count($form->courses) }},
    add() {
        const template = this.$refs.courseTemplate.innerHTML.replaceAll('__INDEX__', this.nextIndex);
        this.$refs.courseContainer.insertAdjacentHTML('beforeend', template);
        this.nextIndex++;
    },
    remove(index) {
        if (confirm('Are you sure you want to remove this item?')) {
            document.getElementById('course-row-' + index)?.remove();
            $wire.removeCourse(index);
        }
    }
}">
    <form wire:submit.prevent="saveCourses" class="space-y-6">
        <div x-ref="courseContainer">
            @foreach ($form->courses as $index => $course)
                <div wire:key="course-{{ $index }}" id="course-row-{{ $index }}" class="relative border-b pb-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-forms.input-label for="course_title_{{ $index }}">@lang('general.title')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="form.courses.{{ $index }}.title" id="course_title_{{ $index }}" />
                            <x-error-span>{{ $errors->first('form.courses.' . $index . '.title') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="course_org_{{ $index }}">@lang('general.issuing_organization')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="form.courses.{{ $index }}.issuing_organization" id="course_org_{{ $index }}" />
                            <x-error-span>{{ $errors->first('form.courses.' . $index . '.issuing_organization') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="course_date_{{ $index }}">@lang('general.date')</x-forms.input-label>
                            <x-forms.input type="date" wire:model="form.courses.{{ $index }}.date" id="course_date_{{ $index }}" />
                            <x-error-span>{{ $errors->first('form.courses.' . $index . '.date') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label>@lang('general.status')</x-forms.input-label>
                            <div class="flex space-x-4 items-center mt-2">
                                <x-forms.radio-input name="courses_{{ $index }}_approved" value="1" wire:model="form.courses.{{ $index }}.is_approved">@lang('general.approved')</x-forms.radio-input>
                                <x-forms.radio-input name="courses_{{ $index }}_approved" value="0" wire:model="form.courses.{{ $index }}.is_approved">@lang('general.not_approved')</x-forms.radio-input>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <template x-ref="courseTemplate">
             <div class="relative border-b pb-6 mb-6" id="course-row-__INDEX__">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-forms.input-label for="course_title___INDEX__">@lang('general.title')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="form.courses.__INDEX__.title" id="course_title___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label for="course_org___INDEX__">@lang('general.issuing_organization')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="form.courses.__INDEX__.issuing_organization" id="course_org___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label for="course_date___INDEX__">@lang('general.date')</x-forms.input-label>
                        <x-forms.input type="date" wire:model="form.courses.__INDEX__.date" id="course_date___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label>@lang('general.status')</x-forms.input-label>
                        <div class="flex space-x-4 items-center mt-2">
                            <x-forms.radio-input name="courses___INDEX___approved" value="1" wire:model="form.courses.__INDEX__.is_approved">@lang('general.approved')</x-forms.radio-input>
                            <x-forms.radio-input name="courses___INDEX___approved" value="0" wire:model="form.courses.__INDEX__.is_approved">@lang('general.not_approved')</x-forms.radio-input>
                        </div>
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
