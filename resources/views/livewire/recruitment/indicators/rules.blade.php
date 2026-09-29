<div class="space-y-6 p-6">
    <div>
        <h1 class="text-h3 font-semibold">{{ __('recruitment-indicator-rules.title') }}</h1>
        <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-indicator-rules.hint') }}</p>
        <p class="mt-2 text-xs text-secondary-medium">
            {{ $customised ? __('recruitment-indicator-rules.customised') : __('recruitment-indicator-rules.defaults') }}
            &middot; {{ __('recruitment-indicator-rules.version', ['version' => $version]) }}
        </p>
    </div>


    <form wire:submit="save" class="space-y-6">
        <section class="rounded-surface border border-secondary-soft bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-indicator-rules.criteria_title') }}</h2>
            <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-indicator-rules.criteria_hint') }}</p>
            <ul class="mt-4 divide-y divide-secondary-soft text-sm">
                @foreach ($indicatorKeys as $key)
                    <li class="py-2">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model="enabled.{{ $key }}" class="rounded border-secondary-light">
                            <span class="font-medium">{{ __('recruitment-applicant.indicators.names.'.$key) }}</span>
                            @if ($key === 'previous_applicant')
                                <span class="text-xs text-secondary-medium">&middot; {{ __('recruitment-indicator-rules.not_scored') }}</span>
                            @endif
                        </label>
                    </li>
                @endforeach
            </ul>
            @error('disabled') <p class="mt-2 text-sm text-primary">{{ $message }}</p> @enderror
        </section>

        <section class="rounded-surface border border-secondary-soft bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-indicator-rules.experience_title') }}</h2>
            <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-indicator-rules.experience_hint') }}</p>

            <div class="mt-4 max-w-sm">
                <x-forms.input-label for="defaultYears">{{ __('recruitment-indicator-rules.default_experience') }}</x-forms.input-label>
                <input id="defaultYears" type="number" min="0" max="50" wire:model="defaultYears" class="mt-1 w-32 rounded-control border-secondary-light">
                <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-indicator-rules.default_experience_hint') }}</p>
                @error('defaultYears') <p class="mt-1 text-sm text-primary">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-secondary-medium">
                            <th class="py-2 pe-4 text-start font-semibold">{{ __('recruitment-indicator-rules.answer') }}</th>
                            <th class="py-2 pe-4 text-start font-semibold">{{ __('recruitment-indicator-rules.min_years') }}</th>
                            <th class="py-2 text-start font-semibold">{{ __('recruitment-indicator-rules.max_years') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-soft">
                        @foreach ($ranges as $option => $range)
                            <tr wire:key="range-{{ $option }}">
                                <td class="py-2 pe-4">
                                    {{ \Illuminate\Support\Facades\Lang::has('recruitment-indicator-rules.range_options.'.$option) ? __('recruitment-indicator-rules.range_options.'.$option) : $option }}
                                </td>
                                <td class="py-2 pe-4">
                                    <label class="sr-only" for="min-{{ $option }}">{{ __('recruitment-indicator-rules.min_years') }}</label>
                                    <input id="min-{{ $option }}" type="number" min="0" max="50" wire:model="ranges.{{ $option }}.min" class="w-24 rounded-control border-secondary-light">
                                </td>
                                <td class="py-2">
                                    <label class="sr-only" for="max-{{ $option }}">{{ __('recruitment-indicator-rules.max_years') }}</label>
                                    <input id="max-{{ $option }}" type="number" min="0" max="50" wire:model="ranges.{{ $option }}.max" title="{{ __('recruitment-indicator-rules.open_ended') }}" class="w-24 rounded-control border-secondary-light">
                                    @error('ranges.'.$option) <p class="mt-1 text-sm text-primary">{{ $message }}</p> @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-2 text-xs text-secondary-medium">{{ __('recruitment-indicator-rules.open_ended') }}</p>
            </div>
        </section>

        <section class="rounded-surface border border-secondary-soft bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-indicator-rules.qualification_title') }}</h2>
            <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-indicator-rules.qualification_hint') }}</p>

            <div class="mt-4 max-w-sm">
                <x-forms.input-label for="defaultLevel">{{ __('recruitment-indicator-rules.default_qualification') }}</x-forms.input-label>
                <select id="defaultLevel" wire:model="defaultLevel" class="mt-1 w-full rounded-control border-secondary-light">
                    <option value="">{{ __('recruitment-indicator-rules.no_default') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level }}">{{ __('recruitment-applicant.indicators.levels.'.$level) }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-indicator-rules.default_qualification_hint') }}</p>
                @error('defaultLevel') <p class="mt-1 text-sm text-primary">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 space-y-4">
                @foreach ($levels as $level)
                    <div wire:key="keywords-{{ $level }}">
                        <x-forms.input-label for="keywords-{{ $level }}">{{ __('recruitment-indicator-rules.keywords', ['level' => __('recruitment-applicant.indicators.levels.'.$level)]) }}</x-forms.input-label>
                        <textarea id="keywords-{{ $level }}" rows="2" wire:model="keywords.{{ $level }}" class="mt-1 w-full rounded-control border-secondary-light text-sm"></textarea>
                        @error('keywords.'.$level) <p class="mt-1 text-sm text-primary">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('recruitment-indicator-rules.save') }}</x-primary-button>
            <button type="button" wire:click="resetToDefaults" wire:confirm="{{ __('recruitment-indicator-rules.reset_confirm') }}"
                class="cursor-pointer rounded-control border border-secondary-light px-3 py-2 text-sm font-semibold hover:bg-secondary-soft/30">{{ __('recruitment-indicator-rules.reset') }}</button>
            <p class="text-xs text-secondary-medium">{{ __('recruitment-indicator-rules.form_hint') }}</p>
        </div>
    </form>
</div>
