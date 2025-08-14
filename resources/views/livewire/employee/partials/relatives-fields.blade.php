<div class="space-y-6">
    @foreach ($form->relatives as $index => $relative)
        <div wire:key="relative-{{ $index }}" class="relative">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <x-forms.input-label for="relative_name_{{ $index }}">@lang('general.name')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.relatives.{{ $index }}.name"
                        id="relative_name_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.relatives.' . $index . '.name') }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="relative_phone_{{ $index }}">@lang('general.phone')</x-forms.input-label>
                    <x-forms.input type="tel" wire:model="form.relatives.{{ $index }}.phone"
                        id="relative_phone_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.relatives.' . $index . '.phone') }}</x-error-span>
                </div>

                <div class="md:col-span-2">
                    <x-forms.input-label>@lang('general.relation')</x-forms.input-label>

                    @php
                        $relations = ['father', 'mother', 'brother', 'sister', 'husband', 'wife', 'son', 'daughter', 'other'];
                    @endphp

                    <div class="flex flex-wrap gap-4 mt-2">
                        @foreach ($relations as $relation)
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="relative_{{ $index }}_relation"
                                    wire:model="form.relatives.{{ $index }}.relation"
                                    value="{{ $relation }}" class="text-primary focus:ring-primary">
                                <span>@lang('general.relations.' . $relation)</span>
                            </label>
                        @endforeach
                    </div>

                    <x-error-span>{{ $errors->first('form.relatives.' . $index . '.relation') }}</x-error-span>
                </div>
            </div>
        </div>
    @endforeach

    <div>
        <button
            type="button"
            wire:click="form.addRelative"
            class="px-4 py-2 rounded bg-primary text-white hover:opacity-90">
            + @lang('general.add_new')
        </button>
    </div>
</div>
