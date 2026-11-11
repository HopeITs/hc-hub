@props(['request', 'version' => null, 'draftContext' => []])

{{--
    What a request asks for, grouped the way a reader asks about it: what role,
    on what terms, why, and what the job is. A draft has no version yet, so its
    saved form payload is shown instead.
--}}
@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $eyebrow = 'text-xs font-semibold uppercase tracking-wide '.$muted;
    $slugLabels = ['recruitment_reason' => 'reason_', 'priority' => 'priority_', 'volunteer_type' => 'volunteer_type_'];
    $money = fn ($amount, $currency, $per): string => $amount.' '.(\Modules\Hr\Enums\Currency::getLocalizedName((int) $currency)[app()->getLocale()] ?? '').' / '.(\Modules\Hr\Enums\SalaryPer::getLocalizedName((int) $per)['value'] ?? '');
    $long = ['main_duties', 'required_qualifications', 'detailed_explanation', 'reason', 'other_reason', 'qualifications', 'responsibilities'];
    $isDraft = (int) $request->status === \Modules\Hr\Enums\RecruitmentRequestStatus::Draft->value;
    $versions = $request->versions;
    $switcher = 'inline-flex min-h-8 items-center rounded-md px-3 text-sm';
@endphp

@if ($versions->count() > 1)
    <div role="group" aria-label="{{ __('hr_recruitment.versions') }}" class="flex flex-wrap gap-1">
        @foreach ($versions as $savedVersion)
            @php
                $on = $version?->id === $savedVersion->id;
            @endphp
            <button type="button" wire:key="version-{{ $savedVersion->id }}" wire:click="$set('versionId', {{ $savedVersion->id }})" aria-pressed="{{ $on ? 'true' : 'false' }}"
                @class([$switcher, 'bg-secondary-soft font-semibold dark:bg-surface-3' => $on, $muted.' hover:bg-secondary-soft/60 dark:hover:bg-surface-2' => ! $on])>
                {{ __('hr_recruitment.version_no', ['no' => $savedVersion->version_no]) }}
            </button>
        @endforeach
    </div>
@endif

