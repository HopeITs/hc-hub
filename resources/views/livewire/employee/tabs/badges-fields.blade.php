<form wire:submit.prevent="saveBadges" class="space-y-6">
    @foreach ($badgeForm->badges as $index => $badge)
        <input type="hidden" wire:model="badgeForm.badges.{{ $index }}.id">

        <div wire:key="badge-{{ $index }}" class="relative border-b pb-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3 flex justify-end">
                    <x-primary-button type="button"
                                      :showSpinner="false"
                                      wire:click="removeBadge({{ $index }}, {{ $badge['id'] ?? 'null' }})"
                                      title="@lang('general.remove')">
                        <x-icons.trash/>
                    </x-primary-button>
                </div>
                <div>
                    <x-forms.input-label for="badge_type_{{ $index }}">@lang('general.type')</x-forms.input-label>
                    <x-forms.select2-input id="badge_type_{{ $index }}"
                                          wire:model.live="badgeForm.badges.{{ $index }}.type">
                        @foreach(\App\Enums\BadgeType::getValues() as $key => $value)
                            <option value="{{ $value }}">{{ \App\Enums\BadgeType::getLocalizedName($value) }}</option>
                        @endforeach
                    </x-forms.select2-input>

                    <x-error-span>{{ $errors->first("badgeForm.badges.$index.type") }}</x-error-span>
                </div>

                    <div>
                        <x-forms.input-label for="badge_date_{{ $index }}">@lang('general.date')</x-forms.input-label>
                        <x-forms.date-input id="badge_date_{{ $index }}"
                                            wire:model.defer="badgeForm.badges.{{ $index }}.date"/>
                        <x-error-span>{{ $errors->first("badgeForm.badges.$index.date") }}</x-error-span>
                    </div>

                    <div>
                        <x-forms.input-label
                            for="badge_evaluation_{{ $index }}">@lang('general.related_evaluation')</x-forms.input-label>
                        <x-forms.select2-input id="badge_evaluation_{{ $index }}"
                                              wire:model.defer="badgeForm.badges.{{ $index }}.evaluation_id">
                            <option value="">-- @lang('general.select') --</option>
                        </x-forms.select2-input>
                        <x-error-span>{{ $errors->first("badgeForm.badges.$index.evaluation_id") }}</x-error-span>
                    </div>


                {{-- Show salary and currency inputs only if type == Salary_Increase --}}
                @if(($badgeForm->badges[$index]['type'] ?? null) == \App\Enums\BadgeType::Salary_Increase)
                    <div>
                        <x-forms.input-label
                            for="current_salary_{{ $index }}">@lang('general.current_salary')</x-forms.input-label>
                        <x-forms.input id="current_salary_{{ $index }}" type="number" step="0.01"
                                       wire:model.defer="badgeForm.badges.{{ $index }}.current_salary"/>
                        <x-error-span>{{ $errors->first("badgeForm.badges.$index.current_salary") }}</x-error-span>
                    </div>

                    <div>
                        <x-forms.input-label
                            for="new_salary_{{ $index }}">@lang('general.new_salary')</x-forms.input-label>
                        <x-forms.input id="new_salary_{{ $index }}" type="number" step="0.01"
                                       wire:model.defer="badgeForm.badges.{{ $index }}.new_salary"/>
                        <x-error-span>{{ $errors->first("badgeForm.badges.$index.new_salary") }}</x-error-span>
                    </div>

                    <div>
                        <x-forms.input-label
                            for="badge_currency_{{ $index }}">@lang('general.currency')</x-forms.input-label>
                        <x-forms.select2-input id="badge_currency_{{ $index }}"
                                              wire:model.defer="badgeForm.badges.{{ $index }}.currency">
                            <option value="0">@lang('general.unknown')</option>
                            <option value="1">SYP</option>
                            <option value="2">USD</option>
                            <option value="3">EUR</option>
                        </x-forms.select2-input>
                        <x-error-span>{{ $errors->first("badgeForm.badges.$index.currency") }}</x-error-span>
                    </div>
                @endif


                <div class="md:col-span-3">
                    <x-forms.input-label for="badge_reason_{{ $index }}">@lang('general.reason')</x-forms.input-label>
                    <x-forms.textarea id="badge_reason_{{ $index }}"
                                      wire:model.defer="badgeForm.badges.{{ $index }}.reason"
                                      rows="1"></x-forms.textarea>
                    <x-error-span>{{ $errors->first("badgeForm.badges.$index.reason") }}</x-error-span>
                </div>

                <div class="md:col-span-3">
                    <x-forms.input-label for="badge_notes_{{ $index }}">@lang('general.notes')</x-forms.input-label>
                    <x-forms.textarea id="badge_notes_{{ $index }}"
                                      wire:model.defer="badgeForm.badges.{{ $index }}.notes"
                                      rows="3"></x-forms.textarea>
                    <x-error-span>{{ $errors->first("badgeForm.badges.$index.notes") }}</x-error-span>
                </div>
            </div>
        </div>
    @endforeach

    <div class="flex justify-center">
        <x-primary-button  :showSpinner="false" type="button" wire:click="addBadge">
            + @lang('general.add-new')
        </x-primary-button>
    </div>

    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit" class="!w-auto">@lang('general.save-badges')</x-primary-button>
        <x-secondary-button :href="route('employees.index')"
                            class="!w-auto">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
