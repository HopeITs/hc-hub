@php
    use App\Enums\LeaveTypeCases;
@endphp
<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
                :title="__('general.my-leaves')"
                :crumbs="[
                ['label' => __('general.leaves-management')],
                ['label' => __('general.leaves') ],
                ['label' => $title],
            ]"

        />

    </div>

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
                    @foreach($availableLeaves as $availableLeave)
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
                        <x-forms.input-label>Leave Unit</x-forms.input-label>
                        <div class="flex gap-4">
                            <x-forms.radio-input wire:model.live="form.unit" value="day">Day</x-forms.radio-input>
                            <x-forms.radio-input wire:model.live="form.unit" value="hour">Hour</x-forms.radio-input>
                        </div>
                    </div>
                @endif
            @endif
            @if($form->leave_balance_id === null || optional($availableLeaves->find($form->leave_balance_id))->leaveType->id !== LeaveTypeCases::Annual)

                <div>
                    <x-forms.input-label for="start_date">{{__('general.start-date')}}</x-forms.input-label>
                    <x-forms.date-input id="start_date" wire:model="form.start_date" label="Start Date"/>
                    <x-error-span>{{ $errors->first('form.alternative_employee') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="end_date">{{__('general.end-date')}}</x-forms.input-label>
                    <x-forms.date-input id="end_date" wire:model="form.end_date" label="End Date"/>
                    <x-error-span>{{ $errors->first('form.end_date') }}</x-error-span>
                </div>

            @else

                @if($form->unit === 'day')
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <x-forms.input-label for="start_date">{{__('general.start-date')}}</x-forms.input-label>
                            <x-forms.date-input id="start_date" wire:model="form.start_date" label="Start Date"/>
                            <x-error-span>{{ $errors->first('form.alternative_employee') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="end_date">{{__('general.end-date')}}</x-forms.input-label>
                            <x-forms.date-input id="end_date" wire:model="form.end_date" label="End Date"/>
                            <x-error-span>{{ $errors->first('form.end_date') }}</x-error-span>
                        </div>
                    </div>
                @elseif($form->unit === 'hour')

                    <div>
                        <x-forms.input-label for="city">{{__('general.leave-date')}}</x-forms.input-label>
                        <x-forms.date-input wire:model="form.leave_date" label="Leave Date"/>
                        <x-error-span>{{ $errors->first('form.leave_date') }}</x-error-span>
                    </div>

                    <div>
                        <x-forms.input-label for="start_time">{{__('general.start-time')}}</x-forms.input-label>
                        <x-forms.date-input id="start_time" type="time" wire:model="form.start_hour"
                                            label="Start Hour"/>
                        <x-error-span>{{ $errors->first('form.start_hour') }}</x-error-span>
                    </div>
                    <div>
                        <x-forms.input-label for="end_time">{{__('general.end-time')}}</x-forms.input-label>
                        <x-forms.date-input type="time" id="end_time" wire:model="form.end_hour" label="End Time"/>
                        <x-error-span>{{ $errors->first('form.end_hour') }}</x-error-span>
                    </div>

                @endif

            @endif
            <div class="col-span-full">
                <x-forms.input-label for="reason">{{__('general.reason')}}</x-forms.input-label>
                <x-forms.input  id="reason" wire:model="form.reason"/>
                <x-error-span>{{ $errors->first('form.reason') }}</x-error-span>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
            <x-primary-button :href="route('positions.index')"
                              class="!bg-secondary-light !w-auto">@lang('general.cancel')</x-primary-button>
        </div>
    </form>

</div>
