<div class="space-y-6">
    @foreach ($form->courses as $index => $course)
        <div wire:key="course-{{ $index }}" class="relative">
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
                    <x-error-span>{{ $errors->first('form.courses.' . $index . '.is_approved') }}</x-error-span>
                </div>
            </div>

            <button type="button" wire:click="removeCourse({{ $index }})" class="absolute top-0 -right-2 text-gray-400 hover:text-red-600 transition-colors" title="@lang('general.remove_course')">
                &times;
            </button>
        </div>
    @endforeach

    <div>
        <button
            type="button"
            wire:click="addCourse"
            class="px-4 py-2 rounded bg-primary text-white hover:opacity-90">
            + @lang('general.add_new')
        </button>
    </div>
</div>
