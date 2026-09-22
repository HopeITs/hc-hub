<x-slot:title>{{ __('recruitment.details') }} · {{ $request->request_number }}</x-slot:title>

<div class="space-y-6">
    @if (session('recruitment_saved'))
        <p role="status" class="rounded-control border border-green-200 bg-green-50 p-4 text-green-800">{{ session('recruitment_saved') }}</p>
    @endif
    <a class="text-primary hover:underline" href="{{ route('recruitment.requests.index') }}">{{ __('recruitment.back') }}</a>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-secondary-medium">{{ __('recruitment.details') }}</p>
            <h1 class="text-h2 font-semibold text-neutral-900" dir="auto">{{ $request->request_number }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-full bg-neutral-100 px-4 py-2 text-sm font-semibold">{{ \App\Enums\RecruitmentRequestStatus::getLocalizedName($request->status)['value'] }}</span>
            @can('update', $request)
                <a class="rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50" href="{{ route('recruitment.requests.edit', ['requestId' => $request->id]) }}">{{ __('recruitment.edit') }}</a>
            @endcan
            @can('cancel', $request)
                <button type="button" class="rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50" wire:click="cancelRequest" wire:confirm="{{ __('recruitment.confirm_cancel') }}" wire:loading.attr="disabled" wire:target="cancelRequest">{{ __('recruitment.cancel_request') }}</button>
            @endcan
            @can('submit', $request)
                {{-- Disabled while in flight so a double click cannot fire twice; the
                     service is idempotent regardless, which is what actually protects it. --}}
                <x-primary-button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">{{ __('recruitment.submit') }}</x-primary-button>
            @endcan
        </div>
    </header>
    @if ($errors->any())
        <div role="alert" class="rounded-control border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="font-semibold">{{ __('recruitment.cannot_submit') }}</p>
            <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if ($pendingStep)
        {{-- Shown only to the approver this step is waiting on. The service asks
             the same six questions again, so this is convenience, not a guard. --}}
        <section class="rounded-surface border border-primary/30 bg-primary/5 p-6" aria-label="{{ __('recruitment.your_decision') }}">
            <h2 class="text-h4 font-semibold">{{ __('recruitment.your_decision') }}</h2>
            <p class="mt-1 text-sm text-secondary-medium">{{ \App\Enums\WorkflowStepKey::getLocalizedName((int) $pendingStep->workflowStep?->step_key)['value'] }}</p>
            <div class="mt-4">
                <x-forms.input-label for="decisionComment">{{ __('recruitment.decision_comment') }}</x-forms.input-label>
                <textarea id="decisionComment" rows="3" wire:model="decisionComment" class="mt-1 w-full rounded-control border-neutral-300"></textarea>
                <p class="mt-1 text-sm text-red-600">@error('comment'){{ $message }}@enderror @error('step'){{ $message }}@enderror @error('version'){{ $message }}@enderror @error('request'){{ $message }}@enderror</p>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-primary-button type="button" wire:click="decide('approve')" wire:loading.attr="disabled" wire:target="decide">{{ __('recruitment.approve') }}</x-primary-button>
                <button type="button" class="rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50" wire:click="decide('request_changes')" wire:loading.attr="disabled" wire:target="decide">{{ __('recruitment.request_changes') }}</button>
                <button type="button" class="rounded-control border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50" wire:click="decide('reject')" wire:confirm="{{ __('recruitment.confirm_reject') }}" wire:loading.attr="disabled" wire:target="decide">{{ __('recruitment.reject_action') }}</button>
            </div>
        </section>
    @endif
    @if ($awaitingExecution)
        {{-- The decision is made; the change has not happened. A reduction or a
             closure dated ahead waits for that date, and until then the post is
             still open. Saying so here is the only place anyone would learn it. --}}
        <section class="rounded-surface border border-blue-200 bg-blue-50 p-6" aria-label="{{ __('recruitment.awaiting_execution') }}">
            <h2 class="text-h4 font-semibold text-blue-900">{{ __('recruitment.awaiting_execution') }}</h2>
            @if ($effectiveDate)
                <p class="mt-1 text-sm text-blue-800">{{ __('recruitment.effective_on', ['date' => $effectiveDate->toDateString()]) }}</p>
            @endif
            @if ($mayExecuteNow)
                <div class="mt-4">
                    <button type="button"
                        class="rounded-control border border-blue-300 bg-white px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100"
                        wire:click="executeNow"
                        wire:confirm="{{ __('recruitment.confirm_execute_now') }}"
                        wire:loading.attr="disabled"
                        wire:target="executeNow">{{ __('recruitment.execute_now') }}</button>
                </div>
            @endif
        </section>
    @endif
    @if ($previousRejections->isNotEmpty())
        {{-- D61: context for whoever reads the request, never a reason to hide it. --}}
        <section role="note" class="rounded-surface border border-amber-200 bg-amber-50 p-6" aria-label="{{ __('recruitment.previous_rejections') }}">
            <h2 class="text-h4 font-semibold text-amber-900">{{ __('recruitment.previous_rejections') }}</h2>
            <p class="mt-1 text-sm text-amber-800">{{ __('recruitment.previous_rejections_help') }}</p>
            <ul class="mt-4 space-y-3">
                @foreach ($previousRejections as $rejected)
                    @php $decision = $rejected->events->first(); @endphp
                    <li class="rounded-control border border-amber-200 bg-white p-4" wire:key="rejection-{{ $rejected->id }}">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-semibold" dir="auto">{{ $rejected->request_number }}</span>
                            <time class="text-sm text-secondary-medium" datetime="{{ ($decision?->created_at ?? $rejected->updated_at)?->toIso8601String() }}">{{ ($decision?->created_at ?? $rejected->updated_at)?->format('Y-m-d') }}</time>
                        </div>
                        <p class="mt-1 text-sm text-secondary-medium">
                            {{ __('recruitment.requester') }}: {{ $rejected->requester?->profile?->name ?? $rejected->requester?->name ?? '-' }}
                            @if ($decision?->actor)
                                &middot; {{ __('recruitment.rejected_by') }}: {{ $decision->actor->profile?->name ?? $decision->actor->name }}
                            @endif
                        </p>
                        @if (filled($decision?->comment))
                            <p class="mt-2 whitespace-pre-wrap break-words" dir="auto">{{ $decision->comment }}</p>
                        @else
                            <p class="mt-2 text-sm text-secondary-medium">{{ __('recruitment.no_reason_recorded') }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
    <section class="rounded-surface border border-neutral-200 bg-white p-6" aria-label="{{ __('recruitment.details') }}">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                'type' => \App\Enums\RecruitmentRequestType::getLocalizedName($request->request_type)['value'],
                'mode' => \App\Enums\RecruitmentRequestMode::getLocalizedName($request->mode)['value'],
                'requester' => $request->requester?->profile?->name ?? $request->requester?->name,
                'city' => $request->scopeCity?->name,
                'position' => $request->targetPosition?->title,
                'created_at' => $request->created_at?->format('Y-m-d H:i'),
                'submitted_at' => $request->submitted_at?->format('Y-m-d H:i'),
            ] as $label => $value)
                <div><dt class="text-sm text-secondary-medium">{{ __('recruitment.'.$label) }}</dt><dd class="mt-1 break-words font-medium" dir="auto">{{ $value ?? '—' }}</dd></div>
            @endforeach
        </dl>
    </section>
    @if ($request->status === \App\Enums\RecruitmentRequestStatus::Draft)
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment.draft_content') }}</h2>
            <dl class="mt-4 grid gap-6 sm:grid-cols-2">
                @php
                    // Stored as slugs; shown by their label, in either language.
                    $draftSlugs = ['recruitment_reason' => 'reason_', 'priority' => 'priority_', 'volunteer_type' => 'volunteer_type_'];
                @endphp
                @foreach ($draftSlugs as $field => $prefix)
                    @if (! empty($request->draft_payload_json[$field]))
                        <div><dt class="text-sm text-secondary-medium">{{ __('recruitment.'.$field) }}</dt><dd class="mt-1 break-words" dir="auto">{{ __('recruitment.'.$prefix.$request->draft_payload_json[$field]) }}</dd></div>
                    @endif
                @endforeach
                @foreach ($draftContext as $label => $value)
                    <div><dt class="text-sm text-secondary-medium">{{ __('recruitment.'.$label) }}</dt><dd class="mt-1 break-words" dir="auto">{{ $value }}</dd></div>
                @endforeach
                @foreach (['title_en', 'title_ar', 'max_emp', 'qualifications', 'responsibilities', 'other_reason', 'reason', 'weekly_hours', 'needed_by_date', 'volunteer_period_start_date', 'volunteer_period_end_date', 'additional_count', 'reduce_by', 'effective_date', 'salary_amount', 'detailed_explanation'] as $field)
                    @if (isset($request->draft_payload_json[$field]) && is_scalar($request->draft_payload_json[$field]))
                        <div><dt class="text-sm text-secondary-medium">{{ __('recruitment.'.$field) }}</dt><dd class="mt-1 whitespace-pre-wrap break-words" dir="auto">{{ $request->draft_payload_json[$field] }}@if ($field === 'salary_amount') {{ \App\Enums\Currency::getLocalizedName((int) ($request->draft_payload_json['currency'] ?? 0))[app()->getLocale()] }} / {{ \App\Enums\SalaryPer::getLocalizedName((int) ($request->draft_payload_json['salary_per'] ?? 0))['value'] }}@endif</dd></div>
                    @endif
                @endforeach
            </dl>
        </section>
    @endif
    @if ($version?->positionDetail)
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment.version', ['number' => $version->version_no]) }}</h2>
            <dl class="mt-4 grid gap-6 sm:grid-cols-2">
                @php
                    // The three fixed lists are stored as slugs, so they are
                    // translated here rather than shown raw.
                    $slugLabels = ['recruitment_reason' => 'reason_', 'priority' => 'priority_', 'volunteer_type' => 'volunteer_type_'];
                @endphp
                @foreach (['role_title_en', 'role_title_ar', 'current_headcount', 'requested_headcount', 'salary_amount', 'recruitment_reason', 'other_reason', 'priority', 'volunteer_type', 'weekly_hours', 'expected_start_date', 'volunteer_period_start_date', 'volunteer_period_end_date', 'reason', 'main_duties', 'required_qualifications', 'detailed_explanation'] as $field)
                    @if ($version->positionDetail->{$field} !== null && $version->positionDetail->{$field} !== '')
                        <div>
                            <dt class="text-sm text-secondary-medium">{{ __('recruitment.'.$field) }}</dt>
                            <dd class="mt-1 whitespace-pre-wrap break-words" dir="auto">@isset($slugLabels[$field]){{ __('recruitment.'.$slugLabels[$field].$version->positionDetail->{$field}) }}@else{{ $version->positionDetail->{$field} }}@endisset@if ($field === 'salary_amount') {{ \App\Enums\Currency::getLocalizedName((int) $version->positionDetail->currency)[app()->getLocale()] }} / {{ \App\Enums\SalaryPer::getLocalizedName((int) $version->positionDetail->salary_per)['value'] }}@endif</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </section>
    @endif
    <div class="grid items-start gap-6 lg:grid-cols-2">
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment.versions') }}</h2>
            <ul class="mt-4 divide-y divide-neutral-100">
                @forelse ($request->versions as $savedVersion)
                    <li class="flex flex-wrap justify-between gap-2 py-3" wire:key="version-{{ $savedVersion->id }}">
                        <button type="button" class="text-primary hover:underline" wire:click="$set('versionId', {{ $savedVersion->id }})" aria-pressed="{{ $version?->id === $savedVersion->id ? 'true' : 'false' }}">{{ __('recruitment.version', ['number' => $savedVersion->version_no]) }}</button>
                        <time class="text-sm text-secondary-medium" datetime="{{ $savedVersion->submitted_at?->toIso8601String() }}">{{ $savedVersion->submitted_at?->format('Y-m-d H:i') }}</time>
                    </li>
                @empty
                    <li class="py-3 text-secondary-medium">{{ __('recruitment.no_versions') }}</li>
                @endforelse
            </ul>
        </section>
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment.timeline') }}</h2>
            <ol class="mt-4 space-y-4">
                @forelse ($request->events as $event)
                    {{-- The role and the scope are read from the row, never looked
                         up from the actor today: reading a year-old decision through
                         this week's roles would misstate who decided it. --}}
                    <li class="border-s-2 border-primary/20 ps-4" wire:key="event-{{ $event->id }}">
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
        </section>
    </div>
    <section class="rounded-surface border border-neutral-200 bg-white p-6">
        <h2 class="text-h4 font-semibold">{{ __('recruitment.comments') }}</h2>
        <div class="mt-4 space-y-4">
            @forelse ($request->comments as $comment)
                <article class="border-s-2 border-neutral-200 ps-4" wire:key="comment-{{ $comment->id }}">
                    <p class="text-sm text-secondary-medium">{{ $comment->user?->profile?->name ?? $comment->user?->name }} · {{ $comment->created_at?->format('Y-m-d H:i') }}</p>
                    <p class="mt-2 whitespace-pre-wrap break-words" dir="auto">{{ $comment->body }}</p>
                </article>
            @empty
                <p class="text-secondary-medium">{{ __('recruitment.no_comments') }}</p>
            @endforelse
        </div>
    </section>
    @if ($request->mode === \App\Enums\RecruitmentRequestMode::DirectHr)
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment.external_confirmations') }}</h2>
            <p class="mt-2 text-secondary-medium">{{ __('recruitment.external_description') }}</p>
            <p class="mt-4 whitespace-pre-wrap break-words" dir="auto">{{ $request->direct_open_reason }}</p>
            <ul class="mt-4 space-y-4">
                @forelse ($request->externalConfirmations as $confirmation)
                    <li class="border-s-2 border-neutral-200 ps-4" wire:key="confirmation-{{ $confirmation->id }}">
                        <p>{{ \App\Enums\ExternalConfirmationDecision::getLocalizedName($confirmation->decision)['value'] }}</p>
                        @if ($confirmation->decision === \App\Enums\ExternalConfirmationDecision::NotApplicable)
                            <p class="whitespace-pre-wrap break-words" dir="auto">{{ $confirmation->not_applicable_reason }}</p>
                        @else
                            <p>{{ $confirmation->confirmer_name }} · {{ \App\Enums\ExternalConfirmationMethod::getLocalizedName((int) $confirmation->method)['value'] }} · {{ $confirmation->confirmed_at?->format('Y-m-d') }}</p>
                        @endif
                        <p class="text-sm text-secondary-medium">{{ __('recruitment.recorded_by') }}: {{ $confirmation->recordedBy?->profile?->name ?? $confirmation->recordedBy?->name }}</p>
                    </li>
                @empty
                    <li class="text-secondary-medium">{{ __('recruitment.no_confirmations') }}</li>
                @endforelse
            </ul>
        </section>
    @endif
</div>
