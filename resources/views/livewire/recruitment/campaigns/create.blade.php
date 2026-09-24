<div class="space-y-6 p-6">
    <div>
        <h1 class="text-h3 font-semibold">{{ __('recruitment-campaign.new_campaign') }}</h1>
        <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-campaign.new_campaign_hint') }}</p>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-surface border border-neutral-200 bg-white p-6">
        <div>
            <x-forms.input-label for="vacancyId" required>{{ __('recruitment-campaign.vacancy') }}</x-forms.input-label>
            <select id="vacancyId" wire:model="vacancyId" class="mt-1 w-full rounded-control border-neutral-300">
                <option value="">{{ __('recruitment-campaign.choose_a_vacancy') }}</option>
                @foreach ($vacancies as $vacancy)
                    <option value="{{ $vacancy->id }}">
                        {{ $vacancy->position?->title ?? '—' }}
                        ({{ __('recruitment-campaign.seats', ['open' => $vacancy->needed_count - $vacancy->filled_count]) }})
                    </option>
                @endforeach
            </select>
            @if ($vacancies->isEmpty())
                <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-campaign.no_open_vacancies') }}</p>
            @endif
        </div>

        <fieldset>
            <legend class="text-sm font-semibold">{{ __('recruitment-campaign.link_mode') }}</legend>
            <p class="text-sm text-secondary-medium">{{ __('recruitment-campaign.link_mode_hint') }}</p>
            <div class="mt-2 space-y-2">
                @foreach ($linkModes as $mode)
                    <label class="flex items-start gap-2 text-sm" wire:key="mode-{{ $mode }}">
                        <input type="radio" value="{{ $mode }}" wire:model="linkMode" class="mt-1 border-neutral-300 text-primary">
                        <span>
                            <span class="font-medium">{{ __('recruitment-campaign.modes.'.$mode) }}</span>
                            <span class="block text-secondary-medium">{{ __('recruitment-campaign.mode_hints.'.$mode) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-forms.input-label for="startsAt">{{ __('recruitment-campaign.starts_at') }}</x-forms.input-label>
                <input id="startsAt" type="datetime-local" wire:model="startsAt" class="mt-1 w-full rounded-control border-neutral-300">
            </div>
            <div>
                <x-forms.input-label for="endsAt">{{ __('recruitment-campaign.ends_at') }}</x-forms.input-label>
                <input id="endsAt" type="datetime-local" wire:model="endsAt" class="mt-1 w-full rounded-control border-neutral-300">
            </div>
        </div>
        <p class="text-sm text-secondary-medium">{{ __('recruitment-campaign.window_hint') }}</p>

        <div class="flex flex-wrap gap-3">
            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="save">
                {{ __('recruitment-campaign.create') }}
            </x-primary-button>
            <a href="{{ route('recruitment.campaigns.index') }}" wire:navigate
                class="rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                {{ __('recruitment-campaign.cancel') }}
            </a>
        </div>
    </form>
</div>
