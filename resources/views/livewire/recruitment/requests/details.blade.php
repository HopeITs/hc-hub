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
        <span class="rounded-full bg-neutral-100 px-4 py-2 text-sm font-semibold">{{ \App\Enums\RecruitmentRequestStatus::getLocalizedName($request->status)['value'] }}</span>
    </header>
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
                @foreach ($draftContext as $label => $value)
                    <div><dt class="text-sm text-secondary-medium">{{ __('recruitment.'.$label) }}</dt><dd class="mt-1 break-words" dir="auto">{{ $value }}</dd></div>
                @endforeach
                @foreach (['title_en', 'title_ar', 'max_emp', 'qualifications', 'responsibilities', 'justification', 'reason', 'needed_by_date', 'additional_count', 'reduce_by', 'effective_date', 'salary_amount', 'detailed_explanation'] as $field)
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
                @foreach (['role_title_en', 'role_title_ar', 'current_headcount', 'requested_headcount', 'salary_amount', 'recruitment_reason', 'reason', 'main_duties', 'required_qualifications', 'detailed_explanation'] as $field)
                    @if ($version->positionDetail->{$field} !== null && $version->positionDetail->{$field} !== '')
                        <div>
                            <dt class="text-sm text-secondary-medium">{{ __('recruitment.'.$field) }}</dt>
                            <dd class="mt-1 whitespace-pre-wrap break-words" dir="auto">{{ $version->positionDetail->{$field} }}@if ($field === 'salary_amount') {{ \App\Enums\Currency::getLocalizedName((int) $version->positionDetail->currency)[app()->getLocale()] }} / {{ \App\Enums\SalaryPer::getLocalizedName((int) $version->positionDetail->salary_per)['value'] }}@endif</dd>
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
                    <li class="border-s-2 border-primary/20 ps-4" wire:key="event-{{ $event->id }}">
                        <p class="font-medium">{{ \App\Enums\RecruitmentEventType::getLocalizedName($event->event_type)['value'] }}</p>
                        <p class="text-sm text-secondary-medium">{{ $event->actor?->profile?->name ?? $event->actor?->name ?? __('recruitment.system') }} · {{ $event->created_at?->format('Y-m-d H:i') }}</p>
                        @if ($event->comment)<p class="mt-2 whitespace-pre-wrap break-words" dir="auto">{{ $event->comment }}</p>@endif
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
