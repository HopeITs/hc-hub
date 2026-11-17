{{--
    The question for a day with no record (or a single punch): did they work,
    or were they absent? Leave is recorded in Leave, never here. Included by
    ⚡timesheets.
--}}
<x-ui.modal wire:model="showDayModal" size="sm" :title="__('hr_attendance.timesheets.dialog-title')" submit="saveDecision" close="closeDay" required-note>
    @if ($showDayModal && $openDay && $openDay['row'])
        @php
            $dayRow = $openDay['row'];
        @endphp
        <div class="grid gap-y-3" data-attendance-day-dialog>
            <p class="text-sm font-semibold"><span dir="auto">{{ $openDay['employee']->name }}</span> · <bdi class="tabular-nums">{{ $dayRow['date']->locale(app()->getLocale())->isoFormat('ddd D/M/YYYY') }}</bdi></p>
            <p class="text-sm {{ $muted }}">
                @if ($dayRow['first'] && $dayRow['kind'] === \Modules\Hr\Support\Attendance\Timesheet::ONE_PUNCH)
                    {{ __('hr_attendance.timesheets.dialog-one', ['time' => $dayRow['first']->format('H:i')]) }}
                @else
                    {{ __('hr_attendance.timesheets.dialog-none') }}
                @endif
            </p>

            <x-ui.form-segmented wire:model="dayKind" :label="__('hr_attendance.timesheets.answer')" :options="[
                ['value' => \Modules\Hr\Models\AttendanceDayDecision::WORKED, 'label' => __('hr_attendance.timesheets.worked')],
                ['value' => \Modules\Hr\Models\AttendanceDayDecision::ABSENT, 'label' => __('hr_attendance.timesheets.absent')],
            ]" required />
            <p class="text-xs {{ $muted }}">{{ __('hr_attendance.timesheets.answer-help') }}</p>
            <p class="text-xs {{ $muted }}">{{ __('hr_attendance.timesheets.leave-hint') }}</p>

            <x-ui.form-input wire:model="dayNote" :label="__('hr_attendance.timesheets.note')" maxlength="200" dir="auto" />

            @if ($dayRow['decision'])
                <div>
                    <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="clearDecision" data-test="attendance-clear-decision">{{ __('hr_attendance.timesheets.remove') }}</x-ui.button>
                </div>
            @endif
        </div>
    @endif
</x-ui.modal>
