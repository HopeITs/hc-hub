@php
    use App\Enums\ContractPlace;
@endphp
<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.contract-type',2)"
            :crumbs="[
        ['label' => __('general.dashboard')],
        ['label' => trans_choice('general.contract-type',2)],
    ]"
            :showButton="true"

        />
        <div>
            <x-primary-button wire:click="create">
                @lang('general.add_new')
            </x-primary-button>
        </div>

    </div>




    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading sortable> @lang('general.type')</x-table.heading>
            <x-table.heading>@lang('general.place')</x-table.heading>
            <x-table.heading sortable>@lang('general.weekly_hours')</x-table.heading>
            <x-table.heading/>
        </x-slot>
        <x-slot name="body">
            @forelse($items as $item)
                @php
                    $place = ContractPlace::getLocalizedName($item->place);
                @endphp
                <tr>
                    <x-table.cell>{{$item->id}}</x-table.cell>

                    <x-table.cell>{{$item->type}}</x-table.cell>

                    <x-table.cell>
                        <x-badge :color="$place['color']"> {{ $place['value'] }}</x-badge>
                    </x-table.cell>
                    <x-table.cell>{{$item->weekly_hours}}</x-table.cell>
                    <x-table.cell>
                        <x-table.edit-btn wire:click="edit({{$item->id}})"></x-table.edit-btn>
                        <x-table.delete-btn :modelId="$item->id"/>
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>
        <x-slot name="pagination">
            {{$items->links()}}
        </x-slot>
    </x-table>


{{--    Edit Modal--}}
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>


        <form class="space-y-4" wire:submit.prevent="save">
            <div class="grid grid-cols-2 space-x-2 space-y-4">
                @foreach(config('app.supported_locales') as $locale)
                    <div class="col-span-1">
                        <x-forms.input-label for="type_{{ $locale }}">@lang('general.type')({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.input type="text" id="type_{{ $locale }}"
                                      wire:model="item.type.{{ $locale }}"/>
                        <x-error-span>{{$errors->first('item.type.'.$locale)}}</x-error-span>
                    </div>
                @endforeach
                <div class="col-span-1">
                    <x-forms.input-label for="weekly_hours">@lang('general.weekly_hours')</x-forms.input-label>
                    <x-forms.input type="number" id="weekly_hours" wire:model="item.weekly_hours"/>
                    <x-error-span>{{$errors->first('item.weekly_hours')}}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="type_{{ $locale }}">@lang('general.place')</x-forms.input-label>
                    <div class="flex space-x-2">
                        <x-forms.radio-input id="onsite" name="place" value="{{ContractPlace::Onsite}}"
                                       wire:model="item.place">{{ContractPlace::getLocalizedName(ContractPlace::Onsite)['value']}}</x-forms.radio-input>
                        <x-forms.radio-input id="online" name="place" value="{{ContractPlace::Online}}"
                                       wire:model="item.place">{{ContractPlace::getLocalizedName(ContractPlace::Online)['value']}}</x-forms.radio-input>
                    </div>
                </div>


            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-primary-button type="button" wire:click="cancel" class="!bg-secondary-light !w-auto">@lang('general.cancel')</x-primary-button>
            </div>
        </form>
    </x-modal.dialog>


{{--    Create Modal --}}


</div>
