<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationFormService;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationStatusService;
use Modules\Hr\Actions\Recruitment\RecruitmentEndorsementService;
use Modules\Hr\Actions\Recruitment\RecruitmentInterviewEvaluationService;
use Modules\Hr\Actions\Recruitment\RecruitmentInterviewSchedulingService;
use Modules\Hr\Actions\Recruitment\RecruitmentMatchingIndicators;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Enums\RecruitmentApplicationStatus;
use Modules\Hr\Models\RecruitmentApplicant;
use Modules\Hr\Models\RecruitmentApplicantFile;
use Modules\Hr\Models\RecruitmentApplicantNote;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentApplicationParticipant;
use Modules\Hr\Models\RecruitmentApplicationReview;
use Modules\Hr\Models\RecruitmentApplicationTag;
use Modules\Hr\Models\RecruitmentInterview;
use Modules\Hr\Models\RecruitmentJobOffer;
use Modules\Hr\Models\RecruitmentTag;
use Modules\Hr\Support\Recruitment\Media\HrMedia;

/**
 * An applicant's profile, read once per render: the applicant, the
 * applications the viewer may see (newest first), their files, answers,
 * indicators, decisions, interviews and offer, and the internal notes.
 *
 * Every id from the browser is looked up again through the scope and
 * constrained to this applicant: a record outside it is a 404, never a 403,
 * so guessing ids does not reveal which exist. The policies read relations,
 * so the ones they need are set on each record here instead of lazy-loading.
 */
trait ReadsApplicantProfile
{
    #[Computed]
    public function applicant(): RecruitmentApplicant
    {
        $applicant = RecruitmentApplicant::query()
            ->visibleTo($this->viewer())
            ->with('city')
            ->findOrFail($this->applicantId);

        Gate::authorize('view', $applicant);

        return $applicant;
    }

