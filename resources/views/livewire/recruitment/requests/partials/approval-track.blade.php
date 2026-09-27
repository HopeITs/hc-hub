{{--
    The request's history, one version at a time.

    A request sent back for changes becomes a new version and takes the road
    again from the start (D58), so its history is several short journeys rather
    than one long one. Each version gets its own line, and everything that
    happened to that version sits on it: what the requester changed, who
    decided each stop and what they said, and how the version ended.

    The line carries the progress, not the circles. A segment darkens as the
    request reaches the stop beyond it, so the track reads as a road travelled
    rather than as a row of ticks.
--}}
<section class="space-y-4" aria-label="{{ __('recruitment.track.title') }}">
    <h2 class="text-h4 font-semibold">{{ __('recruitment.track.title') }}</h2>

    @foreach ($track as $round)
        @php $stops = array_merge($round['stops'], [$round['outcome']]); @endphp

        <article class="rounded-surface border border-neutral-200 bg-white p-6"
            wire:key="round-{{ $round['version'] ?? 'draft' }}">

            <header class="flex flex-wrap items-baseline justify-between gap-2">
                <h3 class="text-sm font-semibold">
                    @if ($round['version'])
                        {{ __('recruitment.version_no', ['no' => $round['version']]) }}
                    @else
                        {{ __('recruitment.track.draft') }}
                    @endif
                </h3>
                <p class="text-xs text-secondary-medium">
                    @if ($round['author']){{ $round['author'] }}@endif
                    @if ($round['created']) &middot; {{ $round['created'] }}@endif
                </p>
            </header>

            {{-- What the requester changed to make this version. An approver
                 reading it needs to know whether their own approval still
                 stands, and "edited" alone does not answer that (RV-1b). --}}
            @if ($round['edits'])
                <ul class="mt-4 space-y-1 rounded-control bg-neutral-50 p-3 text-xs">
                    @foreach ($round['edits'] as $edit)
                        <li dir="auto">
                            <span class="font-medium">{{ __('recruitment.fields.'.$edit['field']) }}</span>
                            <span class="text-secondary-medium">
                                {{ \Illuminate\Support\Str::limit((string) ($edit['from'] ?? '—'), 40) }}
                                &rarr;
                                {{ \Illuminate\Support\Str::limit((string) ($edit['to'] ?? '—'), 40) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <ol class="mt-6 flex items-start justify-between gap-2" role="list">
                @foreach ($stops as $index => $stop)
                    @php
                        $reached = in_array($stop['state'], [
                            \App\Services\RecruitmentApprovalTrack::DONE,
                            \App\Services\RecruitmentApprovalTrack::CURRENT,
                            \App\Services\RecruitmentApprovalTrack::SKIPPED,
                        ], true);

                        // Written out rather than interpolated: Tailwind reads
                        // class names as literal text, so a built-up string
                        // would generate nothing at all.
                        $ring = match ($stop['state']) {
                            \App\Services\RecruitmentApprovalTrack::DONE => 'border-neutral-900 bg-neutral-900',
                            \App\Services\RecruitmentApprovalTrack::CURRENT => 'border-neutral-900 bg-white',
                            \App\Services\RecruitmentApprovalTrack::SKIPPED => 'border-neutral-300 border-dashed bg-white',
                            default => 'border-neutral-300 bg-white',
                        };
                    @endphp

                    <li class="relative flex flex-1 flex-col items-center text-center"
                        wire:key="stop-{{ $round['version'] ?? 'draft' }}-{{ $index }}">

                        @if ($index > 0)
                            <span aria-hidden="true"
                                class="absolute top-[0.4rem] end-1/2 w-full
                                    {{ $reached ? 'border-t-2 border-neutral-900' : 'border-t border-neutral-200' }}"></span>
                        @endif

                        <span class="relative z-10 grid size-3.5 place-items-center rounded-full border-2 {{ $ring }}">
                            <span class="sr-only">{{ __('recruitment.track.states.'.$stop['state']) }}</span>
                        </span>

                        <p class="mt-3 text-xs font-semibold {{ $reached ? 'text-neutral-900' : 'text-secondary-medium' }}">
                            {{ $stop['label'] }}
                        </p>

                        <div class="mt-1 space-y-0.5 px-1 text-xs text-secondary-medium">
                            @if ($stop['by'])<p class="font-medium text-neutral-800">{{ $stop['by'] }}</p>@endif

                            {{-- The role as it was recorded on the decision, not
                                 the one the person holds today: reading an old
                                 approval through this week's roles would
                                 misstate who made it (task 10). --}}
                            @if (! empty($stop['role']))<p>{{ $stop['role'] }}</p>@endif

                            @if ($stop['at'])<p>{{ $stop['at'] }}</p>@endif

                            @if (! empty($stop['comment']))
                                <p class="whitespace-pre-wrap break-words text-neutral-800" dir="auto">{{ $stop['comment'] }}</p>
                            @endif

                            {{-- The fields this stop opened for editing. They
                                 are the reason the next version exists, so they
                                 belong at the stop that asked for them. --}}
                            @if (! empty($stop['fields']))
                                <ul class="flex flex-wrap justify-center gap-1 pt-1">
                                    @foreach ($stop['fields'] as $field)
                                        <li class="rounded-control border border-neutral-200 px-1.5 py-0.5 text-neutral-800">
                                            {{ __('recruitment.fields.'.$field) }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </article>
    @endforeach
</section>
