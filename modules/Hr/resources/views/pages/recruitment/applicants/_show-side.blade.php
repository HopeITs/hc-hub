{{-- The profile's side rail: who else may open this profile, and the internal notes kept in view while reading. --}}
@php
    use Modules\Hr\Actions\Recruitment\RecruitmentApplicantNoteService;
    use Modules\Hr\Models\RecruitmentApplicantNote;
    use Modules\Hr\Models\RecruitmentApplicationParticipant;

    $muted = 'text-secondary-medium dark:text-ink-muted';
    $noteApplications = $this->noteApplications;
    $mayAddUnattached = $applications->isEmpty() && $profile['ownsRecord'];
    $mayAddNote = ($noteApplications->isNotEmpty() || $mayAddUnattached) && $this->may('recruitment-applicants.add-notes');
@endphp

@if ($profile['mayManageAccess'])
    <x-ui.panel :title="__('hr_recruitment.applicant_ui.profile_reviews.title')" :subtitle="__('hr_recruitment.applicant_ui.profile_reviews.hint')" :clip="false">
        <form wire:submit="addParticipant" class="space-y-3 p-4">
            <x-ui.form-select wire:model="newParticipantId" :label="__('hr_recruitment.applicant_ui.profile_reviews.person')" :placeholder="__('hr_recruitment.choose')" searchable>
                @foreach ($this->accessCandidates as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.form-select>
            @error('newParticipantId')<p class="text-sm text-primary">{{ $message }}</p>@enderror
            <x-ui.form-select wire:model="newParticipantRole" :label="__('hr_recruitment.applicant_ui.profile_reviews.access')">
                @foreach (RecruitmentApplicationParticipant::roles() as $role)
                    <option value="{{ $role }}">{{ __('hr_recruitment.applicant_ui.profile_reviews.roles.'.$role) }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.button type="submit" tone="secondary" size="sm" icon="user-plus" wire:loading.attr="disabled" wire:target="addParticipant">{{ __('hr_recruitment.applicant_ui.profile_reviews.add') }}</x-ui.button>
        </form>
        <ul class="divide-y divide-panel-border border-t border-panel-border text-sm">
            @forelse ($profile['participants'] as $participant)
                <li wire:key="participant-{{ $participant->id }}" class="flex items-center justify-between gap-2 px-4 py-2">
                    <span class="min-w-0"><span class="font-medium" dir="auto">{{ $participant->user?->name ?? '—' }}</span> <span class="{{ $muted }}">· {{ __('hr_recruitment.applicant_ui.profile_reviews.roles.'.$participant->role) }}</span></span>
                    <x-ui.button tone="ghost" size="sm" wire:click="removeParticipant({{ $participant->id }})" wire:confirm="{{ __('hr_recruitment.applicant_ui.profile_reviews.remove_confirm') }}">{{ __('hr_recruitment.applicant_ui.profile_reviews.remove') }}</x-ui.button>
                </li>
            @empty
                <li class="px-4 py-3 {{ $muted }}">{{ __('hr_recruitment.applicant_ui.profile_reviews.no_participants') }}</li>
            @endforelse
        </ul>
    </x-ui.panel>
@endif

@if ($profile['canViewNotes'])
    {{-- Internal only. Nothing in this panel can change an application's status. --}}
    <x-ui.panel id="internal-notes" :title="__('hr_recruitment.applicant_ui.notes.title')" :subtitle="__('hr_recruitment.applicant_ui.notes.internal_hint')" :clip="false">
        @if ($mayAddNote)
            <form wire:submit="addNote" class="space-y-3 p-4">
                <x-ui.form-select wire:model="noteApplicationId" :label="__('hr_recruitment.applicant_ui.notes.about')" :required="! $mayAddUnattached" :placeholder="$mayAddUnattached ? __('hr_recruitment.applicant_ui.notes.about_applicant') : '—'">
                    @foreach ($noteApplications as $noteApplication)
                        <option value="{{ $noteApplication->id }}">{{ $noteApplication->campaign?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }} · {{ $noteApplication->submitted_at?->format('Y-m-d') }}</option>
                    @endforeach
                </x-ui.form-select>
                @error('noteApplicationId')<p class="text-sm text-primary">{{ $message }}</p>@enderror
                <x-ui.form-textarea wire:model="noteBody" :label="__('hr_recruitment.applicant_ui.notes.body')" rows="3" maxlength="{{ RecruitmentApplicantNoteService::MAX_BODY }}" required dir="auto" />
                <flux:checkbox wire:model="noteSensitive" :label="__('hr_recruitment.applicant_ui.notes.sensitive')" />
                <x-ui.button type="submit" tone="secondary" size="sm" wire:loading.attr="disabled" wire:target="addNote">{{ __('hr_recruitment.applicant_ui.notes.add') }}</x-ui.button>
            </form>
        @endif
        <ul class="divide-y divide-panel-border border-t border-panel-border text-sm xl:max-h-[32rem] xl:overflow-y-auto">
            @forelse ($this->notes as $note)
                <li wire:key="note-{{ $note->id }}" class="space-y-1.5 px-4 py-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <p class="text-xs {{ $muted }}">
                            <span class="font-semibold text-secondary-dark dark:text-ink" dir="auto">{{ $note->actor?->name ?? '—' }}</span>
                            · <span class="tabular-nums">{{ $note->created_at?->format('Y-m-d H:i') }}</span>
                            · <span dir="auto">{{ $note->application?->campaign?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.notes.about_applicant') }}</span>
                        </p>
                        <span class="flex flex-wrap items-center gap-1">
                            @if ($note->note_type === RecruitmentApplicantNote::TYPE_CV_ARCHIVE)
                                <span class="rounded-md bg-secondary-soft px-1.5 py-0.5 text-xs dark:bg-surface-3">{{ __('hr_recruitment.applicant_ui.notes.type_cv_archive') }}</span>
                            @endif
                            @if ($note->is_sensitive)
                                <span class="rounded-md border border-primary px-1.5 py-0.5 text-xs text-primary dark:border-brand dark:text-brand-text">{{ __('hr_recruitment.applicant_ui.notes.sensitive_badge') }}</span>
                            @endif
                            @can('delete', $note)
                                <x-ui.button tone="ghost" size="sm" wire:click="confirmNoteDelete({{ $note->id }})">{{ __('hr_recruitment.applicant_ui.notes.delete') }}</x-ui.button>
                            @endcan
                        </span>
                    </div>
                    <p class="whitespace-pre-line wrap-anywhere" dir="auto">{{ $note->body }}</p>
                </li>
            @empty
                <li class="px-4 py-3 {{ $muted }}">{{ __('hr_recruitment.applicant_ui.notes.empty') }}</li>
            @endforelse
        </ul>
    </x-ui.panel>
@endif
