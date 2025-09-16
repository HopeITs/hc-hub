<form wire:submit.prevent="saveItems" class="space-y-6">
    @foreach ($itemForm->items as $index => $item)
        <div wire:key="item-{{ $index }}" class="border-b pb-4 mb-6 space-y-4">

            <div class="flex justify-end">
                <x-primary-button type="button" wire:click="removeItem({{ $index }}, {{ $item['id'] ?? 'null' }})">
                    <x-icons.trash />
                </x-primary-button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-forms.input-label for="item_{{ $index }}">@lang('general.item')</x-forms.input-label>
                    <select wire:model.defer="itemForm.items.{{ $index }}.item" id="item_{{ $index }}" class="w-full form-select">
                        <option value="">-- @lang('general.select') --</option>
                        @foreach([
                            '4G hotspot Adapter', '4G Router', 'Access Point', 'Audio Record',
                            'Cooling Pad', 'DVR', 'External Hard', 'HeadPhone', 'Hub',
                            'Keyboard', 'Landline Phone', 'Laptop', 'Laptop Bag', 'Laptop Charger',
                            'Memory', 'Mobile', 'Mobile Charger', 'Mouse', 'Number',
                            'Power Bank', 'Printer', 'Projector', 'Ram', 'Router',
                            'Screen', 'SD Reader', 'Security Camera', 'Smart Watch', 'Switch',
                            'Tab', 'Time Attendance Machine', 'Toner', 'USB Flash Memory', 'WebCam'
                        ] as $device)
                            <option value="{{ $device }}">{{ $device }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-forms.input-label for="brand_{{ $index }}">@lang('general.brand')</x-forms.input-label>
                    <x-forms.input type="text" id="brand_{{ $index }}" wire:model.defer="itemForm.items.{{ $index }}.brand"/>
                </div>

                <div>
                    <x-forms.input-label for="model_{{ $index }}">@lang('general.model')</x-forms.input-label>
                    <x-forms.input type="text" id="model_{{ $index }}" wire:model.defer="itemForm.items.{{ $index }}.model"/>
                </div>

                <div>
                    <x-forms.input-label for="serial_{{ $index }}">@lang('general.serial')</x-forms.input-label>
                    <x-forms.input type="text" id="serial_{{ $index }}" wire:model.defer="itemForm.items.{{ $index }}.serial_number"/>
                </div>

                <div>
                    <x-forms.input-label for="status_{{ $index }}">@lang('general.status')</x-forms.input-label>
                    <x-forms.input type="text" id="status_{{ $index }}" wire:model.defer="itemForm.items.{{ $index }}.status"/>
                </div>

                <div class="md:col-span-3">
                    <x-forms.input-label for="notes_{{ $index }}">@lang('general.notes')</x-forms.input-label>
                    <x-forms.textarea id="notes_{{ $index }}" wire:model.defer="itemForm.items.{{ $index }}.notes"/>
                </div>
            </div>
        </div>
    @endforeach

    <div class="flex justify-center">
        <x-primary-button type="button" wire:click="addItem">
            + @lang('general.add-item')
        </x-primary-button>
    </div>

    <div class="flex justify-end pt-4 mt-6 border-t space-x-2">
        <x-primary-button type="submit">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
    </div>
</form>

