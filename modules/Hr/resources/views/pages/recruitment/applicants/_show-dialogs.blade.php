{{-- The profile's dialogs: a decision, a CV archive, a flag, a note deletion, the reviewer's own review and the erasure request. --}}
@php
    use Modules\Hr\Enums\RecruitmentApplicationStatus as Stage;
    use Modules\Hr\Enums\RecruitmentEvaluationRecommendation;
    use Modules\Hr\Models\RecruitmentApplicationFlag;
    use Modules\Hr\Support\Recruitment\RecruitmentConfig;

    $muted = 'text-secondary-medium dark:text-ink-muted';
    $statusApplication = $statusApplicationId ? $applications->firstWhere('id', $statusApplicationId) : null;
    $archiveFile = $archiveFileId ? $applications->flatMap->files->merge($profile['orphans'])->firstWhere('id', $archiveFileId) : null;
    $deletingNote = $deletingNoteId ? $this->notes->firstWhere('id', $deletingNoteId) : null;
    $maxText = (int) RecruitmentConfig::get('recruitment.evaluations.max_text_length', 5000);
@endphp

<flux:modal wire:model="showStatus" class="w-full max-w-lg">
    @if ($statusTarget !== '' && $statusFrom !== null)
        <form wire:submit="changeStatus" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('hr_recruitment.applicant_ui.decisions.titles.'.$statusTarget) }}</flux:heading>
                <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.statuses.'.$statusFrom) }} → {{ __('hr_recruitment.applicant_ui.statuses.'.$statusTarget) }}</p>
            </div>
            @if ($statusTarget === Stage::Rejected)
                <x-ui.form-select wire:model="statusReasonCode" :label="__('hr_recruitment.applicant_ui.decisions.reason_code')" placeholder="—" required>
                    @foreach ($profile['rejectionReasons'] as $code)
                        <option value="{{ $code }}">{{ __('hr_recruitment.applicant_ui.decisions.rejection_reasons.'.$code) }}</option>
                    @endforeach
                </x-ui.form-select>
                @error('statusReasonCode')<p class="text-sm text-primary">{{ $message }}</p>@enderror
                @error('reasonCode')<p class="text-sm text-primary">{{ $message }}</p>@enderror
            @endif
            <x-ui.form-textarea wire:model="statusReason" :label="__('hr_recruitment.applicant_ui.decisions.internal_note')" :help="__('hr_recruitment.applicant_ui.decisions.internal_note_hint')" rows="3" required dir="auto" />
            @if ($statusTarget === Stage::Rejected)
                @if ($statusApplication?->rejectionNotice?->isSettled())
                    <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.decisions.already_notified') }}</p>
                @else
                    <flux:checkbox wire:model="statusNotify" :label="__('hr_recruitment.applicant_ui.decisions.notify')" :description="__('hr_recruitment.applicant_ui.decisions.notify_hint')" />
                @endif
            @endif
            <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.status_change.confirm') }}</p>
            <div class="flex justify-end gap-2">
                <x-ui.button tone="ghost" wire:click="$set('showStatus', false)">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                <x-ui.button type="submit" :tone="$statusTarget === Stage::Rejected ? 'neutral' : 'primary'" wire:loading.attr="disabled" wire:target="changeStatus">{{ __('hr_recruitment.applicant_ui.decisions.confirm.'.$statusTarget) }}</x-ui.button>
            </div>
        </form>
    @endif
</flux:modal>

<flux:modal wire:model="showArchive" class="w-full max-w-lg">
    @if ($archiveFile)
        @php $needsReason = app(\Modules\Hr\Actions\Recruitment\RecruitmentCvArchiveService::class)->requiresReason($archiveFile); @endphp
        <form wire:submit="archiveCv" class="space-y-4">
            <flux:heading size="lg">{{ __('hr_recruitment.applicant_ui.cv_archive.archive_title') }}</flux:heading>
            <p class="text-sm {{ $muted }}">{{ $needsReason ? __('hr_recruitment.applicant_ui.cv_archive.archive_hint_no_vacancy') : __('hr_recruitment.applicant_ui.cv_archive.archive_hint') }}</p>
            <x-ui.form-textarea wire:model="archiveReason" :label="__('hr_recruitment.applicant_ui.cv_archive.reason')" rows="3" :required="$needsReason" dir="auto" />
            <div class="flex justify-end gap-2">
                <x-ui.button tone="ghost" wire:click="$set('showArchive', false)">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" wire:target="archiveCv">{{ __('hr_recruitment.applicant_ui.cv_archive.archive_confirm') }}</x-ui.button>
            </div>
        </form>
    @endif
</flux:modal>

