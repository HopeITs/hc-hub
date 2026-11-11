{{--
    The position create / edit modal (ManagesPositionForm). Included by
    positions/⚡index.blade.php.
--}}
@use('Modules\Core\Models\City')
@use('Modules\Core\Models\Program')
@use('Modules\Hr\Models\Department')

<x-ui.modal wire:model="showPositionModal" size="lg" :title="$editingPositionId === null ? __('hr_positions.actions.add') : __('hr_positions.actions.edit')" submit="savePosition" close="closePosition" required-note>
    @if ($showPositionModal)
        <div class="grid gap-x-4 gap-y-3 sm:grid-cols-2">
            <x-ui.form-input wire:model="form.title_en" :label="__('hr_positions.fields.title_en')" maxlength="255" dir="ltr" />
            <x-ui.form-input wire:model="form.title_ar" :label="__('hr_positions.fields.title_ar')" maxlength="255" dir="rtl" />
            <x-ui.form-select wire:model.live="form.city_id" :label="__('hr_positions.fields.city_id')" :placeholder="__('common.select')" required>
                @foreach ($this->cityOptions as $cityId => $cityName)
                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="form.center_id" :label="__('hr_positions.fields.center_id')" :placeholder="__('hr_positions.form.no_center')">
                <option value="">{{ __('hr_positions.form.no_center') }}</option>
                @foreach ($this->centerOptions() as $centerId => $centerName)
                    <option value="{{ $centerId }}">{{ $centerName }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="form.program_id" :label="__('hr_positions.fields.program_id')" :placeholder="__('common.select')" required>
                @foreach (Program::namesById() as $programId => $programName)
                    <option value="{{ $programId }}">{{ $programName }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="form.department_id" :label="__('hr_positions.fields.department_id')" :placeholder="__('common.select')" required>
                @foreach (Department::options() as $departmentOption)
                    <option value="{{ $departmentOption->id }}">{{ $departmentOption->name }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="form.job_level_id" :label="__('hr_positions.fields.job_level_id')" :placeholder="__('common.select')" required>
                @foreach ($this->jobLevelOptions() as $levelId => $levelName)
                    <option value="{{ $levelId }}">{{ $levelName }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-input wire:model="form.max_emp" :label="__('hr_positions.fields.max_emp')" type="number" min="1" max="500" step="1" required />
            <x-ui.form-select wire:model="form.direct_manager_id" :label="__('hr_positions.fields.direct_manager_id')" :placeholder="__('hr_positions.form.no_manager')">
                <option value="">{{ __('hr_positions.form.no_manager') }}</option>
                @foreach ($this->managerOptions as $managerId => $managerLabel)
                    <option value="{{ $managerId }}">{{ $managerLabel }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="form.tech_manager_id" :label="__('hr_positions.fields.tech_manager_id')" :placeholder="__('hr_positions.form.no_manager')">
                <option value="">{{ __('hr_positions.form.no_manager') }}</option>
                @foreach ($this->managerOptions as $managerId => $managerLabel)
                    <option value="{{ $managerId }}">{{ $managerLabel }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-textarea wire:model="form.qualifications" :label="__('hr_positions.fields.qualifications')" rows="4" required class="sm:col-span-2" />
            <x-ui.form-textarea wire:model="form.responsibilities" :label="__('hr_positions.fields.responsibilities')" rows="4" required class="sm:col-span-2" />
            <flux:checkbox wire:model="form.is_active" :label="__('hr_positions.fields.is_active')" />
        </div>
    @endif
</x-ui.modal>
