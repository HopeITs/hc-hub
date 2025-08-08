<div>
    <livewire:layout.breadcrumbs :title="__('general.add_new')" :crumbs="[
        ['label' => trans_choice('general.employee_management', 2)],
        ['label' => trans_choice('general.employee', 2), 'url' => route('employees.index')],
        ['label' => __('general.add_new')],
    ]"/>

    <form wire:submit.prevent="save" class="mt-4">
        <x-tab.form activeTab="information">
            <x-tab.list>
                <x-tab.item :title="__('general.information')" activeTab="information">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                              clip-rule="evenodd"></path>
                    </svg>
                </x-tab.item>
                <x-tab.item :title="__('general.education')" activeTab="education">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                              clip-rule="evenodd"></path>
                    </svg>
                </x-tab.item>
            </x-tab.list>

            <div class="flex-grow w-full">
                <x-tab.container active-tab="information">
                    @include('livewire.employee.partials.create-info-form')
                </x-tab.container>
                <x-tab.container active-tab="education">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-forms.input-label for="education_title">@lang('general.title')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="form.education_title" id="education_title"/>
                            <x-error-span>{{ $errors->first('form.education_title') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label
                                for="education_university">@lang('general.university')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="form.education_university"
                                           id="education_university"/>
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
                            <x-forms.input-label
                                for="education_graduation_year">@lang('general.graduation_year')</x-forms.input-label>
                            <x-forms.input type="number" placeholder="YYYY" wire:model="form.education_graduation_year"
                                           id="education_graduation_year"/>
                            <x-error-span>{{ $errors->first('form.education_graduation_year') }}</x-error-span>
                        </div>
                    </div>
                </x-tab.container>

            </div>
        </div>

        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
        </div>
    </form>
</div>
