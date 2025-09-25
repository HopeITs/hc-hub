<form wire:submit.prevent="saveChecklist" class="space-y-6">
    <div class="space-y-4">
        @foreach ($checklistForm->items as $index => $item)
            <div wire:key="checklist-item-{{ $item['item_id'] }}" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 items-center @if(!$loop->last) border-b pb-4 @endif">

                <div class="flex items-center space-x-3 pt-2">
                    <input type="checkbox" id="item_selected_{{ $index }}"
                           wire:model.live="checklistForm.items.{{ $index }}.is_selected"
                           class="h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="item_selected_{{ $index }}" class="font-medium text-gray-800">{{ $item['item_name'] }}</label>
                    <input type="hidden" wire:model="checklistForm.items.{{ $index }}.item_id">
                </div>

                <div>
                    @if ($checklistForm->items[$index]['is_selected'])
                        <x-forms.select-input id="delivered_id_{{ $index }}"
                                              wire:model.defer="checklistForm.items.{{ $index }}.delivered_id">
                            <option value="">-- @lang('general.deliver-by') --</option>
                            @foreach ($delivereds as $delivered)
                                <option value="{{ $delivered->id }}" @selected($delivered->id === $item[$index]->defaultDeliverable()->first()->employee_id)>{{ $delivered->name }}</option>
                            @endforeach
                        </x-forms.select-input>
                    @endif
                </div>

            </div>
        @endforeach
    </div>

    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
