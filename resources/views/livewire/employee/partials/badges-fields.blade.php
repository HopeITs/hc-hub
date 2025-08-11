<div class="space-y-6">
    @foreach ($form->badges as $index => $badge)
        <div wire:key="badge-{{ $index }}" class="relative">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <x-forms.input-label for="badge_type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                    <x-forms.select-input wire:model="form.badges.{{ $index }}.type" id="badge_type_{{ $index }}">
                        <option value="">-- @lang('general.select') --</option>
                        <option value="promotion">@lang('general.promotion')</option>
                        <option value="warning">@lang('general.warning')</option>
                        <option value="salary_adjustment">@lang('general.salary_adjustment')</option>
                        <option value="bonus">@lang('general.bonus')</option>
                        <option value="other">@lang('general.other')</option>
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('form.badges.' . $index . '.type') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="badge_date_{{ $index }}">@lang('general.date')</x-forms.input-label>
                    <x-forms.input type="date" wire:model="form.badges.{{ $index }}.date" id="badge_date_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.badges.' . $index . '.date') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="badge_currency_{{ $index }}">@lang('general.currency')</x-forms.input-label>
                    <x-forms.select-input wire:model="form.badges.{{ $index }}.currency" id="badge_currency_{{ $index }}">
                        <option value="0">@lang('general.unknown')</option>
                        <option value="1">SYP</option>
                        <option value="2">USD</option>
                        <option value="3">EUR</option>
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('form.badges.' . $index . '.currency') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="badge_current_salary_{{ $index }}">@lang('general.current_salary')</x-forms.input-label>
                    <x-forms.input type="number" step="0.01" wire:model="form.badges.{{ $index }}.current_salary" id="badge_current_salary_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.badges.' . $index . '.current_salary') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="badge_new_salary_{{ $index }}">@lang('general.new_salary')</x-forms.input-label>
                    <x-forms.input type="number" step="0.01" wire:model="form.badges.{{ $index }}.new_salary" id="badge_new_salary_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.badges.' . $index . '.new_salary') }}</x-error-span>
                </div>
                <div class="lg:col-span-3">
                    <x-forms.input-label for="badge_reason_{{ $index }}">@lang('general.reason')</x-forms.input-label>
                    <x-forms.textarea wire:model="form.badges.{{ $index }}.reason" id="badge_reason_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.badges.' . $index . '.reason') }}</x-error-span>
                </div>
                <div class="lg:col-span-3">
                    <x-forms.input-label for="badge_notes_{{ $index }}">@lang('general.notes')</x-forms.input-label>
                    <x-forms.textarea wire:model="form.badges.{{ $index }}.notes" id="badge_notes_{{ $index }}" />
                    <x-error-span>{{ $errors->first('form.badges.' . $index . '.notes') }}</x-error-span>
                </div>
            </div>
            <button type="button" wire:click="form.removeBadge({{ $index }})" class="absolute top-0 -right-2 text-gray-400 hover:text-red-600 transition-colors" title="@lang('general.remove_badge')">
            </button>
        </div>
    @endforeach
</div>
