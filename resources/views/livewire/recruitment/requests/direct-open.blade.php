<x-slot:title>{{ __('recruitment.direct_open.title') }}</x-slot:title>

<div class="mx-auto max-w-5xl space-y-6">
    <a class="text-primary hover:underline" href="{{ route('recruitment.requests.index') }}">{{ __('recruitment.back') }}</a>
    <header>
        <h1 class="text-h2 font-semibold text-neutral-900">{{ __('recruitment.direct_open.title') }}</h1>
        <p class="mt-2 text-secondary-medium">{{ __('recruitment.direct_open.hint') }}</p>
    </header>

    <form wire:submit="save" class="space-y-6">
        {{-- The same request form a manager fills in: a direct opening shortens
             the approvals, never the facts. --}}
        @include('livewire.recruitment.requests.partials.request-fields')

        <section class="space-y-5 rounded-surface border border-neutral-200 bg-white p-6">
            <div>
                <h2 class="text-h4 font-semibold">{{ __('recruitment.direct_open.title') }}</h2>
                <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment.direct_open.hint') }}</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-forms.input-label for="actualRequester" required>{{ __('recruitment.direct_open.actual_requester_name') }}</x-forms.input-label>
                    <input id="actualRequester" type="text" wire:model="documentation.actual_requester_name"
                        class="mt-1 w-full rounded-control border-neutral-300" dir="auto">
                    <p class="mt-1 text-sm text-red-600">@error('actual_requester_name'){{ $message }}@enderror</p>
                </div>
                <div>
                    <x-forms.input-label for="actualRequesterPosition">{{ __('recruitment.direct_open.actual_requester_position') }}</x-forms.input-label>
                    <select id="actualRequesterPosition" wire:model="documentation.actual_requester_position_id"
                        class="mt-1 w-full rounded-control border-neutral-300">
                        <option value="">—</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}">{{ $position->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <x-forms.input-label for="directOpenReason" required>{{ __('recruitment.direct_open.reason') }}</x-forms.input-label>
                <textarea id="directOpenReason" rows="3" wire:model="documentation.direct_open_reason"
                    class="mt-1 w-full rounded-control border-neutral-300" dir="auto"></textarea>
                <p class="mt-1 text-sm text-red-600">@error('direct_open_reason'){{ $message }}@enderror</p>
            </div>
        </section>

        <section class="space-y-5 rounded-surface border border-neutral-200 bg-white p-6">
            <div>
                <h2 class="text-h4 font-semibold">{{ __('recruitment.direct_open.confirmations') }}</h2>
                <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment.direct_open.confirmations_hint') }}</p>
            </div>

            {{-- Three lines for the three steps a normal request would have
                 taken. These are external confirmations, never approvals: the
                 timeline must not show a click that never happened. --}}
            @foreach ($steps as $stepKey)
                @php $decision = (int) ($documentation['confirmations'][$stepKey]['decision'] ?? 0); @endphp
                <fieldset class="rounded-control border border-neutral-200 p-4" wire:key="confirm-{{ $stepKey }}">
                    <legend class="px-1 text-sm font-semibold">
                        {{ \App\Enums\WorkflowStepKey::getLocalizedName($stepKey)['value'] }}
                    </legend>

                    <div class="flex flex-wrap gap-4">
                        @foreach ($decisions as $value)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="radio" value="{{ $value }}"
                                    wire:model.live="documentation.confirmations.{{ $stepKey }}.decision"
                                    class="border-neutral-300 text-primary">
                                <span>{{ __('recruitment.direct_open.decisions.'.$value) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-sm text-red-600">@error('confirmations.'.$stepKey.'.decision'){{ $message }}@enderror</p>

                    @if ($decision === \App\Enums\ExternalConfirmationDecision::NotApplicable)
                        <div class="mt-4">
                            <x-forms.input-label for="na-{{ $stepKey }}" required>{{ __('recruitment.direct_open.not_applicable_reason') }}</x-forms.input-label>
                            <textarea id="na-{{ $stepKey }}" rows="2"
                                wire:model="documentation.confirmations.{{ $stepKey }}.not_applicable_reason"
                                class="mt-1 w-full rounded-control border-neutral-300" dir="auto"></textarea>
                            <p class="mt-1 text-sm text-red-600">@error('confirmations.'.$stepKey.'.not_applicable_reason'){{ $message }}@enderror</p>
                        </div>
                    @elseif ($decision === \App\Enums\ExternalConfirmationDecision::Approved)
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <x-forms.input-label for="who-{{ $stepKey }}" required>{{ __('recruitment.direct_open.confirmer_name') }}</x-forms.input-label>
                                <input id="who-{{ $stepKey }}" type="text"
                                    wire:model="documentation.confirmations.{{ $stepKey }}.confirmer_name"
                                    class="mt-1 w-full rounded-control border-neutral-300" dir="auto">
                                <p class="mt-1 text-sm text-red-600">@error('confirmations.'.$stepKey.'.confirmer_name'){{ $message }}@enderror</p>
                            </div>
                            <div>
                                <x-forms.input-label for="how-{{ $stepKey }}" required>{{ __('recruitment.direct_open.method') }}</x-forms.input-label>
                                <select id="how-{{ $stepKey }}"
                                    wire:model="documentation.confirmations.{{ $stepKey }}.method"
                                    class="mt-1 w-full rounded-control border-neutral-300">
                                    <option value="">—</option>
                                    @foreach ($methods as $method)
                                        <option value="{{ $method }}">{{ __('recruitment.direct_open.methods.'.$method) }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-red-600">@error('confirmations.'.$stepKey.'.method'){{ $message }}@enderror</p>
                            </div>
                            <div>
                                <x-forms.input-label for="when-{{ $stepKey }}" required>{{ __('recruitment.direct_open.confirmed_at') }}</x-forms.input-label>
                                <input id="when-{{ $stepKey }}" type="datetime-local"
                                    wire:model="documentation.confirmations.{{ $stepKey }}.confirmed_at"
                                    class="mt-1 w-full rounded-control border-neutral-300">
                                <p class="mt-1 text-sm text-red-600">@error('confirmations.'.$stepKey.'.confirmed_at'){{ $message }}@enderror</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <x-forms.input-label for="notes-{{ $stepKey }}">{{ __('recruitment.direct_open.notes') }}</x-forms.input-label>
                            <textarea id="notes-{{ $stepKey }}" rows="2"
                                wire:model="documentation.confirmations.{{ $stepKey }}.notes"
                                class="mt-1 w-full rounded-control border-neutral-300" dir="auto"></textarea>
                        </div>
                    @endif
                </fieldset>
            @endforeach
        </section>

        <footer class="flex flex-wrap items-center justify-between gap-4 rounded-surface border border-neutral-200 bg-white p-5">
            <p class="text-sm text-secondary-medium">{{ __('recruitment.direct_open.hint') }}</p>
            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="save">
                {{ __('recruitment.direct_open.open') }}
            </x-primary-button>
        </footer>
    </form>
</div>
