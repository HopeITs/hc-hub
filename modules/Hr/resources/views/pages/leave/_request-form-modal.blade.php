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
    $submitted = $showRequestModal && $submittedRequestId !== null ? $this->submittedRequest : null;
    $selectedBalance = collect($balances)->firstWhere('id', (int) $leaveBalanceId);
    $offersHours = (bool) ($selectedBalance['annual'] ?? false);
    $dateOf = function (string $value): string {
        try {
            return \Carbon\CarbonImmutable::parse($value)->format('d-m-Y');
        } catch (\Throwable) {
            return $value;
        }
    };
    $periodText = $requestType === 'hour'
        ? $dateOf($leaveDate).', '.$startHour.' – '.$endHour
        : ($startAt === $endAt ? $dateOf($startAt) : $dateOf($startAt).' – '.$dateOf($endAt));
@endphp

<x-ui.modal
    wire:model="showRequestModal"
    size="md"
    :title="$submitted !== null ? __('hr_leave.ui.submitted.title') : ($confirmingRequest ? __('hr_leave.ui.confirm.title') : ($editingLeaveRequestId === null ? __('hr_leave.ui.new_request') : __('hr_leave.ui.edit_request')))"
    :description="$submitted !== null ? null : ($confirmingRequest ? __('hr_leave.ui.confirm.description') : __('hr_leave.ui.request_dialog_description'))"
    submit="submitRequest"
    close="closeRequestForm"
    :required-note="! $confirmingRequest && $submitted === null"
>
    @if ($showRequestModal && $submitted !== null)
        {{-- After the send: where the request stands and who acts next, not only a toast. --}}
        <div class="grid gap-y-3" data-leave-submitted>
            <div class="flex items-start gap-3 rounded-lg border border-secondary-soft bg-secondary-soft/40 px-4 py-3 text-sm dark:border-line dark:bg-surface-2">
                <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-status-ok" aria-hidden="true" />
                <div class="grid gap-1">
                    <p class="font-semibold">{{ __('hr_leave.ui.submitted.heading', ['type' => (string) $submitted->leaveBalance?->leaveType?->name]) }}</p>
                    <p data-leave-next-step>{{ app(\Modules\Hr\Support\Leave\LeaveRequestNextStep::class)->describe($submitted, auth()->user()) }}</p>
                </div>
            </div>
        </div>
    @elseif ($showRequestModal && $confirmingRequest)
        {{-- The priced summary: nothing is sent until the person confirms it. --}}
        <div class="grid gap-y-3" data-leave-confirm>
            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <div>
                    <dt class="type-label {{ $muted }}">{{ __('hr_leave.fields.leave_balance_id') }}</dt>
                    <dd>{{ $selectedBalance['label'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="type-label {{ $muted }}">{{ __('hr_leave.ui.period') }}</dt>
                    <dd class="tabular-nums" dir="auto">{{ $periodText }}</dd>
                </div>
                <div>
                    <dt class="type-label {{ $muted }}">{{ __('hr_leave.fields.alternative_employee') }}</dt>
                    <dd dir="auto">{{ $this->alternativeOptions[(int) $alternativeEmployee] ?? __('hr_leave.ui.no_alternative') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="type-label {{ $muted }}">{{ __('hr_leave.fields.reason') }}</dt>
                    <dd class="whitespace-pre-line" dir="auto">{{ $reason }}</dd>
                </div>
            </dl>

            @if ($preview !== null)
                @include('hr::pages.leave._request-impact', ['quote' => $preview])
            @endif
        </div>
    @elseif ($showRequestModal)
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

                {{-- Hourly leave is for annual leave only; another type is asked for in whole days. --}}
                @if ($offersHours)
                    <x-ui.form-segmented
                        wire:model.live="requestType"
                        :label="__('hr_leave.fields.type')"
                        :options="[
                            ['value' => 'day', 'label' => __('hr_leave.unit.day')],
                            ['value' => 'hour', 'label' => __('hr_leave.unit.hour')],
                        ]"
                        required
                    />
                @endif

                @if ($requestType === 'hour' && $offersHours)
                    <div class="grid gap-3 sm:grid-cols-3">
                        <x-ui.form-input type="date" wire:model.live.blur="leaveDate" :label="__('hr_leave.fields.leave_date')" required />
                        <x-ui.form-input type="time" wire:model.live.blur="startHour" min="09:00" max="21:00" step="900" :label="__('hr_leave.fields.start_hour')" :help="__('hr_leave.ui.hour_help')" required />
                        <x-ui.form-input type="time" wire:model.live.blur="endHour" min="09:00" max="21:00" step="900" :label="__('hr_leave.fields.end_hour')" required />
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

                <x-ui.form-select wire:model="alternativeEmployee" :label="__('hr_leave.fields.alternative_employee')" :placeholder="__('hr_leave.ui.no_alternative')" :help="__('hr_leave.alternative.available_hint')" searchable wire:key="alternative-{{ md5(json_encode([$requestType, $startAt, $endAt, $leaveDate, $startHour, $endHour])) }}">
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
        @if ($submitted !== null)
            <x-ui.button tone="secondary" :href="route('hr.leave.show', $submitted)" wire:navigate data-test="view-submitted-leave">{{ __('hr_leave.ui.submitted.view') }}</x-ui.button>
            <x-ui.button type="submit" tone="primary" data-test="close-submitted-leave">{{ __('hr_leave.ui.submitted.done') }}</x-ui.button>
        @elseif ($confirmingRequest)
            <x-ui.button type="button" tone="secondary" wire:click="backToRequestForm" data-test="back-to-leave-form">{{ __('common.back') }}</x-ui.button>
            <x-ui.button type="submit" tone="primary" data-test="save-leave-request">
                {{ $editingLeaveRequestId === null ? __('hr_leave.ui.send_request') : __('common.save_changes') }}
            </x-ui.button>
        @else
            <x-ui.button type="submit" tone="primary" data-test="review-leave-request">{{ __('hr_leave.ui.review_request') }}</x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.modal>
