@php
    use App\Enums\LeaveType;
@endphp
<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.leave-type', 2)"
            :crumbs="[
                 ['label' => __('general.project-basics')],
                ['label' => trans_choice('general.leave-type', 2)],
            ]"
            :showButton="true"
        />
        <div>
            <x-primary-button wire:click="create" :showSpinner="false">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading
                wire:click="sortBy('id')"
                sortable
                :direction="$sortField === 'id' ? $sortDirection : null">
                ID
            </x-table.heading>

            <x-table.heading>
                @lang('general.name')
            </x-table.heading>

            <x-table.heading
                wire:click="sortBy('base_days')"
                sortable
                :direction="$sortField === 'base_days' ? $sortDirection : null">
                @lang('general.base-days')
            </x-table.heading>

            <x-table.heading
                wire:click="sortBy('allowed_gender')"
                sortable
                :direction="$sortField === 'allowed_gender' ? $sortDirection : null">
                @lang('general.allowed-gender')
            </x-table.heading>

            <x-table.heading
                wire:click="sortBy('marital_status')"
                sortable
                :direction="$sortField === 'marital_status' ? $sortDirection : null">
                @lang('general.marital_status')
            </x-table.heading>

            <x-table.heading />
        </x-slot>


        <x-slot name="body">
            @forelse($items as $item)
                @php
                    $gender = LeaveType::getGender($item->allowed_gender);
                    $marital = LeaveType::getMaritalStatus($item->marital_status);
                @endphp
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>
                    <x-table.cell>{{ $item->name }}</x-table.cell>
                    <x-table.cell>{{ $item->base_days }}</x-table.cell>
                    <x-table.cell>
                        <x-badge :color="$gender['color']">{{ $gender['value'] }}</x-badge>
                    </x-table.cell>
                    <x-table.cell>
                        <x-badge :color="$marital['color']">{{ $marital['value'] }}</x-badge>
                    </x-table.cell>
                    <x-table.cell>
                        <x-table.edit-btn wire:click="edit({{ $item->id }})"/>
                        <x-table.delete-btn :modelId="$item->id"/>
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>

        <x-slot name="pagination">
            {{ $items->links() }}
        </x-slot>
    </x-table>


    {{-- Modal --}}
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                @foreach (config('app.supported_locales') as $locale)
                    <div>
                        <x-forms.input-label for="name_{{ $locale }}">
                            @lang('general.name') ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.input type="text" wire:model="item.name_{{ $locale }}" id="name_{{ $locale }}"/>
                        <x-error-span>{{ $errors->first('item.name_' . $locale) }}</x-error-span>
                    </div>
                @endforeach

                <div>
                    <x-forms.input-label for="base_days">@lang('general.base-days')</x-forms.input-label>
                    <x-forms.input type="number" wire:model="item.base_days" id="base_days"/>
                    <x-error-span>{{ $errors->first('item.base_days') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label>@lang('general.allowed-gender')</x-forms.input-label>
                    <div class="flex justify-between">
                        <x-forms.radio-input id="any_gender" name="allowed_gender" value="{{LeaveType::GENDER_ANY}}"
                                             wire:model="item.allowed_gender">{{LeaveType::getGender(LeaveType::GENDER_ANY)['value']}}</x-forms.radio-input>
                        <x-forms.radio-input id="male" name="allowed_gender" value="{{LeaveType::GENDER_MALE}}"
                                             wire:model="item.allowed_gender">{{LeaveType::getGender(LeaveType::GENDER_MALE)['value']}}</x-forms.radio-input>
                        <x-forms.radio-input id="female" name="allowed_gender" value="{{LeaveType::GENDER_FEMALE}}"
                                             wire:model="item.allowed_gender">{{LeaveType::getGender(LeaveType::GENDER_FEMALE)['value']}}</x-forms.radio-input>
                    </div>

                    <x-error-span>{{ $errors->first('item.allowed_gender') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label>@lang('general.marital_status')</x-forms.input-label>
                    <div class="flex justify-between">
                        <x-forms.radio-input id="any_status" name="marital_status"
                                             value="{{LeaveType::MARITAL_STATUS_ANY}}"
                                             wire:model="item.marital_status">{{LeaveType::getMaritalStatus(LeaveType::MARITAL_STATUS_ANY)['value']}}</x-forms.radio-input>
                        <x-forms.radio-input id="single" name="marital_status"
                                             value="{{LeaveType::MARITAL_STATUS_SINGLE}}"
                                             wire:model="item.marital_status">{{LeaveType::getMaritalStatus(LeaveType::MARITAL_STATUS_SINGLE)['value']}}</x-forms.radio-input>
                        <x-forms.radio-input id="married" name="marital_status"
                                             value="{{LeaveType::MARITAL_STATUS_MARRIED}}"
                                             wire:model="item.marital_status">{{LeaveType::getMaritalStatus(LeaveType::MARITAL_STATUS_MARRIED)['value']}}</x-forms.radio-input>
                    </div>

                    <x-error-span>{{ $errors->first('item.marital_status') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>
</div>
