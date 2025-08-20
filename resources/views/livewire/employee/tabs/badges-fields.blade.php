<div x-data="{
    nextIndex: {{ count($form->badges) }},
    add() {
        const template = this.$refs.badgeTemplate.innerHTML.replaceAll('__INDEX__', this.nextIndex);
        this.$refs.badgeContainer.insertAdjacentHTML('beforeend', template);

        // We need to initialize the new Alpine component for the row we just added
        let newRow = document.getElementById('badge-row-' + this.nextIndex);
        if (newRow) {
            window.Alpine.initTree(newRow);
        }
        this.nextIndex++;
    },
    remove(index) {
        if (confirm('Are you sure you want to remove this item?')) {
            document.getElementById('badge-row-' + index)?.remove();
            $wire.removeBadge(index);
        }
    }
}">
    <form wire:submit.prevent="saveBadges" class="space-y-6">
        <div x-ref="badgeContainer">
            @foreach ($form->badges as $index => $badge)
                <div wire:key="badge-{{ $index }}" id="badge-row-{{ $index }}" x-data="{ type: @entangle('form.badges.'.$index.'.type') }" x-cloak class="relative border-b pb-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <x-forms.input-label for="badge_type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                            <x-forms.select-input wire:model.live="form.badges.{{ $index }}.type" id="badge_type_{{ $index }}">
                                <option value="">-- @lang('general.select') --</option>
                                <option value="promotion">@lang('general.promotion')</option>
                                <option value="warning">@lang('general.warning')</option>
                                <option value="salary_adjustment">@lang('general.salary_adjustment')</option>
                                <option value="bonus">@lang('general.bonus')</option>
                                <option value="other">@lang('general.other')</option>
                            </x-forms.select-input>
                        </div>
                        <div>
                            <x-forms.input-label for="badge_date_{{ $index }}">@lang('general.date')</x-forms.input-label>
                            <x-forms.input type="date" wire:model="form.badges.{{ $index }}.date" id="badge_date_{{ $index }}" />
                        </div>
                        <div class="hidden lg:block"></div>
                        <div x-show="type === 'salary_adjustment'">
                            <x-forms.input-label for="badge_currency_{{ $index }}">@lang('general.currency')</x-forms.input-label>
                            <x-forms.select-input wire:model="form.badges.{{ $index }}.currency" id="badge_currency_{{ $index }}">
                                <option value="1">SYP</option>
                                <option value="2">USD</option>
                                <option value="3">EUR</option>
                            </x-forms.select-input>
                        </div>
                        <div x-show="type === 'salary_adjustment'">
                            <x-forms.input-label for="badge_current_salary_{{ $index }}">@lang('general.current_salary')</x-forms.input-label>
                            <x-forms.input type="number" step="0.01" wire:model="form.badges.{{ $index }}.current_salary" id="badge_current_salary_{{ $index }}" />
                        </div>
                        <div x-show="type === 'salary_adjustment'">
                            <x-forms.input-label for="badge_new_salary_{{ $index }}">@lang('general.new_salary')</x-forms.input-label>
                            <x-forms.input type="number" step="0.01" wire:model="form.badges.{{ $index }}.new_salary" id="badge_new_salary_{{ $index }}" />
                        </div>
                        <div class="lg:col-span-3">
                            <x-forms.input-label for="badge_reason_{{ $index }}">@lang('general.reason')</x-forms.input-label>
                            <x-forms.textarea wire:model="form.badges.{{ $index }}.reason" id="badge_reason_{{ $index }}" />
                        </div>
                        <div class="lg:col-span-3">
                            <x-forms.input-label for="badge_notes_{{ $index }}">@lang('general.notes')</x-forms.input-label>
                            <x-forms.textarea wire:model="form.badges.{{ $index }}.notes" id="badge_notes_{{ $index }}" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <template x-ref="badgeTemplate">
            <div id="badge-row-__INDEX__" x-data="{ type: '' }" x-cloak class="relative border-b pb-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <x-forms.input-label for="badge_type___INDEX__">@lang('general.type')</x-forms.input-label>
                        <x-forms.select-input wire:model.live="form.badges.__INDEX__.type" x-model="type" id="badge_type___INDEX__">
                            <option value="">-- @lang('general.select') --</option>
                            <option value="promotion">@lang('general.promotion')</option>
                            <option value="warning">@lang('general.warning')</option>
                            <option value="salary_adjustment">@lang('general.salary_adjustment')</option>
                            <option value="bonus">@lang('general.bonus')</option>
                            <option value="other">@lang('general.other')</option>
                        </x-forms.select-input>
                    </div>
                    <div>
                        <x-forms.input-label for="badge_date___INDEX__">@lang('general.date')</x-forms.input-label>
                        <x-forms.input type="date" wire:model="form.badges.__INDEX__.date" id="badge_date___INDEX__" />
                    </div>
                    <div class="hidden lg:block"></div>
                    <div x-show="type === 'salary_adjustment'">
                        <x-forms.input-label for="badge_currency___INDEX__">@lang('general.currency')</x-forms.input-label>
                        <x-forms.select-input wire:model="form.badges.__INDEX__.currency" id="badge_currency___INDEX__">
                            <option value="1">SYP</option>
                            <option value="2">USD</option>
                            <option value="3">EUR</option>
                        </x-forms.select-input>
                    </div>
                    <div x-show="type === 'salary_adjustment'">
                        <x-forms.input-label for="badge_current_salary___INDEX__">@lang('general.current_salary')</x-forms.input-label>
                        <x-forms.input type="number" step="0.01" wire:model="form.badges.__INDEX__.current_salary" id="badge_current_salary___INDEX__" />
                    </div>
                    <div x-show="type === 'salary_adjustment'">
                        <x-forms.input-label for="badge_new_salary___INDEX__">@lang('general.new_salary')</x-forms.input-label>
                        <x-forms.input type="number" step="0.01" wire:model="form.badges.__INDEX__.new_salary" id="badge_new_salary___INDEX__" />
                    </div>
                    <div class="lg:col-span-3">
                        <x-forms.input-label for="badge_reason___INDEX__">@lang('general.reason')</x-forms.input-label>
                        <x-forms.textarea wire:model="form.badges.__INDEX__.reason" id="badge_reason___INDEX__" />
                    </div>
                    <div class="lg:col-span-3">
                        <x-forms.input-label for="badge_notes___INDEX__">@lang('general.notes')</x-forms.input-label>
                        <x-forms.textarea wire:model="form.badges.__INDEX__.notes" id="badge_notes___INDEX__" />
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
