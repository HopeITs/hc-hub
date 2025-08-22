<div x-data="{
    nextIndex: {{ count($checklistItemForm->checklists) }},
    add() {
        const template = this.$refs.checklistTemplate.innerHTML.replaceAll('__INDEX__', this.nextIndex);
        this.$refs.checklistContainer.insertAdjacentHTML('beforeend', template);
        this.nextIndex++;
    },
    remove(index) {
        if (confirm('Are you sure you want to remove this item?')) {
            document.getElementById('checklist-row-' + index)?.remove();
            $wire.removeChecklist(index);
        }
    }
}">
    <form wire:submit.prevent="saveChecklist" class="space-y-6">
        <div x-ref="checklistContainer">
            @foreach ($checklistItemForm->checklists as $index => $item)
                <div wire:key="checklist-{{ $index }}" id="checklist-row-{{ $index }}"
                    class="relative border-b pb-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="lg:col-span-3">
                            <x-forms.input-label
                                for="checklist_item_id_{{ $index }}">@lang('general.item_name')</x-forms.input-label>
                            <x-forms.select-input wire:model="checklistItemForm.checklists.{{ $index }}.item_id"
                                id="checklist_item_id_{{ $index }}">
                                <option value="">-- @lang('general.select') --</option>
                                @foreach ($checklistItems as $checklistItem)
                                    <option value="{{ $checklistItem->id }}">{{ $checklistItem->name }}</option>
                                @endforeach
                            </x-forms.select-input>
                        </div>
                        <div>
                            <x-forms.input-label
                                for="checklist_brand_{{ $index }}">@lang('general.brand')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="checklistItemForm.checklists.{{ $index }}.brand"
                                id="checklist_brand_{{ $index }}" />
                        </div>
                        <div>
                            <x-forms.input-label
                                for="checklist_model_{{ $index }}">@lang('general.model')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="checklistItemForm.checklists.{{ $index }}.model"
                                id="checklist_model_{{ $index }}" />
                        </div>
                        <div>
                            <x-forms.input-label
                                for="checklist_serial_{{ $index }}">@lang('general.serial')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="checklistItemForm.checklists.{{ $index }}.serial"
                                id="checklist_serial_{{ $index }}" />
                        </div>
                        <div>
                            <x-forms.input-label
                                for="checklist_delivered_at_{{ $index }}">@lang('general.delivered_at')</x-forms.input-label>
                            <x-forms.input type="date" wire:model="checklistItemForm.checklists.{{ $index }}.delivered_at"
                                id="checklist_delivered_at_{{ $index }}" />
                        </div>
                        <div class="lg:col-span-2">
                            <x-forms.input-label
                                for="checklist_delivered_status_{{ $index }}">@lang('general.delivered_status')</x-forms.input-label>
                            <x-forms.input type="text"
                                wire:model="checklistItemForm.checklists.{{ $index }}.delivered_status"
                                id="checklist_delivered_status_{{ $index }}" />
                        </div>
                        <div>
                            <x-forms.input-label
                                for="checklist_returned_at_{{ $index }}">@lang('general.returned_at')</x-forms.input-label>
                            <x-forms.input type="date" wire:model="checklistItemForm.checklists.{{ $index }}.returned_at"
                                id="checklist_returned_at_{{ $index }}" />
                        </div>
                        <div class="lg:col-span-2">
                            <x-forms.input-label
                                for="checklist_returned_status_{{ $index }}">@lang('general.returned_status')</x-forms.input-label>
                            <x-forms.input type="text"
                                wire:model="checklistItemForm.checklists.{{ $index }}.returned_status"
                                id="checklist_returned_status_{{ $index }}" />
                        </div>
                        <div class="lg:col-span-3">
                            <x-forms.input-label
                                for="checklist_notes_{{ $index }}">@lang('general.notes')</x-forms.input-label>
                            <x-forms.textarea wire:model="checklistItemForm.checklists.{{ $index }}.notes"
                                id="checklist_notes_{{ $index }}" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <template x-ref="checklistTemplate">
            <div id="checklist-row-__INDEX__" class="relative border-b pb-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-3">
                        <x-forms.input-label for="checklist_item_id___INDEX__">@lang('general.item_name')</x-forms.input-label>
                        <x-forms.select-input wire:model="checklistItemForm.checklists.__INDEX__.item_id"
                            id="checklist_item_id___INDEX__">
                            <option value="">-- @lang('general.select') --</option>
                            @foreach ($checklistItems as $checklistItem)
                                <option value="{{ $checklistItem->id }}">{{ $checklistItem->name }}</option>
                            @endforeach
                        </x-forms.select-input>
                    </div>
                    <div>
                        <x-forms.input-label for="checklist_brand___INDEX__">@lang('general.brand')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="checklistItemForm.checklists.__INDEX__.brand"
                            id="checklist_brand___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label for="checklist_model___INDEX__">@lang('general.model')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="checklistItemForm.checklists.__INDEX__.model"
                            id="checklist_model___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label for="checklist_serial___INDEX__">@lang('general.serial')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="checklistItemForm.checklists.__INDEX__.serial"
                            id="checklist_serial___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label
                            for="checklist_delivered_at___INDEX__">@lang('general.delivered_at')</x-forms.input-label>
                        <x-forms.input type="date" wire:model="checklistItemForm.checklists.__INDEX__.delivered_at"
                            id="checklist_delivered_at___INDEX__" />
                    </div>
                    <div class="lg:col-span-2">
                        <x-forms.input-label
                            for="checklist_delivered_status___INDEX__">@lang('general.delivered_status')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="checklistItemForm.checklists.__INDEX__.delivered_status"
                            id="checklist_delivered_status___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label
                            for="checklist_returned_at___INDEX__">@lang('general.returned_at')</x-forms.input-label>
                        <x-forms.input type="date" wire:model="checklistItemForm.checklists.__INDEX__.returned_at"
                            id="checklist_returned_at___INDEX__" />
                    </div>
                    <div class="lg:col-span-2">
                        <x-forms.input-label
                            for="checklist_returned_status___INDEX__">@lang('general.returned_status')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="checklistItemForm.checklists.__INDEX__.returned_status"
                            id="checklist_returned_status___INDEX__" />
                    </div>
                    <div class="lg:col-span-3">
                        <x-forms.input-label for="checklist_notes___INDEX__">@lang('general.notes')</x-forms.input-label>
                        <x-forms.textarea wire:model="checklistItemForm.checklists.__INDEX__.notes" id="checklist_notes___INDEX__" />
                    </div>
                </div>
            </div>
        </template>

        <div><button type="button" @click="add" class="px-4 py-2 rounded bg-primary text-white hover:opacity-90">+
                @lang('general.add_new')</button></div>
        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
        </div>
    </form>
</div>
