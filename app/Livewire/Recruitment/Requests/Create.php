<?php

namespace App\Livewire\Recruitment\Requests;

use App\Enums\RecruitmentRequestType;
use App\Models\Center;
use App\Models\City;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use App\Models\RecruitmentRequest;
use App\Models\Vacancy;
use App\Services\RecruitmentRequestDraftService;
use App\Services\RecruitmentScopeResolver;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class Create extends Component
{
    public array $data = ['request_type' => RecruitmentRequestType::FillVacancy];

    public function boot(): void
    {
        $this->authorizeScreen();
    }

    public function updatedDataRequestType(): void
    {
        $this->data = ['request_type' => (int) $this->data['request_type']];
        $this->selectOnlyProgram();
        $this->resetValidation();
    }

    public function updatedDataPositionId(): void
    {
        unset($this->data['vacancy_id']);
        if ((int) ($this->data['request_type'] ?? 0) === RecruitmentRequestType::FillVacancy) {
            $vacancies = $this->availableVacancies()->limit(2)->get();
            if ($vacancies->count() === 1) {
                $this->data['vacancy_id'] = $vacancies->first()->id;
            }
        }
    }

    public function updatedDataCityId(): void
    {
        unset($this->data['center_id']);
    }

    public function save(RecruitmentRequestDraftService $drafts)
    {
        $this->resetValidation();
        $request = $drafts->create(auth()->user(), $this->data);
        $this->toast(__('recruitment.draft_saved'));

        return $this->redirectRoute('recruitment.requests.show', ['requestId' => $request->id]);
    }

    /**
     * Every picker is narrowed to what this requester may actually choose.
     *
     * A filtered list is a convenience, never the guard: the draft service
     * re-derives the same sets server-side and rejects anything outside them,
     * so editing the request by hand gains nothing.
     */
    public function render()
    {
        $this->authorizeScreen();

        $user = auth()->user();
        $scopes = app(RecruitmentScopeResolver::class);
        $isNewPosition = (int) ($this->data['request_type'] ?? 0) === RecruitmentRequestType::CreatePosition;

        $positions = $isNewPosition
            ? $scopes->scopeProposedManagers(Position::query(), $user)
            : $scopes->scopeManageablePositions(Position::query(), $user);
        $isFill = (int) ($this->data['request_type'] ?? 0) === RecruitmentRequestType::FillVacancy;
        if ($isFill) {
            // Room in the establishment, not an existing vacancies row: until a
            // request is approved and applied nothing opens one, so requiring it
            // would leave every genuinely empty seat unfillable.
            $positions->where('is_active', true)->whereColumn('exist_emp', '<', 'max_emp');
        }
        $positions = $positions->with(['city', 'program'])->orderBy(transColumn('title'))->get();

        $cityId = filter_var($this->data['city_id'] ?? null, FILTER_VALIDATE_INT) ?: null;

        $centers = $scopes->scopeCreatableCenters(Center::query(), $user, $cityId)
            ->orderBy(transColumn('name'))->get();
        $vacancies = $isFill ? $this->availableVacancies()->orderBy('id')->get()
            : Vacancy::query()->visibleTo($user)->where('position_id', $this->data['position_id'] ?? 0)->get();

        return view('livewire.recruitment.requests.create', [
            'positions' => $positions, 'centers' => $centers, 'vacancies' => $vacancies,
            'cities' => $scopes->scopeCreatableCities(City::query(), $user)->orderBy(transColumn('name'))->get(),
            'programs' => $scopes->scopeCreatablePrograms(Program::query(), $user)->orderBy('name')->get(),
            // A centre is named unless the work is remote or the programme runs
            // across many centres rather than out of one.
            'centerRequired' => $this->centerIsRequired(),
            'departments' => Department::query()->orderBy('name')->get(),
            // Strictly junior to the requester's own grade — nobody creates a
            // peer or a superior for himself. Enforced again on save.
            'jobLevels' => JobLevel::query()
                ->whereIn('id', $scopes->creatableJobLevelIds($user))
                ->orderBy('grade')->get(),
            'contractTypes' => ContractType::query()->orderBy(transColumn('name'))->get(),
        ]);
    }

    /** Overridden by Edit, which authorizes against an existing request. */
    protected function authorizeScreen(): void
    {
        Gate::authorize('create', RecruitmentRequest::class);
    }

    private function selectOnlyProgram(): void
    {
        if ((int) ($this->data['request_type'] ?? 0) !== RecruitmentRequestType::CreatePosition) {
            return;
        }
        $programs = app(RecruitmentScopeResolver::class)
            ->scopeCreatablePrograms(Program::query(), auth()->user())->limit(2)->pluck('id');
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

        $exempt = config('recruitment.request_form.centerless_program_slugs', []);
        $slug = empty($this->data['program_id']) ? null
            : Program::query()->whereKey($this->data['program_id'])->value('slug');

        return $slug === null || ! in_array($slug, $exempt, true);
    }

    private function availableVacancies()
    {
        $positions = app(RecruitmentScopeResolver::class)
            ->scopeManageablePositions(Position::query(), auth()->user())
            ->where('is_active', true)->whereColumn('exist_emp', '<', 'max_emp');

        // May legitimately be empty; the vacancy picker only appears when more
        // than one is open and the requester has to choose between them.
        return Vacancy::query()->available()
            ->whereIn('position_id', $positions->select('id'))
            ->where('position_id', $this->data['position_id'] ?? 0);
    }

    /**
     * Say what happened, the way the rest of the application says it.
     *
     * A toast rather than a coloured panel: a red or green block across a form
     * reads as part of the form rather than as a remark about it.
     */
    private function toast(string $message, bool $success = true): void
    {
        $alert = LivewireAlert::title($message);

        ($success ? $alert->success() : $alert->error())
            ->toast()
            ->position('top-start')
            ->show();
    }
}
