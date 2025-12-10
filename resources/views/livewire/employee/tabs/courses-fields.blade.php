<form wire:submit.prevent="saveCourses" class="space-y-6">

    @php
        $courseCategories = \App\Models\CourseCategory::pluck('name_en', 'id')->toArray();
        $organizationOptions = [
            'Hope Center' => 'Hope Center',
            'Personal' => 'Personal',
            'Other' => 'Other',
        ];
        $placeOptions = [
            1 => __('general.online'),
            2 => __('general.onsite'),
        ];
        $statusOptions = [
            1 => __('general.pending'),
            2 => __('general.approved'),
            3 => __('general.rejected'),
            4 => __('general.in_progress'),
            5 => __('general.finished'),
        ];
    @endphp

    @foreach ($courseForm->courses as $index => $course)
        <div wire:key="course-{{ $index }}" class="relative border-b pb-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">

                <div class="md:col-span-2 flex justify-end">
                    <x-primary-button type="button" variant="danger"
                        wire:click="removeCourse({{ $index }} , {{ $course['id'] ?? 'null' }})"
                        wire:loading.attr="disabled" wire:target="removeCourse" class="!px-3 !py-2"
                        title="@lang('general.remove')" aria-label="@lang('general.remove')" :showSpinner="false">
                        <x-icons.trash class="w-4 h-4" />
                    </x-primary-button>
                </div>

                <div>
                    <x-forms.input-label for="course_category_{{ $index }}">
                        @lang('general.category')
                    </x-forms.input-label>

                    <x-forms.select id="course_category_{{ $index }}"
                        wire:model.defer="courseForm.courses.{{ $index }}.category_id" :options="$courseCategories"
                        placeholder="Select Category" :invalid="$errors->has('courseForm.courses.' . $index . '.category_id')" />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.category_id") }}
                    </x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="course_title_{{ $index }}">
                        @lang('general.title')
                    </x-forms.input-label>

                    <x-forms.input id="course_title_{{ $index }}" type="text"
                        wire:model.defer="courseForm.courses.{{ $index }}.title" :invalid="$errors->has('courseForm.courses.' . $index . '.title')" />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.title") }}
                    </x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="course_org_{{ $index }}">
                        @lang('general.organization')
                    </x-forms.input-label>

                    <x-forms.select id="course_org_{{ $index }}"
                        wire:model.defer="courseForm.courses.{{ $index }}.organization" :options="$organizationOptions"
                        placeholder="Select Organization" :invalid="$errors->has('courseForm.courses.' . $index . '.organization')" />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.organization") }}
                    </x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="course_place_{{ $index }}">
                        @lang('general.place')
                    </x-forms.input-label>

                    <x-forms.select id="course_place_{{ $index }}"
                        wire:model.defer="courseForm.courses.{{ $index }}.place" :options="$placeOptions"
                        placeholder="Select Place" :invalid="$errors->has('courseForm.courses.' . $index . '.place')" />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.place") }}
                    </x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="course_granted_by_{{ $index }}">
                        @lang('general.granted_by')
                    </x-forms.input-label>

                    <x-forms.input id="course_granted_by_{{ $index }}" type="text"
                        wire:model.defer="courseForm.courses.{{ $index }}.granted_by" :invalid="$errors->has('courseForm.courses.' . $index . '.granted_by')" />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.granted_by") }}
                    </x-error-span>
                </div>
                {{--
                <div>
                    <x-forms.input-label for="course_cost_{{ $index }}">
                        @lang('general.cost')
                    </x-forms.input-label>

                    <x-forms.input
                        id="course_cost_{{ $index }}"
                        type="number"
                        step="0.01"
                        wire:model.defer="courseForm.courses.{{ $index }}.cost"
                        :invalid="$errors->has('courseForm.courses.'.$index.'.cost')"
                    />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.cost") }}
                    </x-error-span>
                </div> --}}

                {{--
                <div>
                    <x-forms.input-label for="course_start_date_{{ $index }}">
                        @lang('general.start_date')
                    </x-forms.input-label>

                    <x-forms.input
                        id="course_start_date_{{ $index }}"
                        type="date"
                        wire:model.defer="courseForm.courses.{{ $index }}.start_date"
                        :invalid="$errors->has('courseForm.courses.'.$index.'.start_date')"
                    />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.start_date") }}
                    </x-error-span>
                </div> --}}

                {{-- End Date --}}
                <div>
                    <x-forms.input-label for="course_end_date_{{ $index }}">
                        @lang('general.end_date')
                    </x-forms.input-label>

                    <x-forms.input id="course_end_date_{{ $index }}" type="date"
                        wire:model.defer="courseForm.courses.{{ $index }}.end_date" :invalid="$errors->has('courseForm.courses.' . $index . '.end_date')" />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.end_date") }}
                    </x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="course_duration_{{ $index }}">
                        @lang('general.duration') (@lang('general.e.g_20_hours_2_weeks'))
                    </x-forms.input-label>

                    <x-forms.input id="course_duration_{{ $index }}" type="text"
                        wire:model.defer="courseForm.courses.{{ $index }}.duration" :invalid="$errors->has('courseForm.courses.' . $index . '.duration')" />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.duration") }}
                    </x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="course_status_{{ $index }}">
                        @lang('general.status')
                    </x-forms.input-label>

                    <x-forms.select id="course_status_{{ $index }}"
                        wire:model.defer="courseForm.courses.{{ $index }}.status" :options="$statusOptions"
                        placeholder="Select Status" :invalid="$errors->has('courseForm.courses.' . $index . '.status')" />

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.status") }}
                    </x-error-span>
                </div>

                <div class="md:col-span-2">
                    <x-forms.input-label for="course_certificate_{{ $index }}">
                        @lang('general.has_certificate')
                    </x-forms.input-label>

                    <div class="flex space-x-6 mt-2">
                        <label class="inline-flex items-center text-sm font-medium text-gray-700">
                            <input type="radio" name="course_certificate_{{ $index }}" value="1"
                                wire:model.defer="courseForm.courses.{{ $index }}.has_certificate"
                                class="text-primary-600 focus:ring-primary-500 border-gray-300" />
                            <span class="ml-2">@lang('general.yes')</span>
                        </label>

                        <label class="inline-flex items-center text-sm font-medium text-gray-700">
                            <input type="radio" name="course_certificate_{{ $index }}" value="0"
                                wire:model.defer="courseForm.courses.{{ $index }}.has_certificate"
                                class="text-primary-600 focus:ring-primary-500 border-gray-300" />
                            <span class="ml-2">@lang('general.no')</span>
                        </label>
                    </div>

                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.has_certificate") }}
                    </x-error-span>

                </div>

                <div class="md:col-span-2">
                    <x-forms.input-label for="course_notes_{{ $index }}">
                        @lang('general.notes')
                    </x-forms.input-label>
                    <x-forms.textarea id="course_notes_{{ $index }}"
                        wire:model.defer="courseForm.courses.{{ $index }}.notes" rows="3" />
                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.notes") }}
                    </x-error-span>
                </div>

                <div class="md:col-span-2">
                    <x-forms.input-label
                        for="course_attachment_{{ $index }}">@lang('general.certificate_attachment')</x-forms.input-label>
                    <x-forms.file-input wire:model="courseForm.courses.{{ $index }}.course_attachment"
                        name="courseForm.courses.{{ $index }}.course_attachment" preview-type="pdf"
                        :old-file="$course['course_attachment'] ?? null" :file-name="$course['file_name'] ?? null" accept=".pdf, .jpg, .jpeg, .png" />
                    <x-error-span>
                        {{ $errors->first("courseForm.courses.$index.course_attachment") }}
                    </x-error-span>
                </div>

            </div>
        </div>
    @endforeach

    <div class="flex justify-center">
        <x-primary-button type="button" wire:click="addCourse" wire:loading.attr="disabled" wire:target="addCourse"
            :showSpinner="false">
            + @lang('general.add-new')
        </x-primary-button>
    </div>

    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit" class="!w-auto" wire:loading.attr="disabled" wire:target="saveCourses">
            @lang('general.save')
        </x-primary-button>

        <x-secondary-button :href="route('employees.index')" class="!w-auto">
            @lang('general.cancel')
        </x-secondary-button>
    </div>


</form>
