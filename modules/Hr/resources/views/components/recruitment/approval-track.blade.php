@props(['track' => []])

{{--
    The history of a request, one version at a time. The newest version is the
    one people act on, so it is open; earlier versions fold away but keep
    everything that happened on them.
--}}
@php
    $states = \Modules\Hr\Actions\Recruitment\RecruitmentApprovalTrack::class;
    $rounds = array_values($track);
    $reached = fn (?array $stop): bool => $stop !== null && in_array($stop['state'], [$states::DONE, $states::CURRENT, $states::SKIPPED], true);
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<section class="space-y-3" aria-labelledby="track-title" data-approval-track>
    <h2 id="track-title" class="text-base font-semibold">{{ __('hr_recruitment.track.title') }}</h2>

    @foreach ($rounds as $roundIndex => $round)
        @php
            $stops = array_merge($round['stops'], [$round['outcome']]);
            $latest = $roundIndex === 0;
            $last = collect($stops)->filter(fn ($stop) => $reached($stop))->last();
        @endphp
        <details class="group rounded-panel border border-panel-border bg-white shadow-card dark:bg-surface-1 dark:shadow-raised" wire:key="round-{{ $round['version'] ?? 'draft' }}" @if ($latest) open @endif>
            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 px-4 py-3">
                <span class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                    {{ $round['version'] ? __('hr_recruitment.version_no', ['no' => $round['version']]) : __('hr_recruitment.track.draft') }}
                    @if ($latest)
                        <x-ui.enum-badge :case="\App\Enums\StatusTone::Active" :label="__('hr_recruitment.track.latest')" :tone="\App\Enums\StatusTone::Active" />
                    @elseif ($last)
                        <x-ui.enum-badge :case="\App\Enums\StatusTone::Neutral" :label="$last['label']" :tone="\App\Enums\StatusTone::Neutral" />
                    @endif
                </span>
                <span class="flex items-center gap-2 text-xs {{ $muted }}">
                    <span dir="auto">{{ $round['author'] }}</span>
                    @if ($round['created'])<span class="tabular-nums">{{ $round['created'] }}</span>@endif
                    <flux:icon.chevron-down variant="mini" class="size-4 transition-transform group-open:rotate-180" />
                </span>
            </summary>

            <div class="border-t border-panel-border px-4 pb-4">
                @if ($round['edits'])
                    <div class="mt-3 rounded-lg bg-secondary-soft/50 p-3 dark:bg-surface-2">
                        <p class="text-xs font-semibold {{ $muted }}">{{ __('hr_recruitment.track.changes') }}</p>
                        <ul class="mt-1.5 space-y-1 text-sm">
                            @foreach ($round['edits'] as $edit)
                                <li class="flex flex-wrap items-baseline gap-x-2" dir="auto">
                                    <span class="font-semibold">{{ \Modules\Hr\Support\Recruitment\Labels::field($edit['field']) }}</span>
                                    <span class="{{ $muted }} line-through">{{ \Illuminate\Support\Str::limit((string) ($edit['from'] ?? '—'), 40) }}</span>
                                    <span aria-hidden="true">→</span>
                                    <span class="font-semibold">{{ \Illuminate\Support\Str::limit((string) ($edit['to'] ?? '—'), 40) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <ol class="mt-4 grid gap-4 sm:grid-flow-col sm:auto-cols-fr" role="list">
                    @foreach ($stops as $index => $stop)
                        @php
                            $isReached = $reached($stop);
                            $isCurrent = $stop['state'] === $states::CURRENT;
                            $dot = match ($stop['state']) {
                                $states::DONE => 'bg-secondary-dark text-white dark:bg-ink dark:text-surface-0',
                                $states::CURRENT => 'bg-white ring-[3px] ring-inset ring-primary dark:bg-surface-1',
                                $states::SKIPPED => 'border-2 border-dashed border-secondary-light bg-white dark:bg-surface-1 dark:border-line',
                                default => 'border-2 border-secondary-soft bg-white dark:bg-surface-1 dark:border-line',
                            };
                        @endphp
                        <li class="flex gap-3 sm:flex-col sm:items-center sm:text-center" wire:key="stop-{{ $round['version'] ?? 'draft' }}-{{ $index }}" @if ($isCurrent) aria-current="step" @endif>
                            <span class="flex size-5 shrink-0 items-center justify-center rounded-full {{ $dot }}">
                                @if ($stop['state'] === $states::DONE)<flux:icon.check variant="micro" class="size-3" />@endif
                                <span class="sr-only">{{ __('hr_recruitment.track.states.'.$stop['state']) }}</span>
                            </span>
                            <div class="min-w-0">
                                <p @class(['text-sm', 'font-semibold' => $isReached, $muted => ! $isReached])>{{ $stop['label'] }}</p>
                                @if ($isCurrent && ! $stop['by'])
                                    <p class="text-xs font-semibold text-status-critical">{{ __('hr_recruitment.track.states.'.$states::CURRENT) }}</p>
                                @endif
                                @if ($stop['by'] || $stop['at'] || ! empty($stop['role']))
                                    <p class="mt-0.5 text-xs {{ $muted }}">
                                        @if ($stop['by'])<span class="block font-medium" dir="auto">{{ $stop['by'] }}</span>@endif
                                        @if (! empty($stop['role']))<span class="block">{{ \Modules\Hr\Support\Recruitment\Text::roleLabel($stop['role']) }}</span>@endif
                                        @if ($stop['at'])<span class="block tabular-nums">{{ $stop['at'] }}</span>@endif
                                    </p>
                                @endif
                                @if (! empty($stop['comment']))
                                    <p class="mt-1.5 whitespace-pre-wrap rounded-md bg-secondary-soft/50 px-2 py-1.5 text-start text-xs dark:bg-surface-2" dir="auto">{{ $stop['comment'] }}</p>
                                @endif
                                @if (! empty($stop['fields']))
                                    <ul class="mt-1.5 flex flex-wrap gap-1 sm:justify-center">
                                        @foreach ($stop['fields'] as $field)
                                            <li class="rounded-md bg-secondary-soft px-1.5 py-0.5 text-xs dark:bg-surface-2">{{ \Modules\Hr\Support\Recruitment\Labels::field($field) }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </details>
    @endforeach
</section>
