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

        @activerole('HR','super-admin')
        <div>
            <x-primary-button wire:click="$emit('createChecklistItem')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
        @endactiverole
    </div>

    <livewire:basics.checklist-item-table />

    {{-- Modal --}}
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div>
                <x-forms.input-label for="name">@lang('general.name')</x-forms.input-label>
                <x-forms.input type="text" id="name" wire:model="item.name" />
                <x-error-span>{{ $errors->first('item.name') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="order">@lang('general.order')</x-forms.input-label>
                <x-forms.select-input id="order" wire:model="item.order">
                    @foreach([1,2,3] as $value)
                        @php $order = \App\Enums\ChecklistItemOrder::getLocalizedName($value); @endphp
                        <option value="{{ $value }}">{{ $order['value'] }}</option>
                    @endforeach
                </x-forms.select-input>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn />
            </div>
        </form>
    </x-modal.dialog>
</div>

@push('scripts')
    <script>
        Livewire.on('createChecklistItem', () => {
            Livewire.emit('createChecklistItemModal');
        });
    </script>
@endpush
