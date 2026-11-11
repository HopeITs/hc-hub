{{--
    The leave request dialog (ManagesLeaveRequestForm). Included by
    ⚡index, ⚡team and ⚡show. HR filing for someone first picks the employee;
    the impact summary under the dates is priced live by the server.
--}}
@php
    $preview = $showRequestModal ? $this->requestPreview : null;
    $balances = $showRequestModal ? $this->requestBalances : [];
    $employeeOptions = $showRequestModal && $editingLeaveRequestId === null ? $this->requestEmployeeOptions : [];
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<x-ui.modal
    wire:model="showRequestModal"
    size="md"
    :title="$editingLeaveRequestId === null ? __('hr_leave.ui.new_request') : __('hr_leave.ui.edit_request')"
    :description="__('hr_leave.ui.request_dialog_description')"
    submit="saveRequest"
    close="closeRequestForm"
    required-note
>
    @if ($showRequestModal)
        <div class="grid gap-y-3" data-leave-request-form>
            @if ($employeeOptions !== [])
                <x-ui.form-select wire:model.live="requestEmployeeId" :label="__('hr_leave.fields.employee')" :placeholder="__('hr_leave.ui.myself')" searchable>
                    <option value="">{{ __('hr_leave.ui.myself') }}</option>
                    @foreach ($employeeOptions as $optionId => $optionName)
                        <option value="{{ $optionId }}">{{ $optionName }}</option>
                    @endforeach
                </x-ui.form-select>
            @endif

            @if ($this->requestEmployee === null)
                <p class="form-error">{{ __('hr_leave.validation.no-employee') }}</p>
            @elseif ($balances === [])
                <p class="text-sm {{ $muted }}" data-test="no-requestable-balance">{{ __('hr_leave.ui.no_requestable_balance') }}</p>
            @else
                <x-ui.form-select wire:model.live="leaveBalanceId" :label="__('hr_leave.fields.leave_balance_id')" :placeholder="__('hr_leave.ui.choose_type')" required>
                    @foreach ($balances as $balanceOption)
                        <option value="{{ $balanceOption['id'] }}">{{ $balanceOption['label'] }} — {{ __('hr_leave.ui.remaining', ['amount' => $balanceOption['remaining']]) }}</option>
                    @endforeach
                </x-ui.form-select>

                <x-ui.form-segmented
                    wire:model.live="requestType"
                    :label="__('hr_leave.fields.type')"
                    :options="[
                        ['value' => 'day', 'label' => __('hr_leave.unit.day')],
                        ['value' => 'hour', 'label' => __('hr_leave.unit.hour')],
                    ]"
                    required
                />

                @if ($requestType === 'hour')
                    <div class="grid gap-3 sm:grid-cols-3">
                        <x-ui.form-input type="date" wire:model.live.blur="leaveDate" :label="__('hr_leave.fields.leave_date')" required />
                        <x-ui.form-input type="time" wire:model.live.blur="startHour" :label="__('hr_leave.fields.start_hour')" required />
                        <x-ui.form-input type="time" wire:model.live.blur="endHour" :label="__('hr_leave.fields.end_hour')" required />
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.form-input type="date" wire:model.live.blur="startAt" :label="__('hr_leave.fields.start_at')" required />
                        <x-ui.form-input type="date" wire:model.live.blur="endAt" :label="__('hr_leave.fields.end_at')" required />
                    </div>
                @endif

                @if ($this->requestTouchesDecember())
                    <flux:checkbox wire:model.live="isExceptional" :label="__('hr_leave.ui.exceptional')" :description="__('hr_leave.ui.exceptional_help')" />
                @endif

                @if ($preview !== null)
                    @include('hr::pages.leave._request-impact', ['quote' => $preview])
                @endif

                <x-ui.form-textarea wire:model="reason" :label="__('hr_leave.fields.reason')" required maxlength="2000" />

                <x-ui.form-select wire:model="alternativeEmployee" :label="__('hr_leave.fields.alternative_employee')" :placeholder="__('hr_leave.ui.no_alternative')" searchable>
                    <option value="">{{ __('hr_leave.ui.no_alternative') }}</option>
                    @foreach ($this->alternativeOptions as $optionId => $optionName)
                        <option value="{{ $optionId }}">{{ $optionName }}</option>
                    @endforeach
                </x-ui.form-select>
            @endif

            @error('submission')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <x-slot:actions>
        <x-ui.button type="submit" tone="primary" data-test="save-leave-request">
            {{ $editingLeaveRequestId === null ? __('hr_leave.ui.send_request') : __('common.save_changes') }}
        </x-ui.button>
    </x-slot:actions>
</x-ui.modal>
