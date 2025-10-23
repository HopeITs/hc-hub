@php($locale = app()->getLocale())

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-end">


    <div class="lg:col-span-8">
        <div class="grid grid-cols-1 gap-4">

            {{-- Amount --}}
            <div>
                <x-forms.input-label for="salary">@lang('general.salary')</x-forms.input-label>
                <x-forms.input
                    id="salary"
                    type="number"
                    step="0.01"
                    min="0"
                    wire:model.defer="form.salary"
                    placeholder="0.00"
                    class="mt-1 text-right font-mono tabular-nums"
                />
                <x-error-span class="mt-1 block text-sm">
                    {{ $errors->first('form.salary') }}
                </x-error-span>
            </div>

            {{-- Currency --}}
            <div>
                <x-forms.input-label for="currency">@lang('general.currency')</x-forms.input-label>
                <x-forms.select-input id="currency" wire:model="form.currency" class="mt-1 w-full">
                    <option value="0">Unknown</option>
                    <option value="1">SYP</option>
                    <option value="2">USD</option>
                    <option value="3">EUR</option>
                </x-forms.select-input>
                <x-error-span class="mt-1 block text-sm">
                    {{ $errors->first('form.currency') }}
                </x-error-span>
            </div>

        </div>
    </div>


    {{-- Salary per --}}
    <div class="lg:col-span-4">
        <x-forms.input-label for="salary_per">@lang('general.salary-per')</x-forms.input-label>

        <div class="mt-1 grid grid-cols-3 gap-2" role="radiogroup" aria-labelledby="salary_per">
            {{-- Month --}}
            <label class="group cursor-pointer">
                <input type="radio" class="peer sr-only" wire:model="form.salary_per" value="1" />
                <div class="h-full rounded-lg border border-gray-300 bg-white p-3 text-center text-gray-700
                            shadow-sm transition
                            peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white
                            peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/30">
                    <div class="text-lg leading-none mb-1">🗓️</div>
                    <div class="text-sm font-semibold">@lang('general.month')</div>
                </div>
            </label>

            {{-- Hour --}}
            <label class="group cursor-pointer">
                <input type="radio" class="peer sr-only" wire:model="form.salary_per" value="2" />
                <div class="h-full rounded-lg border border-gray-300 bg-white p-3 text-center text-gray-700
                            shadow-sm transition
                            peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white
                            peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/30">
                    <div class="text-lg leading-none mb-1">⏱️</div>
                    <div class="text-sm font-semibold">{{trans_choice('general.hour',1)}}</div>
                </div>
            </label>

            {{-- Session --}}
            <label class="group cursor-pointer">
                <input type="radio" class="peer sr-only" wire:model="form.salary_per" value="3" />
                <div class="h-full rounded-lg border border-gray-300 bg-white p-3 text-center text-gray-700
                            shadow-sm transition
                            peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white
                            peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/30">
                    <div class="text-lg leading-none mb-1">🎓</div>
                    <div class="text-sm font-semibold">@lang('general.session')</div>
                </div>
            </label>
        </div>

        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.salary_per') }}</x-error-span>
    </div>

</div>
