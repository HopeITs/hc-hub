<div>
    <livewire:layout.breadcrumbs :title="__('general.edit')" :crumbs="[
        ['label' => trans_choice('general.employee_management', 2)],
        ['label' => trans_choice('general.employee', 2), 'url' => route('employees.index')],
        ['label' => __('general.edit')],
    ]" />

    <form wire:submit.prevent="save" class="mt-4">
        <div x-data="{ activeTab: 'information' }" class="flex flex-col md:flex-row gap-8">
             <ul class="flex-none w-full md:w-48 text-sm font-medium text-gray-500 space-y-2">
                <li>
                    <button type="button" @click="activeTab = 'information'" :class="{ 'bg-blue-600 text-white': activeTab === 'information', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'information' }" class="w-full flex items-center p-3 rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                        <span class="font-semibold">@lang('general.information')</span>
                    </button>
                </li>
                <li>
                    <button type="button" @click="activeTab = 'education'" :class="{ 'bg-blue-600 text-white': activeTab === 'education', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'education' }" class="w-full flex items-center p-3 rounded-lg">
                         <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 16c1.255 0 2.443-.29 3.5-.804V4.804zM14.5 4c1.255 0 2.443.29 3.5.804v10A7.969 7.969 0 0114.5 16c-1.255 0-2.443-.29-3.5-.804V4.804A7.968 7.968 0 0114.5 4z"></path></svg>
                        <span class="font-semibold">@lang('general.education')</span>
                    </button>
                </li>
            </ul>

            <div class="flex-grow w-full">
                <div x-show="activeTab === 'information'" class="p-4 border rounded-md">
                    @include('livewire.employee.partials.edit-info-form')
                </div>
                {{-- ++ UPDATED: Education tab is now a simple, single form ++ --}}
                <div x-show="activeTab === 'education'" class="p-4 border rounded-md">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-forms.input-label for="education_title">@lang('general.title')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="form.education_title" id="education_title" />
                            <x-error-span>{{ $errors->first('form.education_title') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="education_university">@lang('general.university')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="form.education_university" id="education_university" />
                            <x-error-span>{{ $errors->first('form.education_university') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="education_type">@lang('general.type')</x-forms.input-label>
                            <x-forms.select-input wire:model="form.education_type" id="education_type">
                                <option value="">-- @lang('general.select') --</option>
                                <option value="0">@lang('general.unknown')</option>
                                <option value="1">@lang('general.primary_school')</option>
                                <option value="2">@lang('general.high_school')</option>
                                <option value="3">@lang('general.institute')</option>
                                <option value="4">@lang('general.university')</option>
                                <option value="5">@lang('general.master')</option>
                                <option value="6">@lang('general.phd')</option>
                            </x-forms.select-input>
                            <x-error-span>{{ $errors->first('form.education_type') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="education_graduation_year">@lang('general.graduation_year')</x-forms.input-label>
                            <x-forms.input type="number" placeholder="YYYY" wire:model="form.education_graduation_year" id="education_graduation_year" />
                            <x-error-span>{{ $errors->first('form.education_graduation_year') }}</x-error-span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
        </div>
    </form>
</div>
