@php
    use App\Enums\LeaveType;
@endphp

<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="trans_choice('general.leave-type', 2)" :crumbs="[['label' => __('general.dashboard')], ['label' => trans_choice('general.leave-type', 2)]]" />
        <div>
            <x-primary-button wire:click="create">
                @lang('general.add_new')
            </x-primary-button>
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading sortable>@lang('general.name')</x-table.heading>
            <x-table.heading>@lang('general.base_days')</x-table.heading>
            <x-table.heading>@lang('general.allowed_gender')</x-table.heading>
            <x-table.heading>@lang('general.marital_status')</x-table.heading>
            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse($leaveTypes as $item)
                @php
                    $gender = LeaveType::getGender($item->allowed_gender ?? 0);
                    $maritalStatus = LeaveType::getMaritalStatus($item->marital_status ?? 0);
                @endphp
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>
                    <x-table.cell>{{ $item->name }}</x-table.cell>
                    <x-table.cell>{{ $item->base_days }}</x-table.cell>
                    <x-table.cell>
                        <x-badge :color="$gender['color']">{{ $gender['value'] }}</x-badge>
                    </x-table.cell>
                    <x-table.cell>
                        <x-badge :color="$maritalStatus['color']">{{ $maritalStatus['value'] }}</x-badge>
                    </x-table.cell>
                    <x-table.cell>
                        <x-table.edit-btn wire:click="edit({{ $item->id }})"></x-table.edit-btn>
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data />
            @endforelse
        </x-slot>

        <x-slot name="pagination">
            {{ $leaveTypes->links() }}
        </x-slot>
    </x-table>

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form class="space-y-6" wire:submit.prevent="save">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach (config('app.supported_locales') as $locale)
                    <div>
                        <x-input-label for="name_{{ $locale }}">@lang('general.name')
                            ({{ strtoupper($locale) }})</x-input-label>
                        <x-text-input type="text" id="name_{{ $locale }}" class="mt-1 block w-full"
                            wire:model.defer="item.name.{{ $locale }}" />
                        <x-error-span>{{ $errors->first('item.name.' . $locale) }}</x-error-span>
                    </div>
                @endforeach
            </div>

            <div>
                <x-input-label for="base_days">@lang('general.base_days')</x-input-label>
                <x-text-input type="number" id="base_days" class="mt-1 block w-full"
                    wire:model.defer="item.base_days" />
                <x-error-span>{{ $errors->first('item.base_days') }}</x-error-span>
            </div>

            <div>
                <x-input-label>@lang('general.allowed_gender')</x-input-label>
                <div class="flex space-x-4 mt-2">
                    <x-radio-input name="allowed_gender" value="0"
                        wire:model.defer="item.allowed_gender">{{ LeaveType::getGender(0)['value'] }}</x-radio-input>
                    <x-radio-input name="allowed_gender" value="1"
                        wire:model.defer="item.allowed_gender">{{ LeaveType::getGender(1)['value'] }}</x-radio-input>
                    <x-radio-input name="allowed_gender" value="2"
                        wire:model.defer="item.allowed_gender">{{ LeaveType::getGender(2)['value'] }}</x-radio-input>
                </div>
                <x-error-span>{{ $errors->first('item.allowed_gender') }}</x-error-span>
            </div>

            <div>
                <x-input-label>@lang('general.marital_status')</x-input-label>
                <div class="flex space-x-4 mt-2">
                    <x-radio-input name="marital_status" value="0"
                        wire:model.defer="item.marital_status">{{ LeaveType::getMaritalStatus(0)['value'] }}</x-radio-input>
                    <x-radio-input name="marital_status" value="1"
                        wire:model.defer="item.marital_status">{{ LeaveType::getMaritalStatus(1)['value'] }}</x-radio-input>
                    <x-radio-input name="marital_status" value="2"
                        wire:model.defer="item.marital_status">{{ LeaveType::getMaritalStatus(2)['value'] }}</x-radio-input>
                </div>
                <x-error-span>{{ $errors->first('item.marital_status') }}</x-error-span>
            </div>

            <div class="flex justify-end space-x-2 pt-4">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-primary-button type="button" wire:click="cancel"
                    class="!bg-secondary-light !w-auto">@lang('general.cancel')</x-primary-button>
            </div>
        </form>
    </x-modal.dialog>
</div>