    /** @return Collection<int, RecruitmentApplication> */
    #[Computed]
    public function applications(): Collection
    {
        $applicant = $this->applicant;

        $applications = RecruitmentApplication::query()
            ->profileVisibleTo($this->viewer())
            ->where('recruitment_applications.applicant_id', $applicant->getKey())
            ->with([
                'campaign:id,vacancy_id,status,starts_at,ends_at',
                'campaign.vacancy:id,position_id,created_from_request_id',
                'campaign.vacancy.position:id,title_en,title_ar',
                'campaign.vacancy.createdFromRequest:id,request_number,scope_city_id',
                'campaign.vacancy.createdFromRequest.scopeCity',
                'formVersion',
                'answers',
                'files' => fn ($files) => $files->orderByDesc('uploaded_at')->orderByDesc('id'),
                'files.archivedBy:id,email,employee_id',
                'files.cvAnalysis',
                'endorsements:id,application_id,user_id,voter_role',
                'decisions' => fn ($decisions) => $decisions->latest('decided_at')->latest('id'),
                'decisions.decidedBy:id,email,employee_id',
                'indicatorSnapshot',
                'rejectionNotice',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->get();

        foreach ($applications as $application) {
            $application->setRelation('applicant', $applicant);

            foreach ($application->files as $file) {
                $file->setRelation('application', $application)->setRelation('applicant', $applicant);
            }
        }

        return $applications;
    }

    /**
     * Everything else the page shows, keyed by application id where it
     * belongs to one application.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function profile(): array
    {
        $user = $this->viewer();
        $applicant = $this->applicant;
        $applications = $this->applications;
        $ids = $applications->modelKeys();
        $scopes = app(RecruitmentScopeResolver::class);
        $statuses = app(RecruitmentApplicationStatusService::class);
        $endorsements = app(RecruitmentEndorsementService::class);

        $normalIds = RecruitmentApplication::query()->visibleTo($user)
            ->where('recruitment_applications.applicant_id', $applicant->getKey())
            ->pluck('recruitment_applications.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $total = RecruitmentApplication::query()->where('applicant_id', $applicant->getKey())->count();
        $ownsRecord = $scopes->ownsApplicantRecord($user, $applicant);

        $orphans = $ownsRecord
            ? RecruitmentApplicantFile::query()->where('applicant_id', $applicant->getKey())->whereNull('application_id')
                ->with('archivedBy:id,email,employee_id')->latest('uploaded_at')->get()
                ->each(fn (RecruitmentApplicantFile $file) => $file->setRelation('application', null)->setRelation('applicant', $applicant))
            : new Collection;

        $interviews = RecruitmentInterview::query()
            ->whereIn('application_id', array_values(array_intersect($ids, $normalIds)))
            ->withCount(['currentParticipants as participants_count'])
            ->latest('scheduled_at')
            ->latest('id')
            ->get();
        $evaluations = app(RecruitmentInterviewEvaluationService::class);
        $tallies = $interviews
            ->filter(fn (RecruitmentInterview $interview): bool => Gate::allows('viewEvaluations', $interview))
            ->mapWithKeys(fn (RecruitmentInterview $interview): array => [$interview->getKey() => $evaluations->summary($interview)])
            ->all();

        $offers = Gate::allows('viewAny', RecruitmentJobOffer::class)
            ? RecruitmentJobOffer::query()->whereIn('application_id', $ids)->with(['currentVersion', 'hiringConversion'])->get()->keyBy('application_id')
            : collect();

        $tagAssignments = RecruitmentApplicationTag::query()
            ->whereIn('application_id', $ids)
            ->whereHas('tag')
            ->with(['tag', 'creator:id,email,employee_id'])
            ->oldest('created_at')
            ->get()
            ->groupBy('application_id');

        $participants = $applicant->currentReviewParticipants()->with('user:id,email,employee_id')->oldest('id')->get();
        $own = $participants->firstWhere('user_id', $user->getKey());

        $media = $this->documentMedia($applications->flatMap->files->merge($orphans));

        $per = [];
        foreach ($applications as $application) {
            $id = $application->getKey();
            $inScope = in_array($id, $normalIds, true);
            $mayTag = $inScope && Gate::allows('tag', $application);

            $per[$id] = [
                'inScope' => $inScope,
                'targets' => $inScope ? $statuses->targetsFor($user, $application) : [],
                'answers' => $this->answerSections($application, $media),
                'interviews' => $interviews->where('application_id', $id)->values(),
                'canSchedule' => $inScope && Gate::allows('schedule', [RecruitmentInterview::class, $application])
                    && in_array($application->status, RecruitmentInterviewSchedulingService::SCHEDULABLE, true),
                'mayScheduleAtAll' => $inScope && Gate::allows('schedule', [RecruitmentInterview::class, $application]),
                'offer' => $offers->get($id),
                'canCreateOffer' => $inScope && ! $offers->has($id) && $application->status === RecruitmentApplicationStatus::Selected
                    && Gate::allows('create', [RecruitmentJobOffer::class, $application]),
                'tags' => $tagAssignments->get($id, collect()),
                'mayTag' => $mayTag,
                'flags' => $inScope ? $application->flags()->with('creator:id,email,employee_id')->latest('id')->get() : collect(),
                'myEndorsement' => $endorsements->hasEndorsed($user, $application),
                'reviews' => $this->reviewsFor($application, $inScope && Gate::allows('manageReviewParticipants', $application)),
            ];
        }

        $first = $applications->first();
        $mayManageAccess = $first !== null && in_array($first->getKey(), $normalIds, true) && Gate::allows('manageReviewParticipants', $first);

        return [
            'normalIds' => $normalIds,
            'hidden' => max(0, $total - $applications->count()),
            'isPrevious' => $total > 1,
            'orphans' => $orphans,
            'per' => $per,
            'tallies' => $tallies,
            'indicatorSets' => app(RecruitmentMatchingIndicators::class)->evaluateMany($applications),
            'media' => $media,
            'canEndorse' => $this->may('recruitment-applicants.endorse') && $endorsements->voterRole($user) !== null,
            'canViewNotes' => $normalIds !== [] || $ownsRecord,
            'ownsRecord' => $ownsRecord,
            'participants' => $participants,
            'ownParticipant' => $own,
            'canReview' => $own?->role === RecruitmentApplicationParticipant::ROLE_REVIEWER,
            'mayManageAccess' => $mayManageAccess,
            'mayCreateTag' => Gate::allows('create', RecruitmentTag::class),
            'rejectionReasons' => $statuses->rejectionReasons(),
            'mayErase' => Gate::inspect('requestErasure', $applicant)->allowed(),
        ];
    }

    /** @return Collection<int, RecruitmentApplicantNote> */
    #[Computed]
    public function notes(): Collection
    {
        return RecruitmentApplicantNote::query()
            ->visibleTo($this->viewer())
            ->where('applicant_id', $this->applicantId)
            ->with([
                'actor:id,email,employee_id',
                'application:id,campaign_id',
                'application.campaign:id,vacancy_id',
                'application.campaign.vacancy:id,position_id',
                'application.campaign.vacancy.position:id,title_en,title_ar',
            ])
            ->latest('created_at')
            ->latest('id')
            ->get();
    }

    /** The applications a note may be written about (normal scope only). @return Collection<int, RecruitmentApplication> */
    #[Computed]
    public function noteApplications(): Collection
    {
        $normal = $this->profile['normalIds'];

        return $this->applications->filter(fn (RecruitmentApplication $application): bool => in_array($application->getKey(), $normal, true))->values();
    }

    /** @return array<int, string> every live tag, "kind — name", for the tag pickers */
    #[Computed]
    public function tagOptions(): array
    {
        return RecruitmentTag::query()->orderBy('kind')->orderBy('name')->get(['id', 'name', 'kind'])
            ->mapWithKeys(fn (RecruitmentTag $tag): array => [(int) $tag->getKey() => $tag->kindLabel().' — '.$tag->name])
            ->all();
    }

    /** @return array<int, string> user id => "name — email", for the profile-access picker */
    #[Computed]
    public function accessCandidates(): array
    {
        if (! $this->profile['mayManageAccess']) {
            return [];
        }

        $directory = User::directory();
        $taken = $this->profile['participants']->pluck('user_id')->map(fn ($id): int => (int) $id)->all();
        $employeeId = $this->applicant->employee_id;

        return User::query()
            ->whereHas('employee', fn ($employee) => $employee->where('is_active', true))
            ->when($employeeId !== null, fn ($query) => $query->where(fn ($other) => $other->whereNull('employee_id')->orWhere('employee_id', '!=', $employeeId)))
            ->whereKeyNot($taken)
            ->pluck('id')
            ->mapWithKeys(fn ($id): array => [(int) $id => ($directory[(int) $id]['name'] ?? '#'.$id).' — '.($directory[(int) $id]['email'] ?? '')])
            ->sort()
            ->all();
    }

    protected function resolveApplication(int $applicationId): RecruitmentApplication
    {
        $applicant = $this->applicant;

        $application = RecruitmentApplication::query()
            ->profileVisibleTo($this->viewer())
            ->where('applicant_id', $applicant->getKey())
            ->findOrFail($applicationId);
        $application->setRelation('applicant', $applicant);

        Gate::authorize('view', $application);

        return $application;
    }

    /** An application inside the viewer's normal scope (decisions, tags, notes). */
    protected function scopedApplication(int $applicationId): RecruitmentApplication
    {
        $application = $this->resolveApplication($applicationId);

        if (! RecruitmentApplication::query()->visibleTo($this->viewer())->whereKey($application->getKey())->exists()) {
            throw (new ModelNotFoundException)->setModel(RecruitmentApplication::class, [$applicationId]);
        }

        return $application;
    }

    protected function resolveFile(int $fileId): RecruitmentApplicantFile
    {
        $applicant = $this->applicant;

        $file = RecruitmentApplicantFile::query()
            ->where('applicant_id', $applicant->getKey())
            ->with('application')
            ->findOrFail($fileId);
        $file->setRelation('applicant', $applicant);
        $file->application?->setRelation('applicant', $applicant);

        $visible = $file->application_id === null
            ? app(RecruitmentScopeResolver::class)->ownsApplicantRecord($this->viewer(), $applicant)
            : RecruitmentApplication::query()->profileVisibleTo($this->viewer())->whereKey($file->application_id)->exists();

        if (! $visible) {
            throw (new ModelNotFoundException)->setModel(RecruitmentApplicantFile::class, [$fileId]);
        }

        return $file;
    }

    protected function forgetProfile(): void
    {
        unset($this->applicant, $this->applications, $this->profile, $this->notes, $this->noteApplications, $this->accessCandidates);
    }

    /** @return \Illuminate\Support\Collection<int, RecruitmentApplicationReview> */
    private function reviewsFor(RecruitmentApplication $application, bool $all): \Illuminate\Support\Collection
    {
        return $application->profileReviews()
            ->when(! $all, fn ($reviews) => $reviews->where('reviewer_user_id', $this->viewer()->getKey()))
            ->with('reviewer:id,email,employee_id')
            ->latest('updated_at')
            ->get();
    }

    /**
     * Each file's stored document, in one query.
     *
     * @return array<int, HrMedia> file id => media
     */
    private function documentMedia(\Illuminate\Support\Collection $files): array
    {
        if ($files->isEmpty()) {
            return [];
        }

        return HrMedia::query()
            ->where('model_type', RecruitmentApplicantFile::class)
            ->whereIn('model_id', $files->map(fn (RecruitmentApplicantFile $file): int => (int) $file->getKey())->all())
            ->where('collection_name', 'document')
            ->orderBy('order_column')
            ->orderBy('id')
            ->get()
            ->unique('model_id')
            ->keyBy('model_id')
            ->all();
    }

    /**
     * The answers in the shape the form asked them: one section per form
     * section, label above value.
     *
     * @param  array<int, HrMedia>  $media
     * @return list<array{key: string, label: string, answers: list<array{label: string, value: string, empty: bool}>}>
     */
    private function answerSections(RecruitmentApplication $application, array $media): array
    {
        $locale = app()->getLocale();
        $forms = app(RecruitmentApplicationFormService::class);
        $answers = $application->answers->keyBy('field_key');
        $files = $application->files->groupBy('file_type');
        $none = __('hr_recruitment.applicant_ui.application_answers.no_answer');
        $sections = [];

        foreach ($forms->normalizeFields((array) ($application->formVersion?->fields_json ?? [])) as $field) {
            if (in_array($field['key'], ['privacy_consent', 'response_copy'], true)) {
                continue;
            }

            $sectionKey = (string) $field['section'];
            $sections[$sectionKey] ??= [
                'key' => $sectionKey,
                'label' => $this->localized(RecruitmentApplicationFormService::SECTION_CATALOG[$sectionKey]['label'] ?? [], $locale),
                'answers' => [],
            ];

            $value = $field['type'] === 'file'
                ? $files->get($field['key'], collect())->map(fn (RecruitmentApplicantFile $file): string => (string) ($media[$file->getKey()]?->name ?? '') ?: __('hr_recruitment.applicant_ui.application_answers.file_uploaded'))->implode(__('hr_recruitment.applicant_ui.list_separator'))
                : $this->formatAnswer($field, $answers->get($field['key'])?->answer_json['value'] ?? null, $locale);

            $sections[$sectionKey]['answers'][] = [
                'label' => $this->localized((array) $field['label'], $locale),
                'value' => $value !== '' ? $value : $none,
                'empty' => $value === '',
            ];
        }

        return array_values($sections);
    }

    /** @param array<string, mixed> $field */
    private function formatAnswer(array $field, mixed $value, string $locale): string
    {
        if (is_bool($value)) {
            return $value ? __('hr_recruitment.applicant_ui.application_answers.yes') : __('hr_recruitment.applicant_ui.application_answers.no');
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item): string => $this->formatOption($field, $item, $locale))
                ->filter(fn (string $item): bool => $item !== '')
                ->implode(__('hr_recruitment.applicant_ui.list_separator'));
        }

        if (in_array($field['type'], ['select', 'radio', 'checkbox'], true)) {
            return $this->formatOption($field, $value, $locale);
        }

        return trim((string) ($value ?? ''));
    }

    /** @param array<string, mixed> $field */
    private function formatOption(array $field, mixed $value, string $locale): string
    {
        $option = collect($field['options'] ?? [])->firstWhere('value', $value);

        return trim((string) ($option !== null ? $this->localized((array) ($option['label'] ?? []), $locale) : ($value ?? '')));
    }

    /** @param array<string, string> $text */
    private function localized(array $text, string $locale): string
    {
        return (string) ($text[$locale] ?? $text['en'] ?? $text['ar'] ?? '');
    }
}
