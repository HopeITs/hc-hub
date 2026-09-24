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

    {{-- Underneath the road, what happened along it in full.

         The road shows the shape of the journey; this is the record — every
         edit with its old and new values, every decision with its reason, and
         who did each of them. Both belong to the same request, so they belong
         in the same section rather than being split across the page. --}}
    <div class="mt-8 border-t border-neutral-100 pt-6">
        <h3 class="text-sm font-semibold text-secondary-medium">{{ __('recruitment.timeline') }}</h3>
        <ol class="mt-4 divide-y divide-neutral-100">
            @forelse ($request->events as $event)
                {{-- The role and the scope are read from the row, never looked
                     up from the actor today: reading a year-old decision through
                     this week's roles would misstate who decided it. --}}
                <li class="py-4" wire:key="event-{{ $event->id }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">{{ \App\Enums\RecruitmentEventType::getLocalizedName($event->event_type)['value'] }}</p>
                        @if ($event->decision !== null)
                            <span class="rounded-control bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ \App\Enums\ApprovalDecision::getLocalizedName((int) $event->decision)['value'] }}</span>
                        @endif
                        @if ($event->requestVersion)
                            <span class="text-xs text-secondary-medium">{{ __('recruitment.version_no', ['no' => $event->requestVersion->version_no]) }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-secondary-medium">
                        {{ $event->actor?->profile?->name ?? $event->actor?->name ?? __('recruitment.system') }}
                        @if ($event->actor_role)<span class="text-xs">({{ $event->actor_role }})</span>@endif
                        · {{ $event->created_at?->format('Y-m-d H:i') }}
                    </p>
                    @if ($event->to_status !== null && (int) $event->from_status !== (int) $event->to_status)
                        <p class="mt-1 text-sm text-secondary-medium">
                            @if ($event->from_status !== null){{ \App\Enums\RecruitmentRequestStatus::getLocalizedName((int) $event->from_status)['value'] }} &rarr; @endif
                            <span class="font-medium">{{ \App\Enums\RecruitmentRequestStatus::getLocalizedName((int) $event->to_status)['value'] }}</span>
                        </p>
                    @endif
                    @if ($event->comment)<p class="mt-2 whitespace-pre-wrap break-words" dir="auto">{{ $event->comment }}</p>@endif
                    @php $changes = $event->meta['changes'] ?? null; @endphp
                    @if ($changes)
                        {{-- RV-1b: naming the fields is the point. "Edited" alone
                             tells the approver nothing about whether their own
                             approval still stands. --}}
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($changes as $field => $change)
                                <li dir="auto">
                                    <span class="font-medium">{{ __('recruitment.fields.'.$field) }}</span>
                                    <span class="text-secondary-medium">{{ \Illuminate\Support\Str::limit((string) ($change['from'] ?? '—'), 60) }} &rarr; {{ \Illuminate\Support\Str::limit((string) ($change['to'] ?? '—'), 60) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @php $extra = collect($event->meta ?? [])->except('changes'); @endphp
                    @if ($extra->isNotEmpty())
                        <p class="mt-1 text-xs text-secondary-medium" dir="auto">{{ $extra->map(fn ($value, $key) => $key.': '.(is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)))->implode(' · ') }}</p>
                    @endif
                </li>
            @empty
                <li class="text-secondary-medium">{{ __('recruitment.no_events') }}</li>
            @endforelse
        </ol>
    </div>
</section>
