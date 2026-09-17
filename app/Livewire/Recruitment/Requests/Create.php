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
        $this->resetValidation();
    }

    public function updatedDataPositionId(): void
    {
        unset($this->data['vacancy_id']);
    }

    public function updatedDataCityId(): void
    {
        unset($this->data['center_id']);
    }

    /**
     * Program narrows what is left, so a stale center or city from the previous
     * program cannot survive the change and reach the server.
     */
    public function updatedDataProgramId(): void
    {
        unset($this->data['center_id']);
    }

    public function save(RecruitmentRequestDraftService $drafts)
    {
        $request = $drafts->create(auth()->user(), $this->data);
        session()->flash('recruitment_saved', __('recruitment.draft_saved'));

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

        $positions = $scopes->scopeManageablePositions(Position::query(), $user, $isNewPosition)
            ->with(['city', 'program'])->orderBy(transColumn('title'))->get();

        $cityId = filter_var($this->data['city_id'] ?? null, FILTER_VALIDATE_INT) ?: null;

        $centers = Center::query()
            ->where('is_active', true)
            ->when(! City::isNational($cityId), fn ($query) => $query
                ->where(fn ($query) => $query->where('city_id', $cityId)->orWhereNull('city_id')))
            ->orderBy(transColumn('name'))->get();

        $vacancies = Vacancy::query()->visibleTo($user)
            ->where('position_id', $this->data['position_id'] ?? 0)->get();

        // City and program list the whole organization: D49 keeps them
        // organizational data, so a manager may open the first position in a
        // governorate he has nobody in yet. The ones his tree already touches
        // sort first, which is a convenience and not a restriction.
        $organization = $scopes->creatableOrganizationIds($user);

        return view('livewire.recruitment.requests.create', [
            'positions' => $positions, 'centers' => $centers, 'vacancies' => $vacancies,
            'cities' => $this->ownFirst(
                City::query()->where('is_active', true)->orderBy(transColumn('name'))->get(),
                $organization['cities'],
            ),
            'programs' => $this->ownFirst(
                Program::query()->where('is_active', true)->orderBy('name')->get(),
                $organization['programs'],
            ),
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

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $all
     * @param  array<int, int>  $preferred
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function ownFirst($all, array $preferred)
    {
        return $all->sortBy(fn ($item): int => in_array((int) $item->getKey(), $preferred, true) ? 0 : 1)->values();
    }
}
