<div x-data="{
    nextIndex: {{ count($relativeForm->relatives) }},
    add() {
        const template = this.$refs.relativeTemplate.innerHTML.replaceAll('__INDEX__', this.nextIndex);
        this.$refs.relativeContainer.insertAdjacentHTML('beforeend', template);
        this.nextIndex++;
    },
    remove(index) {
        if (confirm('Are you sure you want to remove this item?')) {
            document.getElementById('relative-row-' + index)?.remove();
            $wire.removeRelative(index);
        }
    }
}">
    <form wire:submit.prevent="saveRelatives" class="space-y-6">
        <div x-ref="relativeContainer">
            @foreach ($relativeForm->relatives as $index => $relative)
                <div wire:key="relative-{{ $index }}" id="relative-row-{{ $index }}" class="relative border-b pb-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-forms.input-label for="relative_name_{{ $index }}">@lang('general.name')</x-forms.input-label>
                            <x-forms.input type="text" wire:model="relativeForm.relatives.{{ $index }}.name" id="relative_name_{{ $index }}" />
                            <x-error-span>{{ $errors->first('relativeForm.relatives.' . $index . '.name') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="relative_phone_{{ $index }}">@lang('general.phone')</x-forms.input-label>
                            <x-forms.input type="tel" wire:model="relativeForm.relatives.{{ $index }}.phone" id="relative_phone_{{ $index }}" />
                            <x-error-span>{{ $errors->first('relativeForm.relatives.' . $index . '.phone') }}</x-error-span>
                        </div>
                        <div class="md:col-span-2">
                            <x-forms.input-label>@lang('general.relation')</x-forms.input-label>
                            @php
                                $relations = ['father', 'mother', 'brother', 'sister', 'husband', 'wife', 'son', 'daughter', 'other'];
                            @endphp
                            <div class="flex flex-wrap gap-4 mt-2">
                                @foreach ($relations as $relation)
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="relative_{{ $index }}_relation" wire:model="relativeForm.relatives.{{ $index }}.relation" value="{{ $relation }}" class="text-primary focus:ring-primary">
                                        <span>@lang('general.relations.' . $relation)</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <template x-ref="relativeTemplate">
            <div class="relative border-b pb-6 mb-6" id="relative-row-__INDEX__">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-forms.input-label for="relative_name___INDEX__">@lang('general.name')</x-forms.input-label>
                        <x-forms.input type="text" wire:model="relativeForm.relatives.__INDEX__.name" id="relative_name___INDEX__" />
                    </div>
                    <div>
                        <x-forms.input-label for="relative_phone___INDEX__">@lang('general.phone')</x-forms.input-label>
                        <x-forms.input type="tel" wire:model="relativeForm.relatives.__INDEX__.phone" id="relative_phone___INDEX__" />
                    </div>
                    <div class="md:col-span-2">
                        <x-forms.input-label>@lang('general.relation')</x-forms.input-label>
                        <div class="flex flex-wrap gap-4 mt-2">
                            @foreach ($relations as $relation)
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="relative___INDEX___relation" wire:model="relativeForm.relatives.__INDEX__.relation" value="{{ $relation }}" class="text-primary focus:ring-primary">
                                    <span>@lang('general.relations.' . $relation)</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div><button type="button" @click="add" class="px-4 py-2 rounded bg-primary text-white hover:opacity-90">+ @lang('general.add_new')</button></div>
        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
        </div>
    </form>
</div>
