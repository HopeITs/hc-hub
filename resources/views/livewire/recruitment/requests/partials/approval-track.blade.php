{{--
    The road this request travels, drawn once across the page.

    One line with a circle at every stop. The stops come from the workflow
    actually running, so a requester with nobody above them simply has no
    manager circle and a direct opening has one — the shortening is in the
    data, not in a style that greys something out.

    The line sits behind the circles at their centre height and stops at the
    first and last, so it never trails past either end.
--}}
<section class="rounded-surface border border-neutral-200 bg-white p-6" aria-label="{{ __('recruitment.track.title') }}">
    <h2 class="text-h4 font-semibold">{{ __('recruitment.track.title') }}</h2>

    <ol class="mt-8 flex items-start justify-between gap-2" role="list">
        @foreach ($track as $index => $stop)
            @php
                // Written out rather than interpolated: Tailwind reads class
                // names as literal text, so a built-up string generates nothing.
                $dot = match ($stop['state']) {
                    \App\Services\RecruitmentApprovalTrack::DONE => 'bg-primary border-primary',
                    \App\Services\RecruitmentApprovalTrack::CURRENT => 'bg-white border-primary ring-4 ring-primary/20',
                    \App\Services\RecruitmentApprovalTrack::SKIPPED => 'bg-white border-neutral-300 border-dashed',
                    default => 'bg-white border-neutral-300',
                };
                $label = $stop['state'] === \App\Services\RecruitmentApprovalTrack::PENDING
                    || $stop['state'] === \App\Services\RecruitmentApprovalTrack::SKIPPED
                    ? 'text-secondary-medium' : 'text-neutral-900';
            @endphp

            <li class="relative flex flex-1 flex-col items-center text-center" wire:key="stop-{{ $stop['key'] }}">
                {{-- The connecting line, drawn by each stop except the first,
                     back towards the one before it. --}}
                @if ($index > 0)
                    <span aria-hidden="true"
                        class="absolute top-[1.35rem] end-1/2 w-full border-t-2
                            {{ $stop['state'] === \App\Services\RecruitmentApprovalTrack::PENDING
                                ? 'border-neutral-200' : 'border-primary/40' }}"></span>
                @endif

                <p class="mb-2 text-sm font-semibold {{ $label }}">{{ $stop['label'] }}</p>

                <span class="relative z-10 grid size-6 place-items-center rounded-full border-2 {{ $dot }}">
                    @if ($stop['state'] === \App\Services\RecruitmentApprovalTrack::SKIPPED)
                        <span class="text-xs text-secondary-medium" aria-hidden="true">&times;</span>
                    @endif
                    <span class="sr-only">{{ __('recruitment.track.states.'.$stop['state']) }}</span>
                </span>

                <div class="mt-2 text-xs text-secondary-medium">
                    @if ($stop['by'])<p class="font-medium">{{ $stop['by'] }}</p>@endif
                    @if ($stop['at'])<p>{{ $stop['at'] }}</p>@endif
                    @if ($stop['state'] === \App\Services\RecruitmentApprovalTrack::SKIPPED)
                        <p>{{ __('recruitment.track.states.skipped') }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</section>
