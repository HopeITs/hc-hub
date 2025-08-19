<div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
    <div class="md:col-span-2">
        <x-forms.input-label for="salary">@lang('general.salary')</x-forms.input-label>
        <div class="flex">
            <x-forms.select-input wire:model="form.currency"
                                  class="w-28 ltr:rounded-r-none rtl:rounded-l-none ltr:border-r-0 rtl:border-l-0">
                <option value="0">UNK</option>
                <option value="1">SYP</option>
                <option value="2">USD</option>
                <option value="3">EUR</option>
            </x-forms.select-input>
            <x-forms.input id="salary" type="number" step="0.01" min="0"
                           wire:model.defer="form.salary"
                           class="w-full ltr:rounded-l-none rtl:rounded-r-none" placeholder="0.00"/>
        </div>
        <x-error-span>{{ $errors->first('form.currency') ?: $errors->first('form.salary') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="salary_per">@lang('general.salary-per')</x-forms.input-label>
        <div class="inline-flex items-center gap-1 rounded-lg border bg-gray-50 p-1">
            <label class="cursor-pointer">
                <input type="radio" class="peer sr-only" wire:model="form.salary_per" value="1">
                <span class="px-2.5 py-1 text-xs rounded-md text-gray-600
                             peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow">
                    @lang('general.month')
                </span>
            </label>
            <label class="cursor-pointer">
                <input type="radio" class="peer sr-only" wire:model="form.salary_per" value="2">
                <span class="px-2.5 py-1 text-xs rounded-md text-gray-600
                             peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow">
                    @lang('general.hour')
                </span>
            </label>
            <label class="cursor-pointer">
                <input type="radio" class="peer sr-only" wire:model="form.salary_per" value="3">
                <span class="px-2.5 py-1 text-xs rounded-md text-gray-600
                             peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow">
                    @lang('general.session')
                </span>
            </label>
        </div>
        <x-error-span>{{ $errors->first('form.salary_per') }}</x-error-span>
    </div>
</div>
