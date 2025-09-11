<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.positions')"
            :crumbs="[
        ['label' => __('general.programs-management')],
        ['label' => __('general.positions') ],
                ['label' => $title],
            ]"

        />

    </div>

    <form wire:submit.prevent="save" class="space-y-4">
        <x-forms.errors-validation-list/>
        <div class="grid md:grid-cols-2 gap-4">

            @foreach (config('app.supported_locales') as $locale)
                <div wire:key="title-{{ $locale }}">
                    <x-forms.input-label for="title_{{ $locale }}">@lang('general.title') ({{ strtoupper($locale) }}
                        )
                    </x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.title.{{ $locale }}" id="title_{{ $locale }}"/>
                    <x-error-span>{{ $errors->first('form.title.' . $locale) }}</x-error-span>
                </div>
            @endforeach

            <div class="md:col-span-1">
                <x-forms.input-label for="city">{{trans_choice('general.city',1)}}</x-forms.input-label>
                <x-forms.select-input wire:model="form.city_id" id="city">
                    @foreach($cities as $city)
                        <option value="{{$city->id}}">{{$city->name}}</option>
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.city_id') }}</x-error-span>
            </div>
            <div class="md:col-span-1">
                <x-forms.input-label for="program">{{trans_choice('general.program',1)}}</x-forms.input-label>
                <x-forms.select-input wire:model="form.program_id" id="program">
                    @foreach($programs as $program)
                        <option value="{{$program->id}}">{{$program->name}}</option>
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.program_id') }}</x-error-span>
            </div>
            <div class="md:col-span-1">
                <x-forms.input-label for="department">{{trans_choice('general.department',1)}}</x-forms.input-label>
                <x-forms.select-input wire:model="form.department_id" id="department">
                    @foreach($departments as $department)
                        <option value="{{$department->id}}">{{$department->name}}</option>
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.department_id') }}</x-error-span>
            </div>
            <div class="md:col-span-1">
                <x-forms.input-label for="job-level">{{trans_choice('general.job-level',1)}}</x-forms.input-label>
                <x-forms.select-input wire:model="form.job_level_id" id="job-level">
                    @foreach($jobLevels as $level)
                        <option value="{{$level->id}}">{{$level->name}}</option>
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.job_level_id') }}</x-error-span>
            </div>
            <div class="md:col-span-1">
                <x-forms.input-label for="center">{{trans_choice('general.center',1)}}</x-forms.input-label>
                <x-forms.select-input wire:model="form.center_id" id="center">
                    @foreach($centers as $center)
                        <option value="{{$center->id}}">{{$center->name .' - ' . $center->city->name}}</option>
                    @endforeach
                </x-forms.select-input>
                <x-error-span>{{ $errors->first('form.center_id') }}</x-error-span>
            </div>
            <div>
                <x-forms.input-label for="max_emp">@lang('general.max')</x-forms.input-label>
                <x-forms.input id="max_emp" type="number" min="0" wire:model.defer="form.max_emp"/>
                <x-error-span>{{ $errors->first('form.max_emp') }}</x-error-span>
            </div>

            <div class="col-span-2">
                <x-forms.input-label for="responsibilities">@lang('general.responsibilities')</x-forms.input-label>
                <x-forms.textarea id="responsibilities" rows="3" wire:model.defer="form.responsibilities"/>
                <x-error-span>{{ $errors->first('form.responsibilities') }}</x-error-span>
            </div>
            <div class="col-span-2">
                <x-forms.input-label for="qualifications">@lang('general.qualifications')</x-forms.input-label>
                <x-forms.textarea id="qualifications" rows="3" wire:model.defer="form.qualifications"/>
                <x-error-span>{{ $errors->first('form.qualifications') }}</x-error-span>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
            <x-modal.cancel-btn/>
        </div>
    </form>

</div>
