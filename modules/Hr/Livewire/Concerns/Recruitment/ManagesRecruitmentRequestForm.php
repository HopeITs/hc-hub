<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentEditableFields;
use Modules\Hr\Actions\Recruitment\RecruitmentRequestDraftService;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Enums\RecruitmentEventType;
use Modules\Hr\Enums\RecruitmentRequestMode;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\Vacancy;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;
use Modules\Hr\Support\Recruitment\Text;

/**
 * The request form shared by the new-request and edit pages (the old
 * Create/Edit pair). Every picker is narrowed to what this requester may
 * choose; that is a convenience, never the guard, since the draft service
 * re-derives the same sets and refuses anything outside them.
 *
 * Expects AuthorizesRecruitment and SubmitsRecruitmentRequests on the host.
 */
trait ManagesRecruitmentRequestForm
{
    /** @var array<string, mixed> */
    public array $data = [];

    #[Locked]
    public ?int $requestId = null;

    /** @param  array<string, mixed>  $data */
    protected function withInitializedRequestSelects(array $data): array
    {
        return array_replace([
            'request_type' => RecruitmentRequestType::FillVacancy->value,
            'position_id' => null,
            'direct_manager_id' => null,
            'vacancy_id' => null,
            'volunteers_required' => null,
            'city_id' => null,
            'program_id' => null,
            'center_id' => null,
            'department_id' => null,
            'job_level_id' => null,
            'recruitment_reason' => null,
            'priority' => null,
            'volunteer_type' => null,
            'currency' => null,
            'salary_per' => null,
            'contract_type_id' => null,
        ], $data);
    }

    protected function initializeForm(?int $requestId = null): void
    {
        RecruitmentConfig::load();

        if ($requestId !== null) {
            $this->requestId = $requestId;
            $request = $this->editedRequest();

            // The draft payload is what the requester last typed, so the form
            // comes back exactly as it was left.
            $this->data = $this->withInitializedRequestSelects(array_merge(
                (array) ($request->draft_payload_json ?? []),
                ['request_type' => (int) $request->request_type],
            ));

            return;
        }

        Gate::authorize('create', RecruitmentRequest::class);
        $this->data = $this->withInitializedRequestSelects([]);
        $this->prefillFromQuery();
    }

    public function editing(): bool
    {
        return $this->requestId !== null;
    }

    public function updatedDataRequestType(): void
    {
        if ($this->editing()) {
            // The type is fixed once the request exists.
            $this->data['request_type'] = (int) $this->editedRequest()->request_type;

            return;
        }

        $this->data = $this->withInitializedRequestSelects(['request_type' => (int) $this->data['request_type']]);
        $this->selectOnlyProgram();
        $this->resetValidation();
    }

    public function updatedDataPositionId(): void
    {
        $this->data['vacancy_id'] = null;

        if ($this->type() === RecruitmentRequestType::FillVacancy->value) {
            $vacancies = $this->availableVacancies()->limit(2)->get(['id']);

            if ($vacancies->count() === 1) {
                $this->data['vacancy_id'] = $vacancies->first()->id;
            }
        }
    }

    public function updatedDataCityId(): void
    {
        $this->data['center_id'] = null;
    }

    /** A changed reason makes the "other" text meaningless, so it goes with it. */
    public function updatedDataRecruitmentReason(): void
    {
        if (($this->data['recruitment_reason'] ?? null) !== 'other') {
            unset($this->data['other_reason']);
            $this->resetValidation('data.other_reason');
        }
    }

    public function save(RecruitmentRequestDraftService $drafts): void
    {
        $this->resetValidation();
        $request = $this->persistDraft($drafts);
        $this->toast($this->savedMessage());
        $this->redirectRoute('hr.recruitment.requests.show', ['requestId' => $request->id], navigate: true);
    }

    /**
     * Save exactly as save() does, then submit (T3) or resubmit (T11). The
     * draft is committed first, so a refused submission leaves it saved.
     */
    public function saveAndSubmit(RecruitmentRequestDraftService $drafts): void
    {
        $this->resetValidation();
        $request = $this->persistDraft($drafts)->fresh();

        if (! Gate::allows('submit', $request)) {
            $this->toast($this->savedMessage());
        } elseif (($refusal = $this->attemptSubmission($request)) !== null) {
            $this->toast($refusal, false);
        } else {
            $this->toast(__('hr_recruitment.submitted'));
        }

        $this->redirectRoute('hr.recruitment.requests.show', ['requestId' => $request->id], navigate: true);
    }

