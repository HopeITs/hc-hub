@php
    use App\Enums\ContractPlace;use App\Enums\ContractType;
@endphp
<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.contract-type',2)"
            :crumbs="[
        ['label' => __('general.project-basics')],
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

            <x-table.heading sortable field="type" wire:click="sortBy('name')">
                @lang('general.name')(AR)
                @if ($sortField === 'name')
                    @if ($sortDirection === 'asc')  @endif
                @endif
            </x-table.heading>
            <x-table.heading sortable field="type" wire:click="sortBy('name')">
                @lang('general.name')(EN)
                @if ($sortField === 'name')
                    @if ($sortDirection === 'asc')  @endif
                @endif
            </x-table.heading>
            <x-table.heading>@lang('general.place')</x-table.heading>
            <x-table.heading sortable>@lang('general.weekly_hours')</x-table.heading>
            <x-table.heading/>
        </x-slot>
        <x-slot name="body">
            @forelse($items as $item)
                @php
                    $place = ContractPlace::getLocalizedName($item->place);
                                   $type = ContractType::getLocalizedName($item->type);
                @endphp
                <x-table.row>
                    <x-table.cell>{{$item->id}}</x-table.cell>

                    <x-table.cell>{{$item->getTranslation('name' , 'ar')}}</x-table.cell>
                    <x-table.cell>{{$item->getTranslation('name' , 'en')}}</x-table.cell>
                    <x-table.cell>
                        <x-badge :color="$place['color']"> {{ $place['value'] }}</x-badge>
                    </x-table.cell>

                    <x-table.cell>
                        <x-badge :color="$type['color']"> {{ $type['value'] }}</x-badge>
                    </x-table.cell>

                    <x-table.cell>
                        <x-table.edit-btn wire:click="edit({{$item->id}})"></x-table.edit-btn>
                        <x-table.delete-btn :modelId="$item->id"/>
                    </x-table.cell>
                </x-table.row>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>
        <x-slot name="pagination">
            {{$items->links()}}
        </x-slot>
    </x-table>


    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>

        <form class="space-y-4" wire:submit.prevent="save">
            <div class="grid grid-cols-2 space-x-2 space-y-4">
                @foreach(config('app.supported_locales') as $locale)
                    <div class="col-span-1">
                        <x-forms.input-label for="name_{{ $locale }}">@lang('general.name')({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.input type="text" id="name_{{ $locale }}"
                                       wire:model="item.name.{{ $locale }}"/>
                        <x-error-span>{{$errors->first('item.name.'.$locale)}}</x-error-span>
                    </div>
                @endforeach
                <div>
                    <x-forms.input-label for="type_{{ $locale }}">@lang('general.type')</x-forms.input-label>
                    <div class="flex space-x-2">
                        <x-forms.radio-input id="full" name="type" value="{{ContractType::FullTime}}"
                                             wire:model="item.type">{{ContractType::getLocalizedName(ContractType::FullTime)['value']}}</x-forms.radio-input>
                        <x-forms.radio-input id="part" name="type" value="{{ContractType::PartTime}}"
                                             wire:model="item.type">{{ContractType::getLocalizedName(ContractType::PartTime)['value']}}</x-forms.radio-input>
                        <x-forms.radio-input id="freelance" name="type" value="{{ContractType::Freelance}}"
                                             wire:model="item.type">{{ContractType::getLocalizedName(ContractType::Freelance)['value']}}</x-forms.radio-input>

                    </div>
                    <x-error-span>{{$errors->first('item.type')}}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="place_{{ $locale }}">@lang('general.place')</x-forms.input-label>
                    <div class="flex space-x-2">
                        <x-forms.radio-input id="onsite" name="place" value="{{ContractPlace::Onsite}}"
                                             wire:model="item.place">{{ContractPlace::getLocalizedName(ContractPlace::Onsite)['value']}}</x-forms.radio-input>
                        <x-forms.radio-input id="online" name="place" value="{{ContractPlace::Online}}"
                                             wire:model="item.place">{{ContractPlace::getLocalizedName(ContractPlace::Online)['value']}}</x-forms.radio-input>

                    </div>
                    <x-error-span>{{$errors->first('item.place')}}</x-error-span>
                </div>


            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>

</div>
