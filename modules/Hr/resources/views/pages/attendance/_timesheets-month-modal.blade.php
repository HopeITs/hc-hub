{{--
    One person's month: where it stands, what the viewer may do with it, the
    signed paper and the printout. Included by ⚡timesheets.
--}}
<x-ui.modal wire:model="showMonthModal" size="md" :title="$panel ? $panel['employee']->name : __('hr_attendance.workflow.timeline')" :description="$monthDate->locale(app()->getLocale())->isoFormat('MMMM YYYY')" close="closeMonth">
    @if ($showMonthModal && $panel)
        @php
            $pEmployee = $panel['employee'];
            $pRecord = $panel['record'];
            $pCan = $panel['can'];
            $pTotals = $panel['sheet']['totals'];
            $stamp = fn ($at): string => $at?->format('d/m/Y H:i') ?? '';
        @endphp
        <div class="grid gap-y-5" data-attendance-month-panel="{{ $pEmployee->id }}">
            <div>
                <flux:badge size="sm" :color="['open' => 'zinc', 'confirmed' => 'amber', 'approved' => 'sky', 'locked' => 'green'][$panel['state']]">{{ __('hr_attendance.workflow.states.'.$panel['state']) }}</flux:badge>
            </div>

            <dl class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-md bg-secondary-soft/60 p-3 dark:bg-surface-2">
                    <dt class="text-xs {{ $muted }}">{{ __('hr_attendance.timesheets.days') }}</dt>
                    <dd class="text-lg font-semibold tabular-nums">{{ $pTotals['working_days'] }}</dd>
                </div>
                <div class="rounded-md bg-secondary-soft/60 p-3 dark:bg-surface-2">
                    <dt class="text-xs {{ $muted }}">{{ __('hr_attendance.timesheets.overtime') }}</dt>
                    <dd class="text-lg font-semibold tabular-nums">{{ $pTotals['overtime_hours'] }}</dd>
                    <dd class="text-xs tabular-nums {{ $muted }}">{{ __('hr_attendance.timesheets.free-days-count', ['days' => $pTotals['overtime_days']]) }}</dd>
                </div>
                <div class="rounded-md bg-secondary-soft/60 p-3 dark:bg-surface-2">
                    <dt class="text-xs {{ $muted }}">{{ __('hr_attendance.timesheets.decide') }}</dt>
                    <dd @class(['text-lg font-semibold tabular-nums', 'text-primary dark:text-brand-text' => $pTotals['to_decide'] > 0])>{{ $pTotals['to_decide'] }}</dd>
                </div>
            </dl>

            <div>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide {{ $muted }}">{{ __('hr_attendance.workflow.timeline') }}</h3>
                <ol class="space-y-2 text-sm">
                    <li class="flex items-start gap-2">
                        <flux:icon :name="$pRecord->employee_confirmed_at ? 'check-circle' : 'minus-circle'" class="mt-0.5 size-4 shrink-0" />
                        <span>{{ __('hr_attendance.workflow.step-confirmed') }} · <span class="tabular-nums {{ $muted }}">{{ $pRecord->employee_confirmed_at ? $stamp($pRecord->employee_confirmed_at) : __('hr_attendance.workflow.not-yet') }}</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <flux:icon :name="$pRecord->approved_at ? 'check-circle' : 'minus-circle'" class="mt-0.5 size-4 shrink-0" />
                        <span>
                            @if ($pRecord->approved_at)
                                {{ __('hr_attendance.workflow.step-approved', ['name' => $pRecord->approver?->name ?? '—']) }} · <span class="tabular-nums {{ $muted }}">{{ $stamp($pRecord->approved_at) }}</span>
                                <span class="block text-xs {{ $muted }}">{{ __('hr_attendance.workflow.overtime-approved', ['hours' => $pRecord->overtime_hours_approved]) }}</span>
                            @else
                                {{ __('hr_attendance.workflow.approve') }} · <span class="{{ $muted }}">{{ __('hr_attendance.workflow.not-yet') }}</span>
                            @endif
                        </span>
                    </li>
                    <li class="flex items-start gap-2">
                        <flux:icon :name="$pRecord->locked_at ? 'lock-closed' : 'minus-circle'" class="mt-0.5 size-4 shrink-0" />
                        <span>
                            @if ($pRecord->locked_at)
                                {{ __('hr_attendance.workflow.step-locked', ['name' => $pRecord->locker?->name ?? '—']) }} · <span class="tabular-nums {{ $muted }}">{{ $stamp($pRecord->locked_at) }}</span>
                            @else
                                {{ __('hr_attendance.workflow.lock') }} · <span class="{{ $muted }}">{{ __('hr_attendance.workflow.not-yet') }}</span>
                            @endif
                        </span>
                    </li>
                </ol>
            </div>

            <div class="flex flex-wrap gap-2" data-attendance-month-actions>
                @if ($pCan['confirm'])
                    <x-ui.button tone="primary" size="sm" wire:click="confirmMonth({{ $pEmployee->id }})" data-test="attendance-confirm">{{ __('hr_attendance.workflow.confirm') }}</x-ui.button>
                @endif
                @if ($pCan['approve'])
                    <x-ui.button tone="primary" size="sm" wire:click="approveMonth({{ $pEmployee->id }})" data-test="attendance-approve">{{ __('hr_attendance.workflow.approve') }}</x-ui.button>
                @endif
                @if ($pCan['lock'])
                    <x-ui.button size="sm" icon="lock-closed" wire:click="lockMonth({{ $pEmployee->id }})" data-test="attendance-lock">{{ __('hr_attendance.workflow.lock') }}</x-ui.button>
                @endif
                @if ($pCan['reopen'])
                    <x-ui.button size="sm" wire:click="reopenMonth({{ $pEmployee->id }})" wire:confirm="{{ __('hr_attendance.workflow.reopen') }}?" data-test="attendance-reopen">{{ __('hr_attendance.workflow.reopen') }}</x-ui.button>
                @endif
            </div>
            @if ($pTotals['to_decide'] > 0 && $panel['state'] === 'open')
                <p class="text-sm {{ $muted }}">{{ trans_choice('hr_attendance.workflow.days-first', $pTotals['to_decide'], ['count' => $pTotals['to_decide']]) }}</p>
            @endif

            <div class="grid gap-y-2" data-attendance-signed>
                <h3 class="text-xs font-semibold uppercase tracking-wide {{ $muted }}">{{ __('hr_attendance.workflow.signed') }}</h3>
                @if ($panelSigned)
                    <div class="flex flex-wrap items-center gap-3">
                        <a class="text-sm font-semibold underline" target="_blank" href="{{ route('hr.attendance.signed', $pRecord->id) }}">{{ __('hr_attendance.workflow.signed-view') }}</a>
                        @if ($pCan['signed'])
                            <x-ui.button tone="ghost" size="sm" wire:click="removeSigned" data-test="attendance-signed-remove">{{ __('hr_attendance.workflow.signed-remove') }}</x-ui.button>
                        @endif
                    </div>
                @else
                    <p class="text-sm {{ $muted }}">{{ __('hr_attendance.workflow.signed-none') }}</p>
                @endif
                @if ($pCan['signed'])
                    <x-ui.form-file wire:model="signedFile" :label="__('hr_attendance.workflow.signed-upload')" :help="__('hr_attendance.workflow.signed-help')" accept=".pdf,.jpg,.jpeg,.png" />
                    <div>
                        <x-ui.button size="sm" wire:click="saveSigned" data-test="attendance-signed-save">{{ __('hr_attendance.workflow.signed-attach') }}</x-ui.button>
                    </div>
                @endif
            </div>

            <div class="border-t border-panel-border pt-4">
                <x-ui.button size="sm" icon="printer" target="_blank" :href="route('hr.attendance.print', ['month' => $month, 'employees' => [$pEmployee->id]])">{{ __('hr_attendance.timesheets.print') }}</x-ui.button>
            </div>
        </div>
    @endif
</x-ui.modal>