    /** @return array<string, mixed> everything the form partial renders */
    protected function formOptions(): array
    {
        $user = $this->viewer();
        $scopes = app(RecruitmentScopeResolver::class);
        $type = $this->type();
        $isFill = $type === RecruitmentRequestType::FillVacancy->value;

        $positions = $type === RecruitmentRequestType::CreatePosition->value
            ? $this->proposableManagerPositions($user)
            : $this->targetablePositions($user);

        if ($type === RecruitmentRequestType::ClosePosition->value) {
            $positions->where('exist_emp', 0)->where('max_emp', 1)->whereDoesntHave('activeContracts');
        }

        if ($type === RecruitmentRequestType::CloseVacancy->value) {
            $positions->where('is_active', true)->where('max_emp', '>', 1)->whereColumn('exist_emp', '<', 'max_emp');
        }

        if ($isFill) {
            // Room in the establishment, not an existing vacancies row.
            $positions->where('is_active', true)->whereColumn('exist_emp', '<', 'max_emp');
        }

        $positions = $positions
            ->select(['id', 'title_en', 'title_ar', 'program_id', 'city_id', 'max_emp', 'exist_emp'])
            ->with(['city:id,name_en,name_ar', 'program:id,name'])
            ->orderBy(Text::transColumn('title'))
            ->get();

        $cityId = filter_var($this->data['city_id'] ?? null, FILTER_VALIDATE_INT) ?: null;

        $vacancies = $isFill
            ? $this->availableVacancies()->orderBy('id')->get(['id', 'position_id', 'needed_count', 'filled_count'])
            : Vacancy::query()
                ->visibleTo($user)
                ->when($type === RecruitmentRequestType::CloseVacancy->value, fn ($query) => $query->available())
                ->where('position_id', $this->data['position_id'] ?? 0)
                ->get(['id', 'position_id', 'needed_count', 'filled_count']);

        $changeRequest = $this->editing() ? $this->latestChangeRequestContext() : null;

        return [
            'data' => $this->data,
            'editing' => $this->editing(),
            'positions' => $positions,
            'vacancies' => $vacancies,
            'cities' => $scopes->scopeCreatableCities(City::query(), $user)->orderBy(Text::transColumn('name'))->get(['id', 'name_en', 'name_ar']),
            'programs' => $scopes->scopeCreatablePrograms(Program::query(), $user)->orderBy('name')->get(['id', 'name', 'slug']),
            'centers' => $scopes->scopeCreatableCenters(Center::query(), $user, $cityId)->orderBy(Text::transColumn('name'))->get(['id', 'name_en', 'name_ar']),
            'centerRequired' => $this->centerIsRequired(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            // Strictly junior to the requester's own grade; enforced again on save.
            'jobLevels' => JobLevel::query()->whereIn('id', $scopes->creatableJobLevelIds($user))->orderBy('grade')->get(['id', 'name_en', 'name_ar', 'grade']),
            'contractTypes' => ContractType::query()->recruitmentOptions()->orderBy(Text::transColumn('name'))->get(['id', 'name_en', 'name_ar']),
            'lockedFields' => $this->lockedFields(),
            'changeRequest' => $changeRequest,
            'requestedFields' => collect($changeRequest['fields'] ?? [])->pluck('key')->all(),
            'canSubmit' => $this->canSubmit(),
            'form' => (array) RecruitmentConfig::get('recruitment.request_form', []),
        ];
    }

    #[Computed]
    public function formView(): array
    {
        return $this->formOptions();
    }

    protected function editedRequest(): RecruitmentRequest
    {
        $request = RecruitmentRequest::query()->visibleTo($this->viewer())->findOrFail($this->requestId);
        Gate::authorize('update', $request);

        return $request;
    }

    private function type(): int
    {
        return (int) ($this->data['request_type'] ?? 0);
    }

    private function canSubmit(): bool
    {
        if ($this->editing()) {
            return Gate::allows('submit', $this->editedRequest());
        }

        return $this->may('recruitment-requests.submit')
            && app(RecruitmentScopeResolver::class)->canRaiseNormalRequest($this->viewer());
    }

    private function persistDraft(RecruitmentRequestDraftService $drafts): RecruitmentRequest
    {
        return $this->editing()
            ? $drafts->update($this->viewer(), $this->editedRequest(), $this->data)
            : $drafts->create($this->viewer(), $this->data);
    }

    private function savedMessage(): string
    {
        return __($this->editing() ? 'hr_recruitment.saved' : 'hr_recruitment.draft_saved');
    }

    /**
     * Fields shown but refused: none on a new request; once submitted, only
     * what an approver asked for may change.
     *
     * @return array<int, string>
     */
    private function lockedFields(): array
    {
        if (! $this->editing()) {
            return [];
        }

        $request = $this->editedRequest();
        $permitted = app(RecruitmentEditableFields::class)->for($request);

        if ($permitted === RecruitmentEditableFields::ALL) {
            return [];
        }

        return array_values(array_diff(array_keys((array) ($request->draft_payload_json ?? [])), (array) $permitted));
    }

    /** @return array{comment: ?string, fields: array<int, array{key: string, label: string}>}|null */
    private function latestChangeRequestContext(): ?array
    {
        $event = $this->editedRequest()->events()
            ->where('event_type', RecruitmentEventType::ChangesRequested->value)
            ->latest('id')
            ->first();

        $fields = $event?->meta['requested_fields'] ?? [];

        if (! $event || ! is_array($fields) || $fields === []) {
            return null;
        }

        return [
            'comment' => $event->comment,
            'fields' => collect($fields)
                ->filter(fn ($field): bool => is_string($field) && $field !== '')
                ->map(fn (string $field): array => ['key' => $field, 'label' => __('hr_recruitment.'.$field)])
                ->values()
                ->all(),
        ];
    }

    private function isDirectOpen(): bool
    {
        return $this->editing() && (int) $this->editedRequest()->mode === RecruitmentRequestMode::DirectHr->value;
    }

    /** @return Builder<Position> */
    private function targetablePositions(User $user): Builder
    {
        $scopes = app(RecruitmentScopeResolver::class);

        // HR does not manage the posts it recruits for, so a direct opening reaches the whole governorate.
        return $this->isDirectOpen()
            ? $scopes->scopeGovernoratePositions(Position::query(), $user)
            : $scopes->scopeRequestablePositions(Position::query(), $user);
    }

    /** @return Builder<Position> */
    private function proposableManagerPositions(User $user): Builder
    {
        $scopes = app(RecruitmentScopeResolver::class);

        return $this->isDirectOpen()
            ? $scopes->scopeGovernoratePositions(Position::query(), $user)
            : $scopes->scopeProposedManagers(Position::query(), $user);
    }

    private function prefillFromQuery(): void
    {
        $type = filter_var(request()->query('type'), FILTER_VALIDATE_INT);
        $types = array_map(fn (RecruitmentRequestType $case): int => $case->value, array_filter(
            RecruitmentRequestType::cases(),
            fn (RecruitmentRequestType $case): bool => $case !== RecruitmentRequestType::Unknown,
        ));

        if ($type !== false && in_array((int) $type, $types, true)) {
            $this->data['request_type'] = (int) $type;
        }

        // The staffing chart names the position, its manager and the one
        // vacancy open on it; the draft service re-checks all three on save.
        foreach (['position_id', 'direct_manager_id', 'vacancy_id'] as $field) {
            $id = filter_var(request()->query($field), FILTER_VALIDATE_INT);

            if ($id !== false && (int) $id > 0) {
                $this->data[$field] = (int) $id;
            }
        }
    }

    private function selectOnlyProgram(): void
    {
        if ($this->type() !== RecruitmentRequestType::CreatePosition->value) {
            return;
        }

        $programs = app(RecruitmentScopeResolver::class)
            ->scopeCreatablePrograms(Program::query(), $this->viewer())->limit(2)->pluck('id');

        if ($programs->count() === 1) {
            $this->data['program_id'] = $programs->first();
        }
    }

    /** Mirrors the rule the draft service enforces on save. */
    private function centerIsRequired(): bool
    {
        if (($this->data['volunteer_type'] ?? null) === 'remote') {
            return false;
        }

        $exempt = (array) RecruitmentConfig::get('recruitment.request_form.centerless_program_slugs', []);
        $slug = empty($this->data['program_id']) ? null
            : Program::query()->whereKey($this->data['program_id'])->value('slug');

        return $slug === null || ! in_array($slug, $exempt, true);
    }

    /** @return Builder<Vacancy> */
    private function availableVacancies(): Builder
    {
        $positions = $this->targetablePositions($this->viewer())
            ->where('is_active', true)->whereColumn('exist_emp', '<', 'max_emp');

        return Vacancy::query()->available()
            ->whereIn('position_id', $positions->select('id'))
            ->where('position_id', $this->data['position_id'] ?? 0);
    }
}
