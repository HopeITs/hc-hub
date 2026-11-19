{{--
    The guided flow after a hire: what is done and what is left around the
    new employee (OnboardingChecklist). $steps: key, done, url, problem,
    params, note. Reads and links only; each link's page checks its own
    permission. Included by ⚡convert.blade.php.
--}}
@if ($steps !== [])
    @php
        $t = 'hr_recruitment.applicant_ui.conversions.onboarding.';
    @endphp
    <x-ui.panel :title="__($t.'title')" data-onboarding>
        <div class="space-y-2 p-4 text-sm">
            <p class="{{ $muted }}">{{ __($t.'hint') }}</p>
            <ol class="divide-y divide-panel-border">
                @foreach ($steps as $step)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" data-step="{{ $step['key'] }}" data-done="{{ $step['done'] ? '1' : '0' }}" wire:key="onboarding-{{ $step['key'] }}-{{ $loop->index }}">
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 font-semibold">
                                @if ($step['done'])
                                    <flux:icon.check-circle variant="micro" class="size-4 shrink-0 text-status-good" aria-hidden="true" />
                                @else
                                    <flux:icon.clock variant="micro" class="size-4 shrink-0 text-status-warn" aria-hidden="true" />
                                @endif
                                <span dir="auto">{{ __($t.$step['key'], $step['params']) }}</span>
                                <span class="text-xs font-normal {{ $muted }}">{{ $step['done'] ? __($t.'done') : __($t.'todo') }}</span>
                            </p>
                            @if ($step['key'] === 'account' && ! $step['done'])
                                <p class="{{ $muted }}">{{ __($t.'account_hint') }}</p>
                            @elseif ($step['key'] === 'previous')
                                <p class="{{ $muted }}">{{ __($t.'previous_hint') }}</p>
                            @elseif ($step['key'] === 'card')
                                <p @class([$muted => $step['note'] === null, 'text-status-warn' => $step['note'] !== null])>{{ $step['note'] ?? $step['problem'] ?? __($t.'card_ready') }}</p>
                            @elseif (in_array($step['key'], ['welcome', 'announce'], true))
                                <p class="{{ $muted }}">{{ __($t.'welcome_hint') }}</p>
                            @endif
                        </div>

                        <div class="shrink-0">
                            @if (in_array($step['key'], ['welcome', 'announce'], true))
                                <x-hr::employees.message-template-menu :employee="$conversion->employee" />
                            @elseif ($step['url'] !== null)
                                <x-ui.button size="sm" tone="secondary" :href="$step['url']">{{ $step['done'] ? __($t.'open') : __($t.'fix') }}</x-ui.button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </x-ui.panel>
@endif
