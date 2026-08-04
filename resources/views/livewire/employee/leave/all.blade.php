@php
    use App\Enums\LeaveTypeCases;
use App\Enums\LeaveRequestStatus;
@endphp
<div>
    <div class="flex justify-between items-center">
        <livewire:layout.breadcrumbs
            :title="__('general.all-leaves')"
            :crumbs="[
                ['label' => __('general.holidays-and-leaves')],

            ]"
            :showButton="true"
        />
        @activerole('HR','super-admin')
        <div>
            <x-primary-button wire:click="create">
                @lang('general.add-new')
            </x-primary-button>
        </div>
        @endactiverole

    </div>

    <x-tabs-list>
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
    <div class="mt-4">
        <livewire:employee.leave.all-leave-request-table :statusFilter="$statusFilter" key="table-{{ $statusFilter }}"/>
    </div>


    <x-modal.dialog max-width="6xl" id="formModal" :title="$modalTitle" wire:model.defer="formModal" wire:ignore.self>

        <form wire:submit.prevent="save" class="space-y-4">
            <x-forms.errors-validation-list/>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-1">
                    <x-forms.input-label for="employee_id" required>{{trans_choice('general.employee',1)}}</x-forms.input-label>
                    <x-forms.select2-input wire:model.live="form.employee_id" id="employee_id">
                        <option value="">Select Employee…</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </x-forms.select2-input>
                    <x-error-span>{{ $errors->first('form.employee_id') }}</x-error-span>
                </div>
            
                <div class="md:col-span-1" wire:key="container-leave-type-{{ $form->employee_id ?? 'none' }}-{{ $form->leaveRequest ? 'edit' : 'create' }}" >
                    <x-forms.input-label for="leave_balance_id" required>{{__('general.type')}}</x-forms.input-label>
                    <x-forms.select2-input wire:model.live="form.leave_balance_id" id="leave_balance_id">
                        <option
                            class="text-secondary-light" @disabled($form->leaveRequest !== null) @selected($form->leaveRequest === null)>
                            @lang('general.select')..
                        </option>
                        @foreach($allAvailableLeaves ?? [] as $availableLeave)
                            <option value="{{$availableLeave->id}}" @selected($form->leave_balance_id === $availableLeave->id)>{{$availableLeave->leaveType->name}}</option>
                        @endforeach
                    </x-forms.select2-input>
                    <x-error-span>{{ $errors->first('form.leave_balance_id') }}</x-error-span>
                </div>
                <div class="md:col-span-1" wire:key="container-alt-emp-{{ $form->employee_id }}-{{ $form->leaveRequest ? 'edit' : 'create' }}">
                    <x-forms.input-label for="alternative_employee">{{__('general.alternative-employee')}}</x-forms.input-label>
                    <x-forms.select2-input wire:model.live="form.alternative_employee" id="alternative_employee">
                        <option
                            class="text-secondary-light" @disabled($form->leaveRequest !== null) @selected($form->leaveRequest === null)>
                            @lang('general.select')..
                        </option>
                        @foreach($alternativeEmployees as $emp)
                            <option value="{{ $emp->id }}" @selected($form->alternative_employee === $emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </x-forms.select2-input>
                    <x-error-span>{{ $errors->first('form.alternative_employee') }}</x-error-span>
                </div>

                @if($form->leave_balance_id !== null)
                    @if($allAvailableLeaves->find($form->leave_balance_id)?->leaveType?->id === LeaveTypeCases::Annual)
                        <div class="mt-4">
                            <x-forms.input-label>Leave Type</x-forms.input-label>
                            <div class="flex gap-4">
                                <x-forms.radio-input wire:model.live="form.type" value="day">Day</x-forms.radio-input>
                                <x-forms.radio-input wire:model.live="form.type" value="hour">Hour</x-forms.radio-input>
                            </div>
                        </div>
                    @endif
                @endif
                @if($form->leave_balance_id === null || $allAvailableLeaves->find($form->leave_balance_id)?->leaveType?->id !== LeaveTypeCases::Annual)
                    <div class="col-span-full md:grid md:grid-cols-2 md:gap-4">
                        <div>
                            <x-forms.input-label for="start_at" required>{{__('general.start-date')}}</x-forms.input-label>
                            <x-forms.date-input id="start_at" wire:model.live="form.start_at" label="Start Date"/>
                            <x-error-span>{{ $errors->first('form.start-date') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="end_at" required>{{__('general.end-date')}}</x-forms.input-label>
                            <x-forms.date-input id="end_at" wire:model.live="form.end_at" label="End Date"/>
                            <x-error-span>{{ $errors->first('form.end_at') }}</x-error-span>
                        </div>
                    </div>
                @else

                    @if($form->type === 'day')
                        <div class="col-span-full grid md:grid-cols-2 gap-4">
                            <div>
                                <x-forms.input-label for="start_at" required>{{__('general.start-date')}}</x-forms.input-label>
                                <x-forms.date-input id="start_at" wire:model.live="form.start_at" label="Start Date"/>
                                <x-error-span>{{ $errors->first('form.start_at') }}</x-error-span>
                            </div>
                            <div>
                                <x-forms.input-label for="end_at" required>{{__('general.end-date')}}</x-forms.input-label>
                                <x-forms.date-input id="end_at" wire:model.live="form.end_at" label="End Date"/>
                                <x-error-span>{{ $errors->first('form.end_at') }}</x-error-span>
                            </div>
                        </div>
                    @elseif($form->type === 'hour')

                        <div>
                            <x-forms.input-label for="leave_date" required>{{__('general.leave-date')}}</x-forms.input-label>
                            <x-forms.date-input id="leave_date" wire:model.live="form.leave_date" label="Leave Date"/>
                            <x-error-span>{{ $errors->first('form.leave_date') }}</x-error-span>
                        </div>
                        <div class=" md:grid md:grid-cols-2 md:gap-4">
                            <div>
                                <x-forms.input-label for="start_time" required>{{__('general.start-time')}}</x-forms.input-label>
                                <x-forms.date-input id="start_time" type="time" wire:model.live="form.start_hour"
                                                    label="Start Hour"/>
                                <x-error-span>{{ $errors->first('form.start_hour') }}</x-error-span>
                            </div>
                            <div>
                                <x-forms.input-label for="end_time" required>{{__('general.end-time')}}</x-forms.input-label>
                                <x-forms.date-input type="time" id="end_time" wire:model.live="form.end_hour"
                                                    label="End Time"/>
                                <x-error-span>{{ $errors->first('form.end_hour') }}</x-error-span>
                            </div>
                        </div>

                    @endif

                @endif
                <div class="col-span-full">
                    <x-forms.input-label for="reason" required>{{__('general.reason')}}</x-forms.input-label>
                    <x-forms.input id="reason" wire:model="form.reason"/>
                    <x-error-span>{{ $errors->first('form.reason') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-primary-button type="button" wire:click="confirmSave"
                                  class="!w-auto">@lang('general.save')</x-primary-button>
                <x-primary-button :href="route('leaves.all')"
                                  class="!bg-secondary-light !w-auto">@lang('general.cancel')</x-primary-button>
            </div>
        </form>
    </x-modal.dialog>

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        @if($selectedLeave)
            <div class="space-y-6">
                <x-leave.decision-summary :leave-request="$selectedLeave" />

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <x-forms.input-label>{{ __('general.name') }}</x-forms.input-label>
                        <p class="text-neutral-800">{{ $selectedLeave->leaveBalance->contract->employee->name }}</p>
                    </div>
                    <div>
                        <x-forms.input-label>{{ __('general.request_date') }}</x-forms.input-label>
                        <p class="text-neutral-800">{{ $selectedLeave->created_at->format('d-M-Y') }}</p>
                    </div>

                    <div>
                        <x-forms.input-label>{{ __('general.type') }}</x-forms.input-label>
                        <p class="text-neutral-800">{{ $selectedLeave->leaveType->name }}</p>
                    </div>

                    <div>
                        <x-forms.input-label>{{ __('general.alternative-employee') }}</x-forms.input-label>
                        <p class="text-neutral-800">
                            {{ optional($selectedLeave->alternativeEmployee)->name ?? '—' }}
                        </p>
                    </div>


                    @if($selectedLeave->type === 'day')
                        <div>
                            <x-forms.input-label>{{ __('general.start-date') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $selectedLeave->start_at->format('d-M-Y') }}</p>
                        </div>

                        <div>
                            <x-forms.input-label>{{ __('general.end-date') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $selectedLeave->end_at->format('d-M-Y') }}</p>
                        </div>
                        <div>
                            <x-forms.input-label>{{ __('general.duration') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $selectedLeave->duration }}</p>
                        </div>
                    @else

                        <div>
                            <x-forms.input-label>{{ __('general.leave-date') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $selectedLeave->start_at->format('d-M-Y')  }}</p>
                        </div>
                        <div>
                            <x-forms.input-label>{{ __('general.duration') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $selectedLeave->duration }}</p>
                        </div>

                        <div>
                            <x-forms.input-label>{{ __('general.start-at') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $selectedLeave->start_at->format('H:i')  }}</p>
                        </div>
                        <div>
                            <x-forms.input-label>{{ __('general.end-at') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $selectedLeave->end_at->format('H:i')  }}</p>
                        </div>

                    @endif

                    <div class="col-span-full">
                        <x-forms.input-label>{{ __('general.reason') }}</x-forms.input-label>
                        <p class="text-neutral-800">{{ $selectedLeave->reason }}</p>
                    </div>

                </div>

                <div class="flex space-x-2 justify-end mt-4">
                    <x-primary-button wire:click="confirmDelete({{$selectedLeave->id}})"
                                      class=" !w-auto">
                        {{ __('general.delete') }}
                    </x-primary-button>
                    <x-primary-button wire:click="closeDetails"
                                      class="!bg-secondary-light !w-auto">
                        {{ __('general.cancel') }}
                    </x-primary-button>
                </div>

            </div>
        @endif
    </x-modal.dialog>

    <x-modal.dialog id="decisionModal" :title="$modalTitle" wire:model.defer="decisionModal" wire:ignore.self>
        @if($decisionLeave)
            <div class="space-y-6">

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <x-forms.input-label>{{ __('general.name') }}</x-forms.input-label>
                        <p class="text-neutral-800">{{ $decisionLeave->leaveBalance->contract->employee->name }}</p>
                    </div>

                    <div>
                        <x-forms.input-label>{{ __('general.request_date') }}</x-forms.input-label>
                        <p class="text-neutral-800">{{ $decisionLeave->created_at->format('d-M-Y') }}</p>
                    </div>

                    <div>
                        <x-forms.input-label>{{ __('general.type') }}</x-forms.input-label>
                        <p class="text-neutral-800">{{ $decisionLeave->leaveType->name }}</p>
                    </div>

                    <div>
                        <x-forms.input-label>{{ __('general.alternative-employee') }}</x-forms.input-label>
                        <p class="text-neutral-800">
                            {{ optional($decisionLeave->alternativeEmployee)->name ?? '—' }}
                        </p>
                    </div>

                    @if($decisionLeave->type === 'day')
                        <div>
                            <x-forms.input-label>{{ __('general.during-period') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $decisionLeave->start_at->format('d-M-Y') .' → '. $decisionLeave->end_at->format('d-M-Y')}}</p>
                        </div>

                        <div>
                            <x-forms.input-label>{{ __('general.duration') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $decisionLeave->duration }}</p>
                        </div>
                    @else

                        <div>
                            <x-forms.input-label>{{ __('general.during-period') }}</x-forms.input-label>
                            <div class="text-neutral-800 flex items-center space-x-2">
                                <x-icons.calendar class="!size-4"/>
                                <span>
                                    {{$decisionLeave->start_at->format('d-M-Y') }}
                                </span></div>
                            <div class="text-neutral-800 flex items-center space-x-2">
                                <x-icons.clock class="!size-4"/>
                                <span>{{$decisionLeave->start_at->format('H:i') .' → '. $decisionLeave->end_at->format('H:i') }}</span>
                            </div>
                        </div>
                        <div>
                            <x-forms.input-label>{{ __('general.duration') }}</x-forms.input-label>
                            <p class="text-neutral-800">{{ $decisionLeave->duration }}</p>
                        </div>

                    @endif

                    <div class="col-span-full">
                        <x-forms.input-label>{{ __('general.reason') }}</x-forms.input-label>
                        <p class="text-neutral-800">{{ $decisionLeave->reason }}</p>
                    </div>
                </div>

                {{-- Decision Section --}}
                @if($decisionLeave->status === LeaveRequestStatus::Pending)
                    <div class="border-t pt-4 space-y-4">

                        <x-forms.input-label>{{ __('general.action') }}</x-forms.input-label>

                        <div class="flex gap-6">
                            <label class="flex items-center space-x-2">
                                <x-forms.radio-input :value="LeaveRequestStatus::Accepted" id="accept"
                                                     wire:model.live="form.status">
                                    {{ __('general.accept') }}
                                </x-forms.radio-input>

                            </label>

                            <label class="flex items-center space-x-2">
                                <x-forms.radio-input :value="LeaveRequestStatus::Rejected" id="reject"
                                                     wire:model.live="form.status">
                                    {{ __('general.reject') }}
                                </x-forms.radio-input>

                            </label>
                        </div>
                        <x-error-span>{{ $errors->first('form.status') }}</x-error-span>

                        @if((int)$form->status === LeaveRequestStatus::Rejected)
                            <div>
                                <x-forms.input-label required>{{ __('general.reject-reason') }}</x-forms.input-label>
                                <x-forms.input wire:model="form.reject_reason"/>
                                <x-error-span>{{ $errors->first('form.reject_reason') }}</x-error-span>
                            </div>
                        @endif


                    </div>
                @endif

                <div class="flex space-x-2 justify-end mt-4">

                    <x-primary-button
                        wire:click="submitDecision"
                        wire:loading.attr="disabled"
                        wire:target="submitDecision"
                        class="!w-auto">
                        {{ __('general.save') }}
                    </x-primary-button>

                    <x-primary-button wire:click="closeDecision"
                                      class="!bg-secondary-light !w-auto">
                        {{ __('general.cancel') }}
                    </x-primary-button>
                </div>

            </div>
        @endif
    </x-modal.dialog>


</div>
