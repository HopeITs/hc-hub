<div class="space-y-6">
    @foreach ($form->checklists as $index => $item)
        <div wire:key="checklist-{{ $index }}" class="relative border-b pb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-3">
                    <x-forms.input-label for="checklist_item_id_{{ $index }}">@lang('general.item_name')</x-forms.input-label>
                    <x-forms.select-input wire:model="form.checklists.{{ $index }}.item_id" id="checklist_item_id_{{ $index }}">
                        <option value="">-- @lang('general.select') --</option>
                        @foreach ($checklistItems as $checklistItem)
                            <option value="{{ $checklistItem->id }}">{{ $checklistItem->name }}</option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('form.checklists.' . $index . '.item_id') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="checklist_brand_{{ $index }}">@lang('general.brand')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.checklists.{{ $index }}.brand" id="checklist_brand_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.checklists.' . $index . '.brand') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="checklist_model_{{ $index }}">@lang('general.model')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.checklists.{{ $index }}.model" id="checklist_model_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.checklists.' . $index . '.model') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="checklist_serial_{{ $index }}">@lang('general.serial')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.checklists.{{ $index }}.serial" id="checklist_serial_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.checklists.' . $index . '.serial') }}</x-error-span>
                </div>

                <div class="lg:col-span-3"><hr class="my-2"></div>

                <div>
                    <x-forms.input-label for="checklist_delivered_at_{{ $index }}">@lang('general.delivered_at')</x-forms.input-label>
                    <x-forms.input type="date" wire:model="form.checklists.{{ $index }}.delivered_at" id="checklist_delivered_at_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.checklists.' . $index . '.delivered_at') }}</x-error-span>
                </div>

                <div class="lg:col-span-2">
                    <x-forms.input-label for="checklist_delivered_status_{{ $index }}">@lang('general.delivered_status')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.checklists.{{ $index }}.delivered_status" id="checklist_delivered_status_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.checklists.' . $index . '.delivered_status') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="checklist_returned_at_{{ $index }}">@lang('general.returned_at')</x-forms.input-label>
                    <x-forms.input type="date" wire:model="form.checklists.{{ $index }}.returned_at" id="checklist_returned_at_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.checklists.' . $index . '.returned_at') }}</x-error-span>
                </div>

                <div class="lg:col-span-2">
                    <x-forms.input-label for="checklist_returned_status_{{ $index }}">@lang('general.returned_status')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.checklists.{{ $index }}.returned_status" id="checklist_returned_status_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.checklists.' . $index . '.returned_status') }}</x-error-span>
                </div>

                <div class="lg:col-span-3">
                    <x-forms.input-label for="checklist_notes_{{ $index }}">@lang('general.notes')</x-forms.input-label>
                    <x-forms.textarea wire:model="form.checklists.{{ $index }}.notes" id="checklist_notes_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.checklists.' . $index . '.notes') }}</x-error-span>
                </div>
            </div>
        </div>
    @endforeach
</div>
