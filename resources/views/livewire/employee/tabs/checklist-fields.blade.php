<form wire:submit.prevent="saveChecklist" class="space-y-6">
    @foreach ($checklistForm->checklists as $index => $checklist)
        <input type="hidden" wire:model="checklistForm.checklists.{{ $index }}.id">

        <div wire:key="checklist-{{ $index }}" class="relative border-b pb-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2 flex justify-end">
                    <x-primary-button type="button"
                        wire:click="removeChecklist({{ $index }}, {{ $checklist['id'] ?? 'null' }})"
                        title="@lang('general.remove')">
                        <x-icons.trash />
                    </x-primary-button>
                </div>

                <div>
                    <x-forms.input-label for="item_id_{{ $index }}">@lang('general.item')</x-forms.input-label>
                    <x-forms.select-input id="item_id_{{ $index }}"
                        wire:model.defer="checklistForm.checklists.{{ $index }}.item_id">
                        <option value="">-- @lang('general.select') --</option>
                        @foreach ($allItems as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.item_id") }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="delivered_id_{{ $index }}">@lang('general.item')</x-forms.input-label>
                    <x-forms.select-input id="delivered_id_{{ $index }}"
                                          wire:model.defer="checklistForm.checklists.{{ $index }}.delivered_id">
                        <option value="">-- @lang('general.select') --</option>
                        @foreach ($delivereds as $delivered)
                            <option value="{{ $delivered->employee_id }}">{{ $delivered->employee->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.delivered_id") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="brand_{{ $index }}">@lang('general.brand')</x-forms.input-label>
                    <x-forms.input id="brand_{{ $index }}" type="text"
                        wire:model.defer="checklistForm.checklists.{{ $index }}.brand" />
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.brand") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="model_{{ $index }}">@lang('general.model')</x-forms.input-label>
                    <x-forms.input id="model_{{ $index }}" type="text"
                        wire:model.defer="checklistForm.checklists.{{ $index }}.model" />
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.model") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="serial_{{ $index }}">@lang('general.serial')</x-forms.input-label>
                    <x-forms.input id="serial_{{ $index }}" type="text"
                        wire:model.defer="checklistForm.checklists.{{ $index }}.serial" />
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.serial") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="delivered_at_{{ $index }}">@lang('general.delivered_at')</x-forms.input-label>
                    <x-forms.date-input id="delivered_at_{{ $index }}"
                        wire:model.defer="checklistForm.checklists.{{ $index }}.delivered_at" />
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.delivered_at") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label
                        for="delivered_status_{{ $index }}">@lang('general.delivered_status')</x-forms.input-label>
                    <x-forms.input id="delivered_status_{{ $index }}" type="text"
                        wire:model.defer="checklistForm.checklists.{{ $index }}.delivered_status" />
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.delivered_status") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="returned_at_{{ $index }}">@lang('general.returned_at')</x-forms.input-label>
                    <x-forms.date-input id="returned_at_{{ $index }}"
                        wire:model.defer="checklistForm.checklists.{{ $index }}.returned_at" />
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.returned_at") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label
                        for="returned_status_{{ $index }}">@lang('general.returned_status')</x-forms.input-label>
                    <x-forms.input id="returned_status_{{ $index }}" type="text"
                        wire:model.defer="checklistForm.checklists.{{ $index }}.returned_status" />
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.returned_status") }}</x-error-span>
                </div>

                <div class="md:col-span-2">
                    <x-forms.input-label for="notes_{{ $index }}">@lang('general.notes')</x-forms.input-label>
                    <x-forms.textarea id="notes_{{ $index }}"
                        wire:model.defer="checklistForm.checklists.{{ $index }}.notes"
                        rows="3"></x-forms.textarea>
                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.notes") }}</x-error-span>
                </div>
            </div>
        </div>
    @endforeach

    <div class="flex justify-center">
        <x-primary-button type="button" wire:click="addChecklist">
            + @lang('general.add-new')
        </x-primary-button>
    </div>

    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
