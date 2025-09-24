<form wire:submit.prevent="saveChecklist" class="space-y-6">
    <div class="flex gap-4">
        <x-forms.input-label class="w-1/5" for="item_id">@lang('general.item')</x-forms.input-label>
        <x-forms.input-label class="w-4/5"
                             for="delivered_id">@lang('general.deliver-by')</x-forms.input-label>
    </div>

    @foreach ($allItems as $index => $item)

            <div class="flex gap-4">
                <div class="w-1/5 my-auto">

                    <x-forms.checkbox-input type="checkbox">{{$item->name}}</x-forms.checkbox-input>
                </div>

                <div class="w-4/5">

                    <x-forms.select-input id="delivered_id_{{ $index }}"
                                          wire:model.defer="checklists.{{ $index }}.delivered_id">
                        <option value="">-- @lang('general.select')</option>
                        @foreach ($delivereds as $delivered)
                            <option value="{{ $delivered->id }}">
                                {{ $delivered->name }}
                            </option>
                        @endforeach
                    </x-forms.select-input>
{{--                    <x-error-span>{{ $errors->first("checklistForm.checklists.$index.delivered_id") }}</x-error-span>--}}
                </div>




            </div>

    @endforeach



    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
