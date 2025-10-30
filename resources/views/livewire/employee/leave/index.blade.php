@php
    use App\Enums\LeaveTypeCases;
@endphp
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
            <x-primary-button wire:click="create">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>
    @isset($availableLeaves)
        <div class="grid grid-cols-{{$availableLeaves->count()}} gap-4 mb-6">
            @foreach($availableLeaves as $availableLeave)
                <div class="flex flex-col gap-2 rounded-2xl shadow-md p-4">
                    <h3 class="font-bold text-secondary-dark">{{$availableLeave->leaveType->name}}</h3>
                    <span class=" flex justify-between px-2 py-1 text-secondary-dark"><span
                            class="font-semibold text-secondary-medium text-secondary-dark me-2">Total:</span>{{$availableLeave->deserved_formatted}}</span>
                    <span class="rounded-full px-2 py-1 bg-gray-200 text-secondary-dark flex justify-between"><span
                            class="font-semibold text-secondary-medium me-2">Remaining:</span>{{ $availableLeave->remaining}}</span>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl shadow-md mb-6 text-center font-semibold text-secondary-medium p-4">
            No Leaves available for you
        </div>

    @endisset

    <livewire:employee.leave.leave-request-table/>

    <x-modal.dialog max-width="6xl" id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>

        <form wire:submit.prevent="save" class="space-y-4">
            <x-forms.errors-validation-list/>
            <div class="grid md:grid-cols-2 gap-4">

                <div class="md:col-span-1">
                    <x-forms.input-label for="city">{{__('general.type')}}</x-forms.input-label>
                    <x-forms.select-input wire:model.live="form.leave_balance_id" id="leave_balance_id">
                        <option
                            class="text-gray-400" @disabled($form->leaveRequest !== null) @selected($form->leaveRequest === null)>
                            Select Any..
                        </option>
                        @foreach($allAvailableLeaves ?? [] as $availableLeave)
                            <option value="{{$availableLeave->id}}">{{$availableLeave->leaveType->name}}</option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('form.leave_balance_id') }}</x-error-span>
                </div>
                <div class="md:col-span-1">
                    <x-forms.input-label for="city">{{__('general.alternative_employee')}}</x-forms.input-label>
                    <x-forms.select-input wire:model="form.alternative_employee" id="alternative_employee">
                        <option
                            class="text-gray-400" @disabled($form->leaveRequest !== null) @selected($form->leaveRequest === null)>
                            Select Any..
                        </option>
                        @foreach($alternativeEmployees as $alternativeEmployee)
                            <option value="{{$alternativeEmployee->id}}">{{$alternativeEmployee->name}}</option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('form.alternative_employee') }}</x-error-span>
                </div>

                @if($form->leave_balance_id !== null)
                    @if(optional($availableLeaves->find($form->leave_balance_id))->leaveType->id === LeaveTypeCases::Annual)
                        <div class="mt-4">
                            <x-forms.input-label>Leave Type</x-forms.input-label>
                            <div class="flex gap-4">
                                <x-forms.radio-input wire:model.live="form.type" value="day">Day</x-forms.radio-input>
                                <x-forms.radio-input wire:model.live="form.type" value="hour">Hour</x-forms.radio-input>
                            </div>
                        </div>
                    @endif
                @endif
                @if($form->leave_balance_id === null || optional($availableLeaves->find($form->leave_balance_id))->leaveType->id !== LeaveTypeCases::Annual)

                    <div>
                        <x-forms.input-label for="start_at">{{__('general.start-date')}}</x-forms.input-label>
                        <x-forms.date-input id="start_at" wire:model.live="form.start_at" label="Start Date"/>
                        <x-error-span>{{ $errors->first('form.alternative_employee') }}</x-error-span>
                    </div>
                    <div>
                        <x-forms.input-label for="end_at">{{__('general.end-date')}}</x-forms.input-label>
                        <x-forms.date-input id="end_at" wire:model.live="form.end_at" label="End Date"/>
                        <x-error-span>{{ $errors->first('form.end_at') }}</x-error-span>
                    </div>

                @else

                    @if($form->type === 'day')
                        <div class="col-span-full grid md:grid-cols-2 gap-4">
                            <div>
                                <x-forms.input-label for="start_at">{{__('general.start-date')}}</x-forms.input-label>
                                <x-forms.date-input id="start_at" wire:model.live="form.start_at" label="Start Date"/>
                                <x-error-span>{{ $errors->first('form.start_at') }}</x-error-span>
                            </div>
                            <div>
                                <x-forms.input-label for="end_at">{{__('general.end-date')}}</x-forms.input-label>
                                <x-forms.date-input id="end_at" wire:model.live="form.end_at" label="End Date"/>
                                <x-error-span>{{ $errors->first('form.end_at') }}</x-error-span>
                            </div>
                        </div>
                    @elseif($form->type === 'hour')

                        <div>
                            <x-forms.input-label for="city">{{__('general.leave-date')}}</x-forms.input-label>
                            <x-forms.date-input wire:model.live="form.leave_date" label="Leave Date"/>
                            <x-error-span>{{ $errors->first('form.leave_date') }}</x-error-span>
                        </div>

                        <div>
                            <x-forms.input-label for="start_time">{{__('general.start-time')}}</x-forms.input-label>
                            <x-forms.date-input id="start_time" type="time" wire:model.live="form.start_hour"
                                                label="Start Hour"/>
                            <x-error-span>{{ $errors->first('form.start_hour') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="end_time">{{__('general.end-time')}}</x-forms.input-label>
                            <x-forms.date-input type="time" id="end_time" wire:model.live="form.end_hour" label="End Time"/>
                            <x-error-span>{{ $errors->first('form.end_hour') }}</x-error-span>
                        </div>

                    @endif

                @endif
                <div class="col-span-full">
                    <x-forms.input-label for="reason">{{__('general.reason')}}</x-forms.input-label>
                    <x-forms.input id="reason" wire:model="form.reason"/>
                    <x-error-span>{{ $errors->first('form.reason') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-primary-button :href="route('leaves.index')"
                                  class="!bg-secondary-light !w-auto">@lang('general.cancel')</x-primary-button>
            </div>
        </form>
    </x-modal.dialog>
</div>
