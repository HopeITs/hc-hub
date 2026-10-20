@php
    use App\Enums\LeaveTypeCases;
use App\Enums\LeaveRequestStatus;
@endphp
<x-slot:title>
    {{ __('general.my-leaves') }}
</x-slot:title>

<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.my-leaves')"
            :crumbs="[
                ['label' => __('general.holidays-and-leaves')],

            ]"
            :showButton="true"
        />

        <div>
            <x-primary-button wire:click="create" data-tour="leaves.request">
                @lang('general.add-new')
            </x-primary-button>
        </div>

    </div>

    @isset($availableLeaves)
        <div data-tour="leaves.balances" class="grid
            grid-cols-1
            sm:grid-cols-2
            lg:grid-cols-3
            xl:grid-cols-5
            gap-6
            mb-6">
            @foreach($availableLeaves as $availableLeave)
                <x-leave-card
                    class="{{ $loop->first ? 'col-span-1 xl:col-span-2' : 'col-span-1' }}"
                    :title="$availableLeave->leaveType->name"
                    :total="$availableLeave->deserved_formatted"
                    :remaining="$availableLeave->remaining"
                    :total-days="$availableLeave->intTotal"
                    :remaining-days="$availableLeave->intRemaining"
                    :is-annual="$availableLeave->isAnnual()"
                    :used="$availableLeave->used_formatted"
                    :pending="formatDuration($availableLeave->pending_days , $availableLeave->pending_hours)"
                />
            @endforeach
        </div>
    @else
        <div class="rounded-surface shadow-e2 mb-6 text-center font-semibold text-secondary-medium p-4">
            @lang('general.no-available-data')
        </div>

    @endisset

    <x-tabs-list data-tour="leaves.filters">
        <x-tabs-list-item
            :active="$statusFilter === 'all'" active-tab="all">
            {{ __('general.all') . ' ('. $counts['all'] ?? 0 }})
        </x-tabs-list-item>

        <x-tabs-list-item
            :active="$statusFilter === (string) LeaveRequestStatus::Pending"
            :active-tab="LeaveRequestStatus::Pending">
            {{ LeaveRequestStatus::getLocalizedName(0)['value'] . ' ('.$counts['pending'] ?? 0  }})
        </x-tabs-list-item>

        <x-tabs-list-item
            :active="$statusFilter === (string) LeaveRequestStatus::Accepted"
            :active-tab="LeaveRequestStatus::Accepted">
            {{ LeaveRequestStatus::getLocalizedName(1)['value'] . ' ('.$counts['accepted'] ?? 0  }})

        </x-tabs-list-item>

        <x-tabs-list-item
            :active="$statusFilter === (string) LeaveRequestStatus::Rejected"
            :active-tab="LeaveRequestStatus::Rejected">
            {{ LeaveRequestStatus::getLocalizedName(2)['value'] . ' ('.$counts['rejected'] ?? 0  }})
        </x-tabs-list-item>
    </x-tabs-list>

    {{-- Table --}}
    <div class="mt-4" data-tour="leaves.table">
        <livewire:employee.leave.my-leave-request-table :statusFilter="$statusFilter" key="table-{{ $statusFilter }}"/>
    </div>

    <x-modal.dialog
        max-width="4xl"
        id="leaveDetailsModal"
        :title="__('general.leave-details')"
        wire:model.defer="detailsModal"
        wire:ignore.self>
        @php
            $selectedLeave = $this->selectedLeave;
        @endphp

        @if($selectedLeave)
            <div class="space-y-6">
                <x-leave.decision-summary :leave-request="$selectedLeave" />

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-neutral-200 p-4">
                        <dt class="text-sm text-neutral-500">{{ trans_choice('general.leave-type', 1) }}</dt>
                        <dd class="mt-1 font-semibold text-neutral-800">{{ $selectedLeave->leaveBalance->leaveType->name }}</dd>
                    </div>

                    <div class="rounded-xl border border-neutral-200 p-4">
                        <dt class="text-sm text-neutral-500">@lang('general.type')</dt>
                        <dd class="mt-1 font-semibold text-neutral-800">{{ trans_choice('general.'.$selectedLeave->type, 1) }}</dd>
                    </div>

                    <div class="rounded-xl border border-neutral-200 p-4">
                        <dt class="text-sm text-neutral-500">@lang('general.during-period')</dt>
                        <dd class="mt-1 font-semibold text-neutral-800">
                            @if($selectedLeave->type === 'hour')
                                {{ $selectedLeave->start_at->format('d-m-Y, H:i') }}
                                <span aria-hidden="true">→</span>
                                {{ $selectedLeave->end_at->format('H:i') }}
                            @else
                                {{ $selectedLeave->start_at->format('d-m-Y') }}
                                <span aria-hidden="true">→</span>
                                {{ $selectedLeave->end_at->format('d-m-Y') }}
                            @endif
                        </dd>
                    </div>

                    <div class="rounded-xl border border-neutral-200 p-4">
                        <dt class="text-sm text-neutral-500">@lang('general.duration')</dt>
                        <dd class="mt-1 font-semibold text-neutral-800">{{ $selectedLeave->duration }}</dd>
                    </div>

                    <div class="rounded-xl border border-neutral-200 p-4 sm:col-span-2">
                        <dt class="text-sm text-neutral-500">@lang('general.alternative-employee')</dt>
                        <dd class="mt-1 font-semibold text-neutral-800">{{ $selectedLeave->alternativeEmployee?->name ?? '—' }}</dd>
                    </div>

                    <div class="rounded-xl border border-neutral-200 p-4 sm:col-span-2">
                        <dt class="text-sm text-neutral-500">@lang('general.reason')</dt>
                        <dd class="mt-1 whitespace-pre-line break-words text-neutral-800">{{ $selectedLeave->reason }}</dd>
                    </div>
                </dl>

                <div class="flex justify-end">
                    <x-primary-button type="button" wire:click="closeDetails" class="!w-auto !bg-secondary-light">
                        @lang('general.cancel')
                    </x-primary-button>
                </div>
            </div>
        @endif
    </x-modal.dialog>


    <x-modal.dialog
        max-width="6xl"
        id="showModal"
        :title="$modalTitle"
        wire:model.defer="showModal"
        wire:ignore.self
        class="flex items-center justify-center px-2">
        <div class="w-full max-h-[60vh] overflow-y-auto bg-white rounded-surface px-6 py-4 md:px-12 md:py-6">
            <form wire:submit.prevent="save" class="space-y-4">
                <x-forms.errors-validation-list/>
                <div class="grid md:grid-cols-2 gap-4">

                    <div class="md:col-span-1" wire:key="container-leave-type-{{ $form->leave_balance_id ?? 'new' }}">
                        <x-forms.input-label for="leave_balance_id" required>{{__('general.type')}}</x-forms.input-label>
                        <x-forms.select2-input wire:model.live="form.leave_balance_id" required id="leave_balance_id">
                            @if($allAvailableLeaves?->count() > 0)
                                <option
                                    class="text-secondary-light" @disabled($form->leaveRequest !== null) @selected($form->leaveRequest === null)>
                                    @lang('general.select')
                                </option>
                                @foreach($allAvailableLeaves  as $availableLeave)
                                    <option
                                        value="{{$availableLeave->id}}">{{$availableLeave->leaveType->name}}</option>
                                @endforeach
                            @else
                                <option
                                    class="text-secondary-light" disabled>
                                    @lang('general.no-available-data')
                                </option>
                            @endif
                        </x-forms.select2-input>
                        <x-error-span>{{ $errors->first('form.leave_balance_id') }}</x-error-span>
                    </div>
                    <div class="md:col-span-1" wire:key="container-alt-employee-{{ $form->alternative_employee ?? 'none' }}">
                        <x-forms.input-label for="alternative_employee">{{__('general.alternative-employee')}}</x-forms.input-label>
                        <x-forms.select2-input wire:model="form.alternative_employee" id="alternative_employee">
                            <option
                                class="text-secondary-light" @disabled($form->leaveRequest !== null) @selected($form->leaveRequest === null)>

                                @lang('general.select')
                            </option>
                            @foreach($alternativeEmployees as $alternativeEmployee)
                                <option value="{{$alternativeEmployee->id}}">{{$alternativeEmployee->name}}</option>
                            @endforeach
                        </x-forms.select2-input>
                        <x-error-span>{{ $errors->first('form.alternative_employee') }}</x-error-span>
                    </div>

                    @if($form->leave_balance_id !== null)
                        @if($availableLeaves->find($form->leave_balance_id)?->leaveType?->id === LeaveTypeCases::Annual)
                            <x-forms.radio-group class="mt-4" :legend="trans_choice('general.leave-type',1)" error="form.type">
                                <x-forms.radio-input wire:model.live="form.type"
                                                     value="day">{{trans_choice('general.day',1)}}</x-forms.radio-input>
                                <x-forms.radio-input wire:model.live="form.type"
                                                     value="hour">{{trans_choice('general.hour',1)}}</x-forms.radio-input>
                            </x-forms.radio-group>
                        @endif
                    @endif
                    @if($form->leave_balance_id === null || $availableLeaves->find($form->leave_balance_id)?->leaveType?->id !== LeaveTypeCases::Annual)

                        <div>
                            <x-forms.input-label for="start_at" required>{{__('general.start-date')}}</x-forms.input-label>
                            <x-forms.date-input id="start_at" wire:model="form.start_at" label="Start Date"/>
                            <x-error-span>{{ $errors->first('form.start_at') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="end_at" required>{{__('general.end-date')}}</x-forms.input-label>
                            <x-forms.date-input id="end_at" wire:model="form.end_at" label="End Date"/>
                            <x-error-span>{{ $errors->first('form.end_at') }}</x-error-span>
                        </div>

                    @else

                        @if($form->type === 'day')
                            <div class="col-span-full grid md:grid-cols-2 gap-4">
                                <div>
                                    <x-forms.input-label
                                        for="start_at" required>{{__('general.start-date')}}</x-forms.input-label>
                                    <x-forms.date-input id="start_at" wire:model="form.start_at" label="Start Date"/>
                                    <x-error-span>{{ $errors->first('form.start_at') }}</x-error-span>
                                </div>
                                <div>
                                    <x-forms.input-label for="end_at" required>{{__('general.end-date')}}</x-forms.input-label>
                                    <x-forms.date-input id="end_at" wire:model="form.end_at" label="End Date"/>
                                    <x-error-span>{{ $errors->first('form.end_at') }}</x-error-span>
                                </div>
                            </div>
                        @elseif($form->type === 'hour')

                            <div>
                                <x-forms.input-label for="leave_date" required>{{__('general.leave-date')}}</x-forms.input-label>
                                <x-forms.date-input id="leave_date" wire:model.live="form.leave_date" label="Leave Date"/>
                                <x-error-span>{{ $errors->first('form.leave_date') }}</x-error-span>
                            </div>

                            <div>
                                <x-forms.input-label for="start_time" required>{{__('general.start-time')}}(24)
                                </x-forms.input-label>
                                <x-forms.time-picker id="start_time" wire-model="form.start_hour" format="HH:mm" min="09:00" max="21:00" />
                                <x-error-span>{{ $errors->first('form.start_hour') }}</x-error-span>
                            </div>
                            <div>
                                <x-forms.input-label for="end_time" required>{{__('general.end-time')}}(24)</x-forms.input-label>
                                <x-forms.time-picker id="end_time" wire-model="form.end_hour" format="HH:mm" min="09:00" max="21:00" />
                                <x-error-span>{{ $errors->first('form.end_hour') }}</x-error-span>
                            </div>

                        @endif

                    @endif
                    <div class="col-span-full">
                        <x-forms.input-label for="reason" required>{{__('general.reason')}}</x-forms.input-label>
                        <x-forms.input id="reason" wire:model="form.reason" required/>
                        <x-error-span>{{ $errors->first('form.reason') }}</x-error-span>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <x-primary-button
                        type="button"
                        wire:click="confirmSave"
                        class="!w-auto">
                        @lang('general.save')
                    </x-primary-button>

                    <x-primary-button
                        :href="route('leaves.index')"
                        class="!bg-secondary-light !w-auto">
                        @lang('general.cancel')
                    </x-primary-button>
                </div>
            </form>
        </div>
    </x-modal.dialog>
</div>
