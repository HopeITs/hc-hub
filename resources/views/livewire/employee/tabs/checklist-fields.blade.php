<form wire:submit.prevent="saveChecklist" class="space-y-6">
    <div class="space-y-4">
        @foreach ($checklistForm->items as $index => $item)
            @php
                $isDelivered = ($item['delivered_status'] ?? null) === 'delivered';
                $isReturned = ($item['returned_status'] ?? null) === 'received';
                $canEdit = $item['can_edit'] ?? true;
            @endphp

            <div wire:key="checklist-item-{{ $item['item_id'] }}"
                class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 items-center @if (!$loop->last) border-b pb-4 @endif">

                <div class="flex items-center space-x-3 pt-2">
                    <input type="checkbox"
                        id="item_selected_{{ $index }}"
                        wire:model.live="checklistForm.items.{{ $index }}.is_selected"
                        class="h-5 w-5 rounded border-neutral-300 text-primary focus:ring-primary"
                        @if (!$canEdit) disabled @endif>
                    <label for="item_selected_{{ $index }}" class="font-medium text-neutral-800">
                        {{ $item['item_name'] }}
                    </label>
                    <input type="hidden" wire:model="checklistForm.items.{{ $index }}.item_id">
                </div>

                <div>
                    <x-forms.select2-input
                        id="delivered_id_{{ $index }}"
                        wire:model.defer="checklistForm.items.{{ $index }}.delivered_id"
                        :disabled="!$checklistForm->items[$index]['is_selected'] || !$canEdit">
                        <option value="">-- @lang('general.deliver-by') --</option>
                        @foreach ($delivereds as $delivered)
                            <option value="{{ $delivered->id }}">{{ $delivered->name }}</option>
                        @endforeach
                    </x-forms.select2-input>
                </div>

                @if ($isDelivered && !$isReturned)
                    <div>
                        <x-forms.select2-input
                            id="receiver_id_{{ $index }}"
                            wire:model.defer="checklistForm.items.{{ $index }}.receiver_id"
                            :disabled="!$checklistForm->items[$index]['is_selected']">
                            <option value="">-- @lang('general.receive-by') --</option>
                            @foreach ($delivereds as $receiver)
                                <option value="{{ $receiver->id }}">{{ $receiver->name }}</option>
                            @endforeach
                        </x-forms.select2-input>
                    </div>
                @endif

            </div>
        @endforeach
    </div>

    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit">@lang('general.save-checklist')</x-primary-button>
        <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