@if ($version?->positionDetail)
    @php
        $detail = $version->positionDetail;
        $snapshot = (array) ($version->snapshot_json ?? []);
        $value = function (string $field) use ($detail, $slugLabels, $money) {
            $raw = $detail->{$field};
            if ($raw === null || $raw === '') {
                return null;
            }
            if (isset($slugLabels[$field])) {
                return __('hr_recruitment.'.$slugLabels[$field].$raw);
            }
            if ($field === 'salary_amount') {
                return $money($raw, $detail->currency, $detail->salary_per);
            }
            if ($raw instanceof \DateTimeInterface) {
                return $raw->translatedFormat(__('formats.date'));
            }

            return $raw;
        };
        $groups = [
            'role' => ['role_title_en', 'role_title_ar'],
            'terms' => ['priority', 'salary_amount', 'weekly_hours', 'expected_start_date', 'volunteer_type', 'volunteer_period_start_date', 'volunteer_period_end_date'],
            'reason' => ['recruitment_reason', 'other_reason', 'reason', 'detailed_explanation'],
            'description' => ['main_duties', 'required_qualifications'],
        ];
        $current = $detail->current_headcount;
        $requested = $detail->requested_headcount;
    @endphp
    <x-ui.panel :title="__('hr_recruitment.version', ['number' => $version->version_no])">
        <div class="divide-y divide-panel-border">
            @if ($current !== null || $requested !== null || isset($snapshot['volunteers_required']) || isset($snapshot['additional_count']))
                <div class="grid gap-4 p-4 sm:grid-cols-3">
                    @if ($current !== null || $requested !== null)
                        <div class="sm:col-span-2">
                            <p class="{{ $eyebrow }}">{{ __('hr_recruitment.requested_headcount') }}</p>
                            <p class="mt-2 flex items-baseline gap-3">
                                <span class="text-xl font-semibold tabular-nums {{ $muted }}">{{ $current ?? '—' }}</span>
                                <flux:icon.arrow-right variant="mini" class="size-5 self-center {{ $muted }} rtl:rotate-180" aria-hidden="true" />
                                <span class="text-2xl font-semibold tabular-nums">{{ $requested ?? '—' }}</span>
                                @if (is_numeric($current) && is_numeric($requested) && (int) $requested !== (int) $current)
                                    <span class="rounded-md bg-secondary-soft px-2 py-0.5 text-sm font-semibold tabular-nums dark:bg-surface-2">{{ (int) $requested > (int) $current ? '+' : '' }}{{ (int) $requested - (int) $current }}</span>
                                @endif
                            </p>
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_recruitment.current_headcount') }} <span aria-hidden="true">→</span> {{ __('hr_recruitment.requested_headcount') }}</p>
                        </div>
                    @endif
                    @foreach (['volunteers_required', 'additional_count'] as $snapField)
                        @if (isset($snapshot[$snapField]))
                            <div>
                                <p class="{{ $eyebrow }}">{{ __('hr_recruitment.'.$snapField) }}</p>
                                <p class="mt-2 text-xl font-semibold tabular-nums">{{ $snapshot[$snapField] }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            @foreach ($groups as $group => $fields)
                @php
                    $present = collect($fields)->filter(fn ($field) => $value($field) !== null);
                @endphp
                @if ($present->isNotEmpty())
                    <div class="p-4">
                        <h3 class="{{ $eyebrow }}">{{ __('hr_recruitment.sections.'.$group) }}</h3>
                        <dl @class(['mt-3 grid gap-x-8 gap-y-4', 'sm:grid-cols-2' => $group !== 'description'])>
                            @foreach ($present as $field)
                                @php
                                    $isLong = in_array($field, $long, true);
                                @endphp
                                <div @class(['min-w-0', 'sm:col-span-2' => $isLong])>
                                    <dt class="text-xs {{ $muted }}">{{ __('hr_recruitment.'.($field === 'expected_start_date' ? 'needed_by_date' : $field)) }}</dt>
                                    <dd @class(['mt-0.5 whitespace-pre-wrap break-words', 'max-w-prose leading-relaxed' => $isLong, 'font-medium' => ! $isLong]) dir="auto">{{ $value($field) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            @endforeach
        </div>
    </x-ui.panel>
@endif

@if ($isDraft)
    @php
        $payload = (array) ($request->draft_payload_json ?? []);
    @endphp
    <x-ui.panel :title="__('hr_recruitment.draft_content')">
        <dl class="grid gap-x-8 gap-y-4 p-4 sm:grid-cols-2">
            @foreach ($slugLabels as $draftField => $prefix)
                @if (! empty($payload[$draftField]))
                    <div><dt class="text-xs {{ $muted }}">{{ __('hr_recruitment.'.$draftField) }}</dt><dd class="mt-0.5 break-words font-medium" dir="auto">{{ __('hr_recruitment.'.$prefix.$payload[$draftField]) }}</dd></div>
                @endif
            @endforeach
            @foreach ($draftContext as $label => $draftValue)
                <div><dt class="text-xs {{ $muted }}">{{ __('hr_recruitment.'.$label) }}</dt><dd class="mt-0.5 break-words font-medium" dir="auto">{{ $draftValue }}</dd></div>
            @endforeach
            @foreach (['title_en', 'title_ar', 'max_emp', 'volunteers_required', 'qualifications', 'responsibilities', 'other_reason', 'reason', 'weekly_hours', 'needed_by_date', 'volunteer_period_start_date', 'volunteer_period_end_date', 'additional_count', 'reduce_by', 'effective_date', 'salary_amount', 'detailed_explanation'] as $draftField)
                @if (isset($payload[$draftField]) && is_scalar($payload[$draftField]))
                    @php
                        $isLong = in_array($draftField, $long, true);
                    @endphp
                    <div @class(['min-w-0', 'sm:col-span-2' => $isLong])>
                        <dt class="text-xs {{ $muted }}">{{ __('hr_recruitment.'.$draftField) }}</dt>
                        <dd @class(['mt-0.5 whitespace-pre-wrap break-words', 'max-w-prose leading-relaxed' => $isLong, 'font-medium' => ! $isLong]) dir="auto">{{ $draftField === 'salary_amount' ? $money($payload[$draftField], $payload['currency'] ?? 0, $payload['salary_per'] ?? 0) : $payload[$draftField] }}</dd>
                    </div>
                @endif
            @endforeach
        </dl>
    </x-ui.panel>
@elseif (! $version)
    <x-ui.empty-state :heading="__('hr_recruitment.no_versions')" />
@endif