<flux:modal wire:model="showFlag" class="w-full max-w-lg">
    @if ($flagApplicationId)
        <form wire:submit="createFlag" class="space-y-4">
            <flux:heading size="lg">{{ __('hr_recruitment.applicant_ui.flags.add') }}</flux:heading>
            <x-ui.form-select wire:model="flagType" :label="__('hr_recruitment.applicant_ui.flags.type')" required>
                @foreach (RecruitmentApplicationFlag::types() as $option)
                    <option value="{{ $option }}">{{ __('hr_recruitment.applicant_ui.flags.types.'.$option) }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model.live="flagCategory" :label="__('hr_recruitment.applicant_ui.flags.category')" required>
                @foreach (array_keys(RecruitmentApplicationFlag::reasons()) as $option)
                    <option value="{{ $option }}">{{ __('hr_recruitment.applicant_ui.flags.categories.'.$option) }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="flagReason" :label="__('hr_recruitment.applicant_ui.flags.reason')" required wire:key="flag-reasons-{{ $flagCategory }}">
                @foreach (RecruitmentApplicationFlag::reasons()[$flagCategory] ?? [] as $option => $translationKey)
                    <option value="{{ $option }}">{{ __('hr_recruitment.applicant_ui.flags.reasons.'.$translationKey) }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-textarea wire:model="flagNote" :label="__('hr_recruitment.applicant_ui.flags.note')" rows="2" maxlength="1000" dir="auto" />
            <div class="flex justify-end gap-2">
                <x-ui.button tone="ghost" wire:click="$set('showFlag', false)">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" wire:target="createFlag">{{ __('hr_recruitment.applicant_ui.flags.add') }}</x-ui.button>
            </div>
        </form>
    @endif
</flux:modal>

<flux:modal wire:model="showNoteDelete" class="w-full max-w-lg">
    @if ($deletingNote)
        <form wire:submit="deleteNote" class="space-y-4">
            <flux:heading size="lg">{{ __('hr_recruitment.applicant_ui.notes.delete') }}</flux:heading>
            <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.notes.delete_hint') }}</p>
            <x-ui.form-textarea wire:model="deletionReason" :label="__('hr_recruitment.applicant_ui.notes.deletion_reason')" rows="2" :required="(bool) $deletingNote->is_sensitive" dir="auto" />
            <div class="flex justify-end gap-2">
                <x-ui.button tone="ghost" wire:click="$set('showNoteDelete', false)">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                <x-ui.button type="submit" tone="neutral" wire:loading.attr="disabled" wire:target="deleteNote">{{ __('hr_recruitment.applicant_ui.notes.delete_confirm') }}</x-ui.button>
            </div>
        </form>
    @endif
</flux:modal>

<flux:modal wire:model="showReview" class="w-full max-w-2xl">
    @if ($reviewApplicationId)
        <form wire:submit="saveReview" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('hr_recruitment.applicant_ui.profile_reviews.my_review') }}</flux:heading>
                <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.profile_reviews.advisory') }}</p>
            </div>
            @error('review')
                <div role="alert" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-primary px-3 py-2 text-sm">
                    <span>{{ $message }}</span>
                    <x-ui.button tone="ghost" size="sm" wire:click="openReview({{ $reviewApplicationId }})">{{ __('hr_recruitment.applicant_ui.profile_reviews.reload') }}</x-ui.button>
                </div>
            @enderror
            <flux:radio.group wire:model="recommendation" :label="__('hr_recruitment.applicant_ui.profile_reviews.recommendation')" variant="segmented">
                @foreach (RecruitmentEvaluationRecommendation::values() as $value)
                    <flux:radio :value="$value" :label="__('hr_recruitment.applicant_ui.evaluations.recommendations.'.$value)" />
                @endforeach
            </flux:radio.group>
            @error('recommendation')<p class="text-sm text-primary">{{ $message }}</p>@enderror
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.form-textarea wire:model="pros" :label="__('hr_recruitment.applicant_ui.profile_reviews.pros')" rows="3" maxlength="{{ $maxText }}" dir="auto" />
                <x-ui.form-textarea wire:model="cons" :label="__('hr_recruitment.applicant_ui.profile_reviews.cons')" rows="3" maxlength="{{ $maxText }}" dir="auto" />
            </div>
            <x-ui.form-textarea wire:model="reviewNotes" :label="__('hr_recruitment.applicant_ui.profile_reviews.notes')" rows="3" maxlength="{{ $maxText }}" dir="auto" />
            <div class="flex justify-end gap-2">
                <x-ui.button tone="ghost" wire:click="$set('showReview', false)">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" wire:target="saveReview">{{ $expectedVersion ? __('hr_recruitment.applicant_ui.profile_reviews.update') : __('hr_recruitment.applicant_ui.profile_reviews.submit') }}</x-ui.button>
            </div>
        </form>
    @endif
</flux:modal>

@if ($profile['mayErase'])
    {{-- A plain POST to the erasure controller: the policy is asked there again and every attempt is audited. --}}
    <flux:modal wire:model="showErasure" class="w-full max-w-lg">
        <form method="POST" action="{{ route('hr.recruitment.applicants.erasure', ['applicant' => $applicant->id]) }}" class="space-y-4">
            @csrf
            <div>
                <flux:heading size="lg">{{ __('hr_recruitment.hub.applicants.erasure_title') }}</flux:heading>
                <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.hub.applicants.erasure_hint') }}</p>
            </div>
            <label class="block space-y-1 text-sm">
                <span class="font-semibold">{{ __('hr_recruitment.hub.applicants.erasure_channel') }}</span>
                <select name="channel" required class="h-10 w-full rounded-lg border border-panel-border bg-white px-3 dark:bg-surface-1">
                    <option value="">—</option>
                    @foreach ((array) RecruitmentConfig::get('recruitment-privacy.erasure.channels', []) as $channel)
                        <option value="{{ $channel }}">{{ __('hr_recruitment.hub.applicants.erasure_channels.'.$channel) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="confirm" value="1" required class="mt-0.5 size-4 rounded border-secondary-light" />
                <span>{{ __('hr_recruitment.hub.applicants.erasure_confirm') }}</span>
            </label>
            <div class="flex justify-end gap-2">
                <x-ui.button tone="ghost" wire:click="$set('showErasure', false)">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                <x-ui.button type="submit" tone="neutral">{{ __('hr_recruitment.hub.applicants.erasure_submit') }}</x-ui.button>
            </div>
        </form>
    </flux:modal>
@endif
