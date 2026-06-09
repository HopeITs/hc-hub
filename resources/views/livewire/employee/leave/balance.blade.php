@php
    use App\Enums\LeaveTypeCases;
use App\Enums\LeaveRequestStatus;
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
        @activerole('HR', 'super-admin')
            <div class="flex items-center gap-2">
                <x-excel-export-button wire:click="$dispatchTo('employee.leave.emp-leave-balance-table', 'exportExcel')" />
            </div>
        @endactiverole
    </div>


    {{-- Table --}}
    <div class="mt-4">
        <livewire:employee.leave.emp-leave-balance-table key="table-balance"/>
    </div>


    <x-modal.dialog max-width="6xl" id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>

        <form wire:submit.prevent="save" class="space-y-4">
            <x-forms.errors-validation-list/>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="md:col-span-1">
                    <x-forms.input-label >{{__('general.name')}}</x-forms.input-label>
                    <p>{{$form->balance?->contract->employee->name}}</p>
                </div>
                <div class="md:col-span-2">
                    <x-forms.input-label >{{__('general.years-of-service')}}</x-forms.input-label>
                    <p>{{$form->balance?->contract->employee->years_of_service}}</p>
                </div>
                <div class="md:col-span-1">
                    <x-forms.input-label >{{__('general.deserved')}}</x-forms.input-label>
                    <p>{!! $form->balance?->deserved_formatted!!}</p>
                </div>
                <div class="md:col-span-1">
                    <x-forms.input-label >{{__('general.used')}}</x-forms.input-label>
                    <p>{!! $form->balance?->used_formatted!!}</p>
                </div>
                <div class="md:col-span-1">
                    <x-forms.input-label >{{__('general.remaining')}}</x-forms.input-label>
                    <p>{!!  $form->balance?->remaining!!}</p>
                </div>

                <div>
                    <x-forms.input-label>@lang('general.operation')</x-forms.input-label>
                    <div class="flex gap-x-4">
                        <x-forms.radio-input id="op_sum" name="operation" value="sum"
                                             wire:model="form.operation">{{__('general.sum')}}</x-forms.radio-input>
                        <x-forms.radio-input id="op_sub" name="operation" value="sub"
                                             wire:model="form.operation">{{__('general.sub')}}</x-forms.radio-input>

                    </div>

                    <x-error-span>{{ $errors->first('form.operation') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label>@lang('general.unit')</x-forms.input-label>
                    <div class="flex gap-x-4">
                        <x-forms.radio-input id="unit_hour" name="unit" value="hour"
                                             wire:model="form.unit">{{trans_choice('general.hour',1)}}</x-forms.radio-input>
                        <x-forms.radio-input id="unit_day" name="unit" value="day"
                                             wire:model="form.unit">{{trans_choice('general.day',1)}}</x-forms.radio-input>

                    </div>

                    <x-error-span>{{ $errors->first('form.unit') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="amount">@lang('general.amount')</x-forms.input-label>
                    <x-forms.input id="amount" wire:model="form.amount" type="numeric"/>
                    <x-error-span>{{ $errors->first('form.amount') }}</x-error-span>
                </div>
                <div>
                    <x-forms.checkbox-input type="checkbox"
                                            wire:model.live="form.add_badge">@lang('general.add-as-badge')</x-forms.checkbox-input>
                    <x-error-span>{{ $errors->first('form.add_badge') }}</x-error-span>
                </div>
                @if($form->add_badge)
                    <div class="col-span-2">
                        <x-forms.input-label for="note">@lang('general.note')</x-forms.input-label>
                        <x-forms.input id="note" wire:model="form.notes" :required="$form->add_badge"/>
                        <x-error-span>{{ $errors->first('form.notes') }}</x-error-span>
                    </div>
                @endif
            </div>
            <div class="flex justify-end gap-2">
                <x-primary-button type="button" wire:click="confirmSave"
                                  class="!w-auto">@lang('general.save')</x-primary-button>
                <x-primary-button :href="route('leaves.balance')"
                                  class="!bg-secondary-light !w-auto">@lang('general.cancel')</x-primary-button>
            </div>
        </form>
    </x-modal.dialog>
</div>
