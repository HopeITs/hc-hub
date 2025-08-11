<div class="space-y-6">
    @foreach ($form->relatives as $index => $relative)
        <div wire:key="relative-{{ $index }}" class="relative">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-forms.input-label for="relative_name_{{ $index }}">@lang('general.name')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.relatives.{{ $index }}.name" id="relative_name_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.relatives.' . $index . '.name') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="relative_phone_{{ $index }}">@lang('general.phone')</x-forms.input-label>
                    <x-forms.input type="tel" wire:model="form.relatives.{{ $index }}.phone" id="relative_phone_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.relatives.' . $index . '.phone') }}</x-error-span>
                </div>
                <div class="md:col-span-2">
                    <x-forms.input-label for="relative_relation_{{ $index }}">@lang('general.relation')</x-forms.input-label>
                    <x-forms.input type="text" wire:model="form.relatives.{{ $index }}.relation" id="relative_relation_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.relatives.' . $index . '.relation') }}</x-error-span>
                </div>
            </div>
            <button type="button" wire:click="form.removeRelative({{ $index }})" class="absolute top-0 -right-2 text-gray-400 hover:text-red-600 transition-colors" title="@lang('general.remove_relative')">
            </button>
        </div>
    @endforeach
</div>
