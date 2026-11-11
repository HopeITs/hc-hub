{{-- An evaluation's revisions, newest first. Only for viewers allowed to see the evaluation itself. --}}
<ol class="mt-2 space-y-3">
    @foreach ($revisions as $revision)
        <li class="border-s-2 border-panel-border ps-3" wire:key="revision-{{ $revision->id }}">
            <p class="font-medium">
                {{ __($e.'actions.'.$revision->action) }}
                <span class="font-normal {{ $muted }}">· v{{ $revision->version }} · {{ $who($revision->changed_by_user_id) }} · {{ $at($revision->changed_at) }}</span>
            </p>
            @if ($revision->reason)
                <p class="mt-1 {{ $muted }}">{{ __($e.'reason') }}: {{ $revision->reason }}</p>
            @endif
            @include('hr::pages.recruitment.interviews._evaluation-content', ['evaluation' => $revision])
        </li>
    @endforeach
</ol>
