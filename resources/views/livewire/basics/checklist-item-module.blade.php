@php
    use App\Enums\ChecklistItemOrder;
@endphp
<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.checklist-items',2)"
            :crumbs="[
        ['label' => __('general.project-basics')],
        ['label' => trans_choice('general.checklist-items',2)],
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
            <x-table.heading
                sortable
                wire:click="sortBy('name')"
                :direction="$sortField === 'name' ? $sortDirection : null">
                @lang('general.name')
            </x-table.heading>
            <x-table.heading
                sortable
                wire:click="sortBy('order')"
                :direction="$sortField === 'order' ? $sortDirection : null">
                @lang('general.order')
            </x-table.heading>
            <x-table.heading/>
        </x-slot>
        <x-slot name="body">
            @forelse($items as $item)
                @php
                    $order = ChecklistItemOrder::getLocalizedName($item->order);
                @endphp
                <tr>
                    <x-table.cell>{{$item->id}}</x-table.cell>

                    <x-table.cell>{{$item->name}}</x-table.cell>

                    <x-table.cell>
                        <x-badge :color="$order['color']"> {{ $order['value'] }}</x-badge>
                    </x-table.cell>

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
            <div class="grid grid-cols-1 space-x-2 space-y-4">
                <div class="col-span-1">
                    <x-forms.input-label for="name">@lang('general.name')</x-forms.input-label>
                    <x-forms.input type="text" id="name" wire:model="item.name"/>
                    <x-error-span>{{$errors->first('item.name')}}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="order">@lang('general.order')</x-forms.input-label>
                    <x-forms.select-input id="order" wire:model="item.order">
                        <option value="{{ChecklistItemOrder::ProbationContract}}">{{ChecklistItemOrder::getLocalizedName(ChecklistItemOrder::ProbationContract)['value']}}</option>
                        <option value="{{ChecklistItemOrder::AnnualContract}}">{{ChecklistItemOrder::getLocalizedName(ChecklistItemOrder::AnnualContract)['value']}}</option>
                        <option value="{{ChecklistItemOrder::Resignation}}">{{ChecklistItemOrder::getLocalizedName(ChecklistItemOrder::Resignation)['value']}}</option>
                    </x-forms.select-input>

                </div>


            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>


    {{--    Create Modal --}}


</div>
