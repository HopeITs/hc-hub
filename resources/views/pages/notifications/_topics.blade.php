{{-- By topic: where each topic reaches me, and the topics I mute. --}}
{{-- Module x channel: only chooses the place. Mutes (below) are a different thing. --}}
<section aria-labelledby="module-channels-heading" class="{{ $section }}" data-module-channels>
    <h2 id="module-channels-heading" class="text-base font-bold">{{ __('notification_settings.matrix.title') }}</h2>
    <p class="{{ $muted_text }}">{{ __('notification_settings.matrix.help') }}</p>
    @error('matrix') <p class="text-sm text-status-critical" role="alert">{{ $message }}</p> @enderror

    @foreach ($channels as $channel)
        @if (! $prefs[$channel->value])
            <p class="{{ $muted_text }}" wire:key="matrix-off-{{ $channel->value }}">{{ __('notification_settings.matrix.channel_off', ['channel' => $channel->label()]) }}</p>
        @endif
    @endforeach

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-secondary-soft text-start dark:border-line">
                    <th scope="col" class="py-2 text-start font-semibold">{{ __('notification_settings.matrix.topic') }}</th>
                    @foreach ($channels as $channel)
                        <th scope="col" class="px-2 py-2 text-center font-semibold">{{ $channel->label() }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-soft dark:divide-line">
                @foreach ($this->matrixModules as $module)
                    <tr wire:key="matrix-{{ $module }}">
                        <th scope="row" class="py-2.5 text-start font-medium">{{ $presenter->moduleLabel($module) }}</th>
                        @foreach ($channels as $channel)
                            <td class="px-2 py-2.5 text-center">
                                <input
                                    type="checkbox"
                                    wire:change="setModuleChannel('{{ $module }}', '{{ $channel->value }}', $event.target.checked)"
                                    @checked($matrix[$module][$channel->value] ?? false)
                                    @disabled($paused || ! $prefs[$channel->value] || (! $pushConfigured && in_array($channel, $pushChannels, true)))
                                    aria-label="{{ __('notification_settings.matrix.cell', ['module' => $presenter->moduleLabel($module), 'channel' => $channel->label()]) }}"
                                    class="size-4 accent-primary"
                                    data-matrix-cell="{{ $module }}.{{ $channel->value }}"
                                />
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

{{-- Module mutes: team-wide updates only; anything addressed to me always arrives. --}}
<section aria-labelledby="module-mutes-heading" class="grid gap-2" data-module-mutes>
    <h2 id="module-mutes-heading" class="text-base font-bold">{{ __('notifications.mutes_label') }}</h2>
    <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('notifications.mutes_help') }}</p>
    <p class="text-sm text-secondary-medium dark:text-ink-muted" data-mutes-versus-matrix>{{ __('notification_settings.mutes_versus_matrix') }}</p>

    <ul class="mt-2 divide-y divide-secondary-soft dark:divide-line">
        @forelse ($this->mutableModules as $module)
            <li class="flex items-center justify-between gap-3 py-2.5" wire:key="mute-{{ $module }}">
                <span @class(['text-sm font-medium', 'text-secondary-light line-through' => in_array($module, $muted, true)])>{{ app(\App\Notifications\NotificationPresenter::class)->moduleLabel($module) }}</span>
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-secondary-medium dark:text-ink-muted">
                    <input type="checkbox" wire:click="toggleMute('{{ $module }}')" @checked(in_array($module, $muted, true)) aria-label="{{ __('notifications.mute_toggle', ['module' => app(\App\Notifications\NotificationPresenter::class)->moduleLabel($module)]) }}" class="size-4 accent-primary" data-mute-toggle="{{ $module }}" />
                    {{ __('notifications.muted') }}
                </label>
            </li>
        @empty
            <li class="py-2.5 text-sm text-secondary-medium dark:text-ink-muted" data-mutes-empty>{{ __('notifications.mutes_empty') }}</li>
        @endforelse
    </ul>
</section>
