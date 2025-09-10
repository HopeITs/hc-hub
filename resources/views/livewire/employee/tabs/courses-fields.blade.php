<form wire:submit.prevent="saveCourses" class="space-y-6">

    @foreach ($courseForm->courses as $index => $course)
        <div
            wire:key="course-{{ $index }}"
            class="relative border-b pb-6 mb-6"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">

                {{-- Row actions --}}
                <div class="md:col-span-2 flex justify-end">
                    <x-primary-button
                        type="button"
                        variant="danger"
                        wire:click="removeCourse({{ $index }})"
                        wire:loading.attr="disabled"
                        wire:target="removeCourse"
                        class="!px-3 !py-2"
                        title="@lang('general.remove')"
                        aria-label="@lang('general.remove')"
                    >
                        <x-icons.trash class="w-4 h-4"/>
                    </x-primary-button>
                </div>

                {{-- Title --}}
                <div>
                    <x-forms.input-label for="course_title_{{ $index }}">
                        @lang('general.title')
                    </x-forms.input-label>

                    <x-forms.input
                        id="course_title_{{ $index }}"
                        type="text"
                        wire:model.defer="courseForm.courses.{{ $index }}.title"
                        :invalid="$errors->has('courseForm.courses.'.$index.'.title')"
                    />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.title") }}
                    </x-error-span>
                </div>

                {{-- Issuing Organization --}}
                <div>
                    <x-forms.input-label for="course_org_{{ $index }}">
                        @lang('general.issuing_organization')
                    </x-forms.input-label>

                    <x-forms.input
                        id="course_org_{{ $index }}"
                        type="text"
                        wire:model.defer="courseForm.courses.{{ $index }}.issuing_organization"
                        :invalid="$errors->has('courseForm.courses.'.$index.'.issuing_organization')"
                    />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.issuing_organization") }}
                    </x-error-span>
                </div>

                {{-- Date --}}
                <div>
                    <x-forms.input-label for="course_date_{{ $index }}">
                        @lang('general.date')
                    </x-forms.input-label>

                    <x-forms.input
                        id="course_date_{{ $index }}"
                        type="date"
                        wire:model.defer="courseForm.courses.{{ $index }}.date"
                        :invalid="$errors->has('courseForm.courses.'.$index.'.date')"
                    />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.date") }}
                    </x-error-span>
                </div>

                {{-- Status --}}
                <div>
                    <x-forms.input-label>
                        @lang('general.status')
                    </x-forms.input-label>

                    <div class="flex space-x-4 items-center mt-2">
                        <x-forms.radio-input
                            name="courses_{{ $index }}_approved"
                            value="1"
                            wire:model.defer="courseForm.courses.{{ $index }}.is_approved"
                        >
                            @lang('general.approved')
                        </x-forms.radio-input>

                        <x-forms.radio-input
                            name="courses_{{ $index }}_approved"
                            value="0"
                            wire:model.defer="courseForm.courses.{{ $index }}.is_approved"
                        >
                            @lang('general.not_approved')
                        </x-forms.radio-input>
                    </div>

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.is_approved") }}
                    </x-error-span>
                </div>

            </div>
        </div>


    @endforeach

    {{-- Add new --}}
    <div class="flex justify-center">
        <x-primary-button
            type="button"
            wire:click="addCourse"
            wire:loading.attr="disabled"
            wire:target="addCourse"
        >
            + @lang('general.add-new')
        </x-primary-button>
    </div>

    {{-- Footer actions --}}
    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button
            type="submit"
            class="!w-auto"
            wire:loading.attr="disabled"
            wire:target="saveCourses"
        >
            @lang('general.save')
        </x-primary-button>

        <x-secondary-button :href="route('employees.index')" class="!w-auto">
            @lang('general.cancel')
        </x-secondary-button>
    </div>


</form>
