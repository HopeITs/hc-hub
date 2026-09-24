{{--
    The road this request travelled, and everything that happened along it.

    One line across the page with a circle at every stop. The stops come from
    the workflow actually running, so a requester with nobody above them has no
    manager circle and a direct opening has one — the shortening is in the data
    rather than in a style that greys something out.

    The line carries the progress, not the circles: the segment behind a stop
    that has been reached is drawn solid and dark, and the rest stays pale. A
    circle filled black read as a bullet point; a line that fills in reads as a
    journey.

    Underneath each stop is what actually happened there — who decided, when,
    what they said, and which fields they asked to have changed — so the story
    of the request is told once, in order, instead of being split between a
    diagram and a list somewhere else on the page.
--}}
<section class="rounded-surface border border-neutral-200 bg-white p-6" aria-label="{{ __('recruitment.track.title') }}">
    <h2 class="text-h4 font-semibold">{{ __('recruitment.track.title') }}</h2>

    <ol class="mt-8 flex items-stretch justify-between gap-2" role="list">
        @foreach ($track as $index => $stop)
            @php
                $reached = in_array($stop['state'], [
                    \App\Services\RecruitmentApprovalTrack::DONE,
                    \App\Services\RecruitmentApprovalTrack::CURRENT,
                    \App\Services\RecruitmentApprovalTrack::SKIPPED,
                ], true);

                // Written out rather than interpolated: Tailwind reads class
                // names as literal text, so a built-up string generates nothing.
                $ring = match ($stop['state']) {
                    \App\Services\RecruitmentApprovalTrack::DONE => 'border-neutral-900 bg-neutral-900',
                    \App\Services\RecruitmentApprovalTrack::CURRENT => 'border-neutral-900 bg-white',
                    \App\Services\RecruitmentApprovalTrack::SKIPPED => 'border-neutral-300 border-dashed bg-white',
                    default => 'border-neutral-300 bg-white',
                };
                $heading = $reached ? 'text-neutral-900' : 'text-secondary-medium';
            @endphp

            <li class="relative flex flex-1 flex-col items-center text-center" wire:key="stop-{{ $stop['key'] }}">
                {{-- The segment reaching back to the previous stop. It is the
                     line that darkens as the request arrives, which is what
                     makes the track read as travelled rather than as ticked. --}}
                @if ($index > 0)
                    <span aria-hidden="true"
                        class="absolute top-[1.85rem] end-1/2 w-full
                            {{ $reached ? 'border-t-2 border-neutral-900' : 'border-t border-neutral-200' }}"></span>
                @endif

                <p class="mb-2 text-sm font-semibold {{ $heading }}">{{ $stop['label'] }}</p>

                <span class="relative z-10 grid size-4 place-items-center rounded-full border-2 {{ $ring }}">
                    <span class="sr-only">{{ __('recruitment.track.states.'.$stop['state']) }}</span>
                </span>

                <div class="mt-3 space-y-1 px-1 text-xs text-secondary-medium">
                    @if ($stop['by'])<p class="font-medium text-neutral-900">{{ $stop['by'] }}</p>@endif
                    @if ($stop['at'])<p>{{ $stop['at'] }}</p>@endif

                    @if ($stop['state'] === \App\Services\RecruitmentApprovalTrack::SKIPPED)
                        <p>{{ $stop['note'] ?? __('recruitment.track.states.skipped') }}</p>
                    @endif

                    {{-- What was said at this stop, and what was asked for. The
                         detail belongs beside the circle it happened at. --}}
                    @if (! empty($stop['comment']))
                        <p class="whitespace-pre-wrap break-words text-neutral-800" dir="auto">{{ $stop['comment'] }}</p>
                    @endif

                    @if (! empty($stop['requested_fields']))
                        <ul class="flex flex-wrap justify-center gap-1 pt-1">
                            @foreach ($stop['requested_fields'] as $field)
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
</section>
